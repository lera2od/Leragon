<?php

$mysql_config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/config/mysql.ini');
define('DB_HOST', $mysql_config['DB_HOST']);
define('DB_USER', $mysql_config['DB_USER']);
define('DB_PASSWORD', $mysql_config['DB_PASSWORD']);
define('DB_NAME', $mysql_config['DB_NAME']);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    //echo "Table users created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$sql = "CREATE TABLE IF NOT EXISTS api_keys (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    api_key VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    //echo "Table api_keys created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}