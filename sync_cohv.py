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
        'db_passwd': '', 
        'db_name': 'cohv',

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
        logging.info("  -> Berhasil terhubung ke SAP.")
        return conn
    except (CommunicationError, ABAPApplicationError) as e:
        logging.error(f"  -> Gagal terhubung ke SAP: {e}")
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
        logging.info("  -> Berhasil terhubung ke database MySQL dengan PyMySQL.")
        return cnx
    except pymysql.Error as err:
        logging.error(f"  -> Gagal terhubung ke database MySQL: {err}")
        return None

def format_sap_date_for_db(sap_date_str):
    """
    Mengubah format tanggal YYYYMMDD dari SAP ke format DATE MySQL (YYYY-MM-DD).
    Berdasarkan struktur SAP: EDATU adalah DATS dengan Length 8
    
    Args:
        sap_date_str: String tanggal dari SAP (YYYYMMDD format)
    
    Returns:
        String tanggal dalam format YYYY-MM-DD atau None jika invalid
    """
    if not sap_date_str or sap_date_str == '00000000' or str(sap_date_str).strip() == '':
        return None
    
    try:
        # SAP DATS format adalah YYYYMMDD (8 karakter)
        date_str = str(sap_date_str).strip()
        if len(date_str) == 8 and date_str.isdigit():
            # Convert YYYYMMDD ke YYYY-MM-DD untuk MySQL DATE field
            return datetime.strptime(date_str, '%Y%m%d').strftime('%Y-%m-%d')
        else:
            logging.warning(f"Invalid SAP date format: '{sap_date_str}' (expected YYYYMMDD)")
            return None
    except ValueError as e:
        logging.warning(f"Error parsing SAP date '{sap_date_str}': {e}")
        return None

def format_display_date(sap_date_str):
    """Mengubah format tanggal YYYYMMDD dari SAP menjadi DD-MM-YYYY untuk display."""
    if not sap_date_str or sap_date_str == '00000000':
        return None
    try:
        return datetime.strptime(sap_date_str, '%Y%m%d').strftime('%d-%m-%Y')
    except ValueError:
        return sap_date_str

def normalize_payload(result):
    """Normalisasi payload persis seperti di controller Laravel."""
    t_data, t_data1, t_data2, t_data3, t_data4 = [], [], [], [], []

    if 'results' in result and isinstance(result['results'], list):
        for res in result['results']:
            t_data.extend(res.get('T_DATA', []))
            t_data1.extend(res.get('T_DATA1', []))
            t_data2.extend(res.get('T_DATA2', []))
            t_data3.extend(res.get('T_DATA3', []))
            t_data4.extend(res.get('T_DATA4', []))
    else:
        t_data, t_data1, t_data2, t_data3, t_data4 = (
            result.get('T_DATA', []), result.get('T_DATA1', []),
            result.get('T_DATA2', []), result.get('T_DATA3', []),
            result.get('T_DATA4', [])
        )

    if t_data and isinstance(t_data, dict): t_data = [t_data]
    if t_data1 and isinstance(t_data1, dict): t_data1 = [t_data1]
    if t_data2 and isinstance(t_data2, dict): t_data2 = [t_data2]
    if t_data3 and isinstance(t_data3, dict): t_data3 = [t_data3]
    if t_data4 and isinstance(t_data4, dict): t_data4 = [t_data4]

    return t_data, t_data1, t_data2, t_data3, t_data4

def safe_get_value(row, key, max_length=None):
    """
    Safely get value from row and truncate if necessary.
    
    Args:
        row: Dictionary containing row data
        key: Key to get value for
        max_length: Maximum length allowed (None = no limit)
    
    Returns:
        Value or None if empty/invalid
    """
    value = row.get(key)
    if value is None:
        return None
    
    # Convert to string and strip
    str_value = str(value).strip()
    if not str_value:
        return None
    
    # Truncate if max_length is specified
    if max_length and len(str_value) > max_length:
        logging.warning(f"Truncating {key}: '{str_value}' to {max_length} chars")
        str_value = str_value[:max_length]
    
    return str_value

def process_plant_data(plant_code, config):
    """Mengambil data dari SAP untuk satu plant dan menyimpannya ke DB."""
    logging.info(f"--- Memulai proses untuk Plant: {plant_code} ---")

    logging.info(f"[Langkah 1/5] Menghubungkan ke SAP untuk Plant {plant_code}...")
    sap_conn = connect_sap(config)
    if not sap_conn: return

    try:
        logging.info(f"  -> Memanggil RFC 'Z_FM_YPPR074Z' untuk Plant: {plant_code}")
        result = sap_conn.call('Z_FM_YPPR074Z', P_WERKS=plant_code)
        sap_conn.close()
        logging.info(f"  -> Berhasil mengambil data dari SAP untuk Plant: {plant_code}")
    except (ABAPApplicationError, CommunicationError) as e:
        logging.error(f"Error saat memanggil RFC untuk Plant {plant_code}: {e}")
        sap_conn.close()
        return

    t_data, t_data1, t_data2, t_data3, t_data4 = normalize_payload(result)
    logging.info(f"  -> Jumlah data diterima: T_DATA={len(t_data)}, T_DATA1={len(t_data1)}, T_DATA2={len(t_data2)}, T_DATA3={len(t_data3)}, T_DATA4={len(t_data4)}")

    logging.info(f"[Langkah 2/5] Menghubungkan ke database MySQL...")
    db_conn = connect_db(config)
    if not db_conn: return
    
    cursor = db_conn.cursor()

    try:
        logging.info(f"[Langkah 3/5] Memulai transaksi dan menghapus data lama untuk Plant {plant_code}...")
        db_conn.begin()

        cursor.execute("DELETE FROM production_t_data WHERE WERKSX = %s", (plant_code,))
        logging.info(f"  -> Data lama dari production_t_data dihapus.")
        cursor.execute("DELETE FROM production_t_data1 WHERE WERKSX = %s", (plant_code,))
        logging.info(f"  -> Data lama dari production_t_data1 dihapus.")
        cursor.execute("DELETE FROM production_t_data2 WHERE WERKSX = %s", (plant_code,))
        logging.info(f"  -> Data lama dari production_t_data2 dihapus.")
        cursor.execute("DELETE FROM production_t_data3 WHERE WERKSX = %s", (plant_code,))
        logging.info(f"  -> Data lama dari production_t_data3 dihapus.")
        cursor.execute("DELETE FROM production_t_data4 WHERE WERKSX = %s", (plant_code,))
        logging.info(f"  -> Data lama dari production_t_data4 dihapus.")

        logging.info(f"[Langkah 4/5] INSERT DATA BARU DARI SAP...")
        
        # == T_DATA — Insert data baru (disesuaikan dengan model Laravel)
        logging.info(f"  -> Memproses {len(t_data)} baris untuk production_t_data...")
        
        # Debug: Cek satu sample data untuk melihat format EDATU
        if t_data:
            sample_edatu = t_data[0].get('EDATU', '')
            logging.info(f"  -> DEBUG: Sample EDATU dari SAP: '{sample_edatu}' (type: {type(sample_edatu)}, length: {len(str(sample_edatu))})")
            formatted_sample = format_sap_date_for_db(sample_edatu)
            logging.info(f"  -> DEBUG: EDATU setelah format: '{formatted_sample}' (length: {len(str(formatted_sample)) if formatted_sample else 0})")
        
        for row in t_data:
            try:
                kunnr = safe_get_value(row, 'KUNNR')
                name1 = safe_get_value(row, 'NAME1')
                if not kunnr and not name1: 
                    continue
                
                # PERBAIKAN: Menangani nama field material yang tidak konsisten dari SAP (MATNR/MATFG)
                matnr = row.get('MATNR') or row.get('MATFG')
                maktx = row.get('MAKTX') or row.get('MAKFG')
                
                # PERBAIKAN: Gunakan format SAP asli YYYYMMDD (8 karakter) untuk EDATU
                raw_edatu = row.get('EDATU')
                edatu_formatted = None
                
                if raw_edatu and str(raw_edatu).strip() != '00000000' and str(raw_edatu).strip() != '':
                    raw_str = str(raw_edatu).strip()
                    # Gunakan format asli SAP YYYYMMDD (8 karakter)
                    if len(raw_str) == 8 and raw_str.isdigit():
                        edatu_formatted = raw_str
                    else:
                        logging.warning(f"Invalid EDATU format: '{raw_edatu}' - expected 8 digits")
                
                logging.debug(f"  -> EDATU processing: '{raw_edatu}' -> '{edatu_formatted}'")
                
                insert_sql = """
                INSERT INTO production_t_data (WERKSX, KDAUF, KDPOS, MATNR, MAKTX, EDATU, KUNNR, NAME1, MANDT)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
                """
                values = (
                    plant_code, 
                    safe_get_value(row, 'KDAUF'), 
                    safe_get_value(row, 'KDPOS'), 
                    matnr,
                    maktx, 
                    edatu_formatted,
                    kunnr, 
                    name1, 
                    safe_get_value(row, 'MANDT')
                )
                cursor.execute(insert_sql, values)
            except Exception as e:
                # Debug: Log lebih detail
                raw_edatu = row.get('EDATU')
                logging.warning(f'Gagal simpan T_DATA untuk Plant {plant_code}: {e}')
                logging.warning(f'  -> KDAUF: {row.get("KDAUF", "N/A")}, Raw EDATU: "{raw_edatu}" (len: {len(str(raw_edatu)) if raw_edatu else 0})')
                logging.warning(f'  -> Formatted EDATU: "{edatu_formatted}" (len: {len(str(edatu_formatted)) if edatu_formatted else 0})')

        # == T_DATA1 — Insert data baru (disesuaikan dengan model Laravel)
        logging.info(f"  -> Memproses {len(t_data1)} baris untuk production_t_data1...")
        for row in t_data1:
            try:
                if not row.get('ORDERX'): continue

                sssl1_display = format_display_date(row.get('SSSLDPV1', ''))
                sssl2_display = format_display_date(row.get('SSSLDPV2', ''))
                sssl3_display = format_display_date(row.get('SSSLDPV3', ''))

                pv1 = f"{row.get('ARBPL1', '').upper()} - {sssl1_display}" if row.get('ARBPL1') and sssl1_display else None
                pv2 = f"{row.get('ARBPL2', '').upper()} - {sssl2_display}" if row.get('ARBPL2') and sssl2_display else None
                pv3 = f"{row.get('ARBPL3', '').upper()} - {sssl3_display}" if row.get('ARBPL3') and sssl3_display else None

                insert_sql = """
                INSERT INTO production_t_data1 (
                    MANDT, ARBPL, PWWRK, KTEXT, WERKSX, ARBID, VERID, KDAUF, KDPOS, AUFNR,
                    PLNUM, STATS, DISPO, MATNR, MTART, MAKTX, VORNR, STEUS, AUART, MEINS,
                    MATKL, PSMNG, WEMNG, MGVRG2, LMNGA, P1, MENG2, VGW01, VGE01, CPCTYX,
                    DTIME, DDAY, SSSLD, SSAVD, MATFG, MAKFG, CATEGORY, ORDERX,
                    STATS2, PV1, PV2, PV3
                ) VALUES (
                    %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
                    %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
                    %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
                    %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
                    %s, %s
                )
                """
                values = (
                    safe_get_value(row, 'MANDT'), safe_get_value(row, 'ARBPL'), safe_get_value(row, 'PWWRK'), 
                    safe_get_value(row, 'KTEXT'), plant_code,
                    safe_get_value(row, 'ARBID'), safe_get_value(row, 'VERID'), safe_get_value(row, 'KDAUF'), 
                    safe_get_value(row, 'KDPOS'), safe_get_value(row, 'AUFNR'),
                    safe_get_value(row, 'PLNUM'), safe_get_value(row, 'STATS'), safe_get_value(row, 'DISPO'), 
                    safe_get_value(row, 'MATNR'), safe_get_value(row, 'MTART'),
                    safe_get_value(row, 'MAKTX'), safe_get_value(row, 'VORNR'), safe_get_value(row, 'STEUS'), 
                    safe_get_value(row, 'AUART'), safe_get_value(row, 'MEINS'),
                    safe_get_value(row, 'MATKL'), row.get('PSMNG'), row.get('WEMNG'), row.get('MGVRG2'), row.get('LMNGA'),
                    row.get('P1'), row.get('MENG2'), row.get('VGW01'), row.get('VGE01'), safe_get_value(row, 'CPCTYX'),
                    row.get('DTIME'), row.get('DDAY'), 
                    format_sap_date_for_db(row.get('SSSLD')), 
                    format_sap_date_for_db(row.get('SSAVD')),
                    safe_get_value(row, 'MATFG'), safe_get_value(row, 'MAKFG'), safe_get_value(row, 'CATEGORY'), 
                    safe_get_value(row, 'ORDERX'),
                    safe_get_value(row, 'STATS2'), pv1, pv2, pv3
                )
                cursor.execute(insert_sql, values)
            except Exception as e:
                logging.warning(f'Gagal simpan T_DATA1 untuk Plant {plant_code}: {e} - ORDERX: {row.get("ORDERX", "N/A")}')

        # == T_DATA2 — Insert data baru
        logging.info(f"  -> Memproses {len(t_data2)} baris untuk production_t_data2...")
        for row in t_data2:
            try:
                insert_sql = """
                INSERT INTO production_t_data2 (MANDT, KDAUF, KDPOS, MATFG, MAKFG, EDATU, WERKSX)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
                """
                values = (
                    safe_get_value(row, 'MANDT'),
                    safe_get_value(row, 'KDAUF'),
                    safe_get_value(row, 'KDPOS'),
                    safe_get_value(row, 'MATFG'),
                    safe_get_value(row, 'MAKFG'),
                    format_sap_date_for_db(row.get('EDATU')),
                    plant_code
                )
                cursor.execute(insert_sql, values)
            except Exception as e:
                logging.warning(f'Gagal simpan T_DATA2 untuk Plant {plant_code}: {e} - KDAUF: {row.get("KDAUF", "N/A")}')

        # == T_DATA3 — Insert data baru (disesuaikan dengan model Laravel)
        logging.info(f"  -> Memproses {len(t_data3)} baris untuk production_t_data3...")
        for row in t_data3:
            try:
                if not row.get('ORDERX'): continue
                
                insert_sql = """
                INSERT INTO production_t_data3 (
                    MANDT, ARBPL, ORDERX, PWWRK, KTEXT, ARBID, VERID, KDAUF, KDPOS, AUFNR,
                    PLNUM, STATS, DISPO, MATNR, MTART, MAKTX, VORNR, STEUS, AUART, MEINS,
                    MATKL, PSMNG, WEMNG, MGVRG2, LMNGA, P1, MENG2, VGW01, VGE01, CPCTYX,
                    DTIME, DDAY, SSSLD, SSAVD, MATFG, MAKFG, CATEGORY, WERKSX, MENGE2, STATS2
                ) VALUES (
                    %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
                    %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
                    %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
                    %s, %s, %s, %s, %s, %s, %s, %s, %s, %s
                )
                """
                values = (
                    safe_get_value(row, 'MANDT'), safe_get_value(row, 'ARBPL'), safe_get_value(row, 'ORDERX'), 
                    safe_get_value(row, 'PWWRK'), safe_get_value(row, 'KTEXT'),
                    safe_get_value(row, 'ARBID'), safe_get_value(row, 'VERID'), safe_get_value(row, 'KDAUF'), 
                    safe_get_value(row, 'KDPOS'), safe_get_value(row, 'AUFNR'),
                    safe_get_value(row, 'PLNUM'), safe_get_value(row, 'STATS'), safe_get_value(row, 'DISPO'), 
                    safe_get_value(row, 'MATNR'), safe_get_value(row, 'MTART'),
                    safe_get_value(row, 'MAKTX'), safe_get_value(row, 'VORNR'), safe_get_value(row, 'STEUS'), 
                    safe_get_value(row, 'AUART'), safe_get_value(row, 'MEINS'),
                    safe_get_value(row, 'MATKL'), row.get('PSMNG'), row.get('WEMNG'), row.get('MGVRG2'), row.get('LMNGA'),
                    row.get('P1'), row.get('MENG2'), row.get('VGW01'), row.get('VGE01'), safe_get_value(row, 'CPCTYX'),
                    row.get('DTIME'), row.get('DDAY'), 
                    format_sap_date_for_db(row.get('SSSLD')), 
                    format_sap_date_for_db(row.get('SSAVD')),
                    safe_get_value(row, 'MATFG'), safe_get_value(row, 'MAKFG'), safe_get_value(row, 'CATEGORY'), 
                    plant_code, row.get('MENGE2'),
                    safe_get_value(row, 'STATS2')
                )
                cursor.execute(insert_sql, values)
            except Exception as e:
                logging.warning(f'Gagal simpan T_DATA3 untuk Plant {plant_code}: {e} - ORDERX: {row.get("ORDERX", "N/A")}')

        # == T_DATA4 — Insert data baru (disesuaikan dengan model Laravel)
        logging.info(f"  -> Memproses {len(t_data4)} baris untuk production_t_data4...")
        for row in t_data4:
            try:
                if not row.get('RSNUM') or not row.get('RSPOS'): continue
                
                insert_sql = """
                INSERT INTO production_t_data4 (
                    MANDT, RSNUM, RSPOS, KDAUF, KDPOS, AUFNR, PLNUM, STATS, DISPO, MATNR,
                    MAKTX, MEINS, BAUGR, WERKSX, BDMNG, KALAB, SOBSL, BESKZ, LTEXT
                ) VALUES (
                    %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
                    %s, %s, %s, %s, %s, %s, %s, %s, %s
                )
                """
                values = (
                    safe_get_value(row, 'MANDT'), safe_get_value(row, 'RSNUM'), safe_get_value(row, 'RSPOS'), 
                    safe_get_value(row, 'KDAUF'), safe_get_value(row, 'KDPOS'),
                    safe_get_value(row, 'AUFNR'), safe_get_value(row, 'PLNUM'), safe_get_value(row, 'STATS'), 
                    safe_get_value(row, 'DISPO'), safe_get_value(row, 'MATNR'),
                    safe_get_value(row, 'MAKTX'), safe_get_value(row, 'MEINS'), safe_get_value(row, 'BAUGR'), 
                    plant_code, row.get('BDMNG'),
                    row.get('KALAB'), safe_get_value(row, 'SOBSL'), safe_get_value(row, 'BESKZ'), safe_get_value(row, 'LTEXT')
                )
                cursor.execute(insert_sql, values)
            except Exception as e:
                logging.warning(f'Gagal simpan T_DATA4 untuk Plant {plant_code}: {e} - RSNUM: {row.get("RSNUM", "N/A")}')

        logging.info(f"[Langkah 5/5] Melakukan commit transaksi ke database...")
        db_conn.commit()
        logging.info(f"--- Transaksi untuk Plant {plant_code} berhasil di-commit. Proses Selesai. ---")

    except pymysql.Error as err:
        logging.error(f"Database error untuk Plant {plant_code}: {err}. Melakukan rollback...")
        db_conn.rollback()
    except Exception as e:
        logging.error(f"Terjadi error tak terduga untuk Plant {plant_code}: {e}. Melakukan rollback...")
        db_conn.rollback()
    finally:
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

def run_automatic_mode():
    """Menjalankan job pertama kali, lalu menjadwalkannya setiap 2 jam."""
    run_job()
    schedule.every(2).hours.do(run_job)
    logging.info("Skrip dijadwalkan untuk berjalan setiap 2 jam. Tekan Ctrl+C untuk berhenti.")
    while True:
        schedule.run_pending()
        time.sleep(1)

def manual_trigger():
    """Menunggu input pengguna (Enter) untuk memicu job secara manual."""
    while True:
        try:
            input()
            logging.info(">>> Pemicu manual diterima! Memulai sinkronisasi... <<<")
            job_thread = threading.Thread(target=run_job)
            job_thread.start()
        except (KeyboardInterrupt, EOFError):
            logging.info("Manual trigger dihentikan.")
            break

def run_both_mode():
    """Menjadwalkan job dan juga memungkinkan pemicu manual kapan saja."""
    run_job()
    schedule.every(0.5).hours.do(run_job)
    
    manual_thread = threading.Thread(target=manual_trigger, daemon=True)
    manual_thread.start()
    
    logging.info("Skrip dijadwalkan dan siap untuk pemicu manual. Tekan Ctrl+C untuk berhenti.")
    logging.info("--> Tekan Enter kapan saja untuk memicu sinkronisasi manual <--")
    
    while manual_thread.is_alive():
        schedule.run_pending()
        time.sleep(1)

# --- Main execution ---
if __name__ == "__main__":
    # Logika yang kompleks di bawah ini kita hapus semua
    # parser = argparse.ArgumentParser(...)
    # args = parser.parse_args()
    # if args.mode == ...
    
    # GANTI SEMUA LOGIKA DI ATAS DENGAN INI:
    try:
        logging.info(">>> Skrip dipanggil oleh Laravel. Memulai sinkronisasi... <<<")
        run_job()
        logging.info(">>> Sinkronisasi selesai. Skrip akan keluar. <<<")
    except KeyboardInterrupt:
        logging.info("Proses sinkronisasi dihentikan oleh user.")
    except Exception as e:
        # Log error fatal jika terjadi
        logging.error(f"Terjadi error fatal di level utama: {e}", exc_info=True)
    finally:
        logging.info("Program Python selesai.")