<?php
namespace App\Controllers;

use App\Models\UserModel;
use App\Validators\UserValidator;
use App\Database\Database;
use Firebase\JWT\JWT;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


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
            return;
        }

        try {
            // Conversion date de naissance en format SQL
            if (isset($input['birthdate'])) {
                $date = \DateTime::createFromFormat('d/m/Y', $input['birthdate']);
                if ($date) {
                    $input['birthdate'] = $date->format('Y-m-d');
                } else {
                    $this->respond('error', 'Format de date invalide');
                    return;
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
            return;
        }

        $user = $this->userModel->authenticate($input['email'], $input['password']);
        if (!$user) {
            $this->respond('error', 'Email ou mot de passe incorrect');
            return;
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

    public function demanderReinitialisation()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';

        $result = $this->userModel->reinitialiserMotdepasse($email);

        if ($result['status'] !== 'success') {
            $this->respond('error', $result['message']);
            return;
        }

        // Préparation de l'email
        $prenom = $result['prenom'];
        $token = $result['token'];
        $resetLink = $_ENV['APP_URL'] . "/reset_password_form.php?token=$token";

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USERNAME'];
            $mail->Password = $_ENV['SMTP_PASSWORD'];
            $mail->SMTPSecure = $_ENV['SMTP_ENCRYPTION'];
            $mail->Port = $_ENV['SMTP_PORT'];

            $mail->setFrom($_ENV['SMTP_USERNAME'], 'Support Cœur de France');
            $mail->addAddress($email);
            $mail->Subject = 'Réinitialisation de votre mot de passe';

            $htmlMessage = <<<HTML
            <html><head><meta charset="UTF-8"><style>
            body { font-family: Arial, sans-serif; background-color: #f8f9fa; color: #333; }
            .container { padding: 20px; background-color: #fff; border-radius: 8px; }
            h1 { color: #050E7A; } a { color: #050E7A; text-decoration: none; }
            a:hover { text-decoration: underline; } p { font-size: 16px; }
            .signature { margin-top: 25px; font-size: 14px; color: #555; text-align: center; line-height: 1.5; }
            </style></head><body>
            <div class="container">
                <h1>Réinitialisation de votre mot de passe</h1>
                <p>Bonjour {$prenom},</p>
                <p>Cliquez sur le lien ci-dessous pour réinitialiser votre mot de passe :</p>
                <p><a href="{$resetLink}">{$resetLink}</a></p>
                <p>Ce lien est valable 5 minutes.</p>
                <p>Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer ce message.</p>
                <p class="signature">Cordialement,<br>L'équipe <strong>Cœur de France</strong></p>
            </div></body></html>
            HTML;

            $mail->isHTML(true);
            $mail->Body = $htmlMessage;
            $mail->AltBody = "Bonjour,\nCliquez ici pour réinitialiser votre mot de passe : $resetLink\nCe lien est valable 5 minutes.\nSi ce n'était pas vous, ignorez ce message.";
            $mail->CharSet = 'UTF-8';
            $mail->send();

            $this->respond('success', 'Email de réinitialisation envoyé');
        } catch (Exception $e) {
            $this->respond('error', 'Erreur PHPMailer : ' . $mail->ErrorInfo);
        }
    }



}
