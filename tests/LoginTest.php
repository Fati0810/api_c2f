<?php
use PHPUnit\Framework\TestCase;

class LoginTest extends TestCase
{
    private $url = 'http://localhost:8888/api/login';

    // Test connexion avec bonnes infos
    public function testConnexionOk()
    {
    $data = [
            'email' => 'fatimatadiallo8@hotmail.com',
            'password' => '123456'
        ];

        list($httpCode, $response) = $this->postRequest($this->url, $data);

        $this->assertEquals(200, $httpCode);
        $this->assertIsArray($response);

        // Vérifie que la clé 'data' est bien présente
        $this->assertArrayHasKey('data', $response);

        // Vérifie que 'token' est dans 'data'
        $this->assertArrayHasKey('token', $response['data']);

        // Tu peux aussi vérifier que le token n'est pas vide
        $this->assertNotEmpty($response['data']['token']);
    }


    // Test mauvais mot de passe
    public function testMotDePasseIncorrect()
    {
        $data = [
            'email' => 'fatimatadiallo8@hotmail.com',
            'password' => 'wrongpassword'
        ];

        list($httpCode, $response) = $this->postRequest($this->url, $data);

        $this->assertEquals(401, $httpCode);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('message', $response);
    }

    // Test email inexistant
    public function testEmailInexistant()
    {
        $data = [
            'email' => 'inexistant@example.com',
            'password' => 'anyPassword'
        ];

        list($httpCode, $response) = $this->postRequest($this->url, $data);

        $this->assertEquals(401, $httpCode);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('message', $response);
    }

    // Test champs manquants (ex: email absent)
    public function testChampsManquants()
    {
        $data = [
            // 'email' => 'missing@example.com', // email manquant volontairement
            'password' => 'anyPassword'
        ];

        list($httpCode, $response) = $this->postRequest($this->url, $data);

        $this->assertEquals(400, $httpCode);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('message', $response);
    }

    private function postRequest(string $url, array $data): array
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // ⬅️ envoie en JSON
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']); // ⬅️ header JSON

        $responseContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        // Affichage pour debug
        echo "\nResponse HTTP code: $httpCode\n";
        echo "Response content: $responseContent\n";

        $response = json_decode($responseContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->fail("Réponse JSON invalide: " . json_last_error_msg());
        }

        return [$httpCode, $response];
    }



    public function testJsonMalforme()
    {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{"email": "test@example.com", "password":'); // JSON cassé
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $responseContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertEquals(400, $httpCode);
    }


    
}
