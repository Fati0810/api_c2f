<?php
namespace App\Controllers;

use App\Models\DonModel;

class DonController
{
    private $model;

    // Injecte le modèle (pour la base de données)
    public function setModel($model): void
    {
        $this->model = $model;
    }

    // Méthode qui traite la logique métier, facile à tester en unitaire
    public function processDon(array $data): array
    {
        if (!isset($data['user_id'], $data['montant'], $data['contribution'], $data['total'], $data['date'])) {
            return ['error' => 'Données manquantes.'];
        }

        try {
            $this->model->beginTransaction();

            $id_don = $this->model->insererDon(
                (int)$data['user_id'],
                (float)$data['montant'],
                (float)$data['contribution'],
                (float)$data['total'],
                $data['date']
            );

            $numero_transaction = uniqid('txn_');

            $this->model->insererTransaction($id_don, $numero_transaction);

            $this->model->commit();

            return [
                'success' => true,
                'message' => 'Don enregistré avec succès.',
                'numero_transaction' => $numero_transaction,
                'id_don' => $id_don
            ];
        } catch (\Exception $e) {
            $this->model->rollBack();
            return [
                'error' => 'Erreur lors de l’enregistrement du don.',
                'details' => $e->getMessage()
            ];
        }
    }

    // Méthode qui gère la requête HTTP et la réponse JSON
    public function enregistrerDon(): void
    {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents("php://input"), true);

        $result = $this->processDon($data);

        echo json_encode($result);
    }
}

