from flask import Flask, request, jsonify
from pyrfc import (
    Connection,
    ABAPApplicationError, ABAPRuntimeError,
    LogonError, CommunicationError, RFCError, RFCLibError
)
import pymysql
from flask_cors import CORS

import os
from decimal import Decimal, InvalidOperation
from datetime import datetime, date

app = Flask(__name__)
CORS(app, supports_credentials=True, resources={r"/api/*": {"origins": "*"}})


# ------------------------------------------------------------
# SAP / DB Connection
# ------------------------------------------------------------
def connect_sap(username=None, password=None):
    username = username or os.environ.get("SAP_USERNAME")
    password = password or os.environ.get("SAP_PASSWORD")
    if not username or not password:
        raise Exception("SAP credentials not provided.")

    return Connection(
        user=username,
        passwd=password,
        ashost=os.environ.get("SAP_ASHOST", "192.168.254.154"),
        sysnr=os.environ.get("SAP_SYSNR", "01"),
        client=os.environ.get("SAP_CLIENT", "300"),
        lang=os.environ.get("SAP_LANG", "EN"),
    )


def get_mysql_connection():
    """Membuka koneksi baru ke database MySQL."""
    return pymysql.connect(
        host="127.0.0.1",
        user="root",
        password="singgampang",
        database="cohv_app",
        port=3306,
    )


def get_credentials():
    """Mengambil kredensial SAP dari header request."""
    username = request.headers.get("X-SAP-Username")
    password = request.headers.get("X-SAP-Password")

    if not username or not password:
        raise ValueError("SAP credentials not found in headers (X-SAP-Username, X-SAP-Password).")

    return username, password


# ------------------------------------------------------------
# Helpers
# ------------------------------------------------------------
def _get_any(d: dict, key: str, default=None):
    """Ambil nilai dari dict dengan key persis atau case-insensitive."""
    if not isinstance(d, dict):
        return default

    if key in d:
        return d[key]

    key_lower = key.lower()
    for k, v in d.items():
        if isinstance(k, str) and k.lower() == key_lower:
            return v

    return default


def extract_header_and_items(payload: dict):
    """
    Menerima payload:
    - {"header": {...}, "items": [...]}  atau
    - {"I_DOCNO": "...", "items": [...]} atau
    - {"T_WI": [...]} dll
    """
    if payload is None:
        payload = {}

    header = payload.get("header") if isinstance(payload.get("header"), dict) else payload

    if isinstance(payload.get("items"), list):
        items = payload.get("items")
    elif isinstance(payload.get("T_WI"), list):
        items = payload.get("T_WI")
    elif isinstance(payload.get("t_wi"), list):
        items = payload.get("t_wi")
    else:
        items = []

    return header or {}, items


def json_safe(obj):
    """Konversi tipe non-JSON (Decimal/datetime/date) supaya aman untuk jsonify."""
    if isinstance(obj, Decimal):
        return format(obj, "f")
    if isinstance(obj, (datetime, date)):
        return obj.isoformat()
    if isinstance(obj, dict):
        return {k: json_safe(v) for k, v in obj.items()}
    if isinstance(obj, (list, tuple)):
        return [json_safe(v) for v in obj]
    return obj


def make_bapiret2_error(message: str):
    """Fallback E_RETURN kalau error di layer gateway (bukan dari SAP)."""
    return {
        "TYPE": "E",
        "ID": "",
        "NUMBER": "",
        "MESSAGE": str(message),
        "LOG_NO": "",
        "LOG_MSG_NO": "",
        "MESSAGE_V1": "",
        "MESSAGE_V2": "",
        "MESSAGE_V3": "",
        "MESSAGE_V4": "",
        "PARAMETER": "",
        "ROW": 0,
        "FIELD": "",
        "SYSTEM": "",
    }


def normalize_budat(value: str, field_name: str = "DATE", required: bool = True) -> str | None:
    """
    Input:  DDMMYYYY (contoh: 28022026)
    Output: YYYYMMDD (untuk SAP) -> 20260228
    """
    if value is None:
        if required:
            raise ValueError(f"{field_name} is required")
        return None

    s = str(value).strip()
    if not s:
        if required:
            raise ValueError(f"{field_name} is required")
        return None

    s = s.replace("-", "").replace("/", "")

    if len(s) != 8 or not s.isdigit():
        raise ValueError(f"{field_name} harus format DDMMYYYY, contoh: 28022026")

    try:
        dt = datetime.strptime(s, "%d%m%Y")
    except ValueError:
        raise ValueError(f"{field_name} tidak valid. Gunakan DDMMYYYY, contoh: 28022026")

    return dt.strftime("%Y%m%d")


def normalize_sap_date(value, field_name="DATE", required=False):
    """Alias: untuk project ini kita pakai standar input DDMMYYYY (strict)."""
    return normalize_budat(value, field_name=field_name, required=required)


def parse_qty(value, field_name="GMNGA", required=True) -> str | None:
    """Konversi qty ke string decimal (pakai titik) supaya aman untuk SAP."""
    if value is None:
        if required:
            raise ValueError(f"{field_name} wajib numeric")
        return None

    s = str(value).strip()
    if s == "":
        if required:
            raise ValueError(f"{field_name} wajib numeric")
        return None

    s = s.replace(",", ".")
    try:
        d = Decimal(s)
    except (InvalidOperation, ValueError):
        raise ValueError(f"{field_name} harus numeric, value: {value}")

    return format(d, "f")


def build_t_wi(items: list, mode: str, header_docno: str | None = None):
    """
    Build TABLE T_WI sesuai rule kamu:
    1) create & update: isi semua field RFC kecuali GMNGX dan RMKQTY
    2) edit_qty: cukup DOCNO, AUFNR, VORNR, PERNR, GMNGA
    """
    if not isinstance(items, list) or len(items) == 0:
        raise ValueError("items/T_WI wajib diisi minimal 1 baris")

    rows = []

    if mode in ("create", "update"):
        required_fields = [
            "DOCNO",      # create boleh kosong, update wajib ada
            "AUFNR",
            "VORNR",
            "PERNR",
            "DATE_FROM",
            "DATE_TO",
            "KDAUF",
            "KDPOS",
            "STEUS",
            "GMNGA",
            "MEINS",
            "MATNR",
        ]

        for idx, it in enumerate(items, start=1):
            if not isinstance(it, dict):
                raise ValueError(f"items[{idx}] harus object/dict")

            row = {}

            for f in required_fields:
                v = _get_any(it, f, None)

                # create: DOCNO boleh kosong (SAP generate)
                if mode == "create" and f == "DOCNO":
                    v = "" if v is None else str(v).strip()
                    # kalau header_docno ada, dan item DOCNO kosong -> isi dari header
                    if (not v) and header_docno:
                        v = str(header_docno).strip()
                    row["DOCNO"] = v
                    continue

                # update: DOCNO wajib
                if v is None or str(v).strip() == "":
                    # untuk update, kalau item DOCNO kosong tapi header_docno ada -> isi otomatis
                    if mode == "update" and f == "DOCNO" and header_docno:
                        row["DOCNO"] = str(header_docno).strip()
                        continue
                    raise ValueError(f"items[{idx}].{f} wajib untuk {mode}")

                row[f] = str(v).strip()

            # normalisasi date fields per item
            row["DATE_FROM"] = normalize_sap_date(row["DATE_FROM"], field_name=f"items[{idx}].DATE_FROM", required=True)
            row["DATE_TO"] = normalize_sap_date(row["DATE_TO"], field_name=f"items[{idx}].DATE_TO", required=True)

            # qty numeric
            row["GMNGA"] = parse_qty(row["GMNGA"], field_name=f"items[{idx}].GMNGA", required=True)

            # rule: jangan kirim GMNGX dan RMKQTY
            row.pop("GMNGX", None)
            row.pop("RMKQTY", None)

            rows.append(row)

    elif mode == "edit_qty":
        required_fields = ["DOCNO", "AUFNR", "VORNR", "PERNR", "GMNGA"]
        for idx, it in enumerate(items, start=1):
            if not isinstance(it, dict):
                raise ValueError(f"items[{idx}] harus object/dict")

            row = {}
            for f in required_fields:
                v = _get_any(it, f, None)

                # kalau DOCNO kosong dan header_docno ada -> isi dari header
                if f == "DOCNO" and (v is None or str(v).strip() == "") and header_docno:
                    v = str(header_docno).strip()

                if v is None or str(v).strip() == "":
                    raise ValueError(f"items[{idx}].{f} wajib untuk edit_qty")

                row[f] = str(v).strip()

            row["GMNGA"] = parse_qty(row["GMNGA"], field_name=f"items[{idx}].GMNGA", required=True)
            rows.append(row)

    elif mode == "add_remark_qty":
        required_fields = ["DOCNO", "AUFNR", "VORNR", "PERNR", "RMKQTY"]
        for idx, it in enumerate(items, start=1):
            if not isinstance(it, dict):
                raise ValueError(f"items[{idx}] harus object/dict")

            row = {}
            for f in required_fields:
                v = _get_any(it, f, None)

                # kalau DOCNO kosong dan header_docno ada -> isi dari header
                if f == "DOCNO" and (v is None or str(v).strip() == "") and header_docno:
                    v = str(header_docno).strip()

                if v is None or str(v).strip() == "":
                    raise ValueError(f"items[{idx}].{f} wajib untuk add_remark_qty")

                row[f] = str(v).strip()

            row["RMKQTY"] = parse_qty(row["RMKQTY"], field_name=f"items[{idx}].RMKQTY", required=True)
            rows.append(row)

    else:
        raise ValueError(f"mode tidak dikenal: {mode}")

    return rows


# ------------------------------------------------------------
# RFC callers
# ------------------------------------------------------------
def call_zrfc_wi_data(params: dict, username: str, password: str) -> dict:
    """Memanggil RFC ZRFC_WI_DATA dan mengembalikan response SAP apa adanya."""
    conn = None
    try:
        conn = connect_sap(username=username, password=password)
        cleaned = {k: v for k, v in (params or {}).items() if v is not None}
        result = conn.call("ZRFC_WI_DATA", **cleaned)
        return result
    finally:
        try:
            if conn is not None:
                conn.close()
        except Exception:
            pass


def call_zrfc_delete_wi(i_docno: str, username: str, password: str) -> dict:
    """Delete WI document menggunakan RFC ZRFC_DELETE_WI."""
    conn = None
    try:
        conn = connect_sap(username=username, password=password)
        result = conn.call("ZRFC_DELETE_WI", I_DOCNO=str(i_docno))
        return result
    finally:
        try:
            if conn is not None:
                conn.close()
        except Exception:
            pass

def call_zrfc_delete_wi_per(i_docno: str, i_aufnr: str, i_vornr: str, i_pernr: str, username: str, password: str) -> dict:
    """Delete item WI per person/operation menggunakan RFC ZRFC_DELETE_WI_PER."""
    conn = None
    try:
        conn = connect_sap(username=username, password=password)
        result = conn.call(
            "ZRFC_DELETE_WI_PER",
            I_DOCNO=str(i_docno),
            I_AUFNR=str(i_aufnr),
            I_VORNR=str(i_vornr),
            I_PERNR=str(i_pernr),
        )
        return result
    finally:
        try:
            if conn is not None:
                conn.close()
        except Exception:
            pass
# ------------------------------------------------------------
# Endpoints
# ------------------------------------------------------------
@app.route("/api/create_document_wi", methods=["POST"])
def create_document_wi():
    """Create WI (ZRFC_WI_DATA)"""
    try:
        username, password = get_credentials()
        payload = request.get_json(force=True) or {}
        header, items = extract_header_and_items(payload)

        hdr_docno = str(_get_any(header, "I_DOCNO", "")).strip()  # boleh kosong untuk create

        params = {
            "I_DATE_FROM": normalize_sap_date(_get_any(header, "I_DATE_FROM"), field_name="I_DATE_FROM", required=False),
            "I_DATE_TO": normalize_sap_date(_get_any(header, "I_DATE_TO"), field_name="I_DATE_TO", required=False),
            "I_DOCNO": hdr_docno,
            "T_WI": build_t_wi(items, mode="create", header_docno=hdr_docno or None),
        }

        result = call_zrfc_wi_data(params, username, password)
        return jsonify(json_safe(result)), 200

    except ValueError as e:
        return jsonify({"ok": False, "error": str(e)}), 400
    except (LogonError, CommunicationError, RFCLibError, RFCError) as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 502
    except (ABAPApplicationError, ABAPRuntimeError) as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 500
    except Exception as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 500


@app.route("/api/update_document_wi", methods=["PUT"])
def update_document_wi():
    """Update WI (ZRFC_WI_DATA)"""
    try:
        username, password = get_credentials()
        payload = request.get_json(force=True) or {}
        header, items = extract_header_and_items(payload)

        hdr_docno = str(_get_any(header, "I_DOCNO", "")).strip()
        if not hdr_docno:
            raise ValueError("I_DOCNO wajib untuk update")

        params = {
            "I_DATE_FROM": normalize_sap_date(_get_any(header, "I_DATE_FROM"), field_name="I_DATE_FROM", required=False),
            "I_DATE_TO": normalize_sap_date(_get_any(header, "I_DATE_TO"), field_name="I_DATE_TO", required=False),
            "I_DOCNO": hdr_docno,
            "T_WI": build_t_wi(items, mode="update", header_docno=hdr_docno),
        }

        result = call_zrfc_wi_data(params, username, password)
        return jsonify(json_safe(result)), 200

    except ValueError as e:
        return jsonify({"ok": False, "error": str(e)}), 400
    except (LogonError, CommunicationError, RFCLibError, RFCError) as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 502
    except (ABAPApplicationError, ABAPRuntimeError) as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 500
    except Exception as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 500


@app.route("/api/edit_qty_wi", methods=["PATCH"])
def edit_qty_wi():
    """Edit Qty WI (ZRFC_WI_DATA)"""
    try:
        username, password = get_credentials()
        payload = request.get_json(force=True) or {}
        header, items = extract_header_and_items(payload)

        hdr_docno = str(_get_any(header, "I_DOCNO", "")).strip() or None

        # Jika items tidak dikirim, bangun 1 baris dari header.
        if not items:
            items = [{
                "DOCNO": _get_any(header, "DOCNO", hdr_docno or ""),
                "AUFNR": _get_any(header, "AUFNR", ""),
                "VORNR": _get_any(header, "VORNR", ""),
                "PERNR": _get_any(header, "PERNR", ""),
                "GMNGA": _get_any(header, "GMNGA", ""),
            }]

        t_wi = build_t_wi(items, mode="edit_qty", header_docno=hdr_docno)

        # header I_DOCNO harus ada (ambil dari header atau dari item)
        if not hdr_docno:
            hdr_docno = str(t_wi[0].get("DOCNO", "")).strip()
        if not hdr_docno:
            raise ValueError("DOCNO/I_DOCNO wajib untuk edit_qty")

        params = {
            "I_DOCNO": hdr_docno,
            "T_WI": t_wi,
        }

        result = call_zrfc_wi_data(params, username, password)
        return jsonify(json_safe(result)), 200

    except ValueError as e:
        return jsonify({"ok": False, "error": str(e)}), 400
    except (LogonError, CommunicationError, RFCLibError, RFCError) as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 502
    except (ABAPApplicationError, ABAPRuntimeError) as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 500
    except Exception as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 500


@app.route("/api/delete_document_wi", methods=["DELETE"])
def delete_document_wi():
    """Delete WI (ZRFC_DELETE_WI)"""
    try:
        username, password = get_credentials()
        payload = request.get_json(force=True) or {}

        i_docno = (
            payload.get("I_DOCNO")
            or payload.get("i_docno")
            or (payload.get("header") or {}).get("I_DOCNO")
            or (payload.get("header") or {}).get("i_docno")
            or payload.get("DOCNO")
            or payload.get("docno")
        )

        if i_docno is None or str(i_docno).strip() == "":
            return jsonify({"ok": False, "error": "I_DOCNO wajib untuk delete"}), 400

        result = call_zrfc_delete_wi(str(i_docno).strip(), username, password)
        return jsonify(json_safe(result)), 200

    except ValueError as e:
        return jsonify({"ok": False, "error": str(e)}), 400
    except (LogonError, CommunicationError, RFCLibError, RFCError) as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 502
    except (ABAPApplicationError, ABAPRuntimeError) as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 500
    except Exception as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 500

@app.route("/api/delete_wi_item", methods=["DELETE"])
def delete_wi_item():
    try:
        username, password = get_credentials()

        # ambil dari query param dulu
        i_docno = request.args.get("I_DOCNO") or request.args.get("docno")
        i_aufnr = request.args.get("I_AUFNR") or request.args.get("aufnr")
        i_vornr = request.args.get("I_VORNR") or request.args.get("vornr")
        i_pernr = request.args.get("I_PERNR") or request.args.get("pernr")

        # fallback: ambil dari body JSON (jika ada)
        payload = {}
        try:
            payload = request.get_json(silent=True) or {}
        except Exception:
            payload = {}

        if not i_docno:
            i_docno = payload.get("I_DOCNO") or payload.get("docno") or (payload.get("header") or {}).get("I_DOCNO")
        if not i_aufnr:
            i_aufnr = payload.get("I_AUFNR") or payload.get("aufnr")
        if not i_vornr:
            i_vornr = payload.get("I_VORNR") or payload.get("vornr")
        if not i_pernr:
            i_pernr = payload.get("I_PERNR") or payload.get("pernr")

        # validasi wajib
        if i_docno is None or str(i_docno).strip() == "":
            return jsonify({"ok": False, "error": "I_DOCNO wajib"}), 400
        if i_aufnr is None or str(i_aufnr).strip() == "":
            return jsonify({"ok": False, "error": "I_AUFNR wajib"}), 400
        if i_vornr is None or str(i_vornr).strip() == "":
            return jsonify({"ok": False, "error": "I_VORNR wajib"}), 400
        if i_pernr is None or str(i_pernr).strip() == "":
            return jsonify({"ok": False, "error": "I_PERNR wajib"}), 400

        result = call_zrfc_delete_wi_per(
            str(i_docno).strip(),
            str(i_aufnr).strip(),
            str(i_vornr).strip(),
            str(i_pernr).strip(),
            username,
            password,
        )

        # return SAP apa adanya
        return jsonify(json_safe(result)), 200

    except ValueError as e:
        return jsonify({"ok": False, "error": str(e)}), 400

    except (LogonError, CommunicationError, RFCLibError, RFCError) as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 502

    except (ABAPApplicationError, ABAPRuntimeError) as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 500

    except Exception as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 500

@app.route("/api/add_remark_qty", methods=["POST"])
def add_remark_qty():
    try:
        username, password = get_credentials()
        payload = request.get_json(force=True) or {}
        header, items = extract_header_and_items(payload)

        hdr_docno = str(_get_any(header, "I_DOCNO", "")).strip() or None

        # Jika items tidak dikirim, bangun 1 baris dari header.
        if not items:
            items = [{
                "DOCNO": _get_any(header, "DOCNO", hdr_docno or ""),
                "AUFNR": _get_any(header, "AUFNR", ""),
                "VORNR": _get_any(header, "VORNR", ""),
                "PERNR": _get_any(header, "PERNR", ""),
                "GMNGA": _get_any(header, "RMKQTY", ""),
            }]

        t_wi = build_t_wi(items, mode="add_remark_qty", header_docno=hdr_docno)

        # header I_DOCNO harus ada (ambil dari header atau dari item)
        if not hdr_docno:
            hdr_docno = str(t_wi[0].get("DOCNO", "")).strip()
        if not hdr_docno:
            raise ValueError("DOCNO/I_DOCNO wajib untuk add_remark_qty")

        params = {
            "I_DOCNO": hdr_docno,
            "T_WI": t_wi,
        }

        result = call_zrfc_wi_data(params, username, password)
        return jsonify(json_safe(result)), 200

    except ValueError as e:
        return jsonify({"ok": False, "error": str(e)}), 400
    except (LogonError, CommunicationError, RFCLibError, RFCError) as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 502
    except (ABAPApplicationError, ABAPRuntimeError) as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 500
    except Exception as e:
        return jsonify({"ok": False, "E_RETURN": make_bapiret2_error(str(e))}), 500

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5015, debug=True, use_reloader=False)