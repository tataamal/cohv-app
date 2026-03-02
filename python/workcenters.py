import logging
import os
from flask import Flask, jsonify
from apscheduler.schedulers.background import BackgroundScheduler
from apscheduler.triggers.cron import CronTrigger
from apscheduler.triggers.date import DateTrigger
from datetime import datetime
from pyrfc import Connection
import pymysql
import atexit
from datetime import datetime



SAP_CONFIG = {
    'ashost': os.getenv('SAP_ASHOST', '192.168.254.154'),
    'sysnr': os.getenv('SAP_SYSNR', '01'),
    'client': os.getenv('SAP_CLIENT', '300'),
    'user': os.getenv('SAP_USER', 'auto_email'),
    'passwd': os.getenv('SAP_PASS', '11223344'),
}

DB_CONFIG = {
    'host': os.getenv('DB_HOST', '192.168.90.114'),
    'user': os.getenv('DB_USER', 'python_client'),
    'password': os.getenv('DB_PASS', 'singgampang'),
    'db': os.getenv('DB_NAME', 'master_workcenters'),
    'cursorclass': pymysql.cursors.DictCursor,
    'autocommit': False,
}

# Schedule harian (default jam 01:00)
SCHEDULE_HOUR = int(os.getenv('SCHEDULE_HOUR', '1'))
SCHEDULE_MINUTE = int(os.getenv('SCHEDULE_MINUTE', '0'))

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)

def get_db_connection():
    return pymysql.connect(**DB_CONFIG)

def safe_str(v):
    """Convert value to trimmed string or None."""
    if v is None:
        return None
    s = str(v).strip()
    return s if s != '' else None

def update_workcenter_from_sap():
    """
    1) Ambil data workcenter dari MySQL (id, kode_wc, plant)
    2) Call SAP RFC CR_CAPACITIES_OF_WORKCENTER
    3) Ambil hanya field:
       - BEGZT -> start_time
       - EINZH -> operating_time
       - ENDZT -> end_time
       - KAPAH -> capacity
    4) Update tabel workcenters
    """
    logger.info("Starting job: update_workcenter_from_sap")

    db_conn = None
    sap_conn = None

    try:
        db_conn = get_db_connection()

        try:
            sap_conn = Connection(**SAP_CONFIG)
            logger.info("Connected to SAP successfully.")
        except Exception as e:
            logger.error(f"Failed to connect to SAP: {e}")
            return

        with db_conn.cursor() as cursor:
            # Sesuaikan dengan tabel Laravel terbaru: plant (bukan WERKS)
            sql_select = "SELECT id, kode_wc, plant FROM workcenters WHERE deleted_at IS NULL"
            cursor.execute(sql_select)
            workcenters = cursor.fetchall()

            logger.info(f"Found {len(workcenters)} workcenters to process.")
            processed = 0
            updated = 0

            for wc in workcenters:
                wc_id = wc.get('id')
                wc_code = safe_str(wc.get('kode_wc'))
                plant = safe_str(wc.get('plant'))

                if not wc_id or not wc_code or not plant:
                    continue

                processed += 1

                try:
                    result = sap_conn.call(
                        'CR_CAPACITIES_OF_WORKCENTER',
                        ARBPL=wc_code,
                        WERKS=plant
                    )

                    # T_KAKO is list of capacities rows
                    t_kako = result.get('T_KAKO', [])

                    # Default kosong
                    begzt = None   # Start time (TIMS -> string like HHMMSS)
                    endzt = None   # End time
                    einzh = None   # Operating time (DEC)
                    kapah = None   # Capacity (DEC)

                    if t_kako:
                        r = t_kako[0]
                        begzt = safe_str(r.get('BEGZT'))
                        endzt = safe_str(r.get('ENDZT'))
                        einzh = r.get('EINZH')   # numeric/decimal
                        kapah = r.get('KAPAH')   # numeric/decimal

                    # Normalisasi numeric biar aman disimpan ke kolom string DB
                    # (Kalau kolom DB kamu string, ini aman. Kalau nanti diganti numeric, juga masih bisa.)
                    operating_time = safe_str(einzh)
                    capacity = safe_str(kapah)

                    sql_update = """
                        UPDATE workcenters
                        SET start_time = %s,
                            end_time = %s,
                            operating_time = %s,
                            capacity = %s,
                            updated_at = %s
                        WHERE id = %s
                    """
                    cursor.execute(sql_update, (begzt, endzt, operating_time, capacity, datetime.now(), wc_id))
                    updated += 1

                    logger.info(
                        f"Updated id={wc_id} wc={wc_code} plant={plant} "
                        f"-> start_time(BEGZT)={begzt}, operating_time(EINZH)={operating_time}, "
                        f"end_time(ENDZT)={endzt}, capacity(KAPAH)={capacity}"
                    )

                except Exception as rfc_err:
                    logger.error(f"Error processing wc={wc_code} plant={plant}: {rfc_err}")

            db_conn.commit()
            logger.info(f"Job completed. processed={processed}, updated={updated}")

    except Exception as e:
        logger.error(f"Database/General error: {e}")
        if db_conn:
            try:
                db_conn.rollback()
            except Exception:
                pass
    finally:
        if sap_conn:
            try:
                sap_conn.close()
            except Exception:
                pass
        if db_conn:
            try:
                db_conn.close()
            except Exception:
                pass

# =========================
# Scheduler (HARIAN)
# =========================
scheduler = BackgroundScheduler()

# Jalan setiap hari jam 01:00 (default), bisa diubah via ENV
scheduler.add_job(
    func=update_workcenter_from_sap,
    trigger=CronTrigger(hour=SCHEDULE_HOUR, minute=SCHEDULE_MINUTE),
    id="update_workcenter_from_sap_daily",
    replace_existing=True
)

scheduler.add_job(
    func=update_workcenter_from_sap,
    trigger=DateTrigger(run_date=datetime.now()),
    id="update_workcenter_from_sap_startup",
    replace_existing=True
)

scheduler.start()
atexit.register(lambda: scheduler.shutdown(wait=False))

# =========================
# Flask endpoints
# =========================
@app.route('/')
def index():
    return "SAP Workcenter Daily Scheduler is Running. Check logs for details."

@app.route('/run-manual', methods=['POST'])
def run_manual():
    try:
        update_workcenter_from_sap()
        return jsonify({"status": "success", "message": "Manual update executed successfully."}), 200
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500

if __name__ == '__main__':
    # use_reloader=False supaya scheduler tidak double-run
    app.run(debug=True, use_reloader=False, port=5014)
