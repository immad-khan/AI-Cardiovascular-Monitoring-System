/*
  ESP32 Firmware for AD8232 ECG Heart Monitor
  ============================================
  Wiring:
    AD8232  -->  ESP32
    OUTPUT  -->  GPIO 36 (VP - ADC pin)
    3.3V    -->  3.3V
    GND     -->  GND
    LO+     -->  GPIO 32 (Lead-Off Detection)
    LO-     -->  GPIO 33 (Lead-Off Detection)

  The ESP32 samples the ECG signal at ~360 Hz for 5 seconds,
  then sends the data over USB Serial to the Raspberry Pi.
*/

#define ECG_PIN     36   // Analog input from AD8232 OUTPUT
#define LO_PLUS     32   // Lead-off detection +
#define LO_MINUS    33   // Lead-off detection -

// Sampling config
const int SAMPLE_RATE_HZ  = 360;
const int DURATION_SEC    = 5;
const int TOTAL_SAMPLES   = SAMPLE_RATE_HZ * DURATION_SEC; // 1800 samples
const int SAMPLE_DELAY_US = 1000000 / SAMPLE_RATE_HZ;      // ~2778 microseconds

int ecgBuffer[TOTAL_SAMPLES];

void setup() {
  Serial.begin(115200);
  pinMode(LO_PLUS,  INPUT);
  pinMode(LO_MINUS, INPUT);
  analogReadResolution(12); // 12-bit ADC (0-4095)
  delay(1000);
  Serial.println("AD8232 ECG Monitor Ready");
}

void loop() {
  // Check lead-off: if electrodes are detached, skip this cycle
  if (digitalRead(LO_PLUS) == 1 || digitalRead(LO_MINUS) == 1) {
    Serial.println("{\"error\":\"leads_off\"}");
    delay(2000);
    return;
  }

  Serial.println("Collecting ECG...");

  // Collect samples at exact timing
  for (int i = 0; i < TOTAL_SAMPLES; i++) {
    ecgBuffer[i] = analogRead(ECG_PIN);
    delayMicroseconds(SAMPLE_DELAY_US);
  }

  // Build comma-separated string of ECG values
  // Send as a single JSON line for easy parsing on the Pi
  Serial.print("{\"ecg\":\"");
  for (int i = 0; i < TOTAL_SAMPLES; i++) {
    Serial.print(ecgBuffer[i]);
    if (i < TOTAL_SAMPLES - 1) Serial.print(",");
  }
  Serial.println("\"}");

  // Wait before next reading (adjust as needed)
  delay(10000); // 10 seconds between readings
}
