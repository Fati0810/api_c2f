<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Controllers\UserController;
use App\Models\UserModel;
use App\Controllers\DonController;
use App\Models\DonModel;
use App\Database\Database;

header('Content-Type: application/json');

// Chargement .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$requestUri = str_replace('/api_c2f', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$method = $_SERVER['REQUEST_METHOD'];
$inputData = json_decode(file_get_contents('php://input'), true);

// Connexion BDD
$database = new Database();
$pdo = $database->getConnection();

// Instanciation avec injection
$userModel = new UserModel($pdo);
$userController = new UserController($userModel);

$donModel = new DonModel($pdo);
$donController = new DonController($donModel);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


try {
    if ($requestUri === '/api/register' && $method === 'POST') {
        $userController->store($inputData);

    } elseif ($requestUri === '/api/login' && $method === 'POST') {
        $userController->login($inputData);

    } elseif ($requestUri === '/api/don' && $method === 'POST') {
        $donController->enregistrerDon($inputData);

    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Route non trouvée']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur serveur',
        'details' => $e->getMessage()
    ]);
}
