<?php
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($request_uri === '/' || $request_uri === '') {
    $file_path = __DIR__ . '/../index.php';
} else {
    $file_path = __DIR__ . '/..' . $request_uri;
}

if (file_exists($file_path) && is_file($file_path)) {
    // Change directory so relative includes in the script work correctly
    chdir(dirname($file_path));
    require $file_path;
} else {
    http_response_code(404);
    echo "404 Not Found";
}
?>
