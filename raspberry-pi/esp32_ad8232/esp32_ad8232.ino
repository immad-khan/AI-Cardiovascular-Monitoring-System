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
#define ECG_PIN     36
#define LO_PLUS     32
#define LO_MINUS    33

const int SAMPLE_RATE_HZ  = 360;
const int DURATION_SEC    = 5;
const int TOTAL_SAMPLES   = SAMPLE_RATE_HZ * DURATION_SEC;
const int SAMPLE_DELAY_US = 1000000 / SAMPLE_RATE_HZ;

int ecgBuffer[TOTAL_SAMPLES];

void setup() {
  Serial.begin(115200);
  pinMode(LO_PLUS,  INPUT);
  pinMode(LO_MINUS, INPUT);
  analogReadResolution(12);
  delay(1000);
  Serial.println("AD8232 ECG Monitor Ready");
}

void loop() {
  if (digitalRead(LO_PLUS) == 1 || digitalRead(LO_MINUS) == 1) {
    Serial.println("{\"error\":\"leads_off\"}");
    delay(2000);
    return;
  }
  Serial.println("Collecting ECG...");
  for (int i = 0; i < TOTAL_SAMPLES; i++) {
    ecgBuffer[i] = analogRead(ECG_PIN);
    delayMicroseconds(SAMPLE_DELAY_US);
  }
  Serial.print("{\"ecg\":\"");
  for (int i = 0; i < TOTAL_SAMPLES; i++) {
    Serial.print(ecgBuffer[i]);
    if (i < TOTAL_SAMPLES - 1) Serial.print(",");
  }
  Serial.println("\"}");
  delay(10000);
}