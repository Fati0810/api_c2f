<?php
namespace App\Models;

use PDO;

class UserModel
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function emailExists(string $email): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    public function insertUser(array $userData): bool
    {
        $sql = "INSERT INTO users 
            (first_name, last_name, email, password, birthdate, address, postal_code, city, country) 
            VALUES 
            (:first_name, :last_name, :email, :password, :birthdate, :address, :postal_code, :city, :country)";
        
        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':first_name'    => $userData['first_name'],
            ':last_name'     => $userData['last_name'],
            ':email'         => $userData['email'],
            ':password'      => $userData['password'], 
            ':birthdate'     => $userData['birthdate'],
            ':address'       => $userData['address'],
            ':postal_code'   => $userData['postal_code'],
            ':city'          => $userData['city'],
            ':country'       => $userData['country'],
        ]);
    }
}
