<?php
// 获取完整的请求 URI, 从请求 URI 中解析出路径部分
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// /build/assets/app-C1-XIpUa.js
// /build/assets/app-C24ONnXZ.css

if (str_starts_with($path, "/build/assets/")) {
    if(str_ends_with($path, ".js")){
        header('Content-Type: application/javascript; charset: UTF-8');
        echo require __DIR__ . '/../public/' . $path;
        return;
    }

    if(str_ends_with($path, ".css")){
        header("Content-type: text/css; charset: UTF-8");
        echo require __DIR__ . '/../public/' . $path;
        return;
    }
}

require __DIR__ . '/../public/index.php';