<?php
namespace App\Controllers;

use App\Models\UserModel;
use App\Validators\UserValidator;
use App\Database\Database;

class UserController
{
    public function store()
    {
        $input = $_POST;

        $validator = new UserValidator();
        $errors = $validator->validate($input);

        if (!empty($errors)) {
            $this->respond('error', 'Validation échouée', $errors);
        }

        try {
            $db = new Database();
            $conn = $db->getConnection();

            $user = new UserModel($conn);

            // Conversion de la date de naissance au format SQL (Y-m-d)
            if (isset($input['birthdate'])) {
                $date = \DateTime::createFromFormat('d/m/Y', $input['birthdate']);
                if ($date) {
                    $input['birthdate'] = $date->format('Y-m-d');
                } else {
                    $this->respond('error', 'Format de date invalide');
                }
            }

            // Hash du mot de passe avant insertion
            if (isset($input['password'])) {
                $input['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
            }

            $result = $user->insertUser($input);

            if ($result === true) {
                $this->respond('success', "Utilisateur ajouté");
            } else {
                $this->respond('error', "Erreur lors de l'ajout de l'utilisateur");
            }
        } catch (\Exception $e) {
            $this->respond('error', "Erreur serveur : " . $e->getMessage());
        }
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
