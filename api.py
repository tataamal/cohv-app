# main.py
from flask import Flask, request, jsonify
from pyrfc import Connection, ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError, RFCError, RFCLibError
from concurrent.futures import ThreadPoolExecutor
import threading
import os
import pymysql
from flask_cors import CORS
from datetime import time
from decimal import Decimal
from datetime import datetime
import traceback

app = Flask(__name__)
CORS(app, supports_credentials=True, resources={r"/api/*": {"origins": "*"}})

# --- LOGGING CONFIGURATION ---
import logging
from logging.handlers import RotatingFileHandler

# Configure logger
logger = logging.getLogger('api_logger')
logger.setLevel(logging.INFO)

# Create file handler
file_handler = RotatingFileHandler('api.log', maxBytes=10*1024*1024, backupCount=5)
file_handler.setLevel(logging.INFO)

# Create formatter
formatter = logging.Formatter('[%(asctime)s] %(levelname)s in %(module)s: %(message)s')
file_handler.setFormatter(formatter)

# Add handler to logger
if not logger.handlers:
    logger.addHandler(file_handler)

# Also log to console for development visibility (optional, but good for 'docker logs')
console_handler = logging.StreamHandler()
console_handler.setFormatter(formatter)
if not logger.handlers: 
   # Prevent duplicate logs if reloaded
   logger.addHandler(console_handler)


def as_list(x):
    if not x:
        return []
    return x if isinstance(x, list) else [x]

def map_werks(lst, fallback_plant):
    out = []
    for row in as_list(lst):
        if isinstance(row, dict):
            row = dict(row)
            row['WERKS'] = row.get('WERKS') or row.get('WERK') or fallback_plant
            row.pop('WERK', None)
        out.append(row)
    return out

def pad12(v: str) -> str:
    v = str(v or '')
    return v if len(v) >= 12 else v.zfill(12)

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
        host="192.168.90.105",     
        user="python_client",   
        password="singgampang", 
        database="cohv_app",    
        port=3306,
        
    )
	
def get_credentials():
    """
    Mengambil kredensial SAP dari header request.
    """
    username = request.headers.get('X-SAP-Username')
    password = request.headers.get('X-SAP-Password')
    
    if not username or not password:
        raise ValueError("SAP credentials not found in headers.")
    
    return username, password

def get_data(plant_code=None, workcenters_csv=None, username=None, password=None):
    conn = connect_sap(username, password)
    all_data = []

    if not plant_code or not workcenters_csv:
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

def fetch_data_for_plant(args):
    """
    Worker function untuk mengambil data SAP (DIJALANKAN DI THREAD).
    Menerima satu tuple 'args' yang berisi (plant, user, pass).
    """
    plant, sap_user, sap_pass = args
    
    logger.info(f"PROCESS: Mulai mengambil data untuk plant: {plant}...")
    conn = None # Inisialisasi conn
    try:
        conn = connect_sap(username=sap_user, password=sap_pass)
        
        # Panggil RFC yang benar
        result = conn.call('Z_FM_YPPR018', P_WERKS=plant)
        
        # Ambil data mentah
        data = result.get('T_DATA1', [])
        logger.info(f"SUCCESS: Selesai mengambil data untuk plant: {plant}, ditemukan {len(data)} baris.")
        return data
        
    except (ABAPApplicationError, CommunicationError) as e:
        logger.warning(f"WARNING: Error SAP saat mengambil data untuk plant {plant}: {e}")
        raise Exception(f"Error SAP di Plant {plant}: {e}")
    finally:
        if conn:
            conn.close()
            logger.info(f"INFO: Koneksi SAP untuk plant {plant} ditutup.")

@app.route('/api/sap-login', methods=['POST'])
def sap_login():
    data = request.json

    try:
        conn = connect_sap(data['username'], data['password'])
        conn.ping()
        logger.info("[DEBUG] Login sukses!")
        return jsonify({'status': 'connected'}), 200
    except Exception as e:
        logger.error(f"[ERROR] SAP Login failed: {str(e)}")
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
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
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

@app.route('/api/save_edit', methods=['POST'])
def changewc():
    conn = None
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        data = request.get_json()
        logger.info(f"Change WC Data received for AUFNR: {data.get('IV_AUFNR', 'Unknown')}")

        if not data:
            return jsonify({'error': 'No JSON payload received'}), 400

        aufnr = data.get('IV_AUFNR')
        commit = data.get('IV_COMMIT', 'X')
        it_operation = data.get('IT_OPERATION', [])

        if not aufnr or not it_operation:
            logger.warning("IT Operation or AUFNR missing in payload")
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

        logger.info(f"Calling RFC CO_SE_PRODORD_CHANGE for AUFNR: {aufnr}")
        result = conn.call(
            'CO_SE_PRODORD_CHANGE',
            IV_ORDER_NUMBER=aufnr,
            IV_COMMIT=commit,
            IT_OPERATION=it_operation_filtered
        )
        
        return jsonify(result), 200

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        logger.error(f"Exception in changewc: {str(e)}")
        return jsonify({'error': str(e)}), 500
    finally:
        if conn:
            logger.info("Closing SAP connection for changewc...")
            conn.close()
            logger.info("SAP connection closed.")

@app.route('/api/sap_combined', methods=['GET'])
def sap_combined():
    plant = request.args.get('plant')
    aufnr = request.args.get('aufnr')  # ✅ tambah

    if not plant:
        return jsonify({'error': 'Missing plant parameter'}), 400

    SAP_TIMEOUT_SEC = 3600
    conn = None

    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        logger.info(
            f"Calling RFC Z_FM_YPPR074Z for plant={plant}, aufnr={aufnr} (timeout={SAP_TIMEOUT_SEC}s)"
        )

        result = conn.call(
            'Z_FM_YPPR074Z',
            options={'timeout': SAP_TIMEOUT_SEC},
            P_WERKS=plant,
            P_AUFNR=aufnr if aufnr else ''
        )

        # Ambil tabel
        t_data  = result.get('T_DATA',  []) or []
        t1      = result.get('T_DATA1', []) or []
        t2      = result.get('T_DATA2', []) or []
        t3      = result.get('T_DATA3', []) or []
        t4      = result.get('T_DATA4', []) or []

        # ✅ Kalau aufnr dikirim, filter ke order tsb saja
        if aufnr:
            aufnr = aufnr.strip()

            t1 = [r for r in t1 if str(r.get('AUFNR', '')).strip() == aufnr]
            t3 = [r for r in t3 if str(r.get('AUFNR', '')).strip() == aufnr]
            t4 = [r for r in t4 if str(r.get('AUFNR', '')).strip() == aufnr]

            # T_DATA2 biasanya link ke T_DATA3 via (KDAUF,KDPOS)
            # Jadi filter T2 berdasarkan pasangan KDAUF/KDPOS yang ada di T3 untuk AUFNR ini
            kd_pairs = set(
                (str(r.get('KDAUF', '')).strip(), str(r.get('KDPOS', '')).strip())
                for r in t3
            )
            t2 = [
                r for r in t2
                if (str(r.get('KDAUF', '')).strip(), str(r.get('KDPOS', '')).strip()) in kd_pairs
            ]

            # T_DATA (parent) link ke T2 via KUNNR+NAME1 (dari logic Laravel kamu)
            parent_pairs = set(
                (str(r.get('KUNNR', '')).strip(), str(r.get('NAME1', '')).strip())
                for r in t2
            )
            t_data = [
                r for r in t_data
                if (str(r.get('KUNNR', '')).strip(), str(r.get('NAME1', '')).strip()) in parent_pairs
            ]

        return jsonify({
            "plant": plant,
            "aufnr": aufnr,
            "T_DATA": t_data,
            "T_DATA1": t1,
            "T_DATA2": t2,
            "T_DATA3": t3,
            "T_DATA4": t4,
        }), 200

    except RFCError as re:
        key = getattr(re, "key", "")
        msg = getattr(re, "message", str(re))
        if key == "RFC_CANCELED":
            return jsonify({
                'error': 'SAP call timed out',
                'detail': f'Connection canceled by timeout after {SAP_TIMEOUT_SEC} seconds'
            }), 504
        return jsonify({'error': 'SAP RFC error', 'detail': msg}), 502

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401

    except Exception as e:
        logger.error(f"Exception in sap_combined: {str(e)}")
        return jsonify({'error': str(e)}), 500

    finally:
        if conn:
            logger.info("Closing SAP connection for sap_combined...")
            conn.close()
            logger.info("SAP connection closed.")

@app.route('/api/refresh-pro', methods=['GET'])
def refresh_single_pro():
    conn = None
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

        logger.info(f"Calling RFC Z_FM_YPPR074Z for plant={plant}, aufnr={a12}")
        res = conn.call('Z_FM_YPPR074Z', P_WERKS=plant, P_AUFNR=a12)

        # --- helpers ---
        def as_list(x):
            if not x:
                return []
            return x if isinstance(x, list) else [x]

        def map_werks(lst, fallback_plant):
            out = []
            for row in as_list(lst):
                if isinstance(row, dict):
                    row = dict(row)
                    row['WERKS'] = row.get('WERKS') or row.get('WERK') or fallback_plant
                    row.pop('WERK', None)
                out.append(row)
            return out

        t_data  = map_werks(res.get('T_DATA',  []), plant)
        t_data1 = map_werks(res.get('T_DATA1', []), plant)
        t_data2 = map_werks(res.get('T_DATA2', []), plant)
        t_data3 = map_werks(res.get('T_DATA3', []), plant)
        t_data4 = map_werks(res.get('T_DATA4', []), plant)

        return jsonify({
            "plant":   plant,
            "AUFNR":   a12,
            "T_DATA":  t_data,
            "T_DATA1": t_data1,
            "T_DATA2": t_data2,
            "T_DATA3": t_data3,
            "T_DATA4": t_data4,
        }), 200

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        logger.error(f"[refresh-pro] exception: {repr(e)}")
        return jsonify({'error': str(e)}), 500
    finally:
        if conn:
            logger.info("Closing SAP connection for refresh-pro...")
            conn.close()
            logger.info("SAP connection closed.")
# TECO
@app.route('/api/teco_order', methods=['POST'])
def teco_order():
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        data = request.get_json()
        logger.info(f"TECO Request received for AUFNR: {data.get('AUFNR', 'Unknown')}")

        aufnr = data.get('AUFNR')
        if not aufnr:
            return jsonify({'error': 'AUFNR is required'}), 400

        logger.info(f"Calling BAPI_PRODORD_COMPLETE_TECH for order {aufnr}...")
        result_teco = conn.call(
            'BAPI_PRODORD_COMPLETE_TECH',
            SCOPE_COMPL_TECH='1',
            WORK_PROCESS_GROUP='COWORK_BAPI',
            WORK_PROCESS_MAX=99,
            ORDERS=[{'ORDER_NUMBER': aufnr}]
        )
        
        if 'DETAIL_RETURN' in result_teco and result_teco['DETAIL_RETURN']:
            for message in result_teco['DETAIL_RETURN']:
                if message['TYPE'] in ['E', 'A']:
                    error_msg = f"SAP Error: {message['MESSAGE']}"
                    logger.warning(error_msg)
                    return jsonify({'error': error_msg, 'sap_response': result_teco}), 400

        logger.info("Calling BAPI_TRANSACTION_COMMIT...")
        result_commit = conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')

        return jsonify({
            'BAPI_PRODORD_COMPLETE_TECH': result_teco,
            'BAPI_TRANSACTION_COMMIT': result_commit
        }), 200

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        logger.error(f"Exception during TECO: {str(e)}")
        # Standard error return
        return jsonify({'error': f'Internal server error: {str(e)}'}), 500
    finally:
        if 'conn' in locals() and conn:
            conn.close()

# CHANGE PV
@app.route('/api/change_prod_version', methods=['POST'])
def change_prod_version():
    import time
    conn = None
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        data = request.get_json()
        aufnr = data.get('AUFNR')
        verid = data.get('PROD_VERSION')

        if not aufnr or not verid:
            return jsonify({'error': 'AUFNR and PROD_VERSION are required'}), 400

        logger.info(f"AUFNR: {aufnr} -> target PROD_VERSION: {verid}")

        result_change = conn.call(
            'BAPI_PRODORD_CHANGE',
            NUMBER=aufnr,
            ORDERDATA={'PROD_VERSION': verid},
            ORDERDATAX={'PROD_VERSION': 'X'}
        )

        sap_return = result_change.get('RETURN', [])
        sap_return_list = sap_return if isinstance(sap_return, list) else [sap_return]
        has_error = any(msg.get('TYPE') in ['E', 'A'] for msg in sap_return_list)

        if has_error:
            error_messages = [f"[{msg.get('TYPE')}] {msg.get('MESSAGE')}" for msg in sap_return_list]
            error_string = "\n".join(error_messages)
            logger.error(f"BAPI Error: {error_string}")
            return jsonify({'error': f'SAP BAPI Error:\n{error_string}'}), 500
            
        logger.info("BAPI_TRANSACTION_COMMIT...")
        conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
        time.sleep(10)

        return jsonify({
            'sap_return': sap_return_list
        })

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        logger.error(f"Exception: {str(e)}")
        return jsonify({'error': str(e)}), 500
    finally:
        if conn:
            logger.info("Closing SAP connection for change_prod_version...")
            conn.close()
            logger.info("SAP connection closed.")

@app.route('/api/schedule_order', methods=['POST'])
def schedule_order():
    conn = None
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        data = request.get_json(silent=True) or {}
        aufnr    = data.get('AUFNR')
        date     = data.get('DATE')      # format: YYYYMMDD
        time_str = data.get('TIME')      # format: HH:MM:SS

        if not aufnr or not date or not time_str:
            return jsonify({'error': 'AUFNR, DATE, and TIME are required'}), 400

        try:
            from datetime import time 
            time_parts = [int(x) for x in time_str.split(':')]
            time_obj = time(*time_parts)  # datetime.time
        except Exception:
            return jsonify({'error': f'Format jam tidak valid: {time_str} (harus HH:MM:SS)'}), 400

        logger.info(f"[Flask] BAPI_PRODORD_SCHEDULE AUFNR={aufnr} DATE={date} TIME={time_obj}")
        logger.info(f"BAPI_PRODORD_SCHEDULE AUFNR={aufnr} DATE={date} TIME={time_obj}")

        result = conn.call(
            'BAPI_PRODORD_SCHEDULE',
            SCHED_TYPE='5',
            FWD_BEG_ORIGIN='1',
            FWD_BEG_DATE=date,
            FWD_BEG_TIME=time_obj,
            WORK_PROCESS_GROUP='COWORK_BAPI',
            WORK_PROCESS_MAX=99,
            ORDERS=[{'ORDER_NUMBER': aufnr}]
        )

        logger.info("BAPI_TRANSACTION_COMMIT...")
        conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')

        return jsonify({
            'sap_return':      result.get('RETURN', []),
            'detail_return':   result.get('DETAIL_RETURN', []),
            'application_log': result.get('APPLICATION_LOG', []),
        }), 200

    except Exception as e:
        logger.error(f"Error in schedule_order: {str(e)}")
        # import traceback; traceback.print_exc() # Removed trace print
        return jsonify({'error': str(e)}), 500
    finally:
        if conn:
            logger.info("Closing SAP connection for schedule_order...")
            conn.close()
            logger.info("SAP connection closed.")
    
@app.route('/api/add_component', methods=['POST'])
def add_component():
    conn = None
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)
        data = request.get_json() or {}

        required_fields = ['IV_AUFNR', 'IV_MATNR', 'IV_BDMNG', 'IV_MEINS', 'IV_WERKS', 'IV_LGORT', 'IV_VORNR']
        missing = [f for f in required_fields if not data.get(f)]
        if missing:
            return jsonify({'success': False, 'error': f'{missing[0]} is required'}), 400

        params = {
            'IV_ORDER_NUMBER': data.get('IV_AUFNR'),
            'IV_MATERIAL': data.get('IV_MATNR'),
            'IV_QUANTITY': str(data.get('IV_BDMNG')),
            'IV_UOM': data.get('IV_MEINS'),
            'IV_LGORT': data.get('IV_LGORT'),
            'IV_PLANT': data.get('IV_WERKS'),
            'IV_POSITIONNO': data.get('IV_VORNR'),
            'IV_BATCH': '',
        }

        logger.info(f"Calling RFC for add component on AUFNR: {params['IV_ORDER_NUMBER']}")
        result = conn.call('Z_RFC_PRODORD_COMPONENT_ADD2', **params)

        sap_return = (result or {}).get('ES_RETURN') or {}
        sap_type = sap_return.get('TYPE')
        sap_msg = sap_return.get('MESSAGE')
        is_error = sap_type in ('E', 'A')

        payload = {
            'success': not is_error,
            'message': sap_msg or ('Komponen berhasil ditambahkan.' if not is_error else 'Data yang dikirim tidak valid menurut SAP.'),
            'sap_response': result,
            'sap_type': sap_type,
        }
        return jsonify(payload), (400 if is_error else 200)

    except ValueError as ve:
        return jsonify({'success': False, 'error': str(ve)}), 401
    except Exception as e:
        logger.error(f"Exception in add_component: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500
    finally:
        if conn:
            logger.info("Closing SAP connection for add_component...")
            conn.close()
            logger.info("SAP connection closed.")
    
# DELETE COMPONENT
@app.route('/api/delete_component', methods=['POST'])
def delete_component():
    try:
        data = request.get_json()
        logger.info(f"Delete component request for AUFNR: {data.get('IV_AUFNR', 'Unknown')}, RSPOS: {data.get('IV_RSPOS', 'Unknown')}")

        aufnr = data.get('IV_AUFNR')
        rspos = data.get('IV_RSPOS')

        if not aufnr or not rspos:
            return jsonify({'error': 'aufnr dan rspos wajib diisi.'}), 400

        username, password = get_credentials()
        conn = connect_sap(username, password)

        logger.info(f"Calling RFC Z_RFC_PRODORD_COMPONENT_DEL with AUFNR={aufnr}, RSPOS={rspos}")

        result = conn.call(
            'Z_RFC_PRODORD_COMPONENT_DEL',
            IV_AUFNR=str(aufnr),
            IV_RSPOS=str(rspos)
        )

        if result.get('EV_SUBRC') == 0:
            logger.info("Operation successful, committing...")
            conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
            
            return jsonify({
                'success': True,
                'return_message': result.get('EV_RETURN_MSG') or 'Komponen berhasil dihapus.',
                'sap_response': result
            }), 200
        else:
            logger.warning("Operation failed, rolling back...")
            conn.call('BAPI_TRANSACTION_ROLLBACK')
            
            return jsonify({
                'success': False,
                'return_message': result.get('EV_RETURN_MSG') or 'Gagal menghapus komponen.',
                'sap_response': result
            }), 400

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        logger.error(f"Exception in delete component: {str(e)}")
        return jsonify({'error': str(e)}), 500
    finally:
        if conn:
            logger.info("Closing SAP connection for delete_component...")
            conn.close()
            logger.info("SAP connection closed.")
    
# READ PP 
@app.route('/api/read-pp', methods=['POST'])
def read_pp():
    """
    Endpoint untuk melakukan re-explode BOM pada Production Order di SAP.
    """
    try:
        data = request.get_json()
        if not data:
            return jsonify({"status": "error", "message": "Request body harus dalam format JSON."}), 400

        aufnr = data.get('IV_AUFNR')
        if not aufnr:
            return jsonify({"status": "error", "message": "Field 'IV_AUFNR' wajib diisi."}), 400

        aufnr = data.get('IV_AUFNR')
        if not aufnr:
            return jsonify({"status": "error", "message": "Field 'IV_AUFNR' wajib diisi."}), 400

        logger.info(f"Read PP request for AUFNR: {aufnr}")

        conn = None  # Inisialisasi koneksi di luar blok try
        username, password = get_credentials()
        conn = connect_sap(username, password)
        if not conn:
            return jsonify({"status": "error", "message": "Gagal terhubung ke SAP."}), 500

        orderdata = {'EXPLODE_NEW': 'X'}

        result = conn.call(
            'BAPI_PRODORD_CHANGE',
            NUMBER=aufnr,
            ORDERDATA=orderdata,
        )

        return_data = result.get('RETURN')
        return_messages = [] 

        if isinstance(return_data, list):
            return_messages = return_data
        elif isinstance(return_data, dict):
            return_messages = [return_data]

        errors = [msg for msg in return_messages if msg.get('TYPE') in ('E', 'A')]
        all_messages_str = [f"{msg.get('TYPE', ' ')}: {msg.get('MESSAGE', '')}" for msg in return_messages]

        if not errors:
            conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
            logger.info(f"Commit success for Read PP AUFNR: {aufnr}")
            return jsonify({
                "status": "success",
                "message": f"Production Order {aufnr} berhasil di-update.",
                "sap_messages": all_messages_str
            }), 200
        else:
            conn.call('BAPI_TRANSACTION_ROLLBACK')
            error_details = [f"{e['TYPE']}: {e['MESSAGE']}" for e in errors]
            logger.warning(f"Error in BAPI for PO {aufnr}: {error_details}. Rolling back.")
            return jsonify({
                "status": "error",
                "message": "Gagal mengupdate Production Order di SAP.",
                "sap_errors": error_details
            }), 400

    except (CommunicationError, LogonError, ABAPApplicationError, ABAPRuntimeError) as e:
        logger.error(f"SAP RFC Error: {str(e)}")
        return jsonify({"status": "error", "message": "Terjadi error teknis saat berkomunikasi dengan SAP."}), 500
    
    except Exception as e:
        logger.error(f"An unexpected error occurred in read-pp: {str(e)}")
        if conn: # Jika error terjadi setelah koneksi dibuat, coba rollback
             conn.call('BAPI_TRANSACTION_ROLLBACK')
        return jsonify({"status": "error", "message": "Terjadi kesalahan pada server."}), 500

    finally:
        # 7. Selalu Tutup Koneksi
        if conn:
            conn.close()
            logger.info("SAP connection closed.")
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

        wc_tujuan = request.args.get('wc')
        pwwrk = request.args.get('pwwrk')

        logger.info(f"get_wc_desc params: wc={wc_tujuan}, pwwrk={pwwrk}")

        # Validasi input
        if not wc_tujuan:
            return jsonify({'error': 'Missing required parameter: wc'}), 400
        if not pwwrk:
            return jsonify({'error': 'Missing required parameter: pwwrk'}), 400

        logger.info(f"Calling RFC Z_FM_GET_WC_DESC with IV_ARBPL={wc_tujuan} dan IV_WERKS={pwwrk}")
        
        # Panggil RFC dengan parameter yang sesuai
        result = conn.call(
            'Z_FM_GET_WC_DESC',
            IV_ARBPL=wc_tujuan,
            IV_WERKS=pwwrk
        )

        return jsonify(result), 200

    except ValueError as ve:
        # Error dari get_credentials()
        return jsonify({'error': str(ve)}), 401
    except (CommunicationError, ABAPApplicationError) as e:
        # Error spesifik dari SAP
        logger.error(f"SAP Error: {e}")
        return jsonify({'error': f"SAP Error: {e}"}), 500
    except Exception as e:
        # Error umum lainnya
        logger.error(f"Exception: {str(e)}")
        return jsonify({'error': str(e)}), 500
    finally:
        # 2. Blok ini akan selalu dieksekusi, memastikan koneksi ditutup
        if conn:
            logger.info("Closing SAP connection for get_wc_desc...")
            conn.close()
            logger.info("SAP connection closed.")
    
def json_decimal_converter(o):
    if isinstance(o, Decimal):
        return str(o)
    raise TypeError(f"Object of type {o.__class__.__name__} is not JSON serializable")

@app.route('/api/bulk-refresh-pro', methods=['POST'])
def refresh_bulk_pro():
    """
    Endpoint untuk me-refresh beberapa Production Order (PRO/AUFNR)
    dengan memprosesnya satu per satu.
    """
    # 1. Inisialisasi koneksi sebagai None di luar blok try
    conn = None
    try:
        # 2. Semua logika, dari koneksi hingga return, sekarang ada di dalam blok try
        
        # 2a. Autentikasi dan Koneksi ke SAP
        username, password = get_credentials()
        conn = connect_sap(username, password)

        # 2b. Validasi Input JSON
        data = request.get_json()
        if not data:
            return jsonify({'error': 'Request body harus dalam format JSON yang valid.'}), 400

        plant = data.get('plant', '').strip()
        aufnr_list = data.get('pros', [])

        if not plant:
            return jsonify({'error': 'Parameter "plant" tidak ditemukan di body JSON'}), 400
        if not aufnr_list or not isinstance(aufnr_list, list):
            return jsonify({'error': 'Parameter "pros" harus berupa array/list yang tidak kosong.'}), 400

        logger.info(f"--- Starting Bulk Refresh for Plant: {plant}, {len(aufnr_list)} PROs ---")
        
        all_responses = []
        any_failures = False

        # 2c. Loop untuk setiap PRO dan panggil RFC
        for aufnr in aufnr_list:
            if not aufnr:
                continue
            
            padded_aufnr = str(aufnr).zfill(12)
            logger.info(f"  -> Processing PRO: {aufnr} ({padded_aufnr})")

            try:
                res = conn.call('Z_FM_YPPR074Z', P_WERKS=plant, P_AUFNR=padded_aufnr)
                # logger.debug(f"     [DEBUG] Raw SAP Response: {res}") # Disabled dump

                sap_return_list = res.get('RETURN', [])
                sap_return = sap_return_list[0] if sap_return_list else {}

                if sap_return.get('TYPE') in ['S', 'W', '']:
                    response_sukses = {
                        "status": "sukses",
                        "aufnr": aufnr,
                        "message": f"Berhasil mendapatkan data untuk PRO: {aufnr}",
                        "details": {
                            "T_DATA": res.get('T_DATA', []),
                            "T_DATA1": res.get('T_DATA1', []),
                            "T_DATA2": res.get('T_DATA2', []),
                            "T_DATA3": res.get('T_DATA3', []),
                            "T_DATA4": res.get('T_DATA4', [])
                        }
                    }
                    all_responses.append(response_sukses)
                    # logger.info(f"     ... SUCCESS")
                else:
                    any_failures = True
                    error_message = sap_return.get('MESSAGE', 'Error tidak diketahui dari SAP')
                    response_gagal = {
                        "status": "gagal",
                        "aufnr": aufnr,
                        "message": f"Gagal mengambil data dari SAP, Error: {error_message}"
                    }
                    all_responses.append(response_gagal)
                    logger.warning(f"     ... FAILED: {error_message}")

            except Exception as rfc_error:
                any_failures = True
                response_gagal_sistem = {
                    "status": "gagal",
                    "aufnr": aufnr,
                    "message": f"Gagal mengambil data dari SAP, Error: {str(rfc_error)}"
                }
                all_responses.append(response_gagal_sistem)
                logger.error(f"     ... FAILED (System): {str(rfc_error)}")
                continue
        
        logger.info("--- Process Finished ---")

        final_status_code = 207 if any_failures else 200
        
        return jsonify({
            "plant": plant,
            "results": all_responses
        }), final_status_code

    except ValueError as ve:
        return jsonify({"error": str(ve)}), 401
    except Exception as e:
        logger.error(f"System Error in bulk refresh: {str(e)}")
        return jsonify({"error": f"Terjadi kesalahan sistem: {str(e)}"}), 500
    finally:
        if conn:
            logger.info("--- Closing SAP Connection ---")
            conn.close()
            logger.info("--- SAP Connection Closed ---")

@app.route('/api/bulk-teco-pro', methods=['POST'])
def process_teco():
    conn = None
    try:
        username, password = get_credentials()

        if not username or not password:
            return jsonify({"error": "Username atau Password SAP tidak ada di header."}), 401

        # Ambil data dari body JSON
        data = request.get_json()
        if not data or 'pro_list' not in data or not isinstance(data['pro_list'], list):
            return jsonify({"error": "Input tidak valid. Body harus berisi 'pro_list' dalam bentuk array."}), 400

        pro_list = data['pro_list']
        if not pro_list:
            return jsonify({"error": "'pro_list' tidak boleh kosong."}), 400

        results = {
            "success_details": [],
            "error_details": []
        }

        conn = connect_sap(username, password)
        logger.info(f"--- Starting Bulk TECO for {len(pro_list)} PROs ---")

        for pro_number in pro_list:
            logger.info(f"Processing TECO for PRO: {pro_number}")

            try:
                bapi_result = conn.call(
                    'BAPI_PRODORD_COMPLETE_TECH',
                    SCOPE_COMPL_TECH='1',
                    WORK_PROCESS_GROUP='COWORK_BAPI',
                    WORK_PROCESS_MAX=99,
                    ORDERS=[{'ORDER_NUMBER': pro_number}]
                )
                
                has_error = False
                return_messages = []

                if isinstance(bapi_result, dict):
                    return_messages = bapi_result.get('RETURN', [])
                
                for msg in return_messages:
                    if isinstance(msg, dict) and msg.get('TYPE') in ['E', 'A']:
                        has_error = True
                        break
                
                # Check DETAIL_RETURN too if available (for consistency with single teco)
                if 'DETAIL_RETURN' in bapi_result:
                    for msg in bapi_result['DETAIL_RETURN']:
                        if msg.get('TYPE') in ['E', 'A']:
                            has_error = True
                            break

                if not has_error:
                    results["success_details"].append({
                        "pro_number": pro_number,
                        "sap_response": bapi_result
                    })
                else:
                    results["error_details"].append({
                        "pro_number": pro_number,
                        "message": f"Gagal melakukan teco pada PRO {pro_number}",
                        "sap_response": bapi_result
                    })
                    logger.warning(f"Failed TECO for {pro_number}")

            except ABAPApplicationError as bapi_err:
                logger.error(f"Error BAPI pada PRO {pro_number}: {bapi_err}")
                results["error_details"].append({
                    "pro_number": pro_number,
                    "message": f"Gagal melakukan teco pada PRO {pro_number}",
                    "sap_response": str(bapi_err)
                })

        # Setelah loop selesai, tentukan status akhir
        logger.info("--- Bulk TECO Finished ---")
        if results["error_details"]:
            return jsonify(results), 400 # Menggunakan status 400 untuk menandakan ada kegagalan
        else:
            return jsonify(results), 200

    except (CommunicationError, ABAPRuntimeError) as conn_err:
        logger.error(f"Connection/Runtime Error: {conn_err}")
        return jsonify({"error": f"Gagal terhubung atau terjadi error runtime di SAP: {conn_err}"}), 503
    except Exception as e:
        logger.error(f"An unexpected error occurred: {e}")
        return jsonify({"error": "Terjadi error tak terduga di server Flask."}), 500
    finally:
        # 2. Blok ini akan selalu dieksekusi, memastikan koneksi ditutup
        if conn:
            logger.info("Closing SAP connection for bulk-teco-pro...")
            conn.close()
            logger.info("SAP connection closed.")
    
@app.route('/api/bulk-readpp-pro', methods=['POST'])
def process_read_pp():

    # 1. Inisialisasi koneksi sebagai None di luar blok try
    conn = None
    try:
        username, password = get_credentials()

        if not username or not password:
            return jsonify({"error": "Username atau Password SAP tidak ada di header."}), 401

        data = request.get_json()
        if not data or 'pro_list' not in data or not isinstance(data['pro_list'], list):
            return jsonify({"error": "Input tidak valid. Body harus berisi 'pro_list' dalam bentuk array."}), 400

        pro_list = data['pro_list']
        if not pro_list:
            return jsonify({"error": "'pro_list' tidak boleh kosong."}), 400

        results = {
            "success_details": [],
            "error_details": []
        }
        
        order_data_input = {'EXPLODE_NEW': 'X'}

        conn = connect_sap(username, password)
        logger.info(f"--- Starting Bulk Read PP for {len(pro_list)} PROs ---")

        for pro_number in pro_list:
            logger.info(f"Processing Read PP for PRO {pro_number}...")

            try:
                bapi_result = conn.call(
                    'BAPI_PRODORD_CHANGE',
                    NUMBER=pro_number,
                    ORDERDATA=order_data_input
                )
                
                has_error = False
                return_messages = []
                if isinstance(bapi_result, dict):
                    return_messages = bapi_result.get('RETURN', [])
                
                for msg in return_messages:
                    if isinstance(msg, dict) and msg.get('TYPE') in ['E', 'A']:
                        has_error = True
                        break

                if not has_error:
                    # In single read-pp we commit, here we should probably commit per item or bulk.
                    # As original code didn't explicit commit inside loop but BAPI might auto-handle or require explicit.
                    # Assuming single call per item implies commit needed? Original bulk code didn't commit per item?
                    # The original bulk code seems to lack COMMIT. 
                    # If Read PP uses BAPI_PRODORD_CHANGE, it needs commit.
                    # I will add commit for success cases to match single endpoint logic.
                    conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
                    results["success_details"].append({
                        "pro_number": pro_number,
                        "sap_response": bapi_result
                    })
                else:
                    # Rollback for safety on this item
                    conn.call('BAPI_TRANSACTION_ROLLBACK')
                    results["error_details"].append({
                        "pro_number": pro_number,
                        "message": f"Gagal Read PP pada PRO {pro_number}",
                        "sap_response": bapi_result
                    })
                    logger.warning(f"Failed Read PP for {pro_number}")

            except ABAPApplicationError as bapi_err:
                logger.error(f"BAPI Error on PRO {pro_number}: {bapi_err}")
                results["error_details"].append({
                    "pro_number": pro_number,
                    "message": f"Gagal Read PP pada PRO {pro_number}",
                    "sap_response": str(bapi_err)
                })

        logger.info("--- Bulk Read PP Finished ---")
        if results["error_details"]:
            return jsonify(results), 400
        else:
            return jsonify(results), 200

    except (CommunicationError, ABAPRuntimeError) as conn_err:
        logger.error(f"Connection/Runtime Error: {conn_err}")
        return jsonify({"error": f"Gagal terhubung atau terjadi error runtime di SAP: {conn_err}"}), 503
    except Exception as e:
        logger.error(f"An unexpected error occurred: {e}")
        return jsonify({"error": f"Terjadi error tak terduga di server Flask: {str(e)}"}), 500
    finally:
        if conn:
            logger.info("Closing SAP connection for bulk-readpp-pro...")
            conn.close()
            logger.info("SAP connection closed.")

@app.route('/api/bulk-schedule-pro', methods=['POST'])
def process_schedule():
    conn = None
    try:
        username, password = get_credentials()

        if not username or not password:
            return jsonify({"error": "Username atau Password SAP tidak ada di header."}), 401

        data = request.get_json()
        required_keys = ['pro_list', 'schedule_date', 'schedule_time']
        if not data or not all(key in data for key in required_keys):
            return jsonify({"error": "Input tidak valid. Body harus berisi 'pro_list', 'schedule_date', dan 'schedule_time'."}), 400

        pro_list = data['pro_list']
        schedule_date = data['schedule_date']
        schedule_time = data['schedule_time']

        results = {
            "success_details": [],
            "error_details": []
        }

        sap_date = datetime.strptime(schedule_date, '%Y-%m-%d').strftime('%Y%m%d')
        sap_time = schedule_time.replace('.', '').replace(':', '')

        conn = connect_sap(username, password)
        logger.info(f"--- Starting Bulk Schedule for {len(pro_list)} PROs ---")

        for pro_number in pro_list:
            logger.info(f"Scheduling PRO {pro_number} for {sap_date} at {sap_time}...")

            try:
                bapi_result = conn.call(
                    'BAPI_PRODORD_SCHEDULE',
                    SCHED_TYPE='5',
                    FWD_BEG_ORIGIN='1',
                    FWD_BEG_DATE=sap_date,
                    FWD_BEG_TIME=sap_time,
                    WORK_PROCESS_GROUP='COWORK_BAPI',
                    WORK_PROCESS_MAX=99,
                    ORDERS=[{'ORDER_NUMBER': pro_number}]
                )
                
                has_error = False
                return_messages = []
                if isinstance(bapi_result, dict):
                    return_messages = bapi_result.get('RETURN', [])
                
                for msg in return_messages:
                    if isinstance(msg, dict) and msg.get('TYPE') in ['E', 'A']:
                        has_error = True
                        break

                if not has_error:
                    # Assuming we need commit here too? The single endpoint does commit.
                    conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
                    results["success_details"].append({ "pro_number": pro_number, "sap_response": bapi_result })
                else:
                    conn.call('BAPI_TRANSACTION_ROLLBACK')
                    results["error_details"].append({ "pro_number": pro_number, "message": f"Gagal schedule pada PRO {pro_number}", "sap_response": bapi_result })
                    logger.warning(f"Failed Schedule for {pro_number}")

            except ABAPApplicationError as bapi_err:
                logger.error(f"BAPI Error on PRO {pro_number}: {bapi_err}")
                results["error_details"].append({ "pro_number": pro_number, "message": f"Gagal schedule pada PRO {pro_number}", "sap_response": str(bapi_err) })

        logger.info("--- Bulk Schedule Finished ---")
        # Setelah loop selesai, tentukan respons akhir
        if results["error_details"]:
            return jsonify(results), 400
        else:
            return jsonify(results), 200

    except (CommunicationError, ABAPApplicationError) as sap_err:
        logger.error(f"SAP RFC Error: {sap_err}")
        return jsonify({"error": f"Gagal terhubung atau mengeksekusi BAPI di SAP: {sap_err}"}), 503
    except Exception as e:
        logger.error(f"An unexpected error occurred: {e}")
        return jsonify({"error": f"Terjadi error tak terduga di server Flask: {str(e)}"}), 500
    finally:
        if conn:
            logger.info("Closing SAP connection for bulk-schedule-pro...")
            conn.close()
            logger.info("SAP connection closed.")
    
@app.route('/api/bulk-change-pv', methods=['POST'])
def bulk_change_pv():
    """
    Endpoint untuk melakukan perubahan Production Version (PV) secara bulk.
    Logika baru: Tetap commit data yang berhasil meskipun ada beberapa yang gagal.
    """
    username, password = get_credentials()
    if not all([username, password]):
        return jsonify({"status": "error", "message": "Kredensial SAP tidak ditemukan di header."}), 401
    
    payload = request.get_json()
    if not payload or 'data' not in payload:
        return jsonify({"status": "error", "message": "Payload JSON tidak valid atau key 'data' tidak ada."}), 400

    pro_verid_list = payload.get('data', [])
    
    conn = None
    successful_changes = []
    failed_changes = []

    try:
        conn = connect_sap(username, password)

        for item in pro_verid_list:
            pro_number = item.get('pro')
            new_verid = item.get('verid')
            
            if not all([pro_number, new_verid]):
                failed_changes.append({"pro": pro_number, "message": "Data PRO atau VERID tidak lengkap."})
                continue

            logger.info(f"Processing Bulk Change PV: PRO={pro_number}, VERID={new_verid}")
            
            try:
                result = conn.call(
                    'BAPI_PRODORD_CHANGE',
                    NUMBER=pro_number,
                    ORDERDATA={'PROD_VERSION': new_verid},
                    ORDERDATAX={'PROD_VERSION': 'X'}
                )

                has_error = False
                if 'RETURN' in result and result['RETURN']:
                    return_messages = result['RETURN'] if isinstance(result['RETURN'], list) else [result['RETURN']]
                    for message in return_messages:
                        if message['TYPE'] in ['E', 'A']:
                            logger.warning(f"ERROR BAPI untuk PRO {pro_number}: {message['MESSAGE']}")
                            failed_changes.append({"pro": pro_number, "message": message['MESSAGE']})
                            has_error = True
                            break
                
                if not has_error:
                    successful_changes.append({"pro": pro_number, "verid": new_verid})

            except (ABAPApplicationError, RFCLibError) as bapi_error:
                error_message = f"Exception saat memanggil BAPI untuk PRO {pro_number}: {bapi_error}"
                logger.error(f"ERROR: {error_message}")
                failed_changes.append({"pro": pro_number, "message": str(bapi_error)})

        if successful_changes:
            logger.info("Committing successful changes...")
            conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
        else:
            logger.info("No successful changes to commit.")

        # 7. Buat respons berdasarkan hasil akhir
        if not failed_changes and successful_changes:
            # Semua berhasil
            return jsonify({
                "status": "sukses",
                "message": "Semua data berhasil diubah dan disimpan.",
                "berhasil": successful_changes,
                "gagal": []
            }), 200
        elif successful_changes and failed_changes:
            # Sebagian berhasil, sebagian gagal
            return jsonify({
                "status": "sukses_parsial",
                "message": f"{len(successful_changes)} data berhasil disimpan, namun {len(failed_changes)} data gagal diproses.",
                "berhasil": successful_changes,
                "gagal": failed_changes
            }), 207
        elif not successful_changes and failed_changes:
            # Semua gagal
            return jsonify({
                "status": "gagal_total",
                "message": "Semua data gagal diproses. Tidak ada perubahan yang disimpan.",
                "berhasil": [],
                "gagal": failed_changes
            }), 400
        else:
            # Tidak ada data sama sekali
            return jsonify({"status": "info", "message": "Tidak ada data untuk diproses."}), 200

    except ConnectionError as e:
        return jsonify({"status": "error", "message": str(e)}), 500
    except Exception as e:
        logger.error(f"ERROR: Terjadi kesalahan tidak terduga: {e}")
        return jsonify({"status": "error", "message": "Terjadi kesalahan internal server."}), 500
    finally:
        if conn:
            conn.close()
            logger.info("DEBUG: Koneksi SAP ditutup.")

# api edit component
@app.route('/api/edit_component', methods=['POST'])
def edit_component():
    """
    Endpoint untuk mengedit komponen Production Order (PRO) di SAP.
    """
    """
    Endpoint untuk mengedit komponen Production Order (PRO) di SAP.
    """
    logger.info("--- [START] Edit Component Process ---")
    conn = None

    try:
        username, password = get_credentials()

        data = request.get_json()
        if not data:
            raise ValueError("Request body harus berisi data JSON.")
        
        logger.info(f"   -> Data received for AUFNR: {data.get('aufnr', 'N/A')}")
        
        # Validasi hanya field kunci. Field lain opsional.
        required_keys = ['aufnr', 'rspos']
        for key in required_keys:
            if key not in data or data[key] is None:
                raise ValueError(f"Field kunci '{key}' wajib diisi.")
        
        conn = connect_sap(username, password)
        logger.info(f"   -> SAP connection established for user '{username}'.")

        logger.info("   -> Preparing parameters for RFC 'Z_RFC_PRODORD_COMPONENT_MAINTA'...")
        
        params = {
            'IV_AUFNR': data['aufnr'],
            'IV_RSPOS': data['rspos'],
        }

        if 'matnr' in data:
            params['IV_MATNR'] = data['matnr']
            params['IV_MATNRX'] = 'X'
        else:
            params['IV_MATNR'] = ''
            params['IV_MATNRX'] = ' '

        if 'bdmng' in data and data['bdmng'] is not None:
            params['IV_BDMNG'] = str(data['bdmng'])
            params['IV_BDMNGX'] = 'X'
        else:
            params['IV_BDMNG'] = '0'
            params['IV_BDMNGX'] = ' '
            
        if 'lgort' in data:
            params['IV_LGORT'] = data['lgort']
            params['IV_LGORTX'] = 'X'
        else:
            params['IV_LGORT'] = ''
            params['IV_LGORTX'] = ' '
            
        if 'sobkz' in data and data['sobkz'] is not None:
            params['IV_SOBKZ'] = 'X' if str(data['sobkz']) == '1' else ' '
            params['IV_SOBKZX'] = 'X'
        else:
            params['IV_SOBKZ'] = ''
            params['IV_SOBKZX'] = ''
            
        logger.info(f"   -> Calling RFC 'Z_RFC_PRODORD_COMPONENT_MAINTA'")
        result_change = conn.call('Z_RFC_PRODORD_COMPONENT_MAINTA', **params)

        return_table = result_change.get('IT_RETURN', [])
        is_success = True
        sap_errors = []

        if return_table:
            for row in return_table:
                if row['MSGTYP'] in ('E', 'A'):
                    is_success = False
                    error_msg = f"Pesan SAP: {row['MESSAGE']}"
                    sap_errors.append(error_msg)
                    logger.warning(f"   -> SAP Error: {error_msg}")
        
        if not is_success:
            logger.info("   -> Transaction Failed. No Commit.")
            return jsonify({
                "success": False, "message": "Proses edit komponen gagal.",
                "sap_errors": sap_errors, "raw_sap_response": result_change
            }), 400

        logger.info("   -> Operation successful. Committing...")

        result_commit = conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')

        return jsonify({
            "success": True, "message": "Komponen berhasil diubah dan transaksi telah di-commit.",
            "raw_change_response": result_change, "raw_commit_response": result_commit
        }), 200

    except ValueError as ve:
        logger.warning(f"   -> ERROR (Validation): {ve}")
        return jsonify({"success": False, "message": str(ve)}), 400
    except RFCError as rfc_err:
        logger.error(f"   -> ERROR (RFC): {rfc_err}")
        return jsonify({"success": False, "message": f"Terjadi kesalahan RFC: {rfc_err}"}), 500
    except Exception as e:
        logger.error(f"   -> ERROR (General): {e}")
        return jsonify({"success": False, "message": "Terjadi kesalahan internal pada server API."}), 500
    finally:
        if conn:
            conn.close()
            logger.info("   -> SAP connection closed.")
        logger.info("--- [END] Edit Component Process ---\n")

@app.route('/api/get_stock', methods=['GET'])
def get_material_stock():
    conn = None
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        material_number = request.args.get('matnr')
        storage_location = request.args.get('lgort')

        logger.info(f"Menerima parameter: matnr={material_number}, lgort={storage_location}")

        if not material_number:
            return jsonify({'error': 'Missing required parameter: matnr'}), 400
        
        if material_number.isdigit():
            formatted_matnr = material_number.zfill(18)
            # logger.info(f"Material number is numeric. Padding to 18 chars: {formatted_matnr}")
        else:
            formatted_matnr = material_number
            # logger.info(f"Material number is alphanumeric. Using as is: {formatted_matnr}")
            
        # logger.info(f"Menyiapkan parameter RFC...")
        rfc_params = {
            'P_MATNR': formatted_matnr
        }

        if storage_location:
            rfc_params['P_LGORT'] = storage_location
            
        logger.info(f"Calling RFC Z_FM_YMMR006NX for MATNR={formatted_matnr}, LGORT={storage_location}")
        
        # 5. Panggil RFC
        result = conn.call(
            'Z_FM_YMMR006NX',
            **rfc_params # <-- Mengirim parameter secara dinamis
        )

        stock_data = result.get('T_DATA', [])
        
        return jsonify(stock_data), 200

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except (CommunicationError, ABAPApplicationError) as e:
        logger.error(f"SAP Error: {e}")
        return jsonify({'error': f"SAP Error: {str(e)}"}), 500
    except Exception as e:
        logger.error(f"An unexpected error occurred: {e}")
        return jsonify({'error': f"An unexpected error occurred: {str(e)}"}), 500
    finally:
        if conn:
            logger.info("Closing SAP connection for get_material_stock...")
            conn.close()
            logger.info("SAP connection closed.")

@app.route('/api/search_stock', methods=['GET'])
def search_material_stock():
    conn = None
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        # 1. Ambil 'matnr' (opsional) dan 'lgort' (opsional)
        material_number = request.args.get('matnr') # Bisa None
        storage_location = request.args.get('lgort') # Bisa None

        # logger.info(f"Menerima parameter: matnr={material_number}, lgort={storage_location}")

        # 2. Validasi BARU: Setidaknya salah satu harus ada
        if not material_number and not storage_location: # <-- DIUBAH
            return jsonify({'error': 'Missing required parameter: either matnr or lgort must be provided'}), 400

        # 3. Siapkan parameter RFC secara dinamis
        rfc_params = {}

        # Tambahkan MATNR HANYA JIKA ada dan lakukan padding
        if material_number:
            if material_number.isdigit():
                formatted_matnr = material_number.zfill(18)
            else:
                formatted_matnr = material_number
            rfc_params['P_MATNR'] = formatted_matnr # <-- P_MATNR opsional di RFC?

        # Tambahkan LGORT HANYA JIKA ada
        if storage_location:
            rfc_params['P_LGORT'] = storage_location

        logger.info(f"Calling RFC Z_FM_YMMR006NX search with params: {rfc_params}")

        # 4. Panggil RFC
        # Asumsi RFC Z_FM_YMMR006NX bisa handle P_MATNR kosong jika P_LGORT diisi, atau sebaliknya
        result = conn.call(
            'Z_FM_YMMR006NX',
            **rfc_params 
        )

        # 5. Proses Hasil (Tidak berubah)
        stock_data = result.get('T_DATA', [])
        return jsonify(stock_data), 200

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except (CommunicationError, ABAPApplicationError) as e:
        logger.error(f"SAP Error: {e}")
        return jsonify({'error': f"SAP Error: {str(e)}"}), 500
    except Exception as e:
        logger.error(f"An unexpected error occurred: {e}")
        return jsonify({'error': f"An unexpected error occurred: {str(e)}"}), 500
    finally:
        if conn:
            logger.info("Closing SAP connection for search_material_stock...")
            conn.close()
            logger.info("SAP connection closed.")

@app.route('/api/search_stock_by_description', methods=['GET'])
def search_material_by_desc():
    """
    Endpoint untuk mencari material berdasarkan deskripsi (MAKTX)
    Memanggil Z_RFC_GET_MATERIAL_BY_DESC dan mengembalikan tabel ET_MATERIAL.
    """
    conn = None
    try:
        # 1. Otentikasi dan Koneksi SAP (sama seperti contoh)
        username, password = get_credentials()
        conn = connect_sap(username, password)

        # 2. Ambil parameter 'maktx' dari URL
        # Sesuai dengan yang dikirim oleh Controller Laravel Anda
        description = request.args.get('maktx')

        # Validasi parameter
        if not description:
            return jsonify({'error': 'Missing required parameter: maktx'}), 400
            
        logger.info(f"Calling RFC Z_RFC_GET_MATERIAL_BY_DESC2 with IV_MAKTX={description}")
        result = conn.call(
            'Z_RFC_GET_MATERIAL_BY_DESC2',
            IV_MAKTX=description
        )

        material_data = result.get('ET_MATERIAL', [])
        
        # (Opsional) Anda juga bisa log pesan sukses dari SAP
        ev_msg = result.get('EV_RETURN_MSG', '')
        # logger.info(f"Pesan balasan SAP: {ev_msg}")

        # 5. Kembalikan data sebagai JSON
        return jsonify(material_data), 200

    except ValueError as ve:
        # Error otentikasi dari get_credentials()
        return jsonify({'error': str(ve)}), 401
    except (CommunicationError, ABAPApplicationError) as e:
        # Error spesifik dari SAP/RFC
        logger.error(f"SAP Error: {e}")
        return jsonify({'error': f"SAP Error: {str(e)}"}), 500
    except Exception as e:
        # Error umum lainnya
        logger.error(f"An unexpected error occurred: {e}")
        return jsonify({'error': f"An unexpected error occurred: {str(e)}"}), 500
    finally:
        # 6. Tutup koneksi (sama seperti contoh)
        if conn:
            logger.info("Closing SAP connection for search_material_by_desc...")
            conn.close()
            logger.info("SAP connection closed.")

# CHANGE QUANTITY
@app.route('/api/change_quantity', methods=['POST'])
def change_quantity():
    import time
    
    # 1. Inisialisasi koneksi sebagai None di luar blok try
    conn = None
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        data = request.get_json()
        aufnr = data.get('AUFNR')
        quantity = data.get('QUANTITY')

        if not aufnr or not quantity:
            return jsonify({'error': 'AUFNR and QUANTITY are required'}), 400

        if not aufnr or not quantity:
            return jsonify({'error': 'AUFNR and QUANTITY are required'}), 400

        logger.info(f"AUFNR: {aufnr} -> Value Quantity: {quantity}")
        result_change = conn.call(
            'BAPI_PRODORD_CHANGE',
            NUMBER=aufnr,
            ORDERDATA={'QUANTITY': quantity},
            ORDERDATAX={'QUANTITY': 'X'}
        )
        sap_return = result_change.get('RETURN', [])
        sap_return_list = sap_return if isinstance(sap_return, list) else [sap_return]
        has_error = any(msg.get('TYPE') in ['E', 'A'] for msg in sap_return_list)

        if has_error:
            error_messages = [f"[{msg.get('TYPE')}] {msg.get('MESSAGE')}" for msg in sap_return_list]
            error_string = "\n".join(error_messages)
            logger.error(f"BAPI Error: {error_string}")
            return jsonify({'error': f'SAP BAPI Error:\n{error_string}'}), 500

        # Commit perubahan
        logger.info("BAPI_TRANSACTION_COMMIT...")
        conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
        return jsonify({
            'sap_return': sap_return_list,
            'status':'success',
            'message': 'Quantity changed successfully, Please Refresh the Production Order data.'
        }), 200

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        logger.error(f"Exception: {str(e)}")
        return jsonify({'error': str(e)}), 500
    finally:
        if conn:
            logger.info("Closing SAP connection for change_quantity...")
            conn.close()
            logger.info("SAP connection closed.")

@app.route('/api/cogi/sync', methods=['POST'])
def fetch_cogi_data_from_sap():
    """
    Endpoint API untuk HANYA MENGAMBIL data COGI dari SAP.
    Penyimpanan akan dilakukan oleh Laravel.
    """
    logger.info(f"\n[{datetime.now()}] Menerima permintaan FETCH data COGI...")
    
    try:
        # 1. Dapatkan Kredensial
        sap_user, sap_pass = get_credentials()
        logger.info("INFO: Berhasil mendapatkan kredensial SAP dari header.")

        # 2. Definisikan Plant & Siapkan Argumen
        plants = ['1001', '1000', '2000', '3000']
        all_cogi_data = []
        task_args = [(plant, sap_user, sap_pass) for plant in plants]

        # 3. Eksekusi Paralel
        logger.info("INFO: Memulai pengambilan data paralel dari SAP...")
        with ThreadPoolExecutor(max_workers=len(plants)) as executor:
            results = executor.map(fetch_data_for_plant, task_args)
            for plant_data in results:
                if plant_data:
                    all_cogi_data.extend(plant_data)

        logger.info(f"INFO: Total data terkumpul dari SAP: {len(all_cogi_data)} baris.")
        
        # 4. [PERUBAHAN] Kembalikan data mentah, JANGAN SIMPAN
        return jsonify({
            "data": all_cogi_data
        }), 200

    # 5. Error Handling (tetap sama)
    except ValueError as ve:
        logger.error(f"ERROR: Kredensial tidak ditemukan. Pesan: {ve}")
        return jsonify({'error': str(ve)}), 401 # 401 Unauthorized
    except (ABAPApplicationError, CommunicationError) as sap_err:
        logger.error(f"ERROR: Gagal mengambil data dari SAP: {sap_err}")
        traceback.print_exc()
        return jsonify({ "error": f"Gagal saat mengambil data dari SAP: {sap_err}" }), 500
    except Exception as e:
        logger.error(f"ERROR: Terjadi kesalahan tidak terduga: {e}")
        traceback.print_exc()
        return jsonify({ "error": f"Terjadi kesalahan tidak terduga: {e}" }), 500
		
@app.route('/api/sap_get_pro_detail', methods=['GET'])
def sap_get_pro_detail():
    """
    Endpoint: Mengambil data detail HANYA UNTUK SATU PRO (AUFNR) SPESIFIK.
    Versi ini sudah dilengkapi:
    - Logging parameter (plant, aufnr_raw, aufnr_padded)
    - Padding AUFNR ke 12 digit (sesuai endpoint lain)
    - Error handling yang lebih jelas (401 / 502 / 500)
    """

    # Ambil parameter dari query string
    plant = (request.args.get('plant') or '').strip()
    aufnr_raw = (request.args.get('aufnr') or '').strip()

    logger.info(f"[sap_get_pro_detail] plant={plant!r}, aufnr_raw={aufnr_raw!r}")

    # Validasi awal
    if not plant or not aufnr_raw:
        return jsonify({'error': 'Missing required parameters: plant and aufnr'}), 400

    # Samakan behaviour dengan endpoint lain: AUFNR dipaksa 12 digit
    aufnr = pad12(aufnr_raw)
    logger.info(f"[sap_get_pro_detail] aufnr_padded={aufnr!r}")

    conn = None

    try:
        # Ambil kredensial dari header
        username, password = get_credentials()
        logger.info(f"[sap_get_pro_detail] username={username!r}")

        # Buka koneksi SAP
        conn = connect_sap(username=username, password=password)
        logger.info("[sap_get_pro_detail] Connected to SAP, calling Z_FM_YPPR074Z...")

        # Panggil RFC dengan plant + AUFNR yang sudah dipadding
        result = conn.call('Z_FM_YPPR074Z', P_WERKS=plant, P_AUFNR=aufnr)

        # Ambil data yang dibutuhkan, pastikan selalu list
        t_data1 = result.get('T_DATA1', []) or []
        t_data3 = result.get('T_DATA3', []) or []
        t_data4 = result.get('T_DATA4', []) or []

        logger.info(
            f"[sap_get_pro_detail] RFC success. "
            f"len(T_DATA1)={len(t_data1)}, len(T_DATA3)={len(t_data3)}, len(T_DATA4)={len(t_data4)}"
        )

        # Kembalikan respons ke Laravel
        return jsonify({
            "plant": plant,
            "AUFNR": aufnr,
            "T_DATA1": t_data1,  # Routings
            "T_DATA3": t_data3,  # Detail PRO
            "T_DATA4": t_data4,  # Components
        }), 200

    except ValueError as ve:
        # Biasanya dari get_credentials() kalau header SAP tidak ada
        logger.info(f"[sap_get_pro_detail] ValueError (credentials): {ve}")
        return jsonify({'error': str(ve)}), 401

    except (ABAPApplicationError, ABAPRuntimeError, CommunicationError, LogonError, RFCError, RFCLibError) as se:
        # Error spesifik dari SAP / RFC
        logger.error(f"[sap_get_pro_detail] SAP/RFC Error: {se}")
        traceback.print_exc()
        return jsonify({'error': f'SAP RFC error: {str(se)}'}), 502

    except Exception as e:
        # Error tak terduga lainnya
        logger.error(f"[sap_get_pro_detail] General Exception: {e}")
        traceback.print_exc()
        return jsonify({'error': f'Internal server error in sap_get_pro_detail: {str(e)}'}), 500

    finally:
        # Pastikan koneksi SAP selalu ditutup
        if conn:
            try:
                logger.info(f"[sap_get_pro_detail] Closing SAP connection for PRO {aufnr!r}")
                conn.close()
                logger.info("[sap_get_pro_detail] SAP connection closed.")
            except Exception as close_err:
                logger.info(f"[sap_get_pro_detail] Error ketika menutup koneksi SAP: {close_err}")

			
@app.route('/api/delete-data', methods=['POST', 'OPTIONS'])
def delete_data_to_mysql():
    """
    Menghapus data (dari SAP) dari klien yang telah diteco.
    Menggunakan helper get_mysql_connection().
    """
    # --- SOLUSI CORS MANUAL ---
    if request.method == 'OPTIONS':
        logger.info("Handling OPTIONS for delete_data_to_mysql")
        response = jsonify({'status': 'preflight_ok'})
        response.headers.add('Access-Control-Allow-Origin', '*')
        response.headers.add('Access-Control-Allow-Headers', 'Content-Type,Authorization,X-SAP-Username,X-SAP-Password')
        response.headers.add('Access-Control-Allow-Methods', 'POST, OPTIONS')
        return response, 200

    conn = None
    cursor = None

    TABLES_TO_DELETE = [
        'production_t_data1', 
        'production_t_data3', 
        'production_t_data4'
    ]

    try:
        # --- PARSING DATA ---
        data = request.get_json(force=True, silent=True)
        
        if not data and request.data:
            try:
                import json
                data = json.loads(request.data.decode('utf-8'))
            except:
                pass

        if not data:
            logger.info("DEBUG: Body yang diterima kosong atau bukan JSON.")
            return jsonify({"message": "Request harus dalam format JSON dan tidak boleh kosong"}), 400
        
        pro_list = data.get('pro_list')

        if not pro_list or not isinstance(pro_list, list):
            return jsonify({'error': 'Input harus berupa JSON list dengan key "pro_list"'}), 400

        # --- KONEKSI DATABASE (Menggunakan Helper) ---
        try:
            conn = get_mysql_connection()
            cursor = conn.cursor()
        except Exception as e:
            logger.error(f"❌ Gagal membuka koneksi database: {e}")
            return jsonify({'error': f'Database Connection Failed: {str(e)}'}), 500

        success_count = 0
        error_details = []
        
        if len(pro_list) > 0:
            logger.info(f"INFO: Memulai penghapusan untuk {len(pro_list)} PRO...")

        for aufnr in pro_list:
            if not aufnr:
                error_details.append({'pro_number': 'KOSONG', 'message': 'Nomor PRO kosong terdeteksi.'})
                continue
            
            try:
                deleted_rows_total = 0
                for table in TABLES_TO_DELETE:
                    query = f"DELETE FROM {table} WHERE AUFNR = %s"
                    
                    # Eksekusi Query
                    cursor.execute(query, (aufnr,))
                    
                    # mysql.connector menyimpan jumlah baris di property rowcount
                    # Check if using pymysql
                    deleted_rows = cursor.rowcount 
                    deleted_rows_total += deleted_rows
                
                conn.commit()
                # logger.info(f"✅ Sukses hapus {aufnr}. Total {deleted_rows_total} baris dihapus.")
                success_count += 1

            except Exception as e_inner:
                conn.rollback()
                logger.error(f"❌ General Error saat hapus {aufnr}: {str(e_inner)}")
                error_details.append({'pro_number': aufnr, 'message': str(e_inner)})

        logger.info(f"Deletion complete. Success: {success_count}, Failed: {len(error_details)}")

        return jsonify({
            'message': f"Proses hapus MySQL selesai. {success_count} PRO sukses, {len(error_details)} PRO gagal.",
            'success_count': success_count,
            'error_details': error_details
        }), 200

    except Exception as e:
        logger.error(f"❌ Exception fatal saat hapus data MySQL: {str(e)}")
        if conn:
             try:
                 conn.rollback()
             except: pass
        return jsonify({'error': f'Internal server error: {str(e)}'}), 500
    finally:
        # --- CLEANUP YANG AMAN (ANTI ERROR) ---
        if cursor:
            try:
                cursor.close()
            except:
                pass # Abaikan error saat tutup cursor
        
        if conn:
            try:
                # Cukup panggil .close() tanpa cek .is_connected()
                # karena setiap library pasti punya .close()
                conn.close()
                logger.info("Info: Koneksi database ditutup.")
            except Exception as e:
                # Jika gagal tutup (misal sudah tertutup duluan), abaikan saja
                logger.warning(f"Warning saat menutup koneksi: {e}")
				
@app.route('/api/release_order', methods=['POST'])
def release_order():
    conn = None
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        data = request.get_json() or {}
        print("Data diterima untuk release:", data)

        aufnr = data.get('AUFNR')
        if not aufnr:
            return jsonify({
                'success': False,
                'message': 'AUFNR is required'
            }), 400

        print(f"Calling RFC BAPI_PRODORD_RELEASE for AUFNR={aufnr}...")

        result = conn.call(
            'BAPI_PRODORD_RELEASE',
            RELEASE_CONTROL='1',
            WORK_PROCESS_GROUP='COWORK_BAPI',
            WORK_PROCESS_MAX=99,
            ORDERS=[{'ORDER_NUMBER': aufnr}]
        )

        # --- Ambil pesan return ---
        return_main   = result.get('RETURN')           # BAPIRET2 (dict)
        return_detail = result.get('DETAIL_RETURN', [])  # table
        app_log       = result.get('APPLICATION_LOG', [])

        # --- Kumpulkan semua pesan ---
        messages = []

        if return_main:
            messages.append({
                'type': return_main.get('TYPE'),
                'message': return_main.get('MESSAGE'),
                'id': return_main.get('ID'),
                'number': return_main.get('NUMBER'),
            })

        for row in return_detail:
            messages.append({
                'type': row.get('TYPE'),
                'message': row.get('MESSAGE'),
                'order': row.get('ORDER_NUMBER'),
                'field': row.get('FIELD'),
            })

        # --- Deteksi error SAP ---
        error_types = {'E', 'A', 'X'}
        has_error = any(m.get('type') in error_types for m in messages)

        if has_error:
            # ❌ JANGAN commit kalau error
            return jsonify({
                'success': False,
                'aufnr': aufnr,
                'messages': messages,
                'raw': {
                    'RETURN': return_main,
                    'DETAIL_RETURN': return_detail,
                }
            }), 422

        # --- Commit jika sukses ---
        conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')

        return jsonify({
            'success': True,
            'aufnr': aufnr,
            'messages': messages,
            'raw': {
                'RETURN': return_main,
                'DETAIL_RETURN': return_detail,
                'APPLICATION_LOG': app_log,
            }
        }), 200

    except ValueError as ve:
        return jsonify({
            'success': False,
            'message': str(ve)
        }), 401

    except Exception as e:
        print("Exception saat release order:", str(e))
        return jsonify({
            'success': False,
            'message': str(e)
        }), 500

    finally:
        if conn:
            try:
                conn.close()
            except Exception:
                pass
                pass

if __name__ == "__main__":
    # Tambahkan use_reloader=False
    app.run(host='0.0.0.0', port=5001, debug=True, use_reloader=False)