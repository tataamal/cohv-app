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

# ================ BAGIAN HELPER ================

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

# ================ BAGIAN API ================

# API untuk login SAP
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
    
# API untuk change Work Center
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
    
#API untuk Refresh Data
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
    
# API untuk Change PV (Production Version)
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