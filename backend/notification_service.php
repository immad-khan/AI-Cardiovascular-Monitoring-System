<?php
/**
 * Service: /backend/notification_service.php
 * Purpose: Handle external notifications (SMS/Email) for Critical Alerts
 * Using Twilio Placeholder (Requires Account SID, Token, and From Number)
 */

define('TWILIO_SID', 'YOUR_TWILIO_SID');
define('TWILIO_TOKEN', 'YOUR_TWILIO_TOKEN');
define('TWILIO_FROM', 'YOUR_TWILIO_PHONE_NUMBER');

/**
 * Send SMS to Doctor/Staff for Critical Alert
 * 
 * @param string $toPhoneNumber
 * @param string $message
 * @return bool
 */
function sendCriticalSMS($toPhoneNumber, $message) {
    if (TWILIO_SID == 'YOUR_TWILIO_SID') {
        error_log("Notification Service: SMS simulated - To: $toPhoneNumber, Msg: $message");
        return true; 
    }

    $url = "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_SID . "/Messages.json";
    $data = array(
        'From' => TWILIO_FROM,
        'To' => $toPhoneNumber,
        'Body' => $message
    );

    $post = http_build_query($data);
    $x = curl_init($url);
    curl_setopt($x, CURLOPT_POST, true);
    curl_setopt($x, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($x, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($x, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($x, CURLOPT_USERPWD, TWILIO_SID . ":" . TWILIO_TOKEN);
    curl_setopt($x, CURLOPT_POSTFIELDS, $post);
    
    $response = curl_exec($x);
    curl_close($x);
    
    return $response !== false;
}
?>
