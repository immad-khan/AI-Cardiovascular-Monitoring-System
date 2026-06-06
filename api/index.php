<?php
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($request_uri === '/' || $request_uri === '') {
    $file_path = __DIR__ . '/../index.php';
} else {
    $file_path = __DIR__ . '/..' . $request_uri;
}

if (file_exists($file_path) && is_file($file_path)) {
    chdir(dirname($file_path));
    require $file_path;
} else {
    // Fallback: Check if the file exists inside the frontend/ folder
    $frontend_path = __DIR__ . '/../frontend' . $request_uri;
    if (file_exists($frontend_path) && is_file($frontend_path)) {
        chdir(dirname($frontend_path));
        require $frontend_path;
    } else {
        http_response_code(404);
        echo "404 Not Found";
    }
}
?>
