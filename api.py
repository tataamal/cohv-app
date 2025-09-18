# main.py
from flask import Flask, request, jsonify
from pyrfc import Connection, ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError
from concurrent.futures import ThreadPoolExecutor
import threading
import os
from flask_cors import CORS
from datetime import time

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

def get_credentials():
    """
    Mengambil kredensial SAP dari header request.
    """
    username = request.headers.get('X-SAP-Username')
    password = request.headers.get('X-SAP-Password')
    
    if not username or not password:
        # Kembalikan 401 Unauthorized jika tidak ada header
        raise ValueError("SAP credentials not found in headers.")
    
    return username, password

def get_data(plant_code=None, workcenters_csv=None, username=None, password=None):
    conn = connect_sap(username, password)
    all_data = []

    if not plant_code or not workcenters_csv:
        # Ambil semua data jika tidak ada plant atau workcenter
        result = conn.call('Z_FM_YPPR074', P_WERKS='', P_ARBPL='')
        all_data = result.get('T_DATA2', [])
    else:
        workcenters = workcenters_csv.split(',')
        for wc in workcenters:
            result = conn.call('Z_FM_YPPR074', P_WERKS=plant_code, P_ARBPL=wc)
            data = result.get('T_DATA2', [])
            all_data.extend(data)
    return all_data

def get_detail(plant_code=None, workcenter=None, username=None, password=None):
    conn = connect_sap(username, password)
    result = conn.call('Z_FM_YPPR074',
        P_WERKS=plant_code if plant_code else '',
        P_ARBPL=workcenter if workcenter else '',
    )
    return result.get('T_DATA1', [])

@app.route('/api/sap-login', methods=['POST'])
def sap_login():
    data = request.json

    try:
        conn = connect_sap(data['username'], data['password'])
        conn.ping()
        print("[DEBUG] Login sukses!")
        return jsonify({'status': 'connected'})
    except Exception as e:
        print("[ERROR] SAP Login failed:", str(e))
        return jsonify({'error': str(e)}), 401


@app.route('/api/sap_data', methods=['GET'])
def sap_data():
    """
    Endpoint untuk mengambil data SAP berdasarkan plant dan workcenter.
    """
    plant = request.args.get('plant')
    workcenter = request.args.get('workcenter')

    if not plant or not workcenter:
        return jsonify({'error': 'Missing plant or workcenter'}), 400

    try:
        username, password = get_credentials()
        data = get_data(plant, workcenter, username, password)
        return jsonify(data), 200
    except ValueError as ve:
        # Khusus untuk error validasi header
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        # Untuk error internal lainnya
        return jsonify({'error': f'Internal error: {str(e)}'}), 500

@app.route('/api/sap_detail', methods=['GET'])
def sap_detail():
    plant = request.args.get('plant')
    workcenter = request.args.get('workcenter')
    if not plant or not workcenter:
        return jsonify({'error': 'Missing plant or workcenter'}), 400

    try:
        username, password = get_credentials()
        detail = get_detail(plant, workcenter, username, password)
        return jsonify(detail)
    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        return jsonify({'error': str(e)}), 500

# CANGE WC
@app.route('/api/save_edit', methods=['POST'])
def changewc():
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        data = request.get_json()
        print("Data diterima:", data)

        if not data:
            return jsonify({'error': 'No JSON payload received'}), 400

        aufnr = data.get('IV_AUFNR')
        commit = data.get('IV_COMMIT', 'X')
        it_operation = data.get('IT_OPERATION', [])

        if not aufnr or not it_operation:
            print("IT Operation belum ada")
            return jsonify({'error': 'Missing required fields'}), 400

        if isinstance(it_operation, dict):
            it_operation = [it_operation]

        it_operation_filtered = []
        for op in it_operation:
            filtered = {
                'SEQUENCE': op.get('SEQUEN', ' '),
                'OPERATION': op.get('OPER', ''),
                'WORK_CENTER': op.get('WORK_CEN', ''),
                'WORK_CENTER_X': op.get('W', 'X'),
                'SHORT_TEXT': op.get('SHORT_T', ''),
                'SHORT_TEXT_X': op.get('S', 'X'),
            }
            it_operation_filtered.append(filtered)

        print("Calling RFC CO_SE_PRODORD_CHANGE...")
        result = conn.call(
            'CO_SE_PRODORD_CHANGE',
            IV_ORDER_NUMBER=aufnr,
            IV_COMMIT=commit,
            IT_OPERATION=it_operation_filtered
        )
        import time
        time.sleep(2) 
        return jsonify(result)

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        print("Exception:", str(e))
        return jsonify({'error': str(e)}), 500

@app.route('/api/sap_combined', methods=['GET'])
def sap_combined():
    plant = request.args.get('plant') #plant disini adalah kode

    if not plant:
        return jsonify({'error': 'Missing plant parameter'}), 400

    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        # Panggil RFC hanya dengan parameter plant
        result = conn.call('Z_FM_YPPR074Z', P_WERKS=plant)

        return jsonify({
            "T_DATA": result.get('T_DATA', []),
            "T_DATA1": result.get('T_DATA1', []),
            "T_DATA2": result.get('T_DATA2', []),
            "T_DATA3": result.get('T_DATA3', []),
            "T_DATA4": result.get('T_DATA4', []),
        })

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/refresh-pro', methods=['GET'])
def refresh_single_pro():
    
    try:
        plant = (request.args.get('plant') or request.args.get('WERKS') or '').strip()
        aufnr = (request.args.get('aufnr') or request.args.get('order') or '').strip()

        if not plant:
            return jsonify({'error': 'Missing plant parameter'}), 400
        if not aufnr:
            return jsonify({'error': 'Missing AUFNR parameter'}), 400
        if ',' in aufnr:
            return jsonify({'error': 'Only single AUFNR is allowed.'}), 400

        def pad12(v: str) -> str:
            v = str(v or '')
            return v if len(v) >= 12 else v.zfill(12)

        a12 = pad12(aufnr)

        username, password = get_credentials()
        conn = connect_sap(username, password)

        res = conn.call('Z_FM_YPPR074Z', P_WERKS=plant, P_AUFNR=a12)

        # --- helpers ---
        def as_list(x):
            if not x:
                return []
            return x if isinstance(x, list) else [x]

        def map_werks(lst, fallback_plant):
            """
            Map field SAP 'WERK' -> 'WERKS' agar selaras dengan kolom MySQL.
            Jika tidak ada keduanya, isi dengan fallback_plant.
            """
            out = []
            for row in as_list(lst):
                if isinstance(row, dict):
                    row = dict(row)  # shallow copy
                    row['WERKS'] = row.get('WERKS') or row.get('WERK') or fallback_plant
                    # hapus WERK untuk hindari kebingungan di sisi Laravel/DB
                    row.pop('WERK', None)
                out.append(row)
            return out

        # --- normalisasi list + mapping WERK->WERKS ---
        t_data  = map_werks(res.get('T_DATA',  []), plant)
        t_data1 = map_werks(res.get('T_DATA1', []), plant)
        t_data2 = map_werks(res.get('T_DATA2', []), plant)
        t_data3 = map_werks(res.get('T_DATA3', []), plant)
        t_data4 = map_werks(res.get('T_DATA4', []), plant)

        return jsonify({
            "plant":  plant,
            "AUFNR":  a12,
            "T_DATA":  t_data,
            "T_DATA1": t_data1,
            "T_DATA2": t_data2,
            "T_DATA3": t_data3,
            "T_DATA4": t_data4,
        }), 200

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        print("[refresh-pro] exception:", repr(e))
        return jsonify({'error': str(e)}), 500
    
@app.route('/api/data_refresh', methods=['GET'])
def sap_refresh():
    """
    GET  : /api/data_refresh?plant=A100&AUFNR=000000123456&AUFNR=000000123457
           atau /api/data_refresh?plant=A100&AUFNR=000000123456,000000123457
    POST : body {"plant":"A100","AUFNR":["000000123456","000000123457"]}
    """
    try:
        # --- ambil input ---
        if request.method == 'GET':
            plant = request.args.get('plant') or request.args.get('WERKS')
            aufnr_list = request.args.getlist('AUFNR')
            if not aufnr_list:
                s = (request.args.get('AUFNR') or '').strip()
                if s:
                    aufnr_list = [x.strip() for x in s.split(',') if x.strip()]
        else:  # POST
            data = request.get_json(silent=True) or {}
            plant = data.get('plant') or data.get('WERKS')
            tmp = data.get('AUFNR') or data.get('orders') or []
            if isinstance(tmp, list):
                aufnr_list = [str(x).strip() for x in tmp if str(x).strip()]
            elif isinstance(tmp, str):
                aufnr_list = [x.strip() for x in tmp.split(',') if x.strip()]
            else:
                aufnr_list = []

        if not plant:
            return jsonify({'error': 'Missing plant parameter'}), 400

        # helper: pad AUFNR ke 12 digit
        def pad12(v: str) -> str:
            v = str(v or '')
            return v if len(v) >= 12 else v.zfill(12)

        username, password = get_credentials()
        conn = connect_sap(username, password)

        # --- SINGLE (atau tanpa AUFNR, tergantung RFC: berarti semua di plant) ---
        if not aufnr_list or len(aufnr_list) == 1:
            a = pad12(aufnr_list[0]) if aufnr_list else None
            res = conn.call('Z_FM_YPPR074Z', P_WERKS=plant, AUFNR=a)
            return jsonify({
                "plant": plant,
                "AUFNR": a,
                "T_DATA": res.get('T_DATA', []),
                "T_DATA1": res.get('T_DATA1', []),
                "T_DATA2": res.get('T_DATA2', []),
                "T_DATA3": res.get('T_DATA3', []),
                "T_DATA4": res.get('T_DATA4', []),
            }), 200

        # --- MULTI: loop per AUFNR dalam 1 koneksi SAP ---
        out = []
        for a in aufnr_list:
            a12 = pad12(a)
            res = conn.call('Z_FM_YPPR074Z', P_WERKS=plant, AUFNR=a12)
            out.append({
                "plant": plant,
                "AUFNR": a12,
                "T_DATA": res.get('T_DATA', []),
                "T_DATA1": res.get('T_DATA1', []),
                "T_DATA2": res.get('T_DATA2', []),
                "T_DATA3": res.get('T_DATA3', []),
                "T_DATA4": res.get('T_DATA4', []),
            })

        return jsonify({"results": out}), 200

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        print("[COHV] data_refresh exception:", repr(e))
        return jsonify({'error': str(e)}), 500

@app.route('/api/sap_combined_multi', methods=['POST'])
def sap_combined_multi():
    data = request.get_json()
    plant = data.get('plant')
    aufnrs = data.get('aufnrs', [])

    if not plant or not isinstance(aufnrs, list):
        return jsonify({'error': 'Missing plant or aufnrs[]'}), 400

    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)
        import time
        time.sleep(2)
        all_data1 = []
        all_data4 = []

        for aufnr in aufnrs:
            result = conn.call('Z_FM_YPPR074Z', P_WERKS=plant, P_AUFNR=aufnr)
            all_data1.extend(result.get('T_DATA1', []))
            all_data4.extend(result.get('T_DATA4', []))

        return jsonify({
            'T_DATA1': all_data1,
            'T_DATA4': all_data4,
        })

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        return jsonify({'error': str(e)}), 500


# RELEASE PRO
@app.route('/api/release_order', methods=['POST'])
def release_order():
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        data = request.get_json()
        print("Data diterima untuk release:", data)

        aufnr = data.get('AUFNR')
        if not aufnr:
            return jsonify({'error': 'AUFNR is required'}), 400

        print("Calling RFC BAPI_PRODORD_RELEASE...")

        result = conn.call(
            'BAPI_PRODORD_RELEASE',
            RELEASE_CONTROL='1',
            WORK_PROCESS_GROUP='COWORK_BAPI',
            WORK_PROCESS_MAX=99,
            ORDERS=[{'ORDER_NUMBER': aufnr}]
        )

        return jsonify(result)

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        print("Exception saat release order:", str(e))
        return jsonify({'error': str(e)}), 500

# TECO
@app.route('/api/teco_order', methods=['POST'])
def teco_order():
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        data = request.get_json()
        print("Data diterima untuk teco:", data)

        aufnr = data.get('AUFNR')
        if not aufnr:
            return jsonify({'error': 'AUFNR is required'}), 400

        # Panggil BAPI_PRODORD_COMPLETE_TECH
        print(f"Calling BAPI_PRODORD_COMPLETE_TECH for order {aufnr}...")
        result_teco = conn.call(
            'BAPI_PRODORD_COMPLETE_TECH',
            SCOPE_COMPL_TECH='1',
            WORK_PROCESS_GROUP='COWORK_BAPI',
            WORK_PROCESS_MAX=99,
            ORDERS=[{'ORDER_NUMBER': aufnr}]
        )
        print("Result from TECO BAPI:", result_teco)

        # Validasi hasil dari BAPI TECO
        # BAPI ini sering mengembalikan pesan error di tabel DETAIL_RETURN
        if 'DETAIL_RETURN' in result_teco and result_teco['DETAIL_RETURN']:
            for message in result_teco['DETAIL_RETURN']:
                if message['TYPE'] in ['E', 'A']: # E = Error, A = Abort
                    error_msg = f"SAP Error: {message['MESSAGE']}"
                    print(error_msg)
                    # Jika ada error, jangan commit dan kembalikan pesan error
                    return jsonify({'error': error_msg, 'sap_response': result_teco}), 400

        # Jika tidak ada error, lakukan COMMIT
        print("Calling BAPI_TRANSACTION_COMMIT...")
        result_commit = conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
        print("Result from COMMIT BAPI:", result_commit)

        # Kembalikan respons sukses
        return jsonify({
            'BAPI_PRODORD_COMPLETE_TECH': result_teco,
            'BAPI_TRANSACTION_COMMIT': result_commit
        }), 200

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401 # Unauthorized
    except Exception as e:
        print("Exception saat teco:", str(e))
        # Log error yang lebih detail di server
        import traceback
        traceback.print_exc()
        return jsonify({'error': 'Internal server error in Flask API.'}), 500
    finally:
        if 'conn' in locals() and conn:
            conn.close() # Selalu tutup koneksi

# CHANGE PV
@app.route('/api/change_prod_version', methods=['POST'])
def change_prod_version():
    import time
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        data = request.get_json()
        aufnr = data.get('AUFNR')
        verid = data.get('PROD_VERSION')

        if not aufnr or not verid:
            return jsonify({'error': 'AUFNR and PROD_VERSION are required'}), 400

        print("AUFNR:", aufnr, "→ target PROD_VERSION:", verid)

        # Ambil PROD_VERSION sebelum diubah
        before_detail = conn.call('BAPI_PRODORD_GET_DETAIL', NUMBER=aufnr)
        before_version = before_detail.get('ORDER_GENERAL_DETAIL', {}).get('PROD_VERSION', 'unknown')

        print("Sebelum ubah: PROD_VERSION =", before_version)

        # Lakukan perubahan
        result_change = conn.call(
            'BAPI_PRODORD_CHANGE',
            NUMBER=aufnr,
            ORDERDATA={'PROD_VERSION': verid},
            ORDERDATAX={'PROD_VERSION': 'X'}
        )

        # Ambil tabel RETURN
        sap_return = result_change.get('RETURN', [])

        # Cari pesan dengan tipe 'E' (Error) atau 'A' (Abort)
        has_error = any(
            msg.get('TYPE') in ['E', 'A']
            for msg in (sap_return if isinstance(sap_return, list) else [sap_return])
        )

        if has_error:
            # Jika ada error, kumpulkan pesannya dan kembalikan sebagai error server
            error_messages = [
                f"[{msg.get('TYPE')}] {msg.get('MESSAGE')}"
                for msg in (sap_return if isinstance(sap_return, list) else [sap_return])
            ]
            error_string = "\n".join(error_messages)
            print("❌ BAPI Error:", error_string)
            # Jangan lanjutkan ke COMMIT jika BAPI gagal
            return jsonify({'error': f'SAP BAPI Error:\n{error_string}'}), 500

        # Commit perubahan
        print("BAPI_TRANSACTION_COMMIT...")
        conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')

        # Delay agar commit selesai
        time.sleep(10)

        # Ambil ulang versi setelah perubahan
        after_detail = conn.call('BAPI_PRODORD_GET_DETAIL', NUMBER=aufnr)
        after_version = after_detail.get('ORDER_GENERAL_DETAIL', {}).get('PROD_VERSION', 'unknown')

        # Ambil pesan dari RETURN
        sap_return = result_change.get('RETURN', [])

        return jsonify({
            'before_version': before_version,
            'after_version': after_version,
            'sap_return': sap_return
        })

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        print("❌ Exception:", str(e))
        return jsonify({'error': str(e)}), 500

# CONVERT 
# @app.route('/api/create_prod_order', methods=['POST'])
# def create_prod_order_from_plord():
#     try:
#         username, password = get_credentials()
#         conn = connect_sap(username, password)

#         data = request.get_json()
#         plnum = data.get('PLANNED_ORDER')
#         order_type = data.get('AUART')
#         plant = data.get('PLANT')

#         if not plnum or not order_type:
#             return jsonify({'error': 'PLANNED_ORDER and AUART are required'}), 400

#         print(f"Calling BAPI_PRODORD_CREATE_FROM_PLORD with PLANNED_ORDER: {plnum} and ORDER_TYPE: {order_type}")

#         result = conn.call(
#             'BAPI_PRODORD_CREATE_FROM_PLORD',
#             PLANNED_ORDER=plnum,
#             ORDER_TYPE=order_type
#         )

#         return_data = result.get('RETURN', {})
#         order_number = result.get('PRODUCTION_ORDER', '')

#         print("Result from BAPI_PRODORD_CREATE_FROM_PLORD:", result)

#         # Commit jika tidak error
#         if return_data.get('TYPE') != 'E':
#             conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')

#         return jsonify({
#             'success': return_data.get('TYPE') != 'E',
#             'order_number': order_number,
#             'return': return_data
#         })

#     except ValueError as ve:
#         return jsonify({'error': str(ve)}), 401
#     except Exception as e:
#         print("Exception:", str(e))
#         return jsonify({'error': str(e)}), 500

@app.route('/api/create_prod_order', methods=['POST'])
def create_prod_order_from_plord():
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        data = request.get_json() or {}

        # --- helper untuk normalisasi RETURN ---
        def normalize_return(ret):
            if isinstance(ret, list):
                msgs = ret
            elif isinstance(ret, dict):
                msgs = [ret] if ret else []
            else:
                msgs = []
            has_error = any((m.get('TYPE') in ('E', 'A')) for m in msgs)
            # minimal fields agar enak dipakai FE
            msgs = [{
                'type':    m.get('TYPE'),
                'id':      m.get('ID'),
                'number':  m.get('NUMBER'),
                'message': m.get('MESSAGE'),
                'log_no':  m.get('LOG_NO'),
                'log_msg_no': m.get('LOG_MSG_NO')
            } for m in msgs]
            return msgs, has_error

        # --- deteksi batch ---
        items = data.get('ITEMS') or data.get('PLANNED_ORDERS')
        if isinstance(items, list):
            # Bisa list of dict atau list of string PLANNED_ORDER
            def to_item(x):
                if isinstance(x, dict):
                    return x
                return {
                    'PLANNED_ORDER': x,
                    'AUART': data.get('AUART'),
                    'PLANT': data.get('PLANT')
                }

            results = []
            for it in map(to_item, items):
                plnum = it.get('PLANNED_ORDER')
                auart = it.get('AUART') or data.get('AUART')
                plant = it.get('PLANT') or data.get('PLANT')

                if not plnum or not auart:
                    results.append({
                        'planned_order': plnum,
                        'plant': plant,
                        'production_orders': [],
                        'success': False,
                        'messages': [{'type': 'E', 'message': 'PLANNED_ORDER and AUART are required'}]
                    })
                    continue

                print(f"[COHV] CREATE_FROM_PLORD: PLO={plnum} AUART={auart}")
                res = conn.call('BAPI_PRODORD_CREATE_FROM_PLORD',
                                PLANNED_ORDER=plnum,
                                ORDER_TYPE=auart)

                msgs, has_error = normalize_return(res.get('RETURN'))
                aufnr = (res.get('PRODUCTION_ORDER') or '').zfill(12)
                orders = [aufnr] if aufnr.strip('0') else []

                results.append({
                    'planned_order': plnum,
                    'plant': plant,
                    'production_orders': orders,   # <- satu atau lebih (saat ini 1 per PLO)
                    'success': not has_error,
                    'messages': msgs
                })

            return jsonify({'results': results}), 200

        # --- single item ---
        plnum = data.get('PLANNED_ORDER')
        auart = data.get('AUART')
        plant = data.get('PLANT')  # ini kita echo balik ke FE

        if not plnum or not auart:
            return jsonify({'error': 'PLANNED_ORDER and AUART are required'}), 400

        print(f"[COHV] CREATE_FROM_PLORD: PLO={plnum} AUART={auart}")
        result = conn.call('BAPI_PRODORD_CREATE_FROM_PLORD',
                           PLANNED_ORDER=plnum,
                           ORDER_TYPE=auart)

        msgs, has_error = normalize_return(result.get('RETURN'))
        aufnr = (result.get('PRODUCTION_ORDER') or '').zfill(12)
        orders = [aufnr] if aufnr.strip('0') else []

        return jsonify({
            'planned_order': plnum,
            'plant': plant,                 # <- dikembalikan sesuai request
            'production_orders': orders,    # <- array; bisa 1 atau >1
            'success': not has_error,
            'messages': msgs
        }), 200

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        print("[COHV] Exception:", str(e))
        return jsonify({'error': str(e)}), 500

@app.route('/api/sap-po', methods=['POST'])
def fetch_purchase_orders():
    try:
        username, password = get_credentials()
        plants = request.json.get('plants', [])

        all_data1 = []
        all_data2 = []
        lock = threading.Lock()

        def fetch_from_sap(plant):
            try:
                local_conn = connect_sap(username, password)
                print(f"Fetching from plant: {plant}")
                result = local_conn.call('Z_FM_YMMR068', P_WERKS=plant)

                with lock:
                    if 'T_DATA1' in result:
                        all_data1.extend(result['T_DATA1'])
                    if 'T_DATA2' in result:
                        # PASTIKAN TIDAK ADA MANIPULASI TEXT DI SINI - BIARKAN ORIGINAL
                        for row in result['T_DATA2']:
                            # JANGAN TAMBAHKAN APAPUN KE TEXT FIELD
                            # Biarkan kosong jika memang kosong dari SAP
                            pass
                        all_data2.extend(result['T_DATA2'])
            except Exception as e:
                print(f"[ERROR] Plant {plant}: {str(e)}")

        with ThreadPoolExecutor(max_workers=min(5, len(plants))) as executor:
            executor.map(fetch_from_sap, plants)

        return jsonify({
            'T_DATA1': all_data1,
            'T_DATA2': all_data2,
        })

    except Exception as e:
        print("Exception:", str(e))
        return jsonify({'error': str(e)}), 500

@app.route('/api/reject_po', methods=['POST'])
def reject_po():
    try:
        username, password = get_credentials()
        data = request.json or {}
        ebeln = data.get('EBELN')

        if not ebeln:
            return jsonify({'error': 'Parameter EBELN wajib diisi'}), 400

        print("EBELN diterima:", ebeln)  # Debug

        conn = connect_sap(username, password)
        result = conn.call('Z_PO_REJECT', I_EBELN=ebeln)

        return jsonify({'status': 'success', 'result': result}), 200

    except Exception as e:
        print("[ERROR] Reject PO:", str(e))
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/api/z_po_comment_update', methods=['POST'])
def comment_update():
    try:
        username, password = get_credentials()
        data = request.json or {}

        ebeln = data.get('PURCHASEORDER')
        comment = data.get('COMMENT_TEXT')

        print(f"[DEBUG] Received Comment Update: EBELN={ebeln}, TEXT={comment}")

        if not ebeln or not comment:
            return jsonify({'status': 'error', 'message': 'PURCHASEORDER dan COMMENT_TEXT wajib diisi'}), 400

        conn = connect_sap(username, password)

        result = conn.call('Z_PO_COMMENT_UPDATE',
            PURCHASEORDER=ebeln,
            COMMENT_TEXT=comment,
            TEXT_ID='F01',
            TEXT_LANGU='EN',
            HEADER_LEVEL='X',
            ITEM_NUMBER='00000'
        )

        print("[DEBUG] SAP Response:", result)
        return jsonify({'status': 'success', 'result': result}), 200

    except Exception as e:
        print("[ERROR] Z_PO_COMMENT_UPDATE:", str(e))
        return jsonify({'status': 'error', 'message': str(e)}), 500



@app.route('/api/z_po_release2', methods=['POST'])
def z_po_release2():
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        data = request.get_json()
        ebeln = data.get('EBELN')
        rel_code = data.get('REL_CODE')

        if not ebeln or not rel_code:
            return jsonify({'status': 'error', 'message': 'EBELN and REL_CODE are required'}), 400

        result = conn.call('Z_PO_RELEASE2', PURCHASEORDER=ebeln, PO_REL_CODE=rel_code)
        return_table = result.get('RETURN', [])
        first_return = return_table[0] if return_table else {}

        if first_return.get('TYPE') != 'E':
            conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
            return jsonify({
                'status': 'success',
                'message': first_return.get('MESSAGE', ''),
                'details': return_table
            }), 200
        else:
            return jsonify({
                'status': 'error',
                'message': first_return.get('MESSAGE', ''),
                'details': return_table
            }), 200

    except (ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError) as sap_err:
        return jsonify({
            'status': 'sap_error',
            'message': str(sap_err)
        }), 200

    except Exception as e:
        print("Exception saat Z_PO_RELEASE2:", str(e))
        return jsonify({'status': 'exception', 'error': str(e)}), 500

@app.route('/api/schedule_order', methods=['POST'])
def schedule_order():
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        data = request.get_json(silent=True) or {}
        aufnr    = data.get('AUFNR')
        date     = data.get('DATE')      # format: YYYYMMDD
        time_str = data.get('TIME')      # format: HH:MM:SS

        if not aufnr or not date or not time_str:
            return jsonify({'error': 'AUFNR, DATE, and TIME are required'}), 400

        # Konversi jam
        try:
            time_parts = [int(x) for x in time_str.split(':')]
            time_obj = time(*time_parts)  # datetime.time
        except Exception:
            return jsonify({'error': f'Format jam tidak valid: {time_str} (harus HH:MM:SS)'}), 400

        print(f"[Flask] BAPI_PRODORD_SCHEDULE AUFNR={aufnr} DATE={date} TIME={time_obj}")

        result = conn.call(
            'BAPI_PRODORD_SCHEDULE',
            SCHED_TYPE='1',
            FWD_BEG_ORIGIN='1',
            FWD_BEG_DATE=date,
            FWD_BEG_TIME=time_obj,
            WORK_PROCESS_GROUP='COWORK_BAPI',
            WORK_PROCESS_MAX=99,
            ORDERS=[{'ORDER_NUMBER': aufnr}]
        )

        # Commit
        conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')

        # Selalu kembalikan field ini supaya konsisten dengan Laravel
        return jsonify({
            'sap_return':       result.get('RETURN', []),
            'detail_return':    result.get('DETAIL_RETURN', []),
            'application_log':  result.get('APPLICATION_LOG', []),
        }), 200

    except Exception as e:
        import traceback
        traceback.print_exc()
        return jsonify({'error': str(e)}), 500
    
@app.route('/api/add_component', methods=['POST'])
def add_component():
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)
        data = request.get_json()

        # Validasi (asumsi frontend mengirim kunci IV_AUFNR, dll.)
        required_fields = ['IV_AUFNR', 'IV_MATNR', 'IV_BDMNG', 'IV_MEINS', 'IV_WERKS', 'IV_LGORT', 'IV_VORNR']
        for field in required_fields:
            if not data.get(field):
                return jsonify({'error': f'{field} is required'}), 400

        # Mapping parameter ke RFC Z_RFC_PRODORD_COMPONENT_ADD2
        params = {
            'IV_ORDER_NUMBER': data.get('IV_AUFNR'),
            'IV_MATERIAL': data.get('IV_MATNR'),
            'IV_QUANTITY': str(data.get('IV_BDMNG')),
            'IV_UOM': data.get('IV_MEINS'),
            'IV_LGORT': data.get('IV_LGORT'),
            'IV_PLANT': data.get('IV_WERKS'),
            'IV_POSITIONNO': data.get('IV_VORNR'),
            'IV_BATCH': '', # Mengirim string kosong jika tidak ada
        }

        print("Calling RFC with params:", params)
        result = conn.call('Z_RFC_PRODORD_COMPONENT_ADD2', **params)
        print("Respons LENGKAP dari SAP:", result)

        # Ambil struktur return dan pesannya dengan aman
        sap_return_structure = result.get('ES_RETURN', {})
        sap_message_type = sap_return_structure.get('TYPE')
        sap_message_text = sap_return_structure.get('MESSAGE')

        # Cek tipe pesan untuk menentukan sukses atau gagal
        if sap_message_type not in ['E', 'A']:
            # SUKSES: Jika tipe pesan BUKAN Error atau Abort
            print("Operasi SAP berhasil, melakukan COMMIT...")
            conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
            return jsonify({
                'success': True,
                'message': sap_message_text or 'Komponen berhasil ditambahkan.',
                'sap_response': result
            }), 200
        else:
            # GAGAL: Jika tipe pesan adalah Error atau Abort
            print("Operasi SAP gagal, melakukan ROLLBACK...")
            conn.call('BAPI_TRANSACTION_ROLLBACK')
            return jsonify({
                'success': False,
                'message': sap_message_text or 'Data yang dikirim tidak valid menurut SAP.',
                'sap_response': result
            }), 400

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        print("Exception saat add component:", str(e))
        return jsonify({'error': str(e)}), 500

# # ADD COMPONENT
# @app.route('/api/add_component', methods=['POST'])
# def add_component():
#     try:
#         username, password = get_credentials()
#         conn = connect_sap(username, password)

#         data = request.get_json()
#         print("Data diterima untuk add component:", data)

#         # Validasi input wajib
#         required_fields = ['IV_AUFNR', 'IV_MATNR', 'IV_BDMNG', 'IV_MEINS', 'IV_WERKS', 'IV_LGORT', 'IV_VORNR']
#         for field in required_fields:
#             if not data.get(field):
#                 return jsonify({'error': f'{field} is required'}), 400

#         # Parameter untuk RFC call
#         params = {
#             'IV_ORDER_NUMBER': data.get('IV_AUFNR'),
#             'IV_MATERIAL': data.get('IV_MATNR'),
#             'IV_QUANTITY': str(data.get('IV_BDMNG')),
#             'IV_UOM': data.get('IV_MEINS'),
#             'IV_LGORT': data.get('IV_LGORT'),
#             'IV_PLANT': data.get('IV_WERKS'),
#             'IV_POSITIONNO': data.get('IV_VORNR'),
#             'IV_BATCH': '',
#         }

#         print("Calling RFC with params:", params)
#         result = conn.call('Z_RFC_PRODORD_COMPONENT_ADD2', **params)
#         print("Respons LENGKAP dari SAP:", result)

#         # Cek hasil dari SAP untuk menentukan respons
#         if result.get('EV_SUBRC') == 0:
#             # SUKSES: Lakukan COMMIT dan kirim respons 200 OK
#             print("Operasi SAP berhasil, melakukan COMMIT...")
#             conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
            
#             return jsonify({
#                 'success': True,
#                 'message': result.get('EV_RETURN_MSG') or 'Komponen berhasil ditambahkan.',
#                 'sap_response': result
#             }), 200
#         else:
#             # GAGAL: Lakukan ROLLBACK dan kirim respons 400 Bad Request
#             print("Operasi SAP gagal, melakukan ROLLBACK...")
#             conn.call('BAPI_TRANSACTION_ROLLBACK')
            
#             return jsonify({
#                 'success': False,
#                 'message': result.get('EV_RETURN_MSG') or 'Data yang dikirim tidak valid menurut SAP.',
#                 'sap_response': result
#             }), 400

#     except ValueError as ve:
#         return jsonify({'error': str(ve)}), 401
#     except Exception as e:
#         print("Exception saat add component:", str(e))
#         return jsonify({'error': str(e)}), 500

# DELETE COMPONENT
@app.route('/api/delete_component', methods=['POST'])
def delete_component():
    try:
        data = request.get_json()
        print("Data diterima untuk delete component:", data)

        # 1. Validasi input dari frontend (menggunakan huruf kecil)
        aufnr = data.get('IV_AUFNR')
        rspos = data.get('IV_RSPOS')

        if not aufnr or not rspos:
            return jsonify({'error': 'aufnr dan rspos wajib diisi.'}), 400

        # Jika lolos validasi, hubungkan ke SAP
        username, password = get_credentials()
        conn = connect_sap(username, password)

        print(f"Calling RFC Z_RFC_PRODORD_COMPONENT_DEL with AUFNR={aufnr}, RSPOS={rspos}")

        # 2. Panggil RFC dengan parameter langsung (sesuai gambar)
        result = conn.call(
            'Z_RFC_PRODORD_COMPONENT_DEL',
            IV_AUFNR=str(aufnr),
            IV_RSPOS=str(rspos)
        )
        print("Respons LENGKAP dari SAP:", result)

        # 3. Pengecekan sukses KEMBALI menggunakan EV_SUBRC (sesuai gambar)
        if result.get('EV_SUBRC') == 0:
            print("Operasi SAP berhasil, melakukan COMMIT...")
            conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
            
            return jsonify({
                'success': True,
                'return_message': result.get('EV_RETURN_MSG') or 'Komponen berhasil dihapus.',
                'sap_response': result
            }), 200
        else:
            print("Operasi SAP gagal, melakukan ROLLBACK...")
            conn.call('BAPI_TRANSACTION_ROLLBACK')
            
            return jsonify({
                'success': False,
                'return_message': result.get('EV_RETURN_MSG') or 'Gagal menghapus komponen.',
                'sap_response': result
            }), 400

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        print("Exception saat delete component:", str(e))
        return jsonify({'error': str(e)}), 500
    
# READ PP 
@app.route('/api/read-pp', methods=['POST'])
def read_pp():
    """
    Endpoint untuk melakukan re-explode BOM pada Production Order di SAP.
    """
    try:
        # 1. Validasi Input dari Client
        data = request.get_json()
        if not data:
            return jsonify({"status": "error", "message": "Request body harus dalam format JSON."}), 400

        aufnr = data.get('IV_AUFNR')
        if not aufnr:
            return jsonify({"status": "error", "message": "Field 'IV_AUFNR' wajib diisi."}), 400

        print(f"Data diterima untuk read-pp, AUFNR: {aufnr}")

        conn = None  # Inisialisasi koneksi di luar blok try
        # 2. Manajemen Koneksi yang Aman
        username, password = get_credentials()
        conn = connect_sap(username, password)
        if not conn:
            # Jika koneksi gagal dibuat
            return jsonify({"status": "error", "message": "Gagal terhubung ke SAP."}), 500

        # Data untuk BAPI
        orderdata = {'EXPLODE_NEW': 'X'}

        # Panggil BAPI
        result = conn.call(
            'BAPI_PRODORD_CHANGE',
            NUMBER=aufnr,
            ORDERDATA=orderdata,
        )

        # 3. Penanganan Respons BAPI yang Benar (Tabel)
        return_data = result.get('RETURN')
        return_messages = [] 

        if isinstance(return_data, list):
            # Jika SAP mengirim banyak pesan, gunakan langsung
            return_messages = return_data
        elif isinstance(return_data, dict):
            # Jika SAP hanya mengirim satu pesan, bungkus dalam list
            return_messages = [return_data]

        errors = [msg for msg in return_messages if msg.get('TYPE') in ('E', 'A')]
        all_messages_str = [f"{msg.get('TYPE', ' ')}: {msg.get('MESSAGE', '')}" for msg in return_messages]

        if not errors:
            # 4. Commit Jika Sukses
            conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
            print(f"Commit sukses untuk Production Order: {aufnr}")
            return jsonify({
                "status": "success",
                "message": f"Production Order {aufnr} berhasil di-update.",
                "sap_messages": all_messages_str
            }), 200
        else:
            # 5. Rollback Jika Gagal
            conn.call('BAPI_TRANSACTION_ROLLBACK')
            error_details = [f"{e['TYPE']}: {e['MESSAGE']}" for e in errors]
            print(f"Error pada BAPI untuk PO {aufnr}: {error_details}. Melakukan rollback.")
            return jsonify({
                "status": "error",
                "message": "Gagal mengupdate Production Order di SAP.",
                "sap_errors": error_details
            }), 400 # 400 Bad Request cocok karena errornya terkait data/proses bisnis

    except (CommunicationError, LogonError, ABAPApplicationError, ABAPRuntimeError) as e:
        # 6. Penanganan Error Teknis yang Tepat
        print(f"SAP RFC Error: {str(e)}")
        # Jangan kirim detail teknis ke client, cukup log saja
        return jsonify({"status": "error", "message": "Terjadi error teknis saat berkomunikasi dengan SAP."}), 500
    
    except Exception as e:
        # Menangkap error tak terduga lainnya
        print(f"An unexpected error occurred: {str(e)}")
        if conn: # Jika error terjadi setelah koneksi dibuat, coba rollback
             conn.call('BAPI_TRANSACTION_ROLLBACK')
        return jsonify({"status": "error", "message": "Terjadi kesalahan pada server."}), 500

    finally:
        # 7. Selalu Tutup Koneksi
        if conn:
            conn.close()
            print("Koneksi SAP ditutup.")
@app.route('/api/get_wc_desc', methods=['GET'])
def get_wc_description():
    """
    API untuk memanggil RFC Z_FM_GET_WC_DESC dan mendapatkan deskripsi
    dari sebuah Work Center (ARBPL) pada Plant tertentu (WERKS).
    """
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        # Ambil parameter dari URL query string (contoh: /api/get_wc_desc?wc=WC001&pwwrk=1000)
        wc_tujuan = request.args.get('wc')
        pwwrk = request.args.get('pwwrk')

        print(f"Menerima parameter untuk get_wc_desc: wc={wc_tujuan}, pwwrk={pwwrk}")

        # Validasi input
        if not wc_tujuan:
            return jsonify({'error': 'Missing required parameter: wc'}), 400
        if not pwwrk:
            return jsonify({'error': 'Missing required parameter: pwwrk'}), 400

        print(f"Memanggil RFC Z_FM_GET_WC_DESC dengan IV_ARBPL={wc_tujuan} dan IV_WERKS={pwwrk}")
        
        # Panggil RFC dengan parameter yang sesuai
        result = conn.call(
            'Z_FM_GET_WC_DESC',
            IV_ARBPL=wc_tujuan,
            IV_WERKS=pwwrk
        )

        print("Hasil dari RFC:", result)
        return jsonify(result)

    except ValueError as ve:
        # Error dari get_credentials()
        return jsonify({'error': str(ve)}), 401
    except (CommunicationError, ABAPApplicationError) as e:
        # Error spesifik dari SAP
        print(f"SAP Error: {e}")
        return jsonify({'error': f"SAP Error: {e}"}), 500
    except Exception as e:
        # Error umum lainnya
        print("Exception:", str(e))
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    os.environ['PYTHONHASHSEED'] = '0'
    app.run(host='127.0.0.1', port=8050, debug=True)