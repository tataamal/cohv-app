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
from datetime import datetime

app = Flask(__name__)
CORS(app, supports_credentials=True, resources={r"/api/*": {"origins": "*"}})

def connect_sap(username=None, password=None):
    username = username or os.environ.get('SAP_USERNAME')
    password = password or os.environ.get('SAP_PASSWORD')
    if not username or not password:
        raise Exception("SAP credentials not provided.")

    return Connection(
        user=username,
        passwd=password,
        ashost='192.168.254.154',
        sysnr='01',
        client='300',
        lang='EN',
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
    username = request.headers.get('X-SAP-Username')
    password = request.headers.get('X-SAP-Password')

    if not username or not password:
        raise ValueError("SAP credentials not found in headers.")

    return username, password

def normalize_budat(value: str) -> str:
    """
    Input:  DDMMYYYY (contoh: 28022026)
    Output: YYYYMMDD (untuk SAP) -> 20260228
    """
    if value is None:
        raise ValueError("P_BUDAT is required")

    s = str(value).strip()
    if not s:
        raise ValueError("P_BUDAT is required")

    s = s.replace("-", "").replace("/", "")

    if len(s) != 8 or not s.isdigit():
        raise ValueError("P_BUDAT harus format DDMMYYYY, contoh: 28022026")

    try:
        dt = datetime.strptime(s, "%d%m%Y")
    except ValueError:
        raise ValueError("P_BUDAT tidak valid. Gunakan DDMMYYYY, contoh: 28022026")

    return dt.strftime("%Y%m%d")

def normalize_werks(value: str) -> str:
    if not value:
        return ""
    
    s = str(value).strip()
    if s in ("1001", "1200"):
        return s
        
    if s.startswith("30"):
        return "3000"
    if s.startswith("20"):
        return "2000"
    if s.startswith("10"):
        return "1000"
        
    return s

def to_decimal(val) -> Decimal:
    if val is None or val == "":
        return Decimal("0")
    try:
        # pyrfc kadang sudah Decimal/float/str
        return Decimal(str(val))
    except (InvalidOperation, ValueError, TypeError):
        return Decimal("0")

def extract_sap_errors(return_table) -> list[str]:
    """
    RETURN biasanya BAPIRET2. Anggap error jika TYPE E/A/X.
    """
    errors = []
    for r in (return_table or []):
        t = (r.get("TYPE") or "").upper()
        if t in ("E", "A", "X"):
            msg = r.get("MESSAGE") or ""
            if msg:
                errors.append(msg)
    return errors


# ENDPOINT
@app.route('/api/check_hasil_konfirmasi', methods=['POST'])
def check_hasil_konfirmasi():
    # Default response fields (biar konsisten)
    base_resp = {
        "status": "failed",
        "msg_error": "",
        "total_rows": 0,
        "confirmed_qty": 0.0
    }

    try:
        username, password = get_credentials()

        payload = request.get_json(silent=True) or {}

        p_aufnr = (payload.get("P_AUFNR") or "").strip()
        p_vornr = (payload.get("P_VORNR") or "").strip()
        p_pernr = (payload.get("P_PERNR") or "").strip()
        p_werks_raw = (payload.get("P_WERKS") or "").strip()
        p_budat_raw = payload.get("P_BUDAT")

        missing = []
        if not p_aufnr: missing.append("P_AUFNR")
        if not p_vornr: missing.append("P_VORNR")
        if not p_pernr: missing.append("P_PERNR")
        if not p_werks_raw: missing.append("P_WERKS")
        if p_budat_raw in (None, ""): missing.append("P_BUDAT")

        if missing:
            base_resp["msg_error"] = f"Missing parameter(s): {', '.join(missing)}"
            return jsonify(base_resp), 400

        p_budat = normalize_budat(p_budat_raw)
        p_werks = normalize_werks(p_werks_raw)

        conn = None
        try:
            conn = connect_sap(username, password)

            result = conn.call(
                "Z_FM_YPPR062",
                P_AUFNR=p_aufnr,
                P_VORNR=p_vornr,
                P_PERNR=p_pernr,
                P_BUDAT=p_budat,
                P_WERKS=p_werks
            )

        finally:
            try:
                if conn is not None:
                    conn.close()
            except Exception:
                pass

        return_table = result.get("RETURN") or []
        t_data1 = result.get("T_DATA1") or []

        # Hitung rows
        total_rows = len(t_data1)

        # Sum GMNGX
        confirmed_qty_dec = Decimal("0")
        for row in t_data1:
            confirmed_qty_dec += to_decimal(row.get("GMNGX"))

        # Cek error dari SAP RETURN
        sap_errors = extract_sap_errors(return_table)
        if sap_errors:
            base_resp["msg_error"] = " | ".join(sap_errors)
            base_resp["total_rows"] = total_rows
            base_resp["confirmed_qty"] = float(confirmed_qty_dec.quantize(Decimal("0.001")))
            return jsonify(base_resp), 502

        # Kalau tidak ada data, anggap not found tapi tetap success (qty 0)
        if total_rows == 0:
            resp = {
                "status": "success",
                "msg_error": "Data konfirmasi tidak ditemukan.",
                "total_rows": 0,
                "confirmed_qty": 0.0
            }
            return jsonify(resp), 200

        # Success
        resp = {
            "status": "success",
            "msg_error": "",
            "total_rows": total_rows,
            "confirmed_qty": float(confirmed_qty_dec.quantize(Decimal("0.001")))
        }
        return jsonify(resp), 200

    except ValueError as e:
        base_resp["msg_error"] = str(e)
        return jsonify(base_resp), 400

    except (LogonError,) as e:
        base_resp["msg_error"] = f"SAP logon error: {str(e)}"
        return jsonify(base_resp), 401

    except (CommunicationError,) as e:
        base_resp["msg_error"] = f"SAP communication error: {str(e)}"
        return jsonify(base_resp), 502

    except (ABAPApplicationError, ABAPRuntimeError, RFCError, RFCLibError) as e:
        base_resp["msg_error"] = f"SAP RFC error: {str(e)}"
        return jsonify(base_resp), 502

    except Exception as e:
        base_resp["msg_error"] = f"Unhandled error: {str(e)}"
        return jsonify(base_resp), 500


if __name__ == "__main__":
    app.run(host='0.0.0.0', port=5014, debug=True, use_reloader=False)