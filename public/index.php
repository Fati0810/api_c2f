<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

use Dotenv\Dotenv;
use App\Controllers\UserController;

// Charger les variables d'environnement
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Pour gérer les réponses JSON
header('Content-Type: application/json');

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$controller = new UserController();

try {
    if ($requestUri === '/api/register' && $method === 'POST') {
        $controller->store();
    } elseif ($requestUri === '/api/login' && $method === 'POST') {
        $controller->login();
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Route non trouvée']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur serveur', 'details' => $e->getMessage()]);
}
