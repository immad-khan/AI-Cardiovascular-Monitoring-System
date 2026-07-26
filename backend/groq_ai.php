<?php
// backend/groq_ai.php
// Groq API helper for AI features

// Load API key from environment or .env file
$groq_key = getenv('GROQ_API_KEY');
if (!$groq_key || $groq_key === 'YOUR_API_KEY') {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) continue;
            if (strpos($line, 'GROQ_API_KEY=') === 0) {
                $groq_key = trim(substr($line, strlen('GROQ_API_KEY=')));
                break;
            }
        }
    }
}
define('GROQ_API_KEY', $groq_key ?: 'YOUR_API_KEY');
define('GROQ_MODEL', 'llama-3.3-70b-versatile');
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');

/**
 * Send a chat completion request to Groq
 * @param array $messages Array of {role, content} messages
 * @param float $temperature Temperature (0-2)
 * @param int $max_tokens Max tokens
 * @return string|false The assistant reply content, or false on error
 */
function groqChat($messages, $temperature = 0.7, $max_tokens = 2048) {
    $payload = json_encode([
        'model' => GROQ_MODEL,
        'messages' => $messages,
        'temperature' => $temperature,
        'max_tokens' => $max_tokens,
    ]);

    $ch = curl_init(GROQ_API_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return false;
    }

    $data = json_decode($response, true);
    if (!$data || !isset($data['choices'][0]['message']['content'])) {
        return false;
    }

    return $data['choices'][0]['message']['content'];
}

/**
 * Build a patient health context string from database records
 * Used to give the AI context about the patient
 */
function buildPatientContext($conn, $patientId) {
    $context = [];

    // Patient info
    try {
        $stmt = $conn->prepare("SELECT * FROM patients WHERE \"patientID\" = ?");
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($patient) {
            $context['patient'] = [
                'name' => $patient['name'],
                'age' => $patient['age'],
                'gender' => $patient['gender'],
                'medical_history' => $patient['medical_history'] ?? 'None recorded',
            ];
        }
    } catch (PDOException $e) {}

    // Latest vitals readings (last 20)
    try {
        $stmt = $conn->prepare("
            SELECT vr.timestamp, vr.heartRate, vr.SpO2, vr.RespirationImpedance, 
                   vr.final_prediction, vr.\"confidenceScore\", vr.hrv_sdnn, vr.hrv_rmssd, 
                   vr.signal_quality, vr.arrhythmia_flags
            FROM vital_sign_readings vr
            JOIN monitoring_devices md ON vr.\"deviceID\" = md.\"deviceID\"
            WHERE md.\"patientID\" = ?
            ORDER BY vr.timestamp DESC LIMIT 20
        ");
        $stmt->execute([$patientId]);
        $context['readings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $context['readings'] = [];
    }

    // ECG predictions
    try {
        $stmt = $conn->prepare("
            SELECT predictionClass, \"confidenceScore\", \"inference_time_ms\", model_version, timestamp
            FROM \"AI_PREDICTION_LOG\"
            WHERE \"readingID\" IN (
                SELECT vr.\"readingID\" FROM vital_sign_readings vr
                JOIN monitoring_devices md ON vr.\"deviceID\" = md.\"deviceID\"
                WHERE md.\"patientID\" = ?
            )
            ORDER BY timestamp DESC LIMIT 15
        ");
        $stmt->execute([$patientId]);
        $context['ai_predictions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $context['ai_predictions'] = [];
    }

    // Critical alerts
    try {
        $stmt = $conn->prepare("
            SELECT a.timestamp, a.message, a.status
            FROM \"CRITICAL_ALERT\" a
            JOIN monitoring_devices md ON a.\"deviceID\" = md.\"deviceID\"
            WHERE md.\"patientID\" = ?
            ORDER BY a.timestamp DESC LIMIT 10
        ");
        $stmt->execute([$patientId]);
        $context['alerts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $context['alerts'] = [];
    }

    // Device info
    try {
        $stmt = $conn->prepare("
            SELECT model, serialNo, status, last_heartbeat
            FROM monitoring_devices WHERE \"patientID\" = ?
        ");
        $stmt->execute([$patientId]);
        $context['devices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $context['devices'] = [];
    }

    return $context;
}

/**
 * Format patient context into a readable string for the AI prompt
 */
function formatContextForPrompt($context) {
    $parts = [];

    if (!empty($context['patient'])) {
        $p = $context['patient'];
        $parts[] = "PATIENT INFO:\n- Name: {$p['name']}\n- Age: {$p['age']}\n- Gender: {$p['gender']}\n- Medical History: {$p['medical_history']}";
    }

    if (!empty($context['readings'])) {
        $readingsStr = "VITAL SIGNS READINGS (most recent first):\n";
        foreach ($context['readings'] as $r) {
            $date = $r['timestamp'];
            $hr = $r['heartRate'] ?? 'N/A';
            $spo2 = $r['SpO2'] ?? 'N/A';
            $resp = $r['RespirationImpedance'] ?? 'N/A';
            $pred = $r['final_prediction'] ?? 'N/A';
            $conf = isset($r['confidenceScore']) ? round($r['confidenceScore'] * 100, 1) . '%' : 'N/A';
            $sdnn = $r['hrv_sdnn'] !== null ? round($r['hrv_sdnn'], 1) . 'ms' : 'N/A';
            $rmssd = $r['hrv_rmssd'] !== null ? round($r['hrv_rmssd'], 1) . 'ms' : 'N/A';
            $sqi = $r['signal_quality'] ?? 'N/A';
            $flags = $r['arrhythmia_flags'] ?? 'None';
            $readingsStr .= "  [$date] HR: {$hr} BPM, SpO2: {$spo2}%, Resp: {$resp}, AI Prediction: {$pred} (Conf: {$conf}), HRV SDNN: {$sdnn}, RMSSD: {$rmssd}, SQI: {$sqi}, Flags: {$flags}\n";
        }
        $parts[] = $readingsStr;
    }

    if (!empty($context['ai_predictions'])) {
        $predStr = "ECG AI PREDICTIONS:\n";
        foreach ($context['ai_predictions'] as $ap) {
            $predStr .= "  [{$ap['timestamp']}] Class: {$ap['predictionClass']}, Confidence: " . round(($ap['confidenceScore'] ?? 0) * 100, 1) . "%, Model: {$ap['model_version']}\n";
        }
        $parts[] = $predStr;
    }

    if (!empty($context['alerts'])) {
        $alertStr = "CRITICAL ALERTS:\n";
        foreach ($context['alerts'] as $a) {
            $alertStr .= "  [{$a['timestamp']}] {$a['message']} (Status: {$a['status']})\n";
        }
        $parts[] = $alertStr;
    }

    if (!empty($context['devices'])) {
        $devStr = "CONNECTED DEVICES:\n";
        foreach ($context['devices'] as $d) {
            $devStr .= "  Model: {$d['model']}, Serial: {$d['serialNo']}, Status: {$d['status']}, Last Heartbeat: {$d['last_heartbeat']}\n";
        }
        $parts[] = $devStr;
    }

    return implode("\n\n", $parts);
}
?>
