<?php
// api/ai_chat.php
session_start();
header('Content-Type: application/json');

include("../config/DB_Config.php");
include("../backend/groq_ai.php");

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'patient') {
    echo json_encode(['success' => false, 'message' => 'AI Assistant is available for patients only.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');
$chatHistory = $input['history'] ?? [];

if (empty($userMessage)) {
    echo json_encode(['success' => false, 'message' => 'Empty message']);
    exit();
}

// Get patient ID from email
$patientId = null;
try {
    $stmt = $conn->prepare("SELECT \"patientID\" FROM patients WHERE email = ?");
    $stmt->execute([$_SESSION['email']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $patientId = $row['patientID'];
} catch (PDOException $e) {}

if (!$patientId) {
    echo json_encode(['success' => false, 'message' => 'Patient record not found.']);
    exit();
}

// Build context
$context = buildPatientContext($conn, $patientId);
$contextStr = formatContextForPrompt($context);

$systemPrompt = "You are DigiHealth AI Assistant, a friendly and knowledgeable cardiovascular health chatbot for the DigiHealth hospital monitoring system. You help patients understand their health data.

You have access to the patient's real-time monitoring data including:
- Vital signs (Heart Rate, SpO2, Respiration)
- ECG rhythm analysis and AI predictions
- HRV metrics (SDNN, RMSSD)
- Signal quality indicators
- Critical alerts history
- Connected IoT device status

PATIENT DATA:
{$contextStr}

RULES:
- Be warm, empathetic, and reassuring while remaining professional
- Explain medical terms in simple language
- If the patient asks about their data, reference the specific values above
- If asked about symptoms or concerns, provide general wellness advice but always recommend consulting their doctor
- Never diagnose or prescribe medication
- Keep responses concise (2-4 paragraphs max)
- If you see concerning data values, gently encourage the patient to contact their healthcare provider
- Use the patient's name when possible to personalize the conversation
- Always remind patients that your responses are informational and not a substitute for professional medical advice";

// Build messages array with history (limit to last 10 messages for context)
$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
];

// Add chat history (last 10 messages)
$recentHistory = array_slice($chatHistory, -10);
foreach ($recentHistory as $msg) {
    $messages[] = [
        'role' => isset($msg['role']) ? $msg['role'] : ($msg['sender'] === 'user' ? 'user' : 'assistant'),
        'content' => $msg['content'] ?? $msg['text'] ?? '',
    ];
}

// Add current user message
$messages[] = ['role' => 'user', 'content' => $userMessage];

$reply = groqChat($messages, 0.7, 1024);

if ($reply === false) {
    echo json_encode(['success' => false, 'message' => 'AI service temporarily unavailable. Please try again later.']);
    exit();
}

echo json_encode([
    'success' => true,
    'reply' => $reply,
]);
?>
