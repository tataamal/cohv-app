import os
import traceback
from pyrfc import Connection, ABAPApplicationError, CommunicationError
from concurrent.futures import ThreadPoolExecutor
from dotenv import load_dotenv
import pymysql  # <-- DIUBAH: Menggunakan pymysql

# Muat environment variables dari file .env
load_dotenv()

# --- FUNGSI KONEKSI ---

def connect_sap():
    """Membangun koneksi ke SAP menggunakan kredensial dari .env"""
    try:
        return Connection(
            user=os.environ.get('SAP_USER'),
            passwd=os.environ.get('SAP_PASSWD'),
            ashost='192.168.254.154',
            sysnr='01',
            client='300',
            lang='EN',
        )
    except Exception as e:
        print(f"ERROR: Gagal terhubung ke SAP. Pesan: {e}")
        raise

def connect_db():
    """Membangun koneksi ke MySQL menggunakan kredensial dari .env (menggunakan PyMySQL)"""
    try:
        # DIUBAH: Menggunakan pymysql.connect
        # Pastikan DB_PORT di .env adalah angka jika ada, atau biarkan pymysql menggunakan default (3306)
        db_port_str = os.environ.get('DB_PORT')
        db_port = int(db_port_str) if db_port_str else 3306

        return pymysql.connect(
            host=os.environ.get('DB_HOST'),
            port=db_port,
            database=os.environ.get('DB_DATABASE'),
            user=os.environ.get('DB_USERNAME'),
            password=os.environ.get('DB_PASSWORD'),
            # charset='utf8mb4', # Ditambahkan untuk konsistensi
            # autocommit=False     # Ditambahkan untuk konsistensi
        )
    except pymysql.Error as err: # DIUBAH: Menangkap pymysql.Error
        print(f"ERROR: Gagal terhubung ke MySQL. Pesan: {err}")
        raise
    except ValueError:
        print(f"ERROR: DB_PORT ('{db_port_str}') di file .env harus berupa angka.")
        raise


# --- FUNGSI LOGIKA ---

def save_cogi_to_db(cogi_data):
    """Menyimpan data COGI ke database MySQL."""
    if not cogi_data:
        print("INFO: Tidak ada data COGI baru untuk disimpan.")
        return 0

    db_conn = None
    cursor = None  # DIUBAH: Inisialisasi cursor ke None
    try:
        db_conn = connect_db()
        cursor = db_conn.cursor()

        cursor.execute("TRUNCATE TABLE tb_cogi")
        print("INFO: Tabel tb_cogi berhasil dikosongkan.")

        query = """
            INSERT INTO tb_cogi (
                MANDT, AUFNR, RSNUM, BUDAT, KDAUF, KDPOS, DWERK, MATNRH, MAKTXH,
                DISPOH, PSMNG, WEMNG, MATNR, MAKTX, DISPO, ERFMG, AUFNRX, P1, PW,
                MENGE, MEINS, LGORTH, LGORT, DEVISI, PESAN_ERROR, created_at, updated_at
            ) VALUES (
                %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
                %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW()
            )
        """
        data_to_insert = [
            (
                d.get('MANDT'), d.get('AUFNR'), d.get('RSNUM'), d.get('BUDAT') or None, d.get('KDAUF'),
                d.get('KDPOS'), d.get('DWERK'), d.get('MATNRH'), d.get('MAKTXH'), d.get('DISPOH'),
                d.get('PSMNG'), d.get('WEMNG'), d.get('MATNR'), d.get('MAKTX'), d.get('DISPO'),
                d.get('ERFMG'), d.get('AUFNRX'), d.get('P1'), d.get('PW'), d.get('MENGE'),
                d.get('MEINS'), d.get('LGORTH'), d.get('LGORT'), d.get('DEVISI'), d.get('PESAN_ERROR')
            ) for d in cogi_data
        ]

        # PyMySQL menggunakan %s sebagai placeholder, sama seperti mysql.connector
        cursor.executemany(query, data_to_insert)
        db_conn.commit()

        saved_count = cursor.rowcount
        print(f"SUCCESS: Berhasil menyimpan {saved_count} baris data ke tb_cogi.")
        return saved_count
    
    except pymysql.Error as e: # DIUBAH: Menangkap error spesifik pymysql
        print(f"ERROR: Gagal menyimpan ke DB. Pesan: {e}")
        if db_conn:
            db_conn.rollback()
        raise
    except Exception as e: # Menangkap error non-database lainnya
        print(f"ERROR: Terjadi kesalahan lain. Pesan: {e}")
        if db_conn:
            db_conn.rollback()
        raise
    finally:
        # DIUBAH: Logika penutupan koneksi disesuaikan dengan pymysql
        if cursor:
            cursor.close()
        if db_conn:
            db_conn.close()

def fetch_data_for_plant(plant):
    """Worker function untuk mengambil data SAP untuk satu plant."""
    print(f"PROCESS: Mulai mengambil data untuk plant: {plant}...")
    try:
        conn = connect_sap()
        result = conn.call('P', P_WERKS=plant)
        conn.close()

        data = result.get('T_DATA1', [])
        print(f"SUCCESS: Selesai mengambil data untuk plant: {plant}, ditemukan {len(data)} baris.")
        return data
    except (ABAPApplicationError, CommunicationError) as e:
        print(f"WARNING: Error SAP saat mengambil data untuk plant {plant}: {e}")
        return []

# --- FUNGSI UTAMA UNTUK EKSEKUSI ---

def run_synchronization():
    """Fungsi utama yang menjalankan seluruh proses sinkronisasi."""
    print("==============================================")
    print("====== MEMULAI PROSES SINKRONISASI COGI ======")
    print("==============================================")

    try:
        # 1. Definisikan daftar WERKS
        plants = ['1001', '1000', '2000', '3000', '1200']
        all_cogi_data = []

        # 2. Ambil data dari SAP secara paralel
        with ThreadPoolExecutor(max_workers=len(plants)) as executor:
            # executor.map akan menjalankan fetch_data_for_plant untuk setiap item di 'plants'
            results = executor.map(fetch_data_for_plant, plants)
            # Kumpulkan semua hasil dari setiap plant menjadi satu list besar
            for plant_data in results:
                if plant_data:
                    all_cogi_data.extend(plant_data)

        # 3. Simpan semua data yang terkumpul ke database
        save_cogi_to_db(all_cogi_data)

        print("\n==============================================")
        print("======= SINKRONISASI COGI SELESAI ========")
        print("==============================================")

    except Exception as e:
        print("\n!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!")
        print(f"!!!!!! PROSES SINKRONISASI GAGAL TOTAL !!!!!!")
        print(f"Error: {e}")
        traceback.print_exc()
        print("!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!")

# --- TITIK MASUK EKSEKUSI SKRIP ---

if __name__ == '__main__':
    # Baris ini akan memanggil fungsi run_synchronization()
    # secara otomatis ketika file python dijalankan.
    run_synchronization()