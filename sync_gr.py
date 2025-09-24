import os
import traceback
import logging
import schedule
import time
import sys
import pprint
from datetime import datetime, timedelta
from pyrfc import Connection, ABAPApplicationError, CommunicationError
from dotenv import load_dotenv
import pymysql

# --- Inisialisasi & Konfigurasi Logging ---
dotenv_path = os.path.join(os.path.dirname(__file__), '.env')
load_dotenv(dotenv_path=dotenv_path)

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(os.path.join(os.path.dirname(__file__), 'storage', 'logs', 'sync_gr_data.log')),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# --- Penambahan Mapping MRP berdasarkan Plant ---
MRP_MAPPING = {
    '1000': ['GW1', 'GW2', 'GW3', 'PN1', 'PN2', 'PV1', 'PV2', 'RW1', 'RW3', 'SM1', 'VN1', 'VN2', 'WE1', 'WE2', 'WM1', 'WW1', 'WW2', 'WW3'],
    '1200': ['WH1'],
    '2000': ['C11', 'C12', 'CH1', 'CH2', 'CH3', 'CH4', 'CH5', 'CH7', 'CH8', 'CP1', 'CP2', 'CP3', 'CSK', 'EB2', 'GA1', 'GA2', 'GD1', 'GD2', 'GF1', 'GF2', 'GT1', 'GT2', 'GT3', 'GT4', 'GT5', 'GT6', 'GT7', 'GW1', 'GW2', 'GW3', 'MF1', 'MF2', 'MF3', 'MF4', 'MW1', 'MW2', 'MW3', 'RD2', 'RD3', 'RD4', 'RD5', 'UH1', 'UH2'],
    '3000': ['D21', 'D22', 'D23', 'D24', 'D26', 'D27', 'D28', 'DR1', 'DR2', 'DR3', 'G31', 'MA1', 'MA2', 'MA3', 'MA4', 'MA5', 'MA7', 'MF1', 'MF2', 'MF3', 'MS1', 'MS3', 'MS4', 'MW1', 'MW2', 'MW3', 'PG1', 'PG2', 'PG3']
}


# --- Fungsi Helper ---
def safe_convert(value, target_type, default=None):
    """
    Fungsi ini secara aman mengonversi nilai ke tipe data yang diinginkan (float, int).
    Ini menangani nilai None, string kosong, dan string yang tidak valid.
    """
    if value is None:
        return default
    try:
        if isinstance(value, str) and value.strip() == '':
            return default
        return target_type(value)
    except (ValueError, TypeError):
        logger.debug(f"Gagal mengonversi nilai '{value}' ke tipe {target_type}. Menggunakan nilai default.")
        return default

def map_plant_code(original_werks):
    """
    Memetakan kode plant SAP asli ke kode plant yang disederhanakan.
    Contoh: 1001 -> 1000, 2005 -> 2000, 1201 -> 1200.
    """
    if not isinstance(original_werks, str) or not original_werks.strip():
        return original_werks

    werks = original_werks.strip()

    if werks == '1201':
        return '1200'
    elif werks.startswith('3'):
        return '3000'
    elif werks.startswith('2'):
        return '2000'
    elif werks.startswith('1'):
        return '1000'
    
    return original_werks

# --- Fungsi Koneksi ---
def connect_sap():
    """Membuka koneksi ke SAP."""
    try:
        conn = Connection(
            user=os.getenv("SAP_USERNAME", "auto_email"),
            passwd=os.getenv("SAP_PASSWORD", "11223344"),
            ashost="192.168.254.154",
            sysnr="01",
            client="300",
            lang="EN"
        )
        logger.info("Berhasil terhubung ke SAP.")
        return conn
    except (CommunicationError, ABAPApplicationError) as e:
        logger.error(f"Gagal terhubung ke SAP: {e}")
        return None

def connect_mysql():
    """Membuka koneksi ke MySQL menggunakan PyMySQL."""
    try:
        cnx = pymysql.connect(
            host=os.getenv('DB_HOST', '127.0.0.1'),
            user=os.getenv('DB_USERNAME', 'root'),
            password=os.getenv('DB_PASSWORD', 'root'),
            database=os.getenv('DB_DATABASE', 'cohv_app'),
            charset='utf8mb4',
            autocommit=False
        )
        logger.info("Berhasil terhubung ke database MySQL dengan PyMySQL.")
        return cnx
    except pymysql.Error as err:
        logger.error(f"Gagal terhubung ke database MySQL: {err}")
        return None

# --- Fungsi Sinkronisasi Utama ---
def sync_data_for_date(target_date):
    """Fungsi inti untuk sinkronisasi semua data GR untuk TANGGAL TERTENTU."""
    sap_conn = None
    db_conn = None
    cursor = None
    
    sync_date_sap = target_date.strftime('%Y%m%d')
    sync_date_mysql = target_date.strftime('%Y-%m-%d')
    
    logger.info(f"===== TUGAS DIMULAI: Sinkronisasi data GR untuk tanggal {sync_date_mysql} =====")

    try:
        sap_conn = connect_sap()
        if not sap_conn: return False

        # Looping per Plant, kirim semua MRP sekaligus untuk efisiensi
        all_sap_data = []
        logger.info("Memulai pengambilan data dari SAP (per Plant)...")

        for mapped_plant, dispo_list in MRP_MAPPING.items():
            try:
                # Siapkan tabel parameter T_DISPO dengan semua MRP untuk plant ini
                dispo_table_param = [{'DISPO': code} for code in dispo_list]
                
                logger.info(f"--> Mengambil data untuk Plant: {mapped_plant} dengan {len(dispo_list)} MRP...")
                result = sap_conn.call(
                    'Z_FM_YPPR009',
                    IV_BUDAT=sync_date_sap,
                    IV_WERKS=mapped_plant, 
                    T_DISPO=dispo_table_param
                )
                
                logger.info(f"    Respons mentah dari SAP untuk Plant {mapped_plant}:\n{pprint.pformat(result)}")

                plant_data = result.get('T_DATA1', [])
                if plant_data:
                    all_sap_data.extend(plant_data)
                    logger.info(f"    Ditemukan {len(plant_data)} record.")
                else:
                    logger.info(f"    Tidak ada data yang dikembalikan di tabel T_DATA1 untuk kombinasi ini.")

            except (CommunicationError, ABAPApplicationError) as e:
                logger.error(f"    Gagal mengambil data untuk Plant: {mapped_plant}. Error: {e}")
        
        sap_data = all_sap_data
        logger.info(f"Total {len(sap_data)} record mentah berhasil diambil dari semua iterasi SAP.")
        
        records_to_insert = []
        if sap_data:
            for i, row in enumerate(sap_data):
                try:
                    sap_date_str = row.get('BUDAT_MKPF', '').strip()
                    if not sap_date_str or datetime.strptime(sap_date_str, '%Y%m%d').strftime('%Y-%m-%d') != sync_date_mysql:
                        continue
                    
                    original_werks = row.get('WERKS')
                    mapped_werks_from_data = map_plant_code(original_werks)
                    
                    record = {
                        'MANDT': row.get('MANDT'), 'LGORT': row.get('LGORT'), 'MBLNR': row.get('MBLNR'), 'DISPO': row.get('DISPO'),
                        'AUFNR': row.get('AUFNR'), 
                        'WERKS': mapped_werks_from_data,
                        'CHARG': row.get('CHARG'), 'MATNR': row.get('MATNR'),
                        'MAKTX': row.get('MAKTX'), 'KDAUF': row.get('MAT_KDAUF'), 'KDPOS': row.get('MAT_KDPOS'),
                        'KUNNR': row.get('KUNNR'), 'NAME2': row.get('NAME2'),
                        'PSMNG': safe_convert(row.get('PSMNG'), float, 0.0),
                        'MENGE': safe_convert(row.get('MENGE'), float, 0.0),
                        'MENGEX': safe_convert(row.get('MENGEX'), float, 0.0),
                        'MENGE_M': safe_convert(row.get('MENGE_M'), float, 0.0),
                        'MENGE_M2': safe_convert(row.get('MENGE_M2'), float, 0.0),
                        'MENGE_M3': safe_convert(row.get('MENGE_M3'), float, 0.0),
                        'WEMNG': safe_convert(row.get('WEMNG'), float, 0.0),
                        'MEINS': row.get('MEINS'), 'LINE': row.get('LINE'),
                        'STPRS': safe_convert(row.get('STPRS'), float, 0.0),
                        'WAERS': row.get('WAERS'),
                        'VALUE': safe_convert(row.get('VALUE'), float, 0.0),
                        'BUDAT_MKPF': sync_date_mysql, 'CPUDT_MKPF': row.get('CPUDT_MKPF'),
                        'NODAY': safe_convert(row.get('NODAY'), int, 0),
                        'TXT50': row.get('TXT50'),
                        'NETPR': safe_convert(row.get('NETPR'), float, 0.0),
                        'WAERK': row.get('WAERK'),
                        'VALUSX': safe_convert(row.get('VALUSX'), float, 0.0),
                        'VALUS': safe_convert(row.get('VALUS'), float, 0.0),
                        'PERNR': row.get('PERNR'),
                        'created_at': datetime.now(), 'updated_at': datetime.now()
                    }
                    records_to_insert.append(record)
                except Exception as e:
                    logger.warning(f"Melewati baris data #{i+1} karena error tak terduga: {e} - Data: {row}")

        db_conn = connect_mysql()
        if not db_conn: return False
        cursor = db_conn.cursor()

        try:
            logger.info(f"Memulai transaksi. Menghapus data lama di MySQL untuk tanggal {sync_date_mysql}...")
            cursor.execute("DELETE FROM gr WHERE BUDAT_MKPF = %s", (sync_date_mysql,))
            deleted_count = cursor.rowcount
            logger.info(f"{deleted_count} record lama telah dihapus.")

            if records_to_insert:
                inserted_count = len(records_to_insert)
                logger.info(f"Menjalankan INSERT untuk {inserted_count} record baru...")

                cols = '`, `'.join(records_to_insert[0].keys())
                cols = f"`{cols}`"
                placeholders = ', '.join(['%s'] * len(records_to_insert[0]))
                insert_query = f"INSERT INTO gr ({cols}) VALUES ({placeholders})"
                
                values_to_insert = [tuple(rec.values()) for rec in records_to_insert]
                cursor.executemany(insert_query, values_to_insert)
                
                db_conn.commit()
                logger.info(f"Transaksi berhasil di-commit. {deleted_count} dihapus, {inserted_count} dimasukkan.")
            else:
                db_conn.commit()
                logger.info("Tidak ada data baru atau valid dari SAP. Transaksi penghapusan di-commit.")
        
        except pymysql.Error as db_err:
            logger.error(f"Database error terjadi: {db_err}. Melakukan rollback...")
            db_conn.rollback()
            return False
        
    except Exception as e:
        logger.error(f"Terjadi error fatal dalam proses sinkronisasi: {str(e)}")
        logger.error(traceback.format_exc())
        return False
    finally:
        if cursor: cursor.close()
        if db_conn: db_conn.close()
        if sap_conn: sap_conn.close()
        logger.info("Koneksi ke SAP dan MySQL telah ditutup.")
        logger.info(f"===== TUGAS SELESAI untuk tanggal {sync_date_mysql} =====")
    return True

def run_sync_for_today():
    """Wrapper untuk menjalankan sinkronisasi khusus hari ini."""
    sync_data_for_date(datetime.now())

def run_historical_sync():
    """PERUBAHAN: Menjalankan sinkronisasi untuk SETIAP HARI dari September 2025 s.d. hari ini."""
    logger.info("===== MEMULAI SINKRONISASI DATA HISTORIS (SETIAP HARI) =====")
    start_date = datetime(2025, 9, 1)
    end_date = datetime.now()
    
    current_date = start_date
    total_days = (end_date - start_date).days + 1
    day_count = 0

    while current_date <= end_date:
        day_count += 1
        logger.info(f"--> Memproses hari ke-{day_count} dari {total_days}: {current_date.strftime('%Y-%m-%d')}")
        success = sync_data_for_date(current_date)
        if not success:
            logger.error(f"Sinkronisasi untuk tanggal {current_date.strftime('%Y-%m-%d')} GAGAL. Proses historis dihentikan.")
            break
        
        time.sleep(2) # Jeda 2 detik antar hari
        current_date += timedelta(days=1)
    
    logger.info("===== SINKRONISASI DATA HISTORIS SELESAI =====")

# --- Scheduler ---
def start_scheduler():
    """Mendefinisikan jadwal dan menjalankan scheduler secara terus-menerus."""
    logger.info("Scheduler Service Dimulai.")
    logger.info("Menjalankan sinkronisasi awal (hari ini) saat startup...")
    run_sync_for_today()
    logger.info("Sinkronisasi awal selesai. Menunggu jadwal berikutnya.")
    
    schedule.every().day.at("05:00").do(run_sync_for_today)
    schedule.every().day.at("20:00").do(run_sync_for_today)

    while True:
        schedule.run_pending()
        time.sleep(60)

# --- Titik Eksekusi Utama ---
if __name__ == '__main__':
    if len(sys.argv) > 1:
        mode = sys.argv[1]
        if mode == 'run_now':
            logger.info("Mode: Eksekusi Manual Sekali Jalan (untuk hari ini).")
            run_sync_for_today()
        elif mode == 'run_historical':
            logger.info("Mode: Eksekusi Sinkronisasi Historis (September 2025 - Sekarang, setiap hari).")
            run_historical_sync()
        elif mode == 'run_for_date':
            if len(sys.argv) < 3:
                logger.error("Mode 'run_for_date' memerlukan argumen tanggal (YYYY-MM-DD).")
                logger.error("Contoh: python sync_gr.py run_for_date 2025-09-04")
            else:
                try:
                    date_str = sys.argv[2]
                    target_date = datetime.strptime(date_str, '%Y-%m-%d')
                    logger.info(f"Mode: Eksekusi Manual untuk tanggal spesifik: {date_str}")
                    sync_data_for_date(target_date)
                except ValueError:
                    logger.error(f"Format tanggal salah: '{date_str}'. Gunakan format YYYY-MM-DD.")
        else:
            logger.warning(f"Mode tidak dikenal: '{mode}'. Gunakan 'run_now', 'run_historical', atau 'run_for_date'.")
    else:
        logger.info("Mode: Menjalankan Scheduler Service (untuk data harian).")
        start_scheduler()

