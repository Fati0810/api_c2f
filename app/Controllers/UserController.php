<?php
namespace App\Controllers;

require_once __DIR__ . '/../../config/config.php';

use App\Models\UserModel;
use App\Validators\UserValidator;
use App\Database\Database;
use Firebase\JWT\JWT;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class UserController
{
    private UserModel $userModel;
    private UserValidator $validator;

    public function __construct(?UserModel $userModel = null, ?UserValidator $validator = null)
    {
        if ($userModel === null) {
            $db = new Database();
            $conn = $db->getConnection();
            $this->userModel = new UserModel($conn);
        } else {
            $this->userModel = $userModel;
        }

        $this->validator = $validator ?? new UserValidator();
    }

    public function store()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            $this->respond('error', 'Données invalides', null, 400);
            return;
        }

        $errors = $this->validator->validate($input);

        if (!empty($errors)) {
            $this->respond('error', 'Validation échouée', $errors, 400);
            return;
        }

        try {
            if (isset($input['birthdate'])) {
                $date = \DateTime::createFromFormat('d/m/Y', $input['birthdate']);
                if ($date) {
                    $input['birthdate'] = $date->format('Y-m-d');
                } else {
                    $this->respond('error', 'Format de date invalide', null, 400);
                    return;
                }
            }

            if (isset($input['password'])) {
                $input['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
            }

            $result = $this->userModel->insertUser($input);

            if ($result === true) {
                $this->respond('success', "Utilisateur ajouté", null, 201);
            } else {
                $this->respond('error', "Erreur lors de l'ajout de l'utilisateur", null, 500);
            }
        } catch (\Exception $e) {
            $this->respond('error', "Erreur serveur : " . $e->getMessage(), null, 500);
        }
    }

    // Méthode login modifiée pour recevoir $input en paramètre et faciliter tests unitaires
    public function login(?array $input = null)
{
    if ($input === null) {
        $input = json_decode(file_get_contents('php://input'), true);
    }

    if (!is_array($input)) {
        $input = [];
    }

    $errors = $this->validator->validateLogin($input);

    if (!empty($errors)) {
        // retourner la réponse au lieu de juste appeler respond
        return $this->respond('error', 'Validation échouée', $errors, 400, false);
    }

    $user = $this->userModel->authenticate($input['email'], $input['password']);
    if (!$user) {
        return $this->respond('error', 'Email ou mot de passe incorrect', null, 401, false);
    }

    $payload = [
        "iss" => getenv('JWT_ISSUER'),
        "aud" => getenv('JWT_AUDIENCE'),
        "iat" => time(),
        "exp" => time() + (int)getenv('JWT_EXPIRATION_TIME'),
        "user_id" => $user['id'],
        "email" => $user['email'],
        "first_name" => $user['first_name']
    ];

    $token = JWT::encode($payload, JWT_SECRET_KEY, 'HS256');

    unset($user['password']);

    return $this->respond('success', 'Connexion réussie.', ['user' => $user, 'token' => $token], 200, false);
}


    /**
     * Méthode respond modifiée pour retourner la réponse au lieu d'appeler exit en test
     * @param bool $exit : true pour exit (mode production), false pour test
     */
    private function respond(string $status, string $message, $data = null, int $httpCode = 200, bool $exit = true): ?string
    {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        $response = json_encode([
            'status'  => $status,
            'message' => $message,
            'data'    => $data
        ]);
        echo $response;

        if ($exit) {
            exit;
        }

        return $response;
    }

    // Le reste de tes méthodes inchangées (demanderReinitialisation, etc.)

    public function demanderReinitialisation()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';

        if (empty($email)) {
            $this->respond('error', 'Email requis', null, 400);
            return;
        }

        $result = $this->userModel->reinitialiserMotdepasse($email);

        if ($result['status'] !== 'success') {
            $this->respond('error', $result['message'], null, 400);
            return;
        }

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

            $this->respond('success', 'Email de réinitialisation envoyé', null, 200);
        } catch (Exception $e) {
            $this->respond('error', 'Erreur PHPMailer : ' . $mail->ErrorInfo, null, 500);
        }
    }

    public function storeWithInput(array $input)
    {
        if (!is_array($input)) {
            return $this->respond('error', 'Données invalides', null, 400, false);
        }

        $errors = $this->validator->validate($input);

        if (!empty($errors)) {
            return $this->respond('error', 'Validation échouée', $errors, 400, false);
        }

        try {
            if (isset($input['birthdate'])) {
                $date = \DateTime::createFromFormat('d/m/Y', $input['birthdate']);
                if ($date) {
                    $input['birthdate'] = $date->format('Y-m-d');
                } else {
                    return $this->respond('error', 'Format de date invalide', null, 400, false);
                }
            }

            if (isset($input['password'])) {
                $input['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
            }

            $result = $this->userModel->insertUser($input);

            if ($result === true) {
                return $this->respond('success', "Utilisateur ajouté", null, 201, false);
            } else {
                return $this->respond('error', "Erreur lors de l'ajout de l'utilisateur", null, 500, false);
            }
        } catch (\Exception $e) {
            return $this->respond('error', "Erreur serveur : " . $e->getMessage(), null, 500, false);
        }
    }

}
