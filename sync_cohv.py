import os
import sys
import logging
import schedule
import time
import argparse
import threading
from datetime import datetime
from pyrfc import Connection, ABAPApplicationError, CommunicationError
import pymysql
from collections import defaultdict

# --- Konfigurasi Logging ---
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.StreamHandler(sys.stdout),
        logging.FileHandler('error.log', mode='a')
    ]
)

def load_config():
    """Mendefinisikan konfigurasi langsung di dalam skrip."""
    
    config = {
        'sap_user': 'auto_email',
        'sap_passwd': '11223344',
        'sap_ashost': '192.168.254.154',
        'sap_sysnr': '01',
        'sap_client': '300',

        'db_host': '127.0.0.1',
        'db_user': 'root',
        'db_passwd': 'root', 
        'db_name': 'cohv_app',

        'plants': [
            '1001', '1002', '1003', '1004', '1005', '1006', '1007', '1008',
            '1009', '1010', '1011', '1012', '1201', '2001', '2002', '2003',
            '2004', '2005', '2006', '2007', '2008', '2009', '2010', '2011',
            '2012', '2013', '2014', '2015', '2016', '2017', '2018', '2019',
            '2020', '2021', '2022', '2023', '2024', '2025', '2026', '2027',
            '3001', '3002', '3003', '3004', '3007', '3008', '3009', '3010',
            '3011', '3012', '3013', '3014', '3015', '3016', '3017', '3018',
            '3019', '3020', '3021', '3022'
        ]
    }
    
    for key, value in config.items():
        if value is None and key not in ['plants', 'db_passwd']:
            logging.error(f"Konfigurasi '{key}' kosong. Harap isi nilainya di dalam skrip.")
            sys.exit(1)

    if not config['plants']:
        logging.warning("Tidak ada plant yang dikonfigurasi di dalam skrip (variabel 'plants').")
            
    return config

def connect_sap(config):
    """Membangun koneksi ke sistem SAP."""
    try:
        conn = Connection(
            user=config['sap_user'],
            passwd=config['sap_passwd'],
            ashost=config['sap_ashost'],
            sysnr=config['sap_sysnr'],
            client=config['sap_client'],
            lang='EN'
        )
        logging.info("   -> Berhasil terhubung ke SAP.")
        return conn
    except (CommunicationError, ABAPApplicationError) as e:
        logging.error(f"   -> Gagal terhubung ke SAP: {e}")
        return None

def connect_db(config):
    """Membangun koneksi ke database MySQL menggunakan PyMySQL."""
    try:
        cnx = pymysql.connect(
            host=config['db_host'],
            user=config['db_user'],
            password=config['db_passwd'],
            database=config['db_name'],
            charset='utf8mb4',
            autocommit=False  # Manual commit untuk transaksi
        )
        logging.info("   -> Berhasil terhubung ke database MySQL dengan PyMySQL.")
        return cnx
    except pymysql.Error as err:
        logging.error(f"   -> Gagal terhubung ke database MySQL: {err}")
        return None

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
    """Mengubah format tanggal YYYYMMDD dari SAP menjadi DD-MM-YYYY untuk display (Sesuai Controller Laravel)."""
    if not sap_date_str or str(sap_date_str).strip() in ['00000000', '']:
        return None
    try:
        return datetime.strptime(str(sap_date_str).strip(), '%Y%m%d').strftime('%d-%m-%Y')
    except ValueError:
        logging.warning(f"Invalid SAP date format for display: '{sap_date_str}'")
        return None

def normalize_payload(result):
    """Normalisasi payload persis seperti di controller Laravel."""
    t_data, t_data1, t_data2, t_data3, t_data4 = [], [], [], [], []

    data_blocks = result.get('results', [result])

    for res in data_blocks:
        if 'T_DATA' in res and isinstance(res['T_DATA'], list): t_data.extend(res['T_DATA'])
        if 'T_DATA1' in res and isinstance(res['T_DATA1'], list): t_data1.extend(res['T_DATA1'])
        if 'T_DATA2' in res and isinstance(res['T_DATA2'], list): t_data2.extend(res['T_DATA2'])
        if 'T_DATA3' in res and isinstance(res['T_DATA3'], list): t_data3.extend(res['T_DATA3'])
        if 'T_DATA4' in res and isinstance(res['T_DATA4'], list): t_data4.extend(res['T_DATA4'])

    return t_data, t_data1, t_data2, t_data3, t_data4

def safe_get_value(row, key):
    """Mengambil nilai dari dictionary dengan aman dan membersihkan spasi."""
    value = row.get(key)
    if value is None:
        return '' # Kembalikan string kosong agar konkatenasi kunci tidak error
    return str(value).strip()

def process_plant_data(plant_code, config):
    logging.info(f"--- Memulai proses untuk Plant: {plant_code} ---")
    
    sap_conn = connect_sap(config)
    if not sap_conn: return
    try:
        logging.info(f"   -> Memanggil RFC 'Z_FM_YPPR074Z' untuk Plant: {plant_code}")
        result = sap_conn.call('Z_FM_YPPR074Z', P_WERKS=plant_code)
    except (ABAPApplicationError, CommunicationError) as e:
        logging.error(f"Error saat memanggil RFC untuk Plant {plant_code}: {e}")
        return
    finally:
        if sap_conn: sap_conn.close()

    logging.info("[Langkah 1/5] Mengekstrak dan meratakan data payload...")
    t_data, t1, t2, t3, t4 = normalize_payload(result)
    logging.info(f"   -> Jumlah data mentah: T_DATA: {len(t_data)}, T1: {len(t1)}, T2: {len(t2)}, T3: {len(t3)}, T4: {len(t4)}")

    logging.info("[Langkah 2/5] Mengelompokkan data anak berdasarkan kunci relasi...")
    
    # Kunci T2: KUNNR-NAME1 (Sesuai Controller)
    t2_grouped = defaultdict(list)
    for row in t2:
        key = f"{safe_get_value(row, 'KUNNR')}-{safe_get_value(row, 'NAME1')}"
        t2_grouped[key].append(row)
    
    # Kunci T3: KDAUF-KDPOS (Sesuai Controller)
    t3_grouped = defaultdict(list)
    for row in t3:
        key = f"{safe_get_value(row, 'KDAUF')}-{safe_get_value(row, 'KDPOS')}"
        t3_grouped[key].append(row)

    # Kunci T1: AUFNR (Sesuai Controller BARU)
    t1_grouped = defaultdict(list)
    for row in t1:
        key = safe_get_value(row, 'AUFNR')
        if key: t1_grouped[key].append(row)

    # Kunci T4: AUFNR (Sesuai Controller BARU)
    t4_grouped = defaultdict(list)
    for row in t4:
        key = safe_get_value(row, 'AUFNR')
        if key: t4_grouped[key].append(row)

    logging.info("   -> Data anak berhasil dikelompokkan.")

    db_conn = connect_db(config)
    if not db_conn: return
    cursor = db_conn.cursor()

    try:
        logging.info("[Langkah 3/5] Memulai transaksi dan menghapus data lama...")
        db_conn.begin()
        # Menghapus data dengan mencocokkan plant_code secara tepat
        cursor.execute("DELETE FROM production_t_data4 WHERE WERKSX = %s", (plant_code,))
        cursor.execute("DELETE FROM production_t_data1 WHERE WERKSX = %s", (plant_code,)) # DIUBAH DARI LIKE
        cursor.execute("DELETE FROM production_t_data3 WHERE WERKSX = %s", (plant_code,))
        cursor.execute("DELETE FROM production_t_data2 WHERE WERKSX = %s", (plant_code,))
        cursor.execute("DELETE FROM production_t_data WHERE WERKSX = %s", (plant_code,))
        logging.info("   -> Data lama berhasil dihapus.")

        logging.info("[Langkah 4/5] Memulai proses penyisipan data secara berjenjang...")
        for t_data_row in t_data:
            kunnr = safe_get_value(t_data_row, 'KUNNR')
            name1 = safe_get_value(t_data_row, 'NAME1')
            if not kunnr and not name1: continue

            sql = "INSERT INTO production_t_data (WERKSX, KDAUF, KDPOS, MATNR, MAKTX, EDATU, KUNNR, NAME1, MANDT) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)"
            vals = (plant_code, safe_get_value(t_data_row, 'KDAUF'), safe_get_value(t_data_row, 'KDPOS'), safe_get_value(t_data_row, 'MATNR'), safe_get_value(t_data_row, 'MAKTX'), format_sap_date_for_db(t_data_row.get('EDATU')), kunnr, name1, safe_get_value(t_data_row, 'MANDT'))
            cursor.execute(sql, vals)

            key_t2 = f"{kunnr}-{name1}"
            children_t2 = t2_grouped.get(key_t2, [])
            for t2_row in children_t2:
                kdauf_t2 = safe_get_value(t2_row, 'KDAUF')
                kdpos_t2 = safe_get_value(t2_row, 'KDPOS')
                sql = "INSERT INTO production_t_data2 (MANDT, KDAUF, KDPOS, MATFG, MAKFG, EDATU, WERKSX, KUNNR, NAME1) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)"
                vals = (safe_get_value(t2_row, 'MANDT'), kdauf_t2, kdpos_t2, safe_get_value(t2_row, 'MATFG'), safe_get_value(t2_row, 'MAKFG'), format_sap_date_for_db(t2_row.get('EDATU')), plant_code, kunnr, name1)
                cursor.execute(sql, vals)
                
                key_t3 = f"{kdauf_t2}-{kdpos_t2}"
                children_t3 = t3_grouped.get(key_t3, [])
                for t3_row in children_t3:
                    aufnr_t3 = safe_get_value(t3_row, 'AUFNR')
                    
                    sql = "INSERT INTO production_t_data3 (MANDT, ARBPL, ORDERX, PWWRK, KTEXT, ARBID, VERID, KDAUF, KDPOS, AUFNR, PLNUM, STATS, DISPO, MATNR, MTART, MAKTX, VORNR, STEUS, AUART, MEINS, MATKL, PSMNG, WEMNG, MGVRG2, LMNGA, P1, MENG2, VGW01, VGE01, CPCTYX, DTIME, DDAY, SSSLD, SSAVD, MATFG, MAKFG, CATEGORY, WERKSX, MENGE2, STATS2) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)"
                    vals = (safe_get_value(t3_row, 'MANDT'), safe_get_value(t3_row, 'ARBPL'), safe_get_value(t3_row, 'ORDERX'), safe_get_value(t3_row, 'PWWRK'), safe_get_value(t3_row, 'KTEXT'), safe_get_value(t3_row, 'ARBID'), safe_get_value(t3_row, 'VERID'), kdauf_t2, kdpos_t2, aufnr_t3, safe_get_value(t3_row, 'PLNUM'), safe_get_value(t3_row, 'STATS'), safe_get_value(t3_row, 'DISPO'), safe_get_value(t3_row, 'MATNR'), safe_get_value(t3_row, 'MTART'), safe_get_value(t3_row, 'MAKTX'), safe_get_value(t3_row, 'VORNR'), safe_get_value(t3_row, 'STEUS'), safe_get_value(t3_row, 'AUART'), safe_get_value(t3_row, 'MEINS'), safe_get_value(t3_row, 'MATKL'), t3_row.get('PSMNG'), t3_row.get('WEMNG'), t3_row.get('MGVRG2'), t3_row.get('LMNGA'), t3_row.get('P1'), t3_row.get('MENG2'), t3_row.get('VGW01'), t3_row.get('VGE01'), safe_get_value(t3_row, 'CPCTYX'), t3_row.get('DTIME'), t3_row.get('DDAY'), format_sap_date_for_db(t3_row.get('SSSLD')), format_sap_date_for_db(t3_row.get('SSAVD')), safe_get_value(t3_row, 'MATFG'), safe_get_value(t3_row, 'MAKFG'), safe_get_value(t3_row, 'CATEGORY'), plant_code, t3_row.get('MENGE2'), safe_get_value(t3_row, 'STATS2'))
                    cursor.execute(sql, vals)
                    
                    key_t1_t4 = aufnr_t3
                    if not key_t1_t4: continue

                    children_t1 = t1_grouped.get(key_t1_t4, [])
                    children_t4 = t4_grouped.get(key_t1_t4, [])
                    
                    for t1_row in children_t1:
                        # --- [LOGIKA BARU SESUAI CONTROLLER] ---
                        sssl1 = format_display_date(t1_row.get('SSSLDPV1'))
                        sssl2 = format_display_date(t1_row.get('SSSLDPV2'))
                        sssl3 = format_display_date(t1_row.get('SSSLDPV3'))

                        parts_pv1 = []
                        arbpl1 = safe_get_value(t1_row, 'ARBPL1')
                        if arbpl1: parts_pv1.append(arbpl1.upper())
                        if sssl1: parts_pv1.append(sssl1)
                        pv1 = ' - '.join(parts_pv1) if parts_pv1 else None

                        parts_pv2 = []
                        arbpl2 = safe_get_value(t1_row, 'ARBPL2')
                        if arbpl2: parts_pv2.append(arbpl2.upper())
                        if sssl2: parts_pv2.append(sssl2)
                        pv2 = ' - '.join(parts_pv2) if parts_pv2 else None

                        parts_pv3 = []
                        arbpl3 = safe_get_value(t1_row, 'ARBPL3')
                        if arbpl3: parts_pv3.append(arbpl3.upper())
                        if sssl3: parts_pv3.append(sssl3)
                        pv3 = ' - '.join(parts_pv3) if parts_pv3 else None
                        # --- [PERUBAHAN SELESAI] ---
                        
                        sql = "INSERT INTO production_t_data1 (MANDT, ARBPL, PWWRK, KTEXT, WERKSX, ARBID, VERID, KDAUF, KDPOS, AUFNR, PLNUM, STATS, DISPO, MATNR, MTART, MAKTX, VORNR, STEUS, AUART, MEINS, MATKL, PSMNG, WEMNG, MGVRG2, LMNGA, P1, MENG2, VGW01, VGE01, CPCTYX, DTIME, DDAY, SSSLD, SSAVD, MATFG, MAKFG, CATEGORY, ORDERX, STATS2, PV1, PV2, PV3) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)"
                        vals = (safe_get_value(t1_row, 'MANDT'), safe_get_value(t1_row, 'ARBPL'), safe_get_value(t1_row, 'PWWRK'), safe_get_value(t1_row, 'KTEXT'), plant_code, safe_get_value(t1_row, 'ARBID'), safe_get_value(t1_row, 'VERID'), safe_get_value(t1_row, 'KDAUF'), safe_get_value(t1_row, 'KDPOS'), aufnr_t3, safe_get_value(t1_row, 'PLNUM'), safe_get_value(t1_row, 'STATS'), safe_get_value(t1_row, 'DISPO'), safe_get_value(t1_row, 'MATNR'), safe_get_value(t1_row, 'MTART'), safe_get_value(t1_row, 'MAKTX'), safe_get_value(t1_row, 'VORNR'), safe_get_value(t1_row, 'STEUS'), safe_get_value(t1_row, 'AUART'), safe_get_value(t1_row, 'MEINS'), safe_get_value(t1_row, 'MATKL'), t1_row.get('PSMNG'), t1_row.get('WEMNG'), t1_row.get('MGVRG2'), t1_row.get('LMNGA'), t1_row.get('P1'), t1_row.get('MENG2'), t1_row.get('VGW01'), t1_row.get('VGE01'), safe_get_value(t1_row, 'CPCTYX'), t1_row.get('DTIME'), t1_row.get('DDAY'), format_sap_date_for_db(t1_row.get('SSSLD')), format_sap_date_for_db(t1_row.get('SSAVD')), safe_get_value(t1_row, 'MATFG'), safe_get_value(t1_row, 'MAKFG'), safe_get_value(t1_row, 'CATEGORY'), safe_get_value(t1_row, 'ORDERX'), safe_get_value(t1_row, 'STATS2'), pv1, pv2, pv3)
                        cursor.execute(sql, vals)

                    for t4_row in children_t4:
                        sql = "INSERT INTO production_t_data4 (MANDT, RSNUM, RSPOS, KDAUF, KDPOS, AUFNR, PLNUM, STATS, DISPO, MATNR, MAKTX, MEINS, BAUGR, WERKSX, BDMNG, KALAB, SOBSL, BESKZ, LTEXT) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)"
                        vals = (safe_get_value(t4_row, 'MANDT'), safe_get_value(t4_row, 'RSNUM'), safe_get_value(t4_row, 'RSPOS'), safe_get_value(t4_row, 'KDAUF'), safe_get_value(t4_row, 'KDPOS'), aufnr_t3, safe_get_value(t4_row, 'PLNUM'), safe_get_value(t4_row, 'STATS'), safe_get_value(t4_row, 'DISPO'), safe_get_value(t4_row, 'MATNR'), safe_get_value(t4_row, 'MAKTX'), safe_get_value(t4_row, 'MEINS'), safe_get_value(t4_row, 'BAUGR'), plant_code, t4_row.get('BDMNG'), t4_row.get('KALAB'), safe_get_value(t4_row, 'SOBSL'), safe_get_value(t4_row, 'BESKZ'), safe_get_value(t4_row, 'LTEXT'))
                        cursor.execute(sql, vals)
        
        logging.info("[Langkah 5/5] Melakukan commit transaksi...")
        db_conn.commit()
        logging.info(f"--- Transaksi untuk Plant {plant_code} berhasil. ---")
    except Exception as e:
        logging.error(f"Terjadi error untuk Plant {plant_code}: {e}. Melakukan rollback...", exc_info=True)
        if db_conn: db_conn.rollback()
    finally:
        if db_conn and db_conn.open:
            cursor.close()
            db_conn.close()
            logging.info(f"Koneksi database untuk Plant {plant_code} ditutup.")

def run_job():
    """Fungsi utama yang akan dijalankan oleh scheduler."""
    logging.info("================ JOB MULAI ================")
    config = load_config()
    if not config['plants']:
        logging.warning("Tidak ada plant yang diproses. Periksa variabel 'plants' di dalam skrip.")
        logging.info("================ JOB DIBATALKAN ================")
        return
        
    for plant in config['plants']:
        if plant:
            process_plant_data(plant.strip(), config)
    logging.info("================ JOB SELESAI ================")

# --- Main execution ---
if __name__ == "__main__":
    try:
        logging.info(">>> Skrip dipanggil. Memulai sinkronisasi... <<<")
        run_job()
        logging.info(">>> Sinkronisasi selesai. Skrip akan keluar. <<<")
    except KeyboardInterrupt:
        logging.info("Proses sinkronisasi dihentikan oleh user.")
    except Exception as e:
        logging.error(f"Terjadi error fatal di level utama: {e}", exc_info=True)
    finally:
        logging.info("Program Python selesai.")