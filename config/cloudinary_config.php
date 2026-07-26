<?php
// Cloudinary Configuration
define('CLOUDINARY_CLOUD_NAME', 'dcdhsyj86');
define('CLOUDINARY_API_KEY', '921185953673167');
define('CLOUDINARY_API_SECRET', 'P-Vro4fA8_gF9dnTcHgKnOQ-xGI');
define('CLOUDINARY_UPLOAD_URL', 'https://api.cloudinary.com/v1_1/' . CLOUDINARY_CLOUD_NAME . '/image/upload');

/**
 * Upload an image file to Cloudinary
 * @param array $file $_FILES array element
 * @param string $folder Cloudinary folder name
 * @return array ['url' => string, 'public_id' => string] on success
 */
function uploadToCloudinary($file, $folder = 'digihealth/profiles') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload error: ' . $file['error']];
    }

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($file['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        return ['error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP'];
    }

    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'File size must be less than 5MB'];
    }

    $timestamp = time();
    $public_id = $folder . '/' . uniqid('profile_') . '_' . $timestamp;

    // Build signature
    $params_to_sign = [
        'folder' => $folder,
        'public_id' => $public_id,
        'timestamp' => $timestamp,
    ];
    ksort($params_to_sign);
    $sign_str = '';
    foreach ($params_to_sign as $key => $value) {
        $sign_str .= $key . '=' . $value . '&';
    }
    $sign_str = rtrim($sign_str, '&');
    $signature = sha1($sign_str . CLOUDINARY_API_SECRET);

    // Prepare multipart form data
    $boundary = uniqid('');
    $CRLF = "\r\n";

    $body = '';
    $body .= "--{$boundary}{$CRLF}";
    $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"" . basename($file['name']) . "\"{$CRLF}";
    $body .= "Content-Type: {$fileType}{$CRLF}{$CRLF}";
    $body .= file_get_contents($file['tmp_name']) . $CRLF;

    $fields = [
        'api_key' => CLOUDINARY_API_KEY,
        'folder' => $folder,
        'public_id' => $public_id,
        'timestamp' => $timestamp,
        'signature' => $signature,
    ];

    foreach ($fields as $key => $value) {
        $body .= "--{$boundary}{$CRLF}";
        $body .= "Content-Disposition: form-data; name=\"{$key}\"{$CRLF}{$CRLF}";
        $body .= "{$value}{$CRLF}";
    }

    $body .= "--{$boundary}--{$CRLF}";

    // Send request
    $ch = curl_init(CLOUDINARY_UPLOAD_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: multipart/form-data; boundary={$boundary}",
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['error' => 'Upload failed (HTTP ' . $httpCode . '): ' . $response];
    }

    $result = json_decode($response, true);
    if (!$result || !isset($result['secure_url'])) {
        return ['error' => 'Invalid response from Cloudinary'];
    }

    return [
        'url' => $result['secure_url'],
        'public_id' => $result['public_id'],
    ];
}
?>