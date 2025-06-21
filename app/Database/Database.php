<?php
namespace App\Database;

use PDO;
use PDOException;
use RuntimeException;



class Database
{
    private PDO $conn;

    public function __construct()
    {
        $host = $_SERVER['DB_HOST'];
        $dbname = $_SERVER['DB_NAME'];
        $user = $_SERVER['DB_USER'];
        $password = $_SERVER['DB_PASSWORD'];



        if (!$host || !$dbname || !$user || $password === null) {
            throw new RuntimeException("Variables d'environnement de la base de données manquantes");
        }

        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->conn = new PDO($dsn, $user, $password, $options);
        } catch (PDOException $e) {
            // Log l’erreur en vrai projet
            throw new RuntimeException("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    public function getConnection(): PDO
    {
        return $this->conn;
    }

}

?>