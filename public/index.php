<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

 file_put_contents('php://stderr', "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n");

use Dotenv\Dotenv;
use App\Controllers\UserController;
use App\Controllers\DonController;
use App\Models\DonModel;
use App\Database\Database;

// Chargement des variables d'environnement
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Pour les réponses JSON
header('Content-Type: application/json');

// Récupération de l'URI et de la méthode HTTP
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Connexion BDD et instanciation des modèles et contrôleurs
$database = new Database();
$pdo = $database->getConnection();

$userController = new UserController(); // tu pourras lui injecter un UserModel plus tard si nécessaire
$donModel = new DonModel($pdo);
$donController = new DonController($donModel);

try {
    // Routes API
    if ($requestUri === '/api/register' && $method === 'POST') {
        $userController->store();

    } elseif ($requestUri === '/api/login' && $method === 'POST') {
        $userController->login();

    } elseif ($requestUri === '/api/don' && $method === 'POST') {
        $donController->enregistrerDon();

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
