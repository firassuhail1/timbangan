import os
import requests
import serial
import serial.tools.list_ports
import time
from datetime import datetime

# ================== KONFIGURASI ==================
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
FILE_ID = os.path.join(BASE_DIR, 'storage', 'app', 'private', 'current_id.txt')

LOG_FILE = os.path.join(BASE_DIR, 'storage', 'logs', 'timbangan.log')
def log_status(message, level="INFO"):
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    with open(LOG_FILE, 'a', encoding='utf-8') as f:
        f.write(f"[{timestamp}] [{level}] {message}\n")
    print(f"[{timestamp}] [{level}] {message}")

# BLUETOOTH_NAME = 'Timbangan_ESP'  # Ganti jika nama berbeda
PORT_COM = 'COM3' 
BAUD_RATE = 115200
TIMEOUT = 2
URL_PREVIEW = 'http://127.0.0.1:8000/api/timbang/preview'
RETRY_DELAY = 5
CHECK_INTERVAL = 1  # detik
# =================================================

# === WARNA ANSI UNTUK TERMINAL ===
class bcolors:
    HEADER = '\033[95m'
    OKBLUE = '\033[94m'
    OKCYAN = '\033[96m'
    OKGREEN = '\033[92m'
    WARNING = '\033[93m'
    FAIL = '\033[91m'
    ENDC = '\033[0m'
    BOLD = '\033[1m'
    UNDERLINE = '\033[4m'

# === FUNGSI BANTUAN ===
def log_status(message, level="INFO"):
    timestamp = datetime.now().strftime("%H:%M:%S")
    colors = {
        "INFO": bcolors.OKCYAN,
        "SUCCESS": bcolors.OKGREEN,
        "WARNING": bcolors.WARNING,
        "ERROR": bcolors.FAIL
    }
    color = colors.get(level, bcolors.OKCYAN)
    print(f"{color}[{timestamp}] [{level}] {message}{bcolors.ENDC}")

def get_current_id():
    """Baca ID dari file. Return None jika tidak ada."""
    if not os.path.exists(FILE_ID):
        return None
    try:
        with open(FILE_ID, 'r', encoding='utf-8') as f:
            content = f.read().strip()
            return content if content else None
    except Exception as e:
        log_status(f"Error baca file ID: {e}", "ERROR")
        return None

def kirim_berat(berat):
    """Kirim berat ke Laravel API."""
    id_ordersheet = get_current_id()
    if not id_ordersheet:
        return False

    payload = {
        'id': id_ordersheet,
        'berat': round(float(berat), 2)
    }

    try:
        response = requests.post(URL_PREVIEW, json=payload, timeout=3)
        if response.status_code == 200:
            log_status(f"Berat terkirim: {berat:.2f} kg (ID: {id_ordersheet})", "SUCCESS")
            return True
        else:
            log_status(f"Gagal kirim API: {response.status_code} - {response.text}", "ERROR")
    except Exception as e:
        log_status(f"Error koneksi API: {e}", "ERROR")
    return False

# def cari_port_bluetooth():
#     """Cari port Bluetooth berdasarkan nama."""
#     ports = serial.tools.list_ports.comports()
#     for port in ports:
#         desc = str(port.description or "").lower()
#         name = str(port.device or "").lower()
#         if PORT_COM.lower() in desc or PORT_COM.lower() in name:
#             log_status(f"Bluetooth ditemukan: {port.device} ({port.description})", "SUCCESS")
#             return port.device
#     return None

def cari_port_bluetooth():
    """Cari port Bluetooth berdasarkan nama."""
    ports = serial.tools.list_ports.comports()
    for port in ports:
        desc = str(port.description or "").lower()
        name = str(port.device or "").lower()
        if PORT_COM.lower() in name or 'timbangan_esp' in desc:
            log_status(f"Bluetooth ditemukan: {port.device} ({port.description})", "SUCCESS")
            return port.device
    return None

def connect_bluetooth():
    """Coba sambungkan ke Bluetooth."""
    port = cari_port_bluetooth()
    if not port:
        return None
    try:
        ser = serial.Serial(port, BAUD_RATE, timeout=TIMEOUT)
        log_status(f"Terhubung ke timbangan: {port}", "SUCCESS")
        return ser
    except Exception as e:
        log_status(f"Gagal koneksi ke {port}: {e}", "ERROR")
        return None

# ================== MAIN LOOP ==================
def main():
    log_status("Timbangan Python AKTIF. Menunggu ID & Bluetooth...", "INFO")

    ser = None
    last_id = None
    id_warning_shown = False
    bluetooth_warning_shown = False

    while True:
        current_id = get_current_id()

        # === CEK ID ===
        if not current_id:
            if not id_warning_shown:
                log_status("ID belum dipilih! Klik 'Timbang' di web terlebih dahulu.", "WARNING")
                id_warning_shown = True
            bluetooth_warning_shown = False  # reset bluetooth warning
        else:
            id_warning_shown = False
            if current_id != last_id:
                log_status(f"ID BARU: {current_id} → Timbangan diaktifkan", "INFO")
                last_id = current_id

        # === CEK BLUETOOTH (hanya jika ID ada) ===
        if current_id:
            if not ser:
                if not bluetooth_warning_shown:
                    log_status(f"Mencari perangkat Bluetooth '{PORT_COM}'...", "INFO")
                    bluetooth_warning_shown = True
                ser = connect_bluetooth()
                if not ser:
                    time.sleep(RETRY_DELAY)
                    continue
            else:
                bluetooth_warning_shown = False

            # === BACA DATA DARI TIMBANGAN ===
            try:
                if ser.in_waiting > 0:
                    line = ser.readline().decode('utf-8', errors='ignore').strip()
                    if line:
                        try:
                            berat = float(line)
                            if berat >= 0:
                                kirim_berat(berat)
                            else:
                                log_status(f"Berat negatif diabaikan: {berat}", "WARNING")
                        except ValueError:
                            log_status(f"Data tidak valid: '{line}'", "WARNING")
            except serial.SerialException as e:
                log_status(f"Koneksi Bluetooth terputus: {e}", "ERROR")
                ser.close()
                ser = None
                bluetooth_warning_shown = False
            except Exception as e:
                log_status(f"Error baca serial: {e}", "ERROR")

        # === TIDAK ADA ID → MATIKAN TIMBANGAN ===
        else:
            if ser:
                log_status("ID dihapus. Menonaktifkan timbangan...", "INFO")
                try:
                    ser.close()
                except:
                    pass
                ser = None
            last_id = None

        time.sleep(CHECK_INTERVAL)

# ================== JALANKAN ==================
if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        log_status("Timbangan dihentikan oleh user.", "INFO")