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
        // Récupération des variables d'environnement
        $host = $_SERVER['DB_HOST'] ?? null;
        $dbname = $_SERVER['DB_NAME'] ?? null;
        $user = $_SERVER['DB_USER'] ?? null;
        $password = $_SERVER['DB_PASSWORD'] ?? null;

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
            // Ici tu peux logger $e->getMessage() en production
            throw new RuntimeException("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    /**
     * Récupère la connexion PDO
     */
    public function getConnection(): PDO
    {
        return $this->conn;
    }
}
?>
