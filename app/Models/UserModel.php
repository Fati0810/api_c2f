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
        $sql = "INSERT INTO users (first_name, last_name, email, password, birthdate, address, postal_code, city, country, created_at) 
                VALUES (:first_name, :last_name, :email, :password, :birthdate, :address, :postal_code, :city, :country, NOW())";

        $stmt = $this->conn->prepare($sql);

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
}
