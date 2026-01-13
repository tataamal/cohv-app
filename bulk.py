from flask import Flask, request, Response, jsonify, json
from pyrfc import Connection, ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError, RFCError, RFCLibError
import os
from flask_cors import CORS
import mysql.connector
from datetime import datetime
from collections import defaultdict
import logging
import time
import json

app = Flask(__name__)
CORS(app, supports_credentials=True, resources={r"/api/*": {"origins": "*"}})

# --- LOGGING CONFIGURATION ---
from logging.handlers import RotatingFileHandler

# Configure logger
logger = logging.getLogger('bulk_logger')
logger.setLevel(logging.INFO)

# Create file handler
file_handler = RotatingFileHandler('bulk.log', maxBytes=10*1024*1024, backupCount=5)
file_handler.setLevel(logging.INFO)

# Create formatter
formatter = logging.Formatter('[%(asctime)s] %(levelname)s in %(module)s: %(message)s')
file_handler.setFormatter(formatter)

# Add handler to logger
if not logger.handlers:
    logger.addHandler(file_handler)

# Also log to console for development visibility
console_handler = logging.StreamHandler()
console_handler.setFormatter(formatter)
if not logger.handlers:
    logger.addHandler(console_handler)

# untuk handle koneksi ke SAP
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

# untuk mendapatkan username dan password dari header HTTP
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

# =======================================================================
# == 1. HELPER: KONEKSI DATABASE ==
# =======================================================================
def get_mysql_connection():
    """Membuka koneksi baru ke database MySQL."""
    # AMBIL PENGATURAN KONEKSI ANDA DARI ENV/CONFIG
    # (Ini meniru config 'db_*' dari skrip Anda)
    return mysql.connector.connect(
        host=os.getenv('DB_HOST', '192.168.90.105'),
        user=os.getenv('DB_USERNAME', 'python_client'),
        password=os.getenv('DB_PASSWORD', 'singgampang'),
        database=os.getenv('DB_DATABASE', 'cohv_app'),
        autocommit=False
    )

# =======================================================================
# == 2. HELPER: FORMATTING (Diambil dari skrip run_job Anda) ==
# =======================================================================
def format_sap_date_for_db(sap_date_str):
    """Mengubah format tanggal YYYYMMDD dari SAP ke format DATE MySQL (YYYY-MM-DD)."""
    if not sap_date_str or str(sap_date_str).strip() in ['00000000', '']:
        return None
    try:
        return datetime.strptime(str(sap_date_str).strip(), '%Y%m%d').strftime('%Y-%m-%d')
    except ValueError:
        logging.warning(f"Invalid SAP date format for DB: '{sap_date_str}'")
        return None

def format_display_date(sap_date_str):
    """Mengubah format tanggal YYYYMMDD dari SAP menjadi DD-MM-YYYY untuk display (PV1/2/3)."""
    if not sap_date_str or str(sap_date_str).strip() in ['00000000', '']:
        return None
    try:
        return datetime.strptime(str(sap_date_str).strip(), '%Y%m%d').strftime('%d-%m-%Y')
    except ValueError:
        logging.warning(f"Invalid SAP date format for display: '{sap_date_str}'")
        return None

def safe_get_value(row, key):
    """Mengambil nilai dari dictionary dengan aman dan membersihkan spasi."""
    value = row.get(key)
    if value is None:
        return '' # Kembalikan string kosong agar konkatenasi kunci tidak error
    return str(value).strip()

# =======================================================================
# == 3. HELPER: PEMBUAT QUERY SQL ==
# =======================================================================
def build_insert_sql(table_name, columns):
    """Membuat query INSERT standar."""
    cols_sql = ", ".join([f"`{c}`" for c in columns])
    placeholders = ", ".join(["%s"] * len(columns))
    return f"INSERT INTO {table_name} ({cols_sql}) VALUES ({placeholders})"

def build_upsert_sql(table_name, columns):
    """
    Membuat query 'INSERT ... ON DUPLICATE KEY UPDATE'.
    PENTING: Tabel Anda HARUS memiliki PRIMARY atau UNIQUE key.
    """
    cols_sql = ", ".join([f"`{c}`" for c in columns])
    placeholders = ", ".join(["%s"] * len(columns))
    # Membuat bagian "col1 = VALUES(col1), col2 = VALUES(col2), ..."
    update_sql = ", ".join([f"`{col}` = VALUES(`{col}`)" for col in columns])
    return f"INSERT INTO {table_name} ({cols_sql}) VALUES ({placeholders}) ON DUPLICATE KEY UPDATE {update_sql}"

# API untuk refresh PRO secara massal
@app.route('/bulk-refresh', methods=['POST'])
def bulk_refresh_pro():
    """
    Endpoint untuk me-refresh banyak PRO sekaligus.
    Menerima JSON body: { "plant": "PLANT_CODE", "pros": ["PRO1", "PRO2", ...] }
    """
    if not request.is_json:
        return jsonify({"message": "Request harus dalam format JSON"}), 400

    data = request.get_json()
    plant_kode = data.get('plant')
    pro_list = data.get('pros')

    if not plant_kode or not pro_list:
        return jsonify({"message": "Data 'plant' (plant code) dan 'pros' (list) dibutuhkan"}), 400

    if not isinstance(pro_list, list) or len(pro_list) == 0:
        return jsonify({"message": "'pros' harus berupa array/list yang tidak kosong"}), 400

    logger.info(f"--- Starting Bulk Refresh for Plant: {plant_kode} ---")
    logger.info(f"Total PRO received: {len(pro_list)}")
    conn = None
    aggregated_results = {
        "T_DATA": [],
        "T_DATA1": [],
        "T_DATA2": [],
        "T_DATA3": [],
        "T_DATA4": [],
    }
    success_pros = []
    failed_pros = []

    try:
        username, password = get_credentials()
        conn = connect_sap(username, password)

        for pro_number in pro_list:
            try:
                # logger.info(f"Processing PRO: {pro_number}...") 

                result = conn.call('Z_FM_YPPR074Z', 
                                   P_WERKS=plant_kode, 
                                   P_AUFNR=pro_number)
                
                aggregated_results["T_DATA"].extend(result.get('T_DATA', []))
                aggregated_results["T_DATA1"].extend(result.get('T_DATA1', []))
                aggregated_results["T_DATA2"].extend(result.get('T_DATA2', []))
                aggregated_results["T_DATA3"].extend(result.get('T_DATA3', []))
                aggregated_results["T_DATA4"].extend(result.get('T_DATA4', []))
                
                success_pros.append(pro_number)

            except Exception as e_inner:
                logger.warning(f"  ERROR: Failed processing PRO {pro_number}: {str(e_inner)}")
                failed_pros.append({
                    "pro": pro_number,
                    "error": str(e_inner)
                })

    except Exception as e_main:
        logger.error(f"Main Error in bulk_refresh: {str(e_main)}")
        return jsonify({'error': f"Failed to connect/execute SAP: {str(e_main)}"}), 500
    
    finally:
        if conn:
            logger.info("Closing SAP connection for bulk_refresh...")
            conn.close()

    logger.info("--- Bulk Refresh Finished ---")

    return jsonify({
        "message": f"Process finished. {len(success_pros)} success, {len(failed_pros)} failed.",
        "processed_count": len(success_pros),
        "failed_count": len(failed_pros),
        "failed_details": failed_pros,
        "aggregated_data": aggregated_results
    }), 200

@app.route('/save-data', methods=['POST'])
def save_data_to_mysql():
    """
    Menerima data (dari SAP) dari klien, mentransformasi, 
    dan menyimpannya (Refresh) ke MySQL menggunakan logika mapping dari skrip run_job.
    """
    
    # 1. Validasi Input dari JavaScript
    if not request.is_json:
        return jsonify({"message": "Request harus dalam format JSON"}), 400

    data = request.get_json()
    plant_kode = data.get('kode')
    aggregated_data = data.get('aggregated_data')
    pro_list_to_refresh = data.get('pros_to_refresh') # Dari JS Langkah 1

    if not plant_kode or aggregated_data is None or not pro_list_to_refresh:
        return jsonify({"message": "Data 'kode', 'pros_to_refresh', dan 'aggregated_data' dibutuhkan"}), 400
    
    if not isinstance(pro_list_to_refresh, list) or len(pro_list_to_refresh) == 0:
        return jsonify({"message": "'pros_to_refresh' harus berupa list yang tidak kosong"}), 400

    # 2. Ekstrak & Kelompokkan Data (Logika dari skrip run_job Anda)
    logger.info("[Step 2/5] Extracting and grouping data...")
    T_DATA = aggregated_data.get('T_DATA', [])
    T1 = aggregated_data.get('T_DATA1', [])
    T2 = aggregated_data.get('T_DATA2', [])
    T3 = aggregated_data.get('T_DATA3', [])
    T4 = aggregated_data.get('T_DATA4', [])

    t2_grouped = defaultdict(list)
    for row in T2:
        key = f"{safe_get_value(row, 'KUNNR')}-{safe_get_value(row, 'NAME1')}"
        t2_grouped[key].append(row)

    t3_grouped = defaultdict(list)
    for row in T3:
        key = f"{safe_get_value(row, 'KDAUF')}-{safe_get_value(row, 'KDPOS')}"
        t3_grouped[key].append(row)

    t1_grouped = defaultdict(list)
    for row in T1:
        key = safe_get_value(row, 'AUFNR')
        if key: t1_grouped[key].append(row)

    t4_grouped = defaultdict(list)
    for row in T4:
        key = safe_get_value(row, 'AUFNR')
        if key: t4_grouped[key].append(row)
    logger.info("   -> Data grouping complete.")

    conn = None
    cursor = None
    try:
        conn = get_mysql_connection()
        cursor = conn.cursor()
        conn.start_transaction()

        # ========================================================
        # == 3. [MODIFIKASI] HAPUS DATA ANAK YANG LAMA ==
        # ========================================================
        logger.info(f"[Step 3/5] Deleting old child data for {len(pro_list_to_refresh)} PRO...")
        pro_placeholders = ', '.join(['%s'] * len(pro_list_to_refresh))
        
        cursor.execute(f"DELETE FROM production_t_data1 WHERE AUFNR IN ({pro_placeholders})", tuple(pro_list_to_refresh))
        # logging.info(f"   -> TData1 Deleted: {cursor.rowcount}")
        
        cursor.execute(f"DELETE FROM production_t_data4 WHERE AUFNR IN ({pro_placeholders})", tuple(pro_list_to_refresh))
        # logging.info(f"   -> TData4 Deleted: {cursor.rowcount}")

        cursor.execute(f"DELETE FROM production_t_data3 WHERE AUFNR IN ({pro_placeholders})", tuple(pro_list_to_refresh))
        # logging.info(f"   -> TData3 Deleted: {cursor.rowcount}")
        
        # PENTING: Kita TIDAK menghapus T_DATA atau T_DATA2

        # ========================================================
        # == 4. DEFINISI KOLOM (Dari skrip run_job Anda) ==
        # ========================================================
        # Ini adalah daftar kolom dari pernyataan INSERT di skrip Anda
        
        # 9 Kolom
        T_DATA_COLS = ['WERKSX', 'KDAUF', 'KDPOS', 'MATNR', 'MAKTX', 'EDATU', 'KUNNR', 'NAME1', 'MANDT'] 
        
        # 9 Kolom
        T_DATA2_COLS = ['MANDT', 'KDAUF', 'KDPOS', 'MATFG', 'MAKFG', 'EDATU', 'WERKSX', 'KUNNR', 'NAME1'] 
        
        # 44 Kolom
        T_DATA3_COLS = ['MANDT', 'ARBPL', 'ORDERX', 'PWWRK', 'KTEXT', 'ARBID', 'VERID', 'KDAUF', 'KDPOS', 'AUFNR', 'NAME1', 'KUNNR', 'PLNUM', 'STATS', 'DISPO', 'MATNR', 'MTART', 'MAKTX', 'VORNR', 'STEUS', 'AUART', 'MEINS', 'MATKL', 'PSMNG', 'WEMNG', 'MGVRG2', 'LMNGA', 'P1', 'MENGE2', 'VGW01', 'VGE01', 'CPCTYX', 'DTIME', 'DDAY', 'SSSLD', 'SSAVD', 'GLTRP', 'GSTRP', 'MATFG', 'MAKFG', 'CATEGORY', 'WERKSX', 'STATS2']
        
        # 46 Kolom
        T_DATA1_COLS = ['MANDT', 'ARBPL', 'PWWRK', 'KTEXT', 'WERKSX', 'ARBID', 'KAPID', 'KAPAZ', 'VERID', 'KDAUF', 'KDPOS', 'AUFNR', 'PLNUM', 'STATS', 'DISPO', 'MATNR', 'MTART', 'MAKTX', 'VORNR', 'STEUS', 'AUART', 'MEINS', 'MATKL', 'PSMNG', 'WEMNG', 'MGVRG2', 'LMNGA', 'P1', 'MENGE2', 'VGW01', 'VGE01', 'CPCTYX', 'DTIME', 'DDAY', 'SSSLD', 'SSAVD', 'MATFG', 'MAKFG', 'CATEGORY', 'ORDERX', 'STATS2', 'PV1', 'PV2', 'PV3', 'SSAVZ', 'SSSLZ']
        
        # 23 Kolom
        T_DATA4_COLS = ['MANDT', 'RSNUM', 'RSPOS', 'VORNR', 'KDAUF', 'KDPOS', 'AUFNR', 'PLNUM', 'STATS', 'DISPO', 'MATNR', 'MAKTX', 'MEINS', 'BAUGR', 'WERKSX', 'BDMNG', 'KALAB', 'VMENG', 'SOBSL', 'BESKZ', 'LTEXT', 'LGORT', 'OUTSREQ']
        
        
        # ========================================================
        # == 5. [MODIFIKASI] LAKUKAN UPSERT INDUK & INSERT ANAK ==
        # ========================================================
        logger.info("[Step 5/5] Preparing staged data for Upsert/Insert...")

        tdata_to_upsert = []
        tdata2_to_upsert = []
        tdata1_to_insert = []
        tdata3_to_insert = []
        tdata4_to_insert = []

        # Loop pemetaan data (Logika dari skrip run_job Anda)
        for t_data_row in T_DATA:
            kunnr = safe_get_value(t_data_row, 'KUNNR')
            name1 = safe_get_value(t_data_row, 'NAME1')
            if not kunnr and not name1: continue
            
            # --- T_DATA ---
            mapped_row = {
                'WERKSX': plant_kode, 'KDAUF': safe_get_value(t_data_row, 'KDAUF'),
                'KDPOS': safe_get_value(t_data_row, 'KDPOS'), 'MATNR': safe_get_value(t_data_row, 'MATNR'),
                'MAKTX': safe_get_value(t_data_row, 'MAKTX'),
                'EDATU': format_sap_date_for_db(t_data_row.get('EDATU')), 'KUNNR': kunnr,
                'NAME1': name1, 'MANDT': safe_get_value(t_data_row, 'MANDT')
            }
            tdata_to_upsert.append(tuple(mapped_row.get(col) for col in T_DATA_COLS))

            # --- T_DATA2 ---
            key_t2 = f"{kunnr}-{name1}"
            children_t2 = t2_grouped.get(key_t2, [])
            for t2_row in children_t2:
                kdauf_t2 = safe_get_value(t2_row, 'KDAUF')
                kdpos_t2 = safe_get_value(t2_row, 'KDPOS')
                mapped_row = {
                    'MANDT': safe_get_value(t2_row, 'MANDT'), 'KDAUF': kdauf_t2, 'KDPOS': kdpos_t2,
                    'MATFG': safe_get_value(t2_row, 'MATFG'), 'MAKFG': safe_get_value(t2_row, 'MAKFG'),
                    'EDATU': format_sap_date_for_db(t2_row.get('EDATU')), 'WERKSX': plant_kode,
                    'KUNNR': kunnr, 'NAME1': name1
                }
                tdata2_to_upsert.append(tuple(mapped_row.get(col) for col in T_DATA2_COLS))

                # --- T_DATA3 ---
                key_t3 = f"{kdauf_t2}-{kdpos_t2}"
                children_t3 = t3_grouped.get(key_t3, [])
                for t3_row in children_t3:
                    aufnr_t3 = safe_get_value(t3_row, 'AUFNR')
                    mapped_row = {
                        'MANDT': safe_get_value(t3_row, 'MANDT'), 'ARBPL': safe_get_value(t3_row, 'ARBPL'),
                        'ORDERX': safe_get_value(t3_row, 'ORDERX'), 'PWWRK': safe_get_value(t3_row, 'PWWRK'),
                        'KTEXT': safe_get_value(t3_row, 'KTEXT'), 'ARBID': safe_get_value(t3_row, 'ARBID'),
                        'VERID': safe_get_value(t3_row, 'VERID'), 'KDAUF': kdauf_t2, 'KDPOS': kdpos_t2,
                        'AUFNR': aufnr_t3, 'NAME1': safe_get_value(t3_row, 'NAME1'),
                        'KUNNR': safe_get_value(t3_row, 'KUNNR'), 'PLNUM': safe_get_value(t3_row, 'PLNUM'),
                        'STATS': safe_get_value(t3_row, 'STATS'), 'DISPO': safe_get_value(t3_row, 'DISPO'),
                        'MATNR': safe_get_value(t3_row, 'MATNR'), 'MTART': safe_get_value(t3_row, 'MTART'),
                        'MAKTX': safe_get_value(t3_row, 'MAKTX'), 'VORNR': safe_get_value(t3_row, 'VORNR'),
                        'STEUS': safe_get_value(t3_row, 'STEUS'), 'AUART': safe_get_value(t3_row, 'AUART'),
                        'MEINS': safe_get_value(t3_row, 'MEINS'), 'MATKL': safe_get_value(t3_row, 'MATKL'),
                        'PSMNG': t3_row.get('PSMNG'), 'WEMNG': t3_row.get('WEMNG'),
                        'MGVRG2': t3_row.get('MGVRG2'), 'LMNGA': t3_row.get('LMNGA'),
                        'P1': t3_row.get('P1'), 'MENGE2': t3_row.get('MENGE2'), 'VGW01': t3_row.get('VGW01'),
                        'VGE01': t3_row.get('VGE01'), 'CPCTYX': safe_get_value(t3_row, 'CPCTYX'),
                        'DTIME': t3_row.get('DTIME'), 'DDAY': t3_row.get('DDAY'),
                        'SSSLD': format_sap_date_for_db(t3_row.get('SSSLD')),
                        'SSAVD': format_sap_date_for_db(t3_row.get('SSAVD')),
                        'GLTRP': format_sap_date_for_db(t3_row.get('GLTRP')),
                        'GSTRP': format_sap_date_for_db(t3_row.get('GSTRP')),
                        'MATFG': safe_get_value(t3_row, 'MATFG'), 'MAKFG': safe_get_value(t3_row, 'MAKFG'),
                        'CATEGORY': safe_get_value(t3_row, 'CATEGORY'), 'WERKSX': plant_kode,
                        'STATS2': safe_get_value(t3_row, 'STATS2')
                    }
                    tdata3_to_insert.append(tuple(mapped_row.get(col) for col in T_DATA3_COLS))

                    key_t1_t4 = aufnr_t3
                    if not key_t1_t4: continue

                    # --- T_DATA1 ---
                    children_t1 = t1_grouped.get(key_t1_t4, [])
                    for t1_row in children_t1:
                        sssl1 = format_display_date(t1_row.get('SSSLDPV1'))
                        sssl2 = format_display_date(t1_row.get('SSSLDPV2'))
                        sssl3 = format_display_date(t1_row.get('SSSLDPV3'))
                        
                        parts_pv1 = [p for p in [safe_get_value(t1_row, 'ARBPL1').upper(), sssl1] if p]
                        pv1 = ' - '.join(parts_pv1) if parts_pv1 else None
                        
                        parts_pv2 = [p for p in [safe_get_value(t1_row, 'ARBPL2').upper(), sssl2] if p]
                        pv2 = ' - '.join(parts_pv2) if parts_pv2 else None
                        
                        parts_pv3 = [p for p in [safe_get_value(t1_row, 'ARBPL3').upper(), sssl3] if p]
                        pv3 = ' - '.join(parts_pv3) if parts_pv3 else None
                        
                        mapped_row = {
                            'MANDT': safe_get_value(t1_row, 'MANDT'), 'ARBPL': safe_get_value(t1_row, 'ARBPL'),
                            'PWWRK': safe_get_value(t1_row, 'PWWRK'), 'KTEXT': safe_get_value(t1_row, 'KTEXT'),
                            'WERKSX': plant_kode, 'ARBID': safe_get_value(t1_row, 'ARBID'),
                            'KAPID': safe_get_value(t1_row, 'KAPID'), 'KAPAZ': safe_get_value(t1_row, 'KAPAZ'),
                            'VERID': safe_get_value(t1_row, 'VERID'), 'KDAUF': safe_get_value(t1_row, 'KDAUF'),
                            'KDPOS': safe_get_value(t1_row, 'KDPOS'), 'AUFNR': aufnr_t3,
                            'PLNUM': safe_get_value(t1_row, 'PLNUM'), 'STATS': safe_get_value(t1_row, 'STATS'),
                            'DISPO': safe_get_value(t1_row, 'DISPO'), 'MATNR': safe_get_value(t1_row, 'MATNR'),
                            'MTART': safe_get_value(t1_row, 'MTART'), 'MAKTX': safe_get_value(t1_row, 'MAKTX'),
                            'VORNR': safe_get_value(t1_row, 'VORNR'), 'STEUS': safe_get_value(t1_row, 'STEUS'),
                            'AUART': safe_get_value(t1_row, 'AUART'), 'MEINS': safe_get_value(t1_row, 'MEINS'),
                            'MATKL': safe_get_value(t1_row, 'MATKL'), 'PSMNG': t1_row.get('PSMNG'),
                            'WEMNG': t1_row.get('WEMNG'), 'MGVRG2': t1_row.get('MGVRG2'),
                            'LMNGA': t1_row.get('LMNGA'), 'P1': t1_row.get('P1'), 'MENGE2': t1_row.get('MENGE2'),
                            'VGW01': t1_row.get('VGW01'), 'VGE01': t1_row.get('VGE01'),
                            'CPCTYX': safe_get_value(t1_row, 'CPCTYX'), 'DTIME': t1_row.get('DTIME'),
                            'DDAY': t1_row.get('DDAY'), 'SSSLD': format_sap_date_for_db(t1_row.get('SSSLD')),
                            'SSAVD': format_sap_date_for_db(t1_row.get('SSAVD')), 'MATFG': safe_get_value(t1_row, 'MATFG'),
                            'MAKFG': safe_get_value(t1_row, 'MAKFG'), 'CATEGORY': safe_get_value(t1_row, 'CATEGORY'),
                            'ORDERX': safe_get_value(t1_row, 'ORDERX'), 'STATS2': safe_get_value(t1_row, 'STATS2'),
                            'PV1': pv1, 'PV2': pv2, 'PV3': pv3,
                            'SSAVZ': t1_row.get('SSAVZ'), 'SSSLZ': t1_row.get('SSSLZ')
                        }
                        tdata1_to_insert.append(tuple(mapped_row.get(col) for col in T_DATA1_COLS))

                    # --- T_DATA4 ---
                    children_t4 = t4_grouped.get(key_t1_t4, [])
                    for t4_row in children_t4:
                        mapped_row = {
                            'MANDT': safe_get_value(t4_row, 'MANDT'), 'RSNUM': safe_get_value(t4_row, 'RSNUM'),
                            'RSPOS': safe_get_value(t4_row, 'RSPOS'), 'VORNR': safe_get_value(t4_row, 'VORNR'),
                            'KDAUF': safe_get_value(t4_row, 'KDAUF'), 'KDPOS': safe_get_value(t4_row, 'KDPOS'),
                            'AUFNR': aufnr_t3, 'PLNUM': safe_get_value(t4_row, 'PLNUM'),
                            'STATS': safe_get_value(t4_row, 'STATS'), 'DISPO': safe_get_value(t4_row, 'DISPO'),
                            'MATNR': safe_get_value(t4_row, 'MATNR'), 'MAKTX': safe_get_value(t4_row, 'MAKTX'),
                            'MEINS': safe_get_value(t4_row, 'MEINS'), 'BAUGR': safe_get_value(t4_row, 'BAUGR'),
                            'WERKSX': plant_kode, 'BDMNG': t4_row.get('BDMNG'), 'KALAB': t4_row.get('KALAB'),
                            'VMENG': t4_row.get('VMENG'), 'SOBSL': safe_get_value(t4_row, 'SOBSL'),
                            'BESKZ': safe_get_value(t4_row, 'BESKZ'), 'LTEXT': safe_get_value(t4_row, 'LTEXT'),
                            'LGORT': safe_get_value(t4_row, 'LGORT'), 'OUTSREQ': safe_get_value(t4_row, 'OUTSREQ')
                        }
                        tdata4_to_insert.append(tuple(mapped_row.get(col) for col in T_DATA4_COLS))

        logger.info(f"   -> Data ready: TData(Upsert: {len(tdata_to_upsert)}), TData2(Upsert: {len(tdata2_to_upsert)})")
        logger.info(f"   -> Data ready: TData1(Insert: {len(tdata1_to_insert)}), TData3(Insert: {len(tdata3_to_insert)}), TData4(Insert: {len(tdata4_to_insert)})")

        # Eksekusi UPSERT untuk Induk (T_DATA, T_DATA2)
        if tdata_to_upsert:
            sql_upsert_tdata = build_upsert_sql('production_t_data', T_DATA_COLS)
            cursor.executemany(sql_upsert_tdata, tdata_to_upsert)
        
        if tdata2_to_upsert:
            sql_upsert_tdata2 = build_upsert_sql('production_t_data2', T_DATA2_COLS)
            cursor.executemany(sql_upsert_tdata2, tdata2_to_upsert)

        # Eksekusi INSERT untuk Anak (T1, T3, T4)
        if tdata3_to_insert:
            cursor.executemany(build_insert_sql('production_t_data3', T_DATA3_COLS), tdata3_to_insert)
        if tdata1_to_insert:
            cursor.executemany(build_insert_sql('production_t_data1', T_DATA1_COLS), tdata1_to_insert)
        if tdata4_to_insert:
            cursor.executemany(build_insert_sql('production_t_data4', T_DATA4_COLS), tdata4_to_insert)
        
        conn.commit()
        logger.info(" -> Data successfully refreshed to MySQL.")
        
        return jsonify({
            "message": "Data berhasil di-refresh ke MySQL.",
            "counts": {
                "t_data_upserted": len(tdata_to_upsert),
                "t_data2_upserted": len(tdata2_to_upsert),
                "t_data1_inserted": len(tdata1_to_insert),
                "t_data3_inserted": len(tdata3_to_insert),
                "t_data4_inserted": len(tdata4_to_insert),
            }
        }), 200

    except mysql.connector.Error as err:
        logger.error(f"MySQL Error in save_data: {err.msg}", exc_info=True)
        if conn: conn.rollback()
        return jsonify({"message": f"MySQL Error: {err.msg}", "errno": err.errno}), 500
    except Exception as e:
        logger.error(f"Python Error in save_data: {e}", exc_info=True)
        if conn: conn.rollback()
        return jsonify({"message": f"Terjadi kesalahan: {str(e)}"}), 500
    finally:
        if cursor: 
            try: cursor.close() 
            except: pass
        if conn and conn.is_connected():
            try: conn.close()
            except: pass
            logger.info("MySQL connection closed.")

@app.route('/bulk-schedule-pro', methods=['POST'])
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
                
                # logger.info(f"RAW BAPI Result for {pro_number}: {bapi_result}")

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
                    results["error_details"].append({ "pro_number": pro_number, "message": f"Failed to schedule PRO {pro_number}", "sap_response": bapi_result })

            except ABAPApplicationError as bapi_err:
                logger.error(f"BAPI Error on PRO {pro_number}: {bapi_err}")
                results["error_details"].append({ "pro_number": pro_number, "message": f"BAPI Error scheduling PRO {pro_number}", "sap_response": str(bapi_err) })

        if results["error_details"]:
            return jsonify(results), 400
        else:
            return jsonify(results), 200

    except (CommunicationError, ABAPApplicationError) as sap_err:
        logger.error(f"SAP RFC Error: {sap_err}")
        return jsonify({"error": f"Failed to connect or execute BAPI: {sap_err}"}), 502
    except Exception as e:
        logger.error(f"Unexpected error in bulk_schedule: {e}")
        return jsonify({"error": f"Unexpected server error: {str(e)}"}), 500
    finally:
        if conn:
            logger.info("Closing SAP connection for bulk-schedule-pro...")
            conn.close()
            logger.info("SAP connection closed.")
			
@app.route('/bulk-readpp-pro', methods=['POST'])
def process_read_pp():
    conn = None
    try:
        username, password = get_credentials()
        if not username or not password:
            return jsonify({"error": "Missing SAP credentials."}), 401
        data = request.get_json()
        if not data or 'pro_list' not in data or not isinstance(data['pro_list'], list):
            return jsonify({"error": "Invalid input. 'pro_list' list required."}), 400
        pro_list = data['pro_list']
        if not pro_list:
            return jsonify({"error": "'pro_list' cannot be empty"}), 400
        results = {
            "success_details": [],
            "error_details": []
        }
        order_data_input = {'EXPLODE_NEW': 'X'}
        conn = connect_sap(username, password)
        for pro_number in pro_list:
            # logger.info(f"Processing Read PP for PRO {pro_number}...")
            try:
                bapi_result = conn.call(
                    'BAPI_PRODORD_CHANGE',
                    NUMBER=pro_number,
                    ORDERDATA=order_data_input
                )
                # logger.info(f"RAW BAPI Result for {pro_number}")
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
                    })
                else:
                    results["error_details"].append({
                        "pro_number": pro_number,
                        "message": f"Failed Read PP for PRO {pro_number}",
                        "sap_response": bapi_result
                    })

            except ABAPApplicationError as bapi_err:
                logger.error(f"BAPI Error on PRO {pro_number}: {bapi_err}")
                results["error_details"].append({
                    "pro_number": pro_number,
                    "message": f"BAPI Error Read PP for PRO {pro_number}",
                    "sap_response": str(bapi_err)
                })
        if results["error_details"]:
            return jsonify(results), 400
        else:
            return jsonify(results), 200

    except (CommunicationError, ABAPRuntimeError) as conn_err:
        logger.error(f"Connection/Runtime Error: {conn_err}")
        return jsonify({"error": f"SAP Connection Error: {conn_err}"}), 502
    except Exception as e:
        logger.error(f"Unexpected error in bulk_readpp: {e}")
        return jsonify({"error": f"Unexpected server error: {str(e)}"}), 500
    finally:
        if conn:
            logger.info("Closing SAP connection for bulk-readpp-pro...")
            conn.close()
            print("SAP connection closed.")

@app.route('/bulk-change-pv', methods=['POST'])
def change_bulk_prod_version():
    conn = None
    try:
        username, password = get_credentials()
        data = request.get_json()
        pro_list = data.get('pro_list')
        verid = data.get('PROD_VERSION')
        if not pro_list or not isinstance(pro_list, list) or len(pro_list) == 0:
            return jsonify({'error': "Invalid input. 'pro_list' required."}), 400
        if not verid:
            return jsonify({'error': "Invalid input. 'PROD_VERSION' required."}), 400
        results = {
            "success_details": [],
            "error_details": []
        }
        conn = connect_sap(username, password)
        for aufnr in pro_list:
            logger.info(f"Processing Change PV for PRO {aufnr} to Version {verid}...")

            try:
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
                    raise ABAPApplicationError(f"SAP BAPI Error: {error_string}")
                
                # logger.info(f"  > PRO {aufnr}: Change PV BAPI success, committing...")
                conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
                
                time.sleep(1) # Reduced sleep
                results["success_details"].append({
                    "pro_number": aufnr,
                    "sap_return": sap_return_list
                })

            except (ABAPApplicationError, ABAPRuntimeError, CommunicationError) as e:
                # Tangkap error untuk PRO ini, catat, dan Lanjutkan loop
                logger.warning(f"❌ Error processing PRO {aufnr}: {str(e)}")
                results["error_details"].append({
                    "pro_number": aufnr,
                    "message": f"Failed change PV for PRO {aufnr}",
                    "sap_response": str(e)
                })

        # 6. Setelah loop selesai, kembalikan hasil
        logger.info("Bulk Change PV process finished.")
        if results["error_details"]:
            return jsonify(results), 400 # Partial success
        else:
            return jsonify(results), 200 # Full success

    except (CommunicationError, ABAPRuntimeError) as conn_err:
        # Error koneksi awal
        logger.error(f"Connection/Runtime Error: {conn_err}")
        return jsonify({"error": f"SAP Connection Error: {conn_err}"}), 502
    except Exception as e:
        logger.error(f"Unexpected error in bulk_change_pv: {e}")
        return jsonify({'error': f"Unexpected server error: {str(e)}"}), 500
    finally:
        if conn:
            logger.info("Closing SAP connection for bulk-change-pv...")
            conn.close()
            logger.info("SAP connection closed.")
			
@app.route('/change_quantity', methods=['POST'])
def change_quantity_stream():
    try:
        data = request.get_json()
        username, password = get_credentials() 
    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        return jsonify({'error': f'Bad request: {str(e)}'}), 400
    def generate_responses(request_data, user_creds):
        conn = None
        try:
            creds_user = user_creds[0]
            creds_pass = user_creds[1]
            conn = connect_sap(creds_user, creds_pass) 

            orders = request_data.get('orders')

            if not orders or not isinstance(orders, list):
                error_response = {'status': 'error', 'message': 'Input harus berupa list JSON dengan key "orders"'}
                yield json.dumps(error_response) + '\n'
                return

            for order in orders:
                aufnr = order.get('AUFNR')
                quantity = order.get('QUANTITY')
                
                result_data = {'AUFNR': aufnr, 'QUANTITY': quantity}

                if not aufnr or not quantity:
                    result_data['status'] = 'error'
                    result_data['message'] = 'AUFNR dan QUANTITY Wajib diisi'
                    yield json.dumps(result_data) + '\n'
                    continue 
                
                # logger.info(f"Processing AUFNR: {aufnr} -> Value Quantity: {quantity}")

                try:
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
                        logger.warning(f"BAPI Error for {aufnr}: {error_string}")
                        conn.call('BAPI_TRANSACTION_ROLLBACK')
                        
                        result_data['status'] = 'error'
                        result_data['message'] = f'SAP BAPI Error:\n{error_string}'
                        result_data['sap_return'] = sap_return_list
                    
                    else:
                        # logger.info(f"BAPI_TRANSACTION_COMMIT for {aufnr}...")
                        conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
                        
                        result_data['status'] = 'success'
                        result_data['message'] = 'Quantity changed successfully.'
                        result_data['sap_return'] = sap_return_list

                except Exception as e:
                    logger.error(f"Exception for {aufnr}: {str(e)}")
                    conn.call('BAPI_TRANSACTION_ROLLBACK') 
                    result_data['status'] = 'error'
                    result_data['message'] = f'Python Exception: {str(e)}'

                yield json.dumps(result_data) + '\n'

        except Exception as e: 
            logger.error(f"Fatal Exception in change_quantity: {str(e)}")
            yield json.dumps({'status': 'fatal_error', 'message': str(e)}) + '\n'
        finally:
            if conn:
                # logger.info("Closing SAP connection...")
                conn.close()
                logger.info("SAP connection closed (change_quantity).")

    creds_tuple = (username, password)
    return Response(generate_responses(data, creds_tuple), mimetype='application/x-json-stream')
	
@app.route('/stream_teco_orders', methods=['POST'])
def stream_teco_orders():
    try:
        data = request.get_json()
        username, password = get_credentials()
        creds_tuple = (username, password)
    except ValueError as ve:
        return jsonify({'error': str(ve)}), 401
    except Exception as e:
        return jsonify({'error': f'Bad request: {str(e)}'}), 400
    def generate_teco_responses(request_data, user_creds):
        conn = None
        try:
            conn = connect_sap(user_creds[0], user_creds[1]) 
            pro_list = request_data.get('pro_list')
            if not pro_list or not isinstance(pro_list, list):
                yield json.dumps({'status': 'fatal_error', 'message': 'Input harus berupa JSON list dengan key "pro_list"'}) + '\n'
                return
            logger.info(f"Received {len(pro_list)} PRO for TECO (Streaming)...")

            for aufnr in pro_list:

                result_data = {'AUFNR': aufnr}

                if not aufnr:
                    result_data['status'] = 'error'
                    result_data['message'] = 'Empty PRO number detected.'
                    yield json.dumps(result_data) + '\n'
                    continue
                
                try:
                    # logger.info(f"Processing TECO for: {aufnr}...")
                    result_teco = conn.call(
                        'BAPI_PRODORD_COMPLETE_TECH',
                        SCOPE_COMPL_TECH='1',
                        WORK_PROCESS_GROUP='COWORK_BAPI',
                        WORK_PROCESS_MAX=99,
                        ORDERS=[{'ORDER_NUMBER': aufnr}]
                    )

                    has_error = False
                    error_msg = "Unknown BAPI error."
                    
                    if 'DETAIL_RETURN' in result_teco and result_teco['DETAIL_RETURN']:
                        for message in result_teco['DETAIL_RETURN']:
                            if message['TYPE'] in ['E', 'A']: # E = Error, A = Abort
                                error_msg = f"SAP Error: {message['MESSAGE']}"
                                has_error = True
                                break # One error is enough

                    if has_error:
                        logger.warning(f"Failed TECO {aufnr}: {error_msg}")
                        conn.call('BAPI_TRANSACTION_ROLLBACK') # Rollback this item
                        result_data['status'] = 'error'
                        result_data['message'] = error_msg
                        result_data['sap_response'] = result_teco
                    else:
                        # Success, Commit
                        # logger.info(f"Success TECO {aufnr}. Committing...")
                        conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
                        result_data['status'] = 'success'
                        result_data['message'] = 'TECO successful.'
                        result_data['sap_response'] = result_teco

                except Exception as e_inner:
                    # Catch Python/RFC error per item
                    logger.error(f"Exception processing {aufnr}: {str(e_inner)}")
                    conn.call('BAPI_TRANSACTION_ROLLBACK')
                    result_data['status'] = 'error'
                    result_data['message'] = f'Python Exception: {str(e_inner)}'
                
                # 5. YIELD RESULT
                yield json.dumps(result_data) + '\n'

        except Exception as e_fatal:
            # Fatal error
            logger.error(f"Fatal Exception in stream_teco: {str(e_fatal)}")
            yield json.dumps({'status': 'fatal_error', 'message': str(e_fatal)}) + '\n'
        finally:
            if conn:
                try: conn.close()
                except: pass
                logger.info("SAP connection (TECO stream) closed.")
    return Response(generate_teco_responses(data, creds_tuple), mimetype='application/x-json-stream')

@app.route('/delete-data', methods=['POST', 'OPTIONS'])
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
        response.headers.add('Access-Control-Allow-Headers', 'Content-Type,Authorization')
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
            logger.info("DEBUG: Received empty body or non-JSON.")
            return jsonify({"message": "Request must be JSON and not empty"}), 400
        
        pro_list = data.get('pro_list')

        if not pro_list or not isinstance(pro_list, list):
            return jsonify({'error': 'Input must be JSON list with key "pro_list"'}), 400

        # --- KONEKSI DATABASE (Menggunakan Helper) ---
        try:
            conn = get_mysql_connection()
            cursor = conn.cursor()
        except Exception as e:
            logger.error(f"Failed to open DB connection: {e}")
            return jsonify({'error': f'Database Connection Failed: {str(e)}'}), 500

        success_count = 0
        error_details = []
        
        if len(pro_list) > 0:
            logger.info(f"INFO: Starting deletion for {len(pro_list)} PRO...")

        for aufnr in pro_list:
            if not aufnr:
                error_details.append({'pro_number': 'EMPTY', 'message': 'Empty PRO number detected.'})
                continue
            
            try:
                deleted_rows_total = 0
                for table in TABLES_TO_DELETE:
                    query = f"DELETE FROM {table} WHERE AUFNR = %s"
                    
                    # Eksekusi Query
                    cursor.execute(query, (aufnr,))
                    
                    # mysql.connector menyimpan jumlah baris di property rowcount
                    deleted_rows = cursor.rowcount 
                    deleted_rows_total += deleted_rows
                
                conn.commit()
                # logger.info(f"Success delete {aufnr}. Total {deleted_rows_total} rows deleted.")
                success_count += 1

            except mysql.connector.Error as err:
                conn.rollback()
                logger.error(f"MySQL Error deleting {aufnr}: {err}")
                error_details.append({'pro_number': aufnr, 'message': str(err)})
            except Exception as e_inner:
                conn.rollback()
                logger.error(f"General Error deleting {aufnr}: {str(e_inner)}")
                error_details.append({'pro_number': aufnr, 'message': str(e_inner)})

        logger.info(f"Deletion complete. Success: {success_count}, Failed: {len(error_details)}")

        return jsonify({
            'message': f"MySQL deletion complete. {success_count} success, {len(error_details)} failed.",
            'success_count': success_count,
            'error_details': error_details
        }), 200

    except Exception as e:
        logger.error(f"Fatal Exception in delete-data: {str(e)}")
        if conn and conn.is_connected():
            conn.rollback()
        return jsonify({'error': f'Internal server error: {str(e)}'}), 500
    finally:
        # --- CLEANUP ---
        if cursor: 
            try: cursor.close()
            except: pass
        if conn: 
            try: conn.close()
            except: pass

if __name__ == '__main__':
    logger.info("=========== KONEKSI FLASK COHV BULK =============")
    app.run(host='0.0.0.0', port=5000, debug=True)