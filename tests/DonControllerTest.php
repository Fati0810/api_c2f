<?php

require_once __DIR__ . '/TestPhpStream.php';

use PHPUnit\Framework\TestCase;
use App\Controllers\DonController;
use App\Models\DonModel;

class DonControllerTest extends TestCase
{
    private $modelMock;
    private $controller;

    protected function setUp(): void
    {
        $this->modelMock = $this->createMock(DonModel::class);
        $this->controller = new DonController($this->modelMock);
    }

    public function testEnregistrerDonSuccess(): void
    {
        // Données simulées en entrée
        $inputData = [
            'user_id' => 1,
            'montant' => 50.0,
            'contribution' => 5.0,
            'total' => 55.0,
            'date' => '2025-06-22'
        ];

        // Simuler les méthodes du modèle
        $this->modelMock->expects($this->once())->method('beginTransaction');
        $this->modelMock->expects($this->once())->method('insererDon')->willReturn(123);
        $this->modelMock->expects($this->once())->method('insererTransaction');
        $this->modelMock->expects($this->once())->method('commit');

        // Simuler php://input
        $this->setInputStream($inputData);

        ob_start();
        $this->controller->enregistrerDon();
        $output = ob_get_clean();

        $responseData = json_decode($output, true);
        $this->assertEquals('Don enregistré avec succès.', $responseData['message']);
    }

    public function testEnregistrerDonMissingData(): void
    {
        $this->setInputStream([]); // pas de données

        ob_start();
        $this->controller->enregistrerDon();
        $output = ob_get_clean();

        echo "\nDEBUG output: $output\n";
        $responseData = json_decode($output, true);
        var_dump($responseData);

        $this->assertEquals('Données manquantes.', $responseData['error'] ?? $responseData['message'] ?? null);

    }


    // Méthode utilitaire pour simuler php://input
    private function setInputStream(array $data): void
    {
        $json = json_encode($data);
        file_put_contents('php://memory', $json); // simule un fichier mémoire
        stream_wrapper_unregister("php");
        stream_wrapper_register("php", \TestPhpStream::class);
        \TestPhpStream::setContent($json);
    }
}
