<?php
namespace App\Models;

use PDO;

class DonModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function insererDon(int $id_user, float $montant, float $contribution, float $total, string $date): int
    {
        $stmt = $this->conn->prepare("INSERT INTO dons (id_user, montant, contribution, total, date_don) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_user, $montant, $contribution, $total, $date]);

        return (int)$this->conn->lastInsertId();
    }

    public function insererTransaction(int $id_don, string $numero_transaction, string $statut = 'payé'): void
    {
        $stmt = $this->conn->prepare("INSERT INTO transactions (id_don, numero_transaction, statut) VALUES (?, ?, ?)");
        $stmt->execute([$id_don, $numero_transaction, $statut]);
    }

    public function beginTransaction(): void
    {
        $this->conn->beginTransaction();
    }

    public function commit(): void
    {
        $this->conn->commit();
    }

    public function rollBack(): void
    {
        $this->conn->rollBack();
    }
}
