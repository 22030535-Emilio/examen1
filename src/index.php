<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\PasswordController;

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$controller = new PasswordController();
$controller->handleRequest();