<?php
namespace App\Controllers;

use App\Models\UserModel;
use App\Validators\UserValidator;
use App\Database\Database;
use Firebase\JWT\JWT;

class UserController
{
    private UserModel $userModel;

    public function __construct()
    {
        $db = new Database();
        $conn = $db->getConnection();
        $this->userModel = new UserModel($conn);
    }

    public function store()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $validator = new UserValidator();
        $errors = $validator->validate($input);

        if (!empty($errors)) {
            $this->respond('error', 'Validation échouée', $errors);
        }

        try {
            // Conversion date de naissance en format SQL
            if (isset($input['birthdate'])) {
                $date = \DateTime::createFromFormat('d/m/Y', $input['birthdate']);
                if ($date) {
                    $input['birthdate'] = $date->format('Y-m-d');
                } else {
                    $this->respond('error', 'Format de date invalide');
                }
            }

            // Hash du mot de passe
            if (isset($input['password'])) {
                $input['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
            }

            $result = $this->userModel->insertUser($input);

            if ($result === true) {
                $this->respond('success', "Utilisateur ajouté");
            } else {
                $this->respond('error', "Erreur lors de l'ajout de l'utilisateur");
            }
        } catch (\Exception $e) {
            $this->respond('error', "Erreur serveur : " . $e->getMessage());
        }
    }

    public function login()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $validator = new UserValidator();
        $errors = $validator->validateLogin($input);

        if (!empty($errors)) {
            $this->respond('error', 'Validation échouée', $errors);
        }

        $user = $this->userModel->authenticate($input['email'], $input['password']);
        if (!$user) {
            $this->respond('error', 'Email ou mot de passe incorrect');
        }

        $payload = [
            "iss" => JWT_ISSUER,
            "aud" => JWT_AUDIENCE,
            "iat" => time(),
            "exp" => time() + JWT_EXPIRATION_TIME,
            "user_id" => $user['id'],
            "email" => $user['email'],
            "first_name" => $user['first_name']
        ];

        $token = JWT::encode($payload, JWT_SECRET_KEY, 'HS256');

        unset($user['password']);

        $this->respond('success', 'Connexion réussie.', ['user' => $user, 'token' => $token]);
    }

    private function respond(string $status, string $message, $data = null): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'status'  => $status,
            'message' => $message,
            'data'    => $data
        ]);
        exit;
    }
}
