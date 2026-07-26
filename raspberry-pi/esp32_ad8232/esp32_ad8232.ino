#include <WiFi.h>
#include <ArduinoOTA.h>

const char* ssid     = "ASIN";
const char* password = "abcdefgh1";

void setup() {
  Serial.begin(115200);
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println(WiFi.localIP());
  ArduinoOTA.begin();
  Serial.println("OTA Ready");
}

void loop() {
  ArduinoOTA.handle();
}