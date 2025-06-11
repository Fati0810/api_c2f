<?php
use App\Controllers\UserController;

require_once __DIR__ . '/../vendor/autoload.php';

$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if ($requestUri === '/api/register' && $method === 'POST') {
    (new UserController())->store();
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Route non trouvée']);
}
