#!/usr/bin/env python3
"""
Raspberry Pi ECG Data Forwarder
================================
Reads ECG data from the ESP32 (connected via USB serial),
then POSTs it to the Azure API endpoint for AI analysis and storage.

Setup:
  pip install pyserial requests

Run:
  python3 ecg_forwarder.py
"""

import serial
import serial.tools.list_ports
import requests
import json
import time
import uuid
import logging

# ─── Configuration ───────────────────────────────────────────────────────────
AZURE_API_URL = "https://digihealth-api-123-anhvh5hbafd9f6f7.uaenorth-01.azurewebsites.net/api/vitals.php"
SERIAL_BAUD   = 115200
SERIAL_PORT   = None  # Set to None for auto-detection, or e.g. "/dev/ttyUSB0"

# Get the MAC address of this Raspberry Pi to use as the device identifier
import subprocess
def get_mac_address():
    try:
        result = subprocess.check_output(["cat", "/sys/class/net/eth0/address"]).decode().strip()
        return result
    except:
        return str(uuid.uuid4())[:17]  # fallback unique ID

DEVICE_MAC = get_mac_address()
# ─────────────────────────────────────────────────────────────────────────────

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s"
)
log = logging.getLogger(__name__)


def find_serial_port():
    """Auto-detect the ESP32 serial port."""
    ports = serial.tools.list_ports.comports()
    for port in ports:
        # ESP32 usually shows up as CP210x or CH340
        if any(k in port.description for k in ["CP210", "CH340", "USB Serial", "UART"]):
            log.info(f"Found ESP32 on port: {port.device}")
            return port.device
    # Fallback: return first available USB port
    if ports:
        log.warning(f"No ESP32 detected by name, using: {ports[0].device}")
        return ports[0].device
    raise Exception("No serial port found. Is the ESP32 connected via USB?")


def send_to_azure(ecg_raw_string):
    """Send ECG data to the Azure API endpoint."""
    payload = {
        "mac_address": DEVICE_MAC,
        "ECG_Raw":     ecg_raw_string,
        # AD8232 only measures ECG. Heart rate is derived by the AI model.
        # If you add other sensors, populate these fields:
        # "heartRate":       72,
        # "SpO2":            98.5,
        # "Temperature":     36.6,
        # "RespirationRate": 18,
    }

    try:
        log.info(f"Sending {len(ecg_raw_string.split(','))} ECG samples to Azure...")
        response = requests.post(AZURE_API_URL, data=payload, timeout=30)
        result = response.json()

        if result.get("success"):
            ai = result.get("ai", {})
            log.info(
                f"✅ Success! ReadingID={result.get('readingID')} | "
                f"Prediction={ai.get('prediction')} | "
                f"Confidence={ai.get('confidence'):.2%} | "
                f"HeartRate={ai.get('hr')} BPM"
            )
        else:
            log.error(f"❌ API Error: {result.get('message')}")

    except requests.exceptions.Timeout:
        log.error("Request timed out. Azure may be slow or unreachable.")
    except requests.exceptions.ConnectionError:
        log.error("Could not connect to Azure. Check your internet connection.")
    except Exception as e:
        log.error(f"Unexpected error: {e}")


def main():
    port = SERIAL_PORT or find_serial_port()
    log.info(f"Connecting to ESP32 on {port} at {SERIAL_BAUD} baud...")
    log.info(f"Device MAC: {DEVICE_MAC}")
    log.info(f"Azure endpoint: {AZURE_API_URL}")

    with serial.Serial(port, SERIAL_BAUD, timeout=30) as ser:
        log.info("✅ Serial connection established. Waiting for ECG data...")

        while True:
            try:
                line = ser.readline().decode("utf-8", errors="ignore").strip()

                if not line:
                    continue

                log.debug(f"Serial: {line[:80]}...")  # Print first 80 chars

                # Skip status messages
                if line.startswith("AD8232") or line.startswith("Collecting"):
                    log.info(f"ESP32: {line}")
                    continue

                # Handle lead-off error
                if '"error":"leads_off"' in line:
                    log.warning("⚠️  Electrodes are not attached to the patient!")
                    continue

                # Parse the ECG JSON from ESP32
                if line.startswith('{"ecg":'):
                    try:
                        data = json.loads(line)
                        ecg_string = data.get("ecg", "")

                        if ecg_string:
                            send_to_azure(ecg_string)
                        else:
                            log.warning("Received empty ECG data.")

                    except json.JSONDecodeError:
                        log.error(f"Failed to parse JSON: {line[:100]}")

            except KeyboardInterrupt:
                log.info("Stopped by user.")
                break
            except serial.SerialException as e:
                log.error(f"Serial error: {e}. Reconnecting in 5s...")
                time.sleep(5)


if __name__ == "__main__":
    main()