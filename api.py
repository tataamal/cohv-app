# main.py
from flask import Flask, request, jsonify
from pyrfc import Connection, ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError, RFCError, RFCLibError
from concurrent.futures import ThreadPoolExecutor
import threading
import os
from flask_cors import CORS
from datetime import time
from decimal import Decimal
from datetime import datetime
import traceback

app = Flask(__name__)
CORS(app, supports_credentials=True, resources={r"/api/*": {"origins": "*"}})

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

@app.route('/api/save_edit', methods=['POST'])
def changewc():
    # 1. Inisialisasi koneksi sebagai None di luar blok try
    #    Ini penting agar variabel 'conn' bisa diakses di blok 'finally'.
    conn = None
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password) # Koneksi dibuat di sini

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
        
        # time.sleep(2) tidak terlalu diperlukan di sini, bisa dihapus jika untuk debugging
        
        return jsonify(result)

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        print("Exception:", str(e))
        return jsonify({'error': str(e)}), 500
    finally:
        # 2. Blok ini akan selalu dieksekusi.
        #    Kita cek apakah koneksi berhasil dibuat ('conn' tidak lagi None)
        #    sebelum mencoba menutupnya.
        if conn:
            print("Closing SAP connection...")
            conn.close()
            print("SAP connection closed.")

@app.route('/api/sap_combined', methods=['GET'])
def sap_combined():
    plant = request.args.get('plant') #plant disini adalah kode

    if not plant:
        return jsonify({'error': 'Missing plant parameter'}), 400

    # 1. Inisialisasi koneksi sebagai None di luar blok try
    conn = None
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        # Panggil RFC hanya dengan parameter plant
        print(f"Calling RFC Z_FM_YPPR074Z for plant: {plant}")
        result = conn.call('Z_FM_YPPR074Z', P_WERKS=plant)

        # Mengembalikan data jika berhasil
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
        print(f"Exception in sap_combined: {str(e)}")
        return jsonify({'error': str(e)}), 500
    finally:
        # 2. Blok ini akan selalu dieksekusi, memastikan koneksi ditutup
        if conn:
            print("Closing SAP connection for sap_combined...")
            conn.close()
            print("SAP connection closed.")

@app.route('/api/refresh-pro', methods=['GET'])
def refresh_single_pro():
    
    # 1. Inisialisasi koneksi sebagai None di luar blok try
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

        print(f"Calling RFC Z_FM_YPPR074Z for plant={plant}, aufnr={a12}")
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

        # --- normalisasi list + mapping WERK->WERKS ---
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
        print("[refresh-pro] exception:", repr(e))
        return jsonify({'error': str(e)}), 500
    finally:
        # 2. Blok ini akan selalu dieksekusi, memastikan koneksi ditutup
        if conn:
            print("Closing SAP connection for refresh-pro...")
            conn.close()
            print("SAP connection closed.")
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
    
    # 1. Inisialisasi koneksi sebagai None di luar blok try
    conn = None
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
        # Pastikan sap_return selalu berupa list
        sap_return_list = sap_return if isinstance(sap_return, list) else [sap_return]

        # Cari pesan dengan tipe 'E' (Error) atau 'A' (Abort)
        has_error = any(msg.get('TYPE') in ['E', 'A'] for msg in sap_return_list)

        if has_error:
            # Jika ada error, kumpulkan pesannya dan kembalikan sebagai error server
            error_messages = [f"[{msg.get('TYPE')}] {msg.get('MESSAGE')}" for msg in sap_return_list]
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

        return jsonify({
            'before_version': before_version,
            'after_version': after_version,
            'sap_return': sap_return_list # Kembalikan list yang sudah dinormalisasi
        })

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        print("❌ Exception:", str(e))
        return jsonify({'error': str(e)}), 500
    finally:
        # 2. Blok ini akan selalu dieksekusi, memastikan koneksi ditutup
        if conn:
            print("Closing SAP connection for change_prod_version...")
            conn.close()
            print("SAP connection closed.")

@app.route('/api/schedule_order', methods=['POST'])
def schedule_order():
    # 1. Inisialisasi koneksi sebagai None di luar blok try
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

        # Konversi jam
        try:
            # Diperlukan import 'time' dari modul 'datetime'
            from datetime import time 
            time_parts = [int(x) for x in time_str.split(':')]
            time_obj = time(*time_parts)  # datetime.time
        except Exception:
            return jsonify({'error': f'Format jam tidak valid: {time_str} (harus HH:MM:SS)'}), 400

        print(f"[Flask] BAPI_PRODORD_SCHEDULE AUFNR={aufnr} DATE={date} TIME={time_obj}")

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

        # Commit
        print("BAPI_TRANSACTION_COMMIT...")
        conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')

        # Selalu kembalikan field ini supaya konsisten dengan Laravel
        return jsonify({
            'sap_return':      result.get('RETURN', []),
            'detail_return':   result.get('DETAIL_RETURN', []),
            'application_log': result.get('APPLICATION_LOG', []),
        }), 200

    except Exception as e:
        import traceback
        traceback.print_exc()
        return jsonify({'error': str(e)}), 500
    finally:
        # 2. Blok ini akan selalu dieksekusi, memastikan koneksi ditutup
        if conn:
            print("Closing SAP connection for schedule_order...")
            conn.close()
            print("SAP connection closed.")
    
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
    finally:
        # 2. Blok ini akan selalu dieksekusi, memastikan koneksi ditutup
        if conn:
            print("Closing SAP connection for add_component...")
            conn.close()
            print("SAP connection closed.")
    
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
    finally:
        # 2. Blok ini akan selalu dieksekusi, memastikan koneksi ditutup
        if conn:
            print("Closing SAP connection for add_component...")
            conn.close()
            print("SAP connection closed.")
    
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
    finally:
        # 2. Blok ini akan selalu dieksekusi, memastikan koneksi ditutup
        if conn:
            print("Closing SAP connection for add_component...")
            conn.close()
            print("SAP connection closed.")
    
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

        print(f"\n--- Memulai Proses Refresh untuk Plant: {plant} ---")
        
        all_responses = []
        any_failures = False

        # 2c. Loop untuk setiap PRO dan panggil RFC
        for aufnr in aufnr_list:
            if not aufnr:
                continue
            
            padded_aufnr = str(aufnr).zfill(12)
            print(f"  -> Memproses PRO: {aufnr} ({padded_aufnr})")

            try:
                res = conn.call('Z_FM_YPPR074Z', P_WERKS=plant, P_AUFNR=padded_aufnr)
                print(f"     [DEBUG] Raw SAP Response: {res}")

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
                    print(f"     ... SUKSES")
                else:
                    any_failures = True
                    error_message = sap_return.get('MESSAGE', 'Error tidak diketahui dari SAP')
                    response_gagal = {
                        "status": "gagal",
                        "aufnr": aufnr,
                        "message": f"Gagal mengambil data dari SAP, Error: {error_message}"
                    }
                    all_responses.append(response_gagal)
                    print(f"     ... GAGAL: {error_message}")

            except Exception as rfc_error:
                any_failures = True
                response_gagal_sistem = {
                    "status": "gagal",
                    "aufnr": aufnr,
                    "message": f"Gagal mengambil data dari SAP, Error: {str(rfc_error)}"
                }
                all_responses.append(response_gagal_sistem)
                print(f"     ... GAGAL (Sistem): {str(rfc_error)}")
                continue
        
        print("--- Proses Selesai ---")

        # 2d. Kembalikan semua hasil dengan status code yang sesuai
        final_status_code = 207 if any_failures else 200
        
        return jsonify({
            "plant": plant,
            "results": all_responses
        }), final_status_code

    except ValueError as ve:
        # Gagal karena header otentikasi tidak ada
        return jsonify({"error": str(ve)}), 401
    except Exception as e:
        # Gagal saat proses koneksi/autentikasi SAP atau error tak terduga lainnya
        return jsonify({"error": f"Terjadi kesalahan sistem: {str(e)}"}), 500
    finally:
        # 3. Blok ini akan selalu dieksekusi, memastikan koneksi ditutup
        if conn:
            print("--- Menutup Koneksi SAP ---")
            conn.close()
            print("--- Koneksi SAP Ditutup ---")

@app.route('/api/bulk-teco-pro', methods=['POST'])
def process_teco():
    # 1. Inisialisasi koneksi sebagai None di luar blok try
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

        # =======================================================
        # LANGKAH 2: PROSES LOOPING DAN HIT BAPI
        # =======================================================
        results = {
            "success_details": [],
            "error_details": []
        }

        # Koneksi dibuat di sini, di dalam blok try utama
        conn = connect_sap(username, password)

        for pro_number in pro_list:
            print(f"PRO {pro_number} Sedang di proses...")

            try:
                bapi_result = conn.call(
                    'BAPI_PRODORD_COMPLETE_TECH',
                    SCOPE_COMPL_TECH='1',
                    WORK_PROCESS_GROUP='COWORK_BAPI',
                    WORK_PROCESS_MAX=99,
                    ORDERS=[{'ORDER_NUMBER': pro_number}]
                )
                print(f"RAW BAPI Result for {pro_number}: {bapi_result}")

                has_error = False
                return_messages = []

                if isinstance(bapi_result, dict):
                    return_messages = bapi_result.get('RETURN', [])
                
                for msg in return_messages:
                    if isinstance(msg, dict) and msg.get('TYPE') in ['E', 'A']:
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

            except ABAPApplicationError as bapi_err:
                print(f"Error BAPI pada PRO {pro_number}: {bapi_err}")
                results["error_details"].append({
                    "pro_number": pro_number,
                    "message": f"Gagal melakukan teco pada PRO {pro_number}",
                    "sap_response": str(bapi_err)
                })

        # Setelah loop selesai, tentukan status akhir
        if results["error_details"]:
            return jsonify(results), 400 # Menggunakan status 400 untuk menandakan ada kegagalan
        else:
            return jsonify(results), 200

    except (CommunicationError, ABAPRuntimeError) as conn_err:
        print(f"Connection/Runtime Error: {conn_err}")
        return jsonify({"error": f"Gagal terhubung atau terjadi error runtime di SAP: {conn_err}"}), 503
    except Exception as e:
        print(f"An unexpected error occurred: {e}")
        return jsonify({"error": "Terjadi error tak terduga di server Flask."}), 500
    finally:
        # 2. Blok ini akan selalu dieksekusi, memastikan koneksi ditutup
        if conn:
            print("Closing SAP connection for bulk-teco-pro...")
            conn.close()
            print("SAP connection closed.")
    
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

        # =======================================================
        # LANGKAH 2: PROSES LOOPING DAN HIT BAPI BARU
        # =======================================================
        results = {
            "success_details": [],
            "error_details": []
        }
        
        # Definisikan parameter statis untuk BAPI
        order_data_input = {'EXPLODE_NEW': 'X'}

        # Koneksi dibuat di sini, di dalam blok try utama
        conn = connect_sap(username, password)

        for pro_number in pro_list:
            print(f"Processing Read PP for PRO {pro_number}...") # Log ke konsol Flask

            try:
                # Panggil BAPI BAPI_PRODORD_CHANGE
                bapi_result = conn.call(
                    'BAPI_PRODORD_CHANGE',
                    NUMBER=pro_number,
                    ORDERDATA=order_data_input
                )
                
                print(f"RAW BAPI Result for {pro_number}: {bapi_result}") # Debugging

                # Analisis respons BAPI
                has_error = False
                return_messages = []
                if isinstance(bapi_result, dict):
                    return_messages = bapi_result.get('RETURN', [])
                
                for msg in return_messages:
                    if isinstance(msg, dict) and msg.get('TYPE') in ['E', 'A']:
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
                        "message": f"Gagal Read PP pada PRO {pro_number}",
                        "sap_response": bapi_result
                    })

            except ABAPApplicationError as bapi_err:
                print(f"BAPI Error on PRO {pro_number}: {bapi_err}")
                results["error_details"].append({
                    "pro_number": pro_number,
                    "message": f"Gagal Read PP pada PRO {pro_number}",
                    "sap_response": str(bapi_err)
                })

        # Setelah loop selesai, tentukan respons akhir
        if results["error_details"]:
            return jsonify(results), 400
        else:
            return jsonify(results), 200

    except (CommunicationError, ABAPRuntimeError) as conn_err:
        print(f"Connection/Runtime Error: {conn_err}")
        return jsonify({"error": f"Gagal terhubung atau terjadi error runtime di SAP: {conn_err}"}), 503
    except Exception as e:
        print(f"An unexpected error occurred: {e}")
        return jsonify({"error": f"Terjadi error tak terduga di server Flask: {str(e)}"}), 500
    finally:
        # 2. Blok ini akan selalu dieksekusi, memastikan koneksi ditutup
        if conn:
            print("Closing SAP connection for bulk-readpp-pro...")
            conn.close()
            print("SAP connection closed.")

@app.route('/api/bulk-schedule-pro', methods=['POST'])
def process_schedule():

    # 1. Inisialisasi koneksi sebagai None di luar blok try
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

        # --- Persiapan Format Tanggal & Waktu untuk SAP ---
        sap_date = datetime.strptime(schedule_date, '%Y-%m-%d').strftime('%Y%m%d')
        sap_time = schedule_time.replace('.', '').replace(':', '')

        # Koneksi dibuat di sini, di dalam blok try utama
        conn = connect_sap(username, password)

        for pro_number in pro_list:
            print(f"Scheduling PRO {pro_number} for {sap_date} at {sap_time}...")

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
                
                print(f"RAW BAPI Result for {pro_number}: {bapi_result}")

                has_error = False
                return_messages = []
                if isinstance(bapi_result, dict):
                    return_messages = bapi_result.get('RETURN', [])
                
                for msg in return_messages:
                    if isinstance(msg, dict) and msg.get('TYPE') in ['E', 'A']:
                        has_error = True
                        break

                if not has_error:
                    results["success_details"].append({ "pro_number": pro_number, "sap_response": bapi_result })
                else:
                    results["error_details"].append({ "pro_number": pro_number, "message": f"Gagal schedule pada PRO {pro_number}", "sap_response": bapi_result })

            except ABAPApplicationError as bapi_err:
                print(f"BAPI Error on PRO {pro_number}: {bapi_err}")
                results["error_details"].append({ "pro_number": pro_number, "message": f"Gagal schedule pada PRO {pro_number}", "sap_response": str(bapi_err) })

        # Setelah loop selesai, tentukan respons akhir
        if results["error_details"]:
            return jsonify(results), 400
        else:
            return jsonify(results), 200

    except (CommunicationError, ABAPApplicationError) as sap_err:
        print(f"SAP RFC Error: {sap_err}")
        return jsonify({"error": f"Gagal terhubung atau mengeksekusi BAPI di SAP: {sap_err}"}), 503
    except Exception as e:
        print(f"An unexpected error occurred: {e}")
        return jsonify({"error": f"Terjadi error tak terduga di server Flask: {str(e)}"}), 500
    finally:
        # 2. Blok ini akan selalu dieksekusi, memastikan koneksi ditutup
        if conn:
            print("Closing SAP connection for bulk-schedule-pro...")
            conn.close()
            print("SAP connection closed.")
    
@app.route('/api/bulk-change-pv', methods=['POST'])
def bulk_change_pv():
    """
    Endpoint untuk melakukan perubahan Production Version (PV) secara bulk.
    Logika baru: Tetap commit data yang berhasil meskipun ada beberapa yang gagal.
    """
    # 1 & 2. Dapatkan kredensial dan payload (tidak ada perubahan)
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
        # 3. Buat koneksi ke SAP (tidak ada perubahan)
        conn = connect_sap(username, password)

        # 4. Looping untuk setiap pasangan PRO dan VERID (tidak ada perubahan)
        for item in pro_verid_list:
            pro_number = item.get('pro')
            new_verid = item.get('verid')
            
            if not all([pro_number, new_verid]):
                failed_changes.append({"pro": pro_number, "message": "Data PRO atau VERID tidak lengkap."})
                continue

            print(f"\n--- Memproses PRO: {pro_number}, VERID Baru: {new_verid} ---")
            
            try:
                # 5. Panggil BAPI dan cek hasilnya (tidak ada perubahan)
                result = conn.call(
                    'BAPI_PRODORD_CHANGE',
                    NUMBER=pro_number,
                    ORDERDATA={'PROD_VERSION': new_verid},
                    ORDERDATAX={'PROD_VERSION': 'X'}
                )
                print("Respon asli dari BAPI_PRODORD_CHANGE:", result)

                has_error = False
                if 'RETURN' in result and result['RETURN']:
                    return_messages = result['RETURN'] if isinstance(result['RETURN'], list) else [result['RETURN']]
                    for message in return_messages:
                        if message['TYPE'] in ['E', 'A']:
                            print(f"ERROR BAPI untuk PRO {pro_number}: {message['MESSAGE']}")
                            failed_changes.append({"pro": pro_number, "message": message['MESSAGE']})
                            has_error = True
                            break
                
                if not has_error:
                    print(f"Proses PRO {pro_number} dan Verid {new_verid} berhasil")
                    successful_changes.append({"pro": pro_number, "verid": new_verid})

            except (ABAPApplicationError, RFCLibError) as bapi_error:
                error_message = f"Exception saat memanggil BAPI untuk PRO {pro_number}: {bapi_error}"
                print(f"ERROR: {error_message}")
                failed_changes.append({"pro": pro_number, "message": str(bapi_error)})

        # ======================================================================
        # --- PERUBAHAN LOGIKA UTAMA ADA DI SINI ---
        # ======================================================================

        # 6. Lakukan COMMIT jika ada MINIMAL SATU perubahan yang berhasil.
        if successful_changes:
            print("\n--- Ditemukan data yang sukses, melakukan BAPI Transaction Commit ---")
            conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
            print("Commit berhasil. Perubahan untuk data yang sukses telah disimpan.")
        else:
            print("\n--- Tidak ada data yang sukses, BAPI Transaction Commit DILEWATI ---")

        # 7. Buat respons berdasarkan hasil akhir
        if not failed_changes and successful_changes:
            # Semua berhasil
            return jsonify({
                "status": "sukses",
                "message": "Semua data berhasil diubah dan disimpan.",
                "berhasil": successful_changes,
                "gagal": []
            })
        elif successful_changes and failed_changes:
            # Sebagian berhasil, sebagian gagal
            return jsonify({
                "status": "sukses_parsial",
                "message": f"{len(successful_changes)} data berhasil disimpan, namun {len(failed_changes)} data gagal diproses.",
                "berhasil": successful_changes,
                "gagal": failed_changes
            })
        elif not successful_changes and failed_changes:
            # Semua gagal
            return jsonify({
                "status": "gagal_total",
                "message": "Semua data gagal diproses. Tidak ada perubahan yang disimpan.",
                "berhasil": [],
                "gagal": failed_changes
            })
        else:
            # Tidak ada data sama sekali
            return jsonify({"status": "info", "message": "Tidak ada data untuk diproses."})

    except ConnectionError as e:
        return jsonify({"status": "error", "message": str(e)}), 500
    except Exception as e:
        print(f"ERROR: Terjadi kesalahan tidak terduga: {e}")
        return jsonify({"status": "error", "message": "Terjadi kesalahan internal server."}), 500
    finally:
        if conn:
            conn.close()
            print("\nDEBUG: Koneksi SAP ditutup.")

# api edit component
@app.route('/api/edit_component', methods=['POST'])
def edit_component():
    """
    Endpoint untuk mengedit komponen Production Order (PRO) di SAP.
    """
    print("\n--- [MULAI] Proses Edit Komponen ---")
    conn = None

    try:
        # --- LANGKAH 1: Validasi Kredensial & Data Masuk ---
        print("1. Memvalidasi kredensial dan data masuk...")
        username, password = get_credentials()

        data = request.get_json()
        if not data:
            raise ValueError("Request body harus berisi data JSON.")
        
        print(f"   -> Data yang diterima: {data}")
        
        # Validasi hanya field kunci. Field lain opsional.
        required_keys = ['aufnr', 'rspos']
        for key in required_keys:
            if key not in data or data[key] is None:
                raise ValueError(f"Field kunci '{key}' wajib diisi.")
        
        print("   -> Validasi data masuk berhasil.")

        # --- LANGKAH 2: Menyiapkan Koneksi ke SAP ---
        print("2. Menyiapkan koneksi ke SAP...")
        conn = connect_sap(username, password)
        print(f"   -> Koneksi SAP berhasil dibuat untuk user '{username}'.")

        # --- LANGKAH 3: Mempersiapkan Parameter RFC secara dinamis ---
        print("3. Mempersiapkan parameter untuk RFC 'Z_RFC_PRODORD_COMPONENT_MAINTA'...")
        
        params = {
            'IV_AUFNR': data['aufnr'],
            'IV_RSPOS': data['rspos'],
        }

        # Logika untuk MATNR
        if 'matnr' in data:
            params['IV_MATNR'] = data['matnr']
            params['IV_MATNRX'] = 'X'
        else:
            params['IV_MATNR'] = ''
            params['IV_MATNRX'] = ' '

        # Logika untuk BDMNG
        if 'bdmng' in data and data['bdmng'] is not None:
            params['IV_BDMNG'] = str(data['bdmng'])
            params['IV_BDMNGX'] = 'X'
        else:
            params['IV_BDMNG'] = '0'
            params['IV_BDMNGX'] = ' '
            
        # Logika untuk LGORT
        if 'lgort' in data:
            params['IV_LGORT'] = data['lgort']
            params['IV_LGORTX'] = 'X'
        else:
            params['IV_LGORT'] = ''
            params['IV_LGORTX'] = ' '
            
        # Logika untuk SOBKZ
        if 'sobkz' in data and data['sobkz'] is not None:
            params['IV_SOBKZ'] = 'X' if str(data['sobkz']) == '1' else ' '
            params['IV_SOBKZX'] = 'X'
        else:
            params['IV_SOBKZ'] = ''
            params['IV_SOBKZX'] = ''
            
        print(f"   -> Parameter yang akan dikirim: {params}")

        # --- LANGKAH 4: Memanggil RFC ---
        print("4. Memanggil RFC 'Z_RFC_PRODORD_COMPONENT_MAINTA'...")
        result_change = conn.call('Z_RFC_PRODORD_COMPONENT_MAINTA', **params)
        print("   -> Respons mentah dari RFC Change:")
        print("      " + str(result_change))

        # --- LANGKAH 5: Menganalisis hasil SEBELUM commit ---
        print("5. Menganalisis hasil dari tabel IT_RETURN...")
        return_table = result_change.get('IT_RETURN', [])
        is_success = True
        sap_errors = []

        if return_table:
            for row in return_table:
                if row['MSGTYP'] in ('E', 'A'):
                    is_success = False
                    error_msg = f"Pesan SAP: {row['MESSAGE']}"
                    sap_errors.append(error_msg)
                    print(f"   -> Ditemukan pesan GAGAL: {error_msg}")
        
        if not is_success:
            print("   -> Transaksi GAGAL. Commit tidak akan dilakukan.")
            return jsonify({
                "success": False, "message": "Proses edit komponen gagal.",
                "sap_errors": sap_errors, "raw_sap_response": result_change
            }), 400

        print("   -> Proses edit komponen berhasil. Melanjutkan ke COMMIT.")

        # --- LANGKAH 6: COMMIT Jika Berhasil ---
        print("6. Memanggil BAPI_TRANSACTION_COMMIT...")
        result_commit = conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
        print("   -> Respons mentah dari BAPI COMMIT:", result_commit)

        # --- LANGKAH 7: Mengembalikan Respons Sukses ---
        print("7. Mengirim respons sukses kembali ke controller...")
        return jsonify({
            "success": True, "message": "Komponen berhasil diubah dan transaksi telah di-commit.",
            "raw_change_response": result_change, "raw_commit_response": result_commit
        }), 200

    except ValueError as ve:
        print(f"   -> ERROR (Validasi): {ve}")
        return jsonify({"success": False, "message": str(ve)}), 400
    except RFCError as rfc_err:
        print(f"   -> ERROR (RFC): {rfc_err}")
        traceback.print_exc()
        return jsonify({"success": False, "message": f"Terjadi kesalahan RFC: {rfc_err}"}), 500
    except Exception as e:
        print(f"   -> ERROR (Umum): {e}")
        traceback.print_exc()
        return jsonify({"success": False, "message": "Terjadi kesalahan internal pada server API."}), 500
    finally:
        if conn:
            conn.close()
            print("   -> Koneksi SAP ditutup.")
        print("--- [SELESAI] Proses Edit Komponen ---\n")

@app.route('/api/get_stock', methods=['GET'])
def get_material_stock():
    conn = None
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        # 1. Ambil 'matnr' (wajib) dan 'lgort' (opsional)
        material_number = request.args.get('matnr')
        storage_location = request.args.get('lgort') # <-- Menggantikan 'werks'

        print(f"Menerima parameter: matnr={material_number}, lgort={storage_location}")

        # 2. Validasi parameter wajib
        if not material_number:
            return jsonify({'error': 'Missing required parameter: matnr'}), 400
        
        # 3. LOGIKA PADDING MATNR (Diambil dari /api/get_stock)
        # Ini penting agar RFC berfungsi dengan benar
        if material_number.isdigit():
            formatted_matnr = material_number.zfill(18)
            print(f"Material number is numeric. Padding to 18 chars: {formatted_matnr}")
        else:
            formatted_matnr = material_number
            print(f"Material number is alphanumeric. Using as is: {formatted_matnr}")
            
        # 4. Siapkan parameter RFC secara dinamis
        print(f"Menyiapkan parameter RFC...")
        rfc_params = {
            'P_MATNR': formatted_matnr # <-- Gunakan matnr yang sudah diformat
        }

        # Tambahkan P_LGORT HANYA JIKA 'storage_location' diisi
        if storage_location:
            rfc_params['P_LGORT'] = storage_location # <-- Parameter opsional
            
        print(f"Memanggil RFC Z_FM_YMMR006NX dengan parameter: {rfc_params}")
        
        # 5. Panggil RFC
        result = conn.call(
            'Z_FM_YMMR006NX',
            **rfc_params # <-- Mengirim parameter secara dinamis
        )

        print("Hasil dari RFC diterima. Mengembalikan T_DATA.")
        stock_data = result.get('T_DATA', [])
        
        return jsonify(stock_data)

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except (CommunicationError, ABAPApplicationError) as e:
        print(f"SAP Error: {e}")
        return jsonify({'error': f"SAP Error: {str(e)}"}), 500
    except Exception as e:
        print(f"An unexpected error occurred: {e}")
        return jsonify({'error': f"An unexpected error occurred: {str(e)}"}), 500
    finally:
        if conn:
            print("Closing SAP connection for search_material_stock...")
            conn.close()
            print("SAP connection closed.")

@app.route('/api/search_stock', methods=['GET'])
def search_material_stock():
    conn = None
    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        # 1. Ambil 'matnr' (opsional) dan 'lgort' (opsional)
        material_number = request.args.get('matnr') # Bisa None
        storage_location = request.args.get('lgort') # Bisa None

        print(f"Menerima parameter: matnr={material_number}, lgort={storage_location}")

        # 2. Validasi BARU: Setidaknya salah satu harus ada
        if not material_number and not storage_location: # <-- DIUBAH
            return jsonify({'error': 'Missing required parameter: either matnr or lgort must be provided'}), 400

        # 3. Siapkan parameter RFC secara dinamis
        rfc_params = {}

        # Tambahkan MATNR HANYA JIKA ada dan lakukan padding
        if material_number:
            if material_number.isdigit():
                formatted_matnr = material_number.zfill(18)
                print(f"Material number is numeric. Padding to 18 chars: {formatted_matnr}")
            else:
                formatted_matnr = material_number
                print(f"Material number is alphanumeric. Using as is: {formatted_matnr}")
            rfc_params['P_MATNR'] = formatted_matnr # <-- P_MATNR opsional di RFC?
        else:
             print("Material number not provided.")
             # Jika RFC *membutuhkan* P_MATNR meski kosong, tambahkan:
             # rfc_params['P_MATNR'] = '' 

        # Tambahkan LGORT HANYA JIKA ada
        if storage_location:
            rfc_params['P_LGORT'] = storage_location
            print(f"Storage location provided: {storage_location}")
        else:
            print("Storage location not provided.")
            # Jika RFC *membutuhkan* P_LGORT meski kosong, tambahkan:
            # rfc_params['P_LGORT'] = ''

        print(f"Memanggil RFC Z_FM_YMMR006NX dengan parameter: {rfc_params}")

        # 4. Panggil RFC
        # Asumsi RFC Z_FM_YMMR006NX bisa handle P_MATNR kosong jika P_LGORT diisi, atau sebaliknya
        result = conn.call(
            'Z_FM_YMMR006NX',
            **rfc_params 
        )

        # 5. Proses Hasil (Tidak berubah)
        print("Hasil dari RFC diterima. Mengembalikan T_DATA.")
        stock_data = result.get('T_DATA', [])
        return jsonify(stock_data)

    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except (CommunicationError, ABAPApplicationError) as e:
        print(f"SAP Error: {e}")
        return jsonify({'error': f"SAP Error: {str(e)}"}), 500
    except Exception as e:
        print(f"An unexpected error occurred: {e}")
        return jsonify({'error': f"An unexpected error occurred: {str(e)}"}), 500
    finally:
        if conn:
            print("Closing SAP connection for get_material_stock...")
            conn.close()
            print("SAP connection closed.")

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

        print(f"Menerima parameter untuk get_material_by_desc: maktx={description}")

        # Validasi parameter
        if not description:
            return jsonify({'error': 'Missing required parameter: maktx'}), 400
            
        # 3. Panggil Function Module yang baru
        # Sesuai dengan screenshot:
        # FM Name: Z_RFC_GET_MATERIAL_BY_DESC
        # Import Param: IV_MAKTX
        print(f"Memanggil RFC Z_RFC_GET_MATERIAL_BY_DESC dengan IV_MAKTX={description}")
        result = conn.call(
            'Z_RFC_GET_MATERIAL_BY_DESC',
            IV_MAKTX=description
        )

        # 4. Ambil hasil dari tabel 'ET_MATERIAL'
        # Sesuai dengan screenshot:
        # Tables: ET_MATERIAL
        print("Hasil dari RFC diterima. Mengembalikan ET_MATERIAL.")
        material_data = result.get('ET_MATERIAL', [])
        
        # (Opsional) Anda juga bisa log pesan sukses dari SAP
        ev_msg = result.get('EV_RETURN_MSG', '')
        print(f"Pesan balasan SAP: {ev_msg}")

        # 5. Kembalikan data sebagai JSON
        return jsonify(material_data)

    except ValueError as ve:
        # Error otentikasi dari get_credentials()
        return jsonify({'error': str(ve)}), 401
    except (CommunicationError, ABAPApplicationError) as e:
        # Error spesifik dari SAP/RFC
        print(f"SAP Error: {e}")
        return jsonify({'error': f"SAP Error: {str(e)}"}), 500
    except Exception as e:
        # Error umum lainnya
        print(f"An unexpected error occurred: {e}")
        return jsonify({'error': f"An unexpected error occurred: {str(e)}"}), 500
    finally:
        # 6. Tutup koneksi (sama seperti contoh)
        if conn:
            print("Closing SAP connection for search_material_by_desc...")
            conn.close()
            print("SAP connection closed.")

if __name__ == '__main__':
    os.environ['PYTHONHASHSEED'] = '0'
    app.run(host='127.0.0.1', port=8050, debug=True)