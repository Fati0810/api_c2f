<?php
use PHPUnit\Framework\TestCase;
use App\Controllers\UserController;
use App\Models\UserModel;
use App\Validators\UserValidator;

class UserControllerTest extends TestCase
{
    private $userModelMock;
    private $validatorMock;
    private $controller;

    protected function setUp(): void
    {
        // Crée des mocks
        $this->userModelMock = $this->createMock(UserModel::class);
        $this->validatorMock = $this->createMock(UserValidator::class);

        // Injecte les mocks dans le controller
        $this->controller = new UserController($this->userModelMock, $this->validatorMock);
    }

    /**
     * Test de la méthode login() - succès
     */
   public function testLoginSuccess()
{
    $input = ['email' => 'test@example.com', 'password' => 'password123'];

    $this->validatorMock->expects($this->once())
        ->method('validateLogin')
        ->with($input)
        ->willReturn([]);

    $userData = [
        'id' => 42,
        'email' => $input['email'],
        'first_name' => 'Test',
        'password' => 'hashed_password'
    ];

    $this->userModelMock->expects($this->once())
        ->method('authenticate')
        ->with($input['email'], $input['password'])
        ->willReturn($userData);

    ob_start(); // Commence la mise en tampon de sortie
    $this->controller->login($input);
    $responseJson = ob_get_clean(); // Récupère la sortie echo

    $response = json_decode($responseJson, true);

    $this->assertEquals('success', $response['status']);
    $this->assertEquals('Connexion réussie.', $response['message']);
    $this->assertArrayHasKey('user', $response['data']);
    $this->assertArrayHasKey('token', $response['data']);
    $this->assertEquals($userData['id'], $response['data']['user']['id']);
    $this->assertArrayNotHasKey('password', $response['data']['user']);
}


    /**
     * Test login avec erreurs de validation
     */
    public function testLoginValidationError()
    {
        $input = ['email' => 'invalid-email', 'password' => ''];

        // Simule une erreur de validation
        $this->validatorMock->expects($this->once())
            ->method('validateLogin')
            ->with($input)
            ->willReturn(['email' => 'Email invalide', 'password' => 'Mot de passe requis']);

        // Appelle login et capture la réponse JSON
        $responseJson = $this->controller->login($input);
        $response = json_decode($responseJson, true);

        $this->assertEquals('error', $response['status']);
        $this->assertEquals('Validation échouée', $response['message']);
        $this->assertArrayHasKey('email', $response['data']);
        $this->assertArrayHasKey('password', $response['data']);
    }

    /**
     * Test login échec d'authentification
     */
    public function testLoginAuthenticationFail()
    {
        $input = ['email' => 'test@example.com', 'password' => 'wrongpassword'];

        // Pas d'erreur de validation
        $this->validatorMock->expects($this->once())
            ->method('validateLogin')
            ->with($input)
            ->willReturn([]);

        // Authentification retourne false (échec)
        $this->userModelMock->expects($this->once())
            ->method('authenticate')
            ->with($input['email'], $input['password'])
            ->willReturn(false);

        $responseJson = $this->controller->login($input);
        $response = json_decode($responseJson, true);

        $this->assertEquals('error', $response['status']);
        $this->assertEquals('Email ou mot de passe incorrect', $response['message']);
        $this->assertNull($response['data']);
    }

    /**
     * Test de la méthode store() - succès insertion utilisateur
     */
    public function testStoreSuccess()
    {
        $input = [
            'email' => 'newuser@example.com',
            'password' => 'MyPass123',
            'birthdate' => '25/12/1990',
            'first_name' => 'Jean',
            'last_name' => 'Dupont'
        ];

        // Validation sans erreur
        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->with($input)
            ->willReturn([]);

        // InsertUser retourne true (succès)
        $this->userModelMock->expects($this->once())
            ->method('insertUser')
            ->with($this->callback(function ($arg) use ($input) {
                // La date doit être transformée en Y-m-d
                return $arg['birthdate'] === '1990-12-25'
                    && password_verify($input['password'], $arg['password']) === true
                    && $arg['email'] === $input['email'];
            }))
            ->willReturn(true);

        $responseJson = $this->controller->storeWithInput($input); // Voir note ci-dessous
        $response = json_decode($responseJson, true);

        $this->assertEquals('success', $response['status']);
        $this->assertEquals('Utilisateur ajouté', $response['message']);
    }

    /**
     * Test store() avec validation échouée
     */
    public function testStoreValidationError()
    {
        $input = ['email' => 'invalid', 'password' => ''];

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->with($input)
            ->willReturn(['email' => 'Email invalide', 'password' => 'Mot de passe requis']);

        $responseJson = $this->controller->storeWithInput($input); // Voir note ci-dessous
        $response = json_decode($responseJson, true);

        $this->assertEquals('error', $response['status']);
        $this->assertEquals('Validation échouée', $response['message']);
        $this->assertArrayHasKey('email', $response['data']);
    }
}
