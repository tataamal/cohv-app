import os
import traceback
import logging
import schedule
import time
import sys
from datetime import datetime, timedelta
from pyrfc import Connection, ABAPApplicationError, CommunicationError
from dotenv import load_dotenv
import pymysql
import calendar

# --- Inisialisasi & Konfigurasi Logging ---
dotenv_path = os.path.join(os.path.dirname(__file__), '.env')
load_dotenv(dotenv_path=dotenv_path)

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        # MODIFIKASI: Path log sekarang ada di direktori yang sama dengan script
        logging.FileHandler(os.path.join(os.path.dirname(__file__), 'sync_gr_data.log')),
        logging.StreamHandler()
    ]
)

logger = logging.getLogger()

# --- Penambahan Mapping MRP berdasarkan Plant ---
# Sekarang struktur value = list of list,
# sehingga bisa ada beberapa grup DISPO per plant (jalan berkali-kali).
MRP_MAPPING = {
     # Contoh kalau nanti butuh plant lain:
     '1001': [['WW1', 'WW4', 'WW3', 'WW2']],
     '1000': [['WE1','WE2','WM1','WM2','PN1','PV1','PV2','VN1','VN2','PN2','RW3','RW1',
               'GW1','GW2','GW3','PJ1','PJ2','PJ3','PJ4']],
     '1200': [['WH1']],
     '2000': [['GT1','GT2','GT5','GT6','GT7','CH4','CH5','CH7','C11','C12','UH1',
               'UH2','CH1','CH2','CH9','CH3','CH4','CH5','MF4','CP2','RW6','CSK',
               'CP3','GA1','GA2','EB2','GD1','GD2','GD3','GF1','GF2','MF1','MF2',
               'RD5','RD1','RD2','RD3']],
    '3000': [
        # Group 1: semua DISPO lain
        [
            'DR3', 'G31', 'D21', 'D26', 'D27', 'D22', 'D23', 'D28',
            'MF4', 'D31', 'MA4', 'MA7', 'MF3', 'MW1', 'MW2', 'MW3',
            'MS1', 'MS3', 'MS4', 
            'MA1', 'MA2', 'MA3', 'MA5',
            'PG1', 'PG2', 'PG3'
        ],
        # Group 2: khusus D24 & G32 (mode yang sebelumnya "pasti jalan")
        [
            'D24', 'G32'
        ]
    ]
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
        logger.debug(
            f"Gagal mengonversi nilai '{value}' ke tipe {target_type}. Menggunakan nilai default."
        )
        return default


def map_plant_code(original_werks):
    # 1. PEMBERSIHAN DATA (SANITIZATION)
    if original_werks is None:
        return None

    # Konversi ke string dan bersihkan karakter aneh
    werks = str(original_werks).replace('\xa0', '').strip()

    if not werks:
        return original_werks

    # 2. MAPPING KHUSUS (EXCEPTIONS)
    special_cases = {
        '1201': '1200',
        '1001': '1001',
        '3016': '3000',
    }

    if werks in special_cases:
        return special_cases[werks]

    # 3. LOGIKA UMUM (GENERAL RULES)
    if werks.startswith('30'):
        return '3000'
    elif werks.startswith('20'):
        return '2000'
    elif werks.startswith('10'):
        return '1000'

    # Jika tidak ada yang cocok, kembalikan nilai yang sudah dibersihkan
    return werks


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
            host=os.getenv('DB_HOST', '192.168.90.114'),
            user=os.getenv('DB_USERNAME', 'root'),
            password=os.getenv('DB_PASSWORD', 'karenamereka'),
            database=os.getenv('DB_DATABASE', 'cohv_app'),
            charset='utf8mb4',
            autocommit=False
        )
        logger.info("Berhasil terhubung ke database MySQL.")
        return cnx
    except pymysql.Error as err:
        logger.error(f"Gagal terhubung ke database MySQL: {err}")
        return None


# --- Fungsi Sinkronisasi Utama ---
def sync_data_for_date(target_date):
    """
    Fungsi sinkronisasi dengan metode GRANULAR UPDATE.
    Strategi: Tarik per Plant -> Hapus spesifik (Date+Werks+Dispo) -> Insert baru.
    Sekarang, per Plant bisa punya beberapa grup DISPO,
    dan tiap grup akan diproses (SAP call + delete + insert) terpisah.
    """
    sap_conn = None
    db_conn = None
    cursor = None

    sync_date_sap = target_date.strftime('%Y%m%d')
    sync_date_mysql = target_date.strftime('%Y-%m-%d')

    logger.info(f"===== TUGAS DIMULAI: Sinkronisasi data GR untuk tanggal {sync_date_mysql} =====")

    try:
        # 1. Buka Koneksi (Sekali saja di awal)
        sap_conn = connect_sap()
        if not sap_conn:
            return False

        db_conn = connect_mysql()
        if not db_conn:
            sap_conn.close()
            return False

        cursor = db_conn.cursor()

        # 2. Loop per Plant
        for mapped_plant, dispo_config in MRP_MAPPING.items():
            # Normalisasi: boleh list biasa atau list of list.
            if not dispo_config:
                logger.warning(f"[{mapped_plant}] Konfigurasi DISPO kosong. Dilewati.")
                continue

            if isinstance(dispo_config[0], list):
                dispo_groups = dispo_config  # sudah list of list
            else:
                # kalau user isi list biasa ['A','B',...], bungkus jadi satu grup
                dispo_groups = [dispo_config]

            # 2.b Loop per grup DISPO dalam plant
            for group_index, dispo_list in enumerate(dispo_groups, start=1):
                try:
                    if not dispo_list:
                        logger.warning(
                            f"[{mapped_plant}][Group {group_index}] Dispo list kosong. "
                            "Tidak ada yang dihapus/insert."
                        )
                        continue

                    # --- A. TARIK DATA DARI SAP ---
                    t_dispo_param = [{'DISPO': code} for code in dispo_list]

                    logger.info(
                        f"[{mapped_plant}][Group {group_index}] Mengambil data SAP untuk "
                        f"{len(dispo_list)} MRP Controller..."
                    )

                    result = sap_conn.call(
                        'Z_FM_YPPR009',
                        IV_BUDAT=sync_date_sap,
                        IV_WERKS=mapped_plant,
                        IV_DISPO='',           # Kosongkan agar membaca T_DISPO
                        T_DISPO=t_dispo_param  # Kirim list MRP
                    )

                    sap_data = result.get('T_DATA1', [])
                    logger.info(
                        f"[{mapped_plant}][Group {group_index}] Mendapatkan {len(sap_data)} baris data."
                    )

                    # --- B. PERSIAPAN DATA INSERT ---
                    records_to_insert = []
                    for i, row in enumerate(sap_data):
                        try:
                            # Validasi Tanggal (Double check)
                            sap_date_str = row.get('BUDAT_MKPF', '').strip()
                            if (
                                not sap_date_str or
                                datetime.strptime(sap_date_str, '%Y%m%d').strftime('%Y-%m-%d')
                                != sync_date_mysql
                            ):
                                continue

                            # Bersihkan data (pakai fungsi map_plant_code yg baru)
                            mapped_werks_from_data = map_plant_code(row.get('WERKS'))

                            # Pastikan record ini memang milik Plant yang sedang diproses (Safety check)
                            if mapped_werks_from_data != mapped_plant:
                                logger.warning(
                                    f"Data nyasar? Plant target {mapped_plant} tapi dapat "
                                    f"{mapped_werks_from_data}. Skip."
                                )
                                continue

                            record = {
                                'MANDT': row.get('MANDT'),
                                'LGORT': row.get('LGORT'),
                                'MBLNR': row.get('MBLNR'),
                                'DISPO': row.get('DISPO'),
                                'AUFNR': row.get('AUFNR'),
                                'WERKS': mapped_werks_from_data,
                                'CHARG': row.get('CHARG'),
                                'MATNR': row.get('MATNR'),
                                'MAKTX': row.get('MAKTX'),
                                'MAT_KDAUF': row.get('MAT_KDAUF'),
                                'MAT_KDPOS': row.get('MAT_KDPOS'),
                                'KUNNR': row.get('KUNNR'),
                                'NAME2': row.get('NAME2'),
                                'PSMNG': safe_convert(row.get('PSMNG'), float, 0.0),
                                'MENGE': safe_convert(row.get('MENGE'), float, 0.0),
                                'MENGEX': safe_convert(row.get('MENGEX'), float, 0.0),
                                'MENGE_M': safe_convert(row.get('MENGE_M'), float, 0.0),
                                'MENGE_M2': safe_convert(row.get('MENGE_M2'), float, 0.0),
                                'MENGE_M3': safe_convert(row.get('MENGE_M3'), float, 0.0),
                                'WEMNG': safe_convert(row.get('WEMNG'), float, 0.0),
                                'MEINS': row.get('MEINS'),
                                'LINE': row.get('LINE'),
                                'STPRS': safe_convert(row.get('STPRS'), float, 0.0),
                                'WAERS': row.get('WAERS'),
                                'VALUE': safe_convert(row.get('VALUE'), float, 0.0),
                                'BUDAT_MKPF': sync_date_mysql,
                                'CPUDT_MKPF': row.get('CPUDT_MKPF'),
                                'NODAY': safe_convert(row.get('NODAY'), int, 0),
								'AUFNR2': row.get('AUFNR2'),
                                'CSMG': row.get('CSMG'),
                                'TXT50': row.get('TXT50'),
                                'NETPR': safe_convert(row.get('NETPR'), float, 0.0),
                                'WAERK': row.get('WAERK'),
                                'VALUSX': safe_convert(row.get('VALUSX'), float, 0.0),
                                'VALUS': safe_convert(row.get('VALUS'), float, 0.0),
                                'PERNR': row.get('PERNR'),
								'ARBPL': row.get('ARBPL'),
								'KTEXT': row.get('KTEXT'),
                                'created_at': datetime.now(),
                                'updated_at': datetime.now()
                            }
                            records_to_insert.append(record)
                        except Exception as e:
                            logger.warning(f"Error parsing row: {e}")

                    # --- C. OPERASI DATABASE (DELETE & INSERT) ---
                    placeholders = ', '.join(['%s'] * len(dispo_list))
                    delete_query = f"""
                        DELETE FROM gr
                        WHERE BUDAT_MKPF = %s
                          AND WERKS = %s
                          AND DISPO IN ({placeholders})
                    """

                    delete_params = [sync_date_mysql, mapped_plant] + dispo_list
                    cursor.execute(delete_query, delete_params)
                    deleted_count = cursor.rowcount
                    logger.info(
                        f"[{mapped_plant}][Group {group_index}] Menghapus {deleted_count} record lama "
                        f"(Scope: Tgl {sync_date_mysql}, Plant {mapped_plant}, "
                        f"{len(dispo_list)} MRP)."
                    )

                    # 2. INSERT DATA BARU
                    if records_to_insert:
                        cols = '`, `'.join(records_to_insert[0].keys())
                        cols = f"`{cols}`"
                        vals_placeholders = ', '.join(['%s'] * len(records_to_insert[0]))
                        insert_query = f"INSERT INTO gr ({cols}) VALUES ({vals_placeholders})"

                        values_to_insert = [tuple(rec.values()) for rec in records_to_insert]
                        cursor.executemany(insert_query, values_to_insert)
                        inserted_count = len(records_to_insert)
                        logger.info(
                            f"[{mapped_plant}][Group {group_index}] Memasukkan {inserted_count} "
                            f"record baru."
                        )
                    else:
                        logger.info(
                            f"[{mapped_plant}][Group {group_index}] Tidak ada data baru dari SAP "
                            "untuk dimasukkan."
                        )

                    # 3. COMMIT PER GROUP DISPO
                    db_conn.commit()

                except (CommunicationError, ABAPApplicationError) as sap_err:
                    logger.error(
                        f"[{mapped_plant}][Group {group_index}] Gagal komunikasi SAP: {sap_err}. "
                        "Data database TIDAK disentuh untuk grup ini."
                    )
                    db_conn.rollback()
                except pymysql.Error as db_err:
                    logger.error(
                        f"[{mapped_plant}][Group {group_index}] Gagal Database: {db_err}. "
                        "Rollback untuk grup ini."
                    )
                    db_conn.rollback()

    except Exception as e:
        logger.error(f"Terjadi error fatal level atas: {str(e)}")
        logger.error(traceback.format_exc())
        return False
    finally:
        if cursor:
            cursor.close()
        if db_conn:
            db_conn.close()
        if sap_conn:
            sap_conn.close()
        logger.info(f"===== TUGAS SELESAI untuk tanggal {sync_date_mysql} =====")

    return True


def run_sync_for_today():
    """Wrapper untuk menjalankan sinkronisasi khusus hari ini."""
    sync_data_for_date(datetime.now())


def run_sync_for_one_month(year, month, start_day, end_day_of_month):
    """
    Menjalankan sinkronisasi harian untuk rentang tanggal tertentu dalam satu bulan.
    """
    logger.info(
        f"--> Memulai proses untuk BULAN: {year}-{month:02d} "
        f"(Tanggal {start_day} s.d. {end_day_of_month})"
    )

    try:
        # Tentukan tanggal mulai dan akhir untuk loop harian
        start_date = datetime(year, month, start_day)
        end_date = datetime(year, month, end_day_of_month)
    except ValueError as e:
        logger.warning(
            f"Tanggal tidak valid untuk {year}-{month:02d} "
            f"(dari {start_day} s.d. {end_day_of_month}). Dilewati. Error: {e}"
        )
        return True

    current_date = start_date
    total_days = (end_date - start_date).days + 1
    day_count = 0

    while current_date <= end_date:
        day_count += 1
        logger.info(
            f"    --> Memproses hari ke-{day_count} dari {total_days} "
            f"(di bulan ini): {current_date.strftime('%Y-%m-%d')}"
        )

        success = sync_data_for_date(current_date)

        if not success:
            logger.error(
                f"Sinkronisasi harian GAGAL untuk {current_date.strftime('%Y-%m-%d')}."
            )
            return False

        time.sleep(2)
        current_date += timedelta(days=1)

    logger.info(f"--> Selesai memproses BULAN: {year}-{month:02d}")
    return True


def run_historical_sync(start_date_str):
    """
    MODE 1: User menentukan tanggal start. 
    Akan sync dari start_date sampai H-1 hari ini.
    """
    try:
        start_date = datetime.strptime(start_date_str, '%Y-%m-%d')
        end_date = datetime.now() - timedelta(days=1)
        
        if start_date > end_date:
            logger.warning(f"Tanggal mulai {start_date_str} lebih besar dari hari kemarin. Tidak ada yang diproses.")
            return

        logger.info(f"===== START HISTORICAL SYNC: {start_date.date()} s/d {end_date.date()} =====")
        
        current = start_date
        while current <= end_date:
            success = sync_data_for_date(current)
            if not success:
                logger.error(f"Proses terhenti di tanggal {current.date()} karena error.")
                break
            current += timedelta(days=1)
            time.sleep(1) # Jeda singkat antar hari
            
        logger.info("===== HISTORICAL SYNC SELESAI =====")
    except ValueError:
        logger.error(f"Format tanggal salah: {start_date_str}. Gunakan YYYY-MM-DD.")

def run_sync_for_current_month():
    """
    MODE 2: Deteksi otomatis bulan ini.
    Sync dari tanggal 1 sampai H-1 hari ini.
    """
    today = datetime.now()
    start_date = today.replace(day=1) # Tanggal 1 bulan ini
    end_date = today - timedelta(days=1) # H-1
    
    if start_date > end_date:
        logger.warning("Hari ini adalah tanggal 1, tidak ada data H-1 untuk bulan berjalan.")
        return

    logger.info(f"===== START MONTHLY SYNC (Bulan Berjalan): {start_date.date()} s/d {end_date.date()} =====")
    
    current = start_date
    while current <= end_date:
        sync_data_for_date(current)
        current += timedelta(days=1)
        time.sleep(1)

    logger.info("===== MONTHLY SYNC SELESAI =====")


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
            sync_data_for_date(datetime.now())

        elif mode == 'run_historical':
            # Contoh: python script.py run_historical 2025-09-01
            if len(sys.argv) < 3:
                logger.error("Gunakan: python script.py run_historical YYYY-MM-DD")
            else:
                run_historical_sync(sys.argv[2])

        elif mode == 'run_month':
            # Contoh: python script.py run_month
            run_sync_for_current_month()

        elif mode == 'run_for_date':
            if len(sys.argv) >= 3:
                target_date = datetime.strptime(sys.argv[2], '%Y-%m-%d')
                sync_data_for_date(target_date)
    else:
        start_scheduler()
