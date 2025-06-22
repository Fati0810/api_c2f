<?php
namespace App\Models;

use PDO;
use PDOException;

class UserModel
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function insertUser(array $data): bool
    {
        $sql = "INSERT INTO users 
            (first_name, last_name, email, password, birthdate, address, postal_code, city, country, created_at) 
            VALUES 
            (:first_name, :last_name, :email, :password, :birthdate, :address, :postal_code, :city, :country, NOW())";

        $stmt = $this->conn->prepare($sql);

        // On pourrait ajouter un try/catch ici pour capturer d'éventuelles erreurs SQL
        return $stmt->execute([
            ':first_name'  => $data['first_name'] ?? null,
            ':last_name'   => $data['last_name'] ?? null,
            ':email'       => $data['email'] ?? null,
            ':password'    => $data['password'] ?? null,
            ':birthdate'   => $data['birthdate'] ?? null,
            ':address'     => $data['address'] ?? null,
            ':postal_code' => $data['postal_code'] ?? null,
            ':city'        => $data['city'] ?? null,
            ':country'     => $data['country'] ?? null,
        ]);
    }

    public function authenticate(string $email, string $password)
    {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        return $user;
    }

    public function reinitialiserMotdepasse(string $email): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => 'Email invalide'];
        }

        $stmt = $this->conn->prepare("SELECT id, first_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['status' => 'error', 'message' => 'Aucun utilisateur trouvé avec cet email'];
        }

        $prenom = $user['first_name'] ?? '';
        $token = bin2hex(random_bytes(16));
        $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $stmt = $this->conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $stmt->execute([$token, $expires, $email]);

        return [
            'status' => 'success',
            'message' => 'Token généré',
            'prenom' => $prenom,
            'token' => $token,
            'email' => $email
        ];
    }
}
