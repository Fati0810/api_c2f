<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

if (!file_exists(__DIR__ . '/.env')) {
    die("Fichier .env introuvable !");
}

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "DB_HOST = " . getenv('DB_HOST') . PHP_EOL;
echo "DB_NAME = " . getenv('DB_NAME') . PHP_EOL;
echo "DB_USER = " . getenv('DB_USER') . PHP_EOL;
echo "DB_PASSWORD = " . getenv('DB_PASSWORD') . PHP_EOL;
