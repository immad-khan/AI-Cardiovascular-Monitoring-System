<?php
// api/ai_health_summary.php
session_start();
header('Content-Type: application/json');

include("../config/DB_Config.php");
include("../backend/groq_ai.php");

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'doctor', 'patient'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$patientId = $_GET['patientId'] ?? null;
if (!$patientId) {
    echo json_encode(['success' => false, 'message' => 'Patient ID required']);
    exit();
}

// Build patient context
$context = buildPatientContext($conn, $patientId);
$contextStr = formatContextForPrompt($context);

if (empty($context['readings']) && empty($context['ai_predictions'])) {
    echo json_encode([
        'success' => true,
        'summary' => "No vital signs or ECG data recorded yet for this patient. Once monitoring data is available, the AI health summary will provide insights based on heart rate trends, SpO2 levels, ECG rhythm analysis, and HRV metrics.",
        'risk_level' => 'N/A',
    ]);
    exit();
}

$systemPrompt = "You are DigiHealth AI, a cardiovascular health assistant for a hospital monitoring system. You analyze ECG data, vital signs, and AI predictions to provide clear, concise health summaries for patients.

Your summary MUST include:
1. **Overall Health Status** - A brief 1-2 sentence assessment
2. **Key Findings** - Bullet points of the most important observations from the data
3. **Heart Rhythm Analysis** - Interpret the AI predictions (Normal, AFib, Tachycardia, etc.) and confidence scores
4. **Vital Signs Assessment** - Comment on HR, SpO2, respiration trends (normal/abnormal ranges)
5. **HRV Analysis** - Interpret SDNN and RMSSD values (SDNN normal >50ms, RMSSD normal >20ms)
6. **Risk Level** - Classify as Low / Moderate / High / Critical with a brief reason
7. **Recommendations** - 2-3 actionable recommendations

IMPORTANT RULES:
- Be professional but patient-friendly (avoid heavy medical jargon)
- Use plain text with clear section headers (use ** for headers)
- If data is limited, acknowledge it and provide what insights you can
- Always include a disclaimer that this is AI-generated and not a substitute for medical advice
- If you see concerning patterns (low SpO2, high HR, arrhythmia flags), highlight them prominently";

$userPrompt = "Based on the following patient data, provide a comprehensive AI health summary:\n\n{$contextStr}";

$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => $userPrompt],
];

$reply = groqChat($messages, 0.5, 1500);

if ($reply === false) {
    echo json_encode(['success' => false, 'message' => 'AI service temporarily unavailable. Please try again.']);
    exit();
}

// Determine risk level from the response
$risk_level = 'Unknown';
$replyLower = strtolower($reply);
if (strpos($replyLower, 'critical') !== false) $risk_level = 'Critical';
elseif (strpos($replyLower, 'high risk') !== false || strpos($replyLower, 'high concern') !== false) $risk_level = 'High';
elseif (strpos($replyLower, 'moderate') !== false || strpos($replyLower, 'medium') !== false) $risk_level = 'Moderate';
elseif (strpos($replyLower, 'low risk') !== false || strpos($replyLower, 'low concern') !== false) $risk_level = 'Low';

echo json_encode([
    'success' => true,
    'summary' => $reply,
    'risk_level' => $risk_level,
]);
?>
