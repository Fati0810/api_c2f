<?php
namespace App\Controllers;

use App\Models\DonModel;

class DonController
{
    private DonModel $model;

    public function __construct(DonModel $model)
    {
        $this->model = $model;
    }

    public function enregistrerDon(): void
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['user_id'], $data['montant'], $data['contribution'], $data['total'], $data['date'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Données manquantes.']);
            return;
        }

        $id_user = (int)$data['user_id'];
        $montant = (float)$data['montant'];
        $contribution = (float)$data['contribution'];
        $total = (float)$data['total'];
        $date = $data['date'];

        try {
            $this->model->beginTransaction();

            $id_don = $this->model->insererDon($id_user, $montant, $contribution, $total, $date);
            $numero_transaction = uniqid('txn_');

            $this->model->insererTransaction($id_don, $numero_transaction);

            $this->model->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Don enregistré avec succès.',
                'numero_transaction' => $numero_transaction,
                'id_don' => $id_don
            ]);
        } catch (\Exception $e) {
            $this->model->rollBack();
            http_response_code(500);
            echo json_encode([
                'error' => 'Erreur lors de l’enregistrement du don.',
                'details' => $e->getMessage()
            ]);
        }
    }
}
