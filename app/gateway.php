<?php
session_start();
include_once 'include/mysql.php';

$request = $_SERVER['REQUEST_URI'];

$baseDir = __DIR__;

$allowedExtensions = ['php', 'html', 'jpg', 'png', 'gif', "css", "js"];
$bannedUrls = [
    "/gateway.php"
];

$request = strtok($request, '?');

$requestedFile = realpath("{$baseDir}{$request}");

if(in_array($request, $bannedUrls)) {
    http_response_code(403);
    echo '403 Forbidden';
    exit;
}

if ($request == "/" || $request == "/index.php") {
    $requestedFile = realpath("{$baseDir}/index.php");
}

if(!isset($_SESSION["password"]) || !isset($_SESSION["user"])) {
    if ($request !== "/login.php") {
        header("Location: /login.php");
        exit;
    }
}

if(isset($_SESSION["user"]) && isset($_SESSION["password"])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $_SESSION["user"], $_SESSION["password"]);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        unset($_SESSION["user"]);
        unset($_SESSION["password"]);
        header("Location: /login.php");
        exit;
    }
}

if (is_dir($requestedFile)) {
    header("Location: {$request}/index.php");
    exit;
}

if ($requestedFile !== false && is_file($requestedFile)) {

    $_SERVER['PHP_SELF'] = $request;

    $extension = pathinfo($requestedFile, PATHINFO_EXTENSION);
    if ($extension === '') {
        $requestedFile = realpath($requestedFile . '/index.php');
        if ($requestedFile !== false && is_file($requestedFile)) {
            $extension = 'php';
        }
    }


    if (in_array($extension, $allowedExtensions)) {
        $mimeTypes = [
            'php' => 'text/html',
            'html' => 'text/html',
            'json' => 'application/json',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            "css" => "text/css",
            "js" => "text/javascript"
        ];

        if ($extension === 'php') {
            ob_start();
            include $requestedFile;
            $output = ob_get_clean();
            echo $output;
            exit;
        } else {
            header('Content-Type: ' . $mimeTypes[$extension]);
            readfile($requestedFile);
            exit;
        }
    }
}

http_response_code(404);
echo '404 Not Found';
exit;