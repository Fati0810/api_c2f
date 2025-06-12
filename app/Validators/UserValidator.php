<?php
namespace App\Validators;

class UserValidator
{
    public function validate(array $data): array
    {
        $errors = [];

        $clean = fn($value) => htmlspecialchars(trim(strip_tags($value)), ENT_QUOTES, 'UTF-8');
        $data = array_map($clean, $data);

        if (empty($data['first_name']) || !preg_match("/^[\p{L} '-]{2,50}$/u", $data['first_name'])) {
            $errors['first_name'] = "Prénom invalide";
        }

        if (empty($data['last_name']) || !preg_match("/^[\p{L} '-]{2,50}$/u", $data['last_name'])) {
            $errors['last_name'] = "Nom invalide";
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Email invalide";
        }

        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors['password'] = "Mot de passe trop court";
        }

        if (empty($data['password_confirm']) || $data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = "Confirmation invalide";
        }

        $date = \DateTime::createFromFormat('d/m/Y', $data['birthdate'] ?? '');
        if (!$date || $date > new \DateTime()) {
            $errors['birthdate'] = "Date invalide";
        }

        return $errors;
    }

    public function validateLogin(array $data): array
    {
        $errors = [];

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Email invalide";
        }

        if (empty($password)) {
            $errors['password'] = "Mot de passe requis";
        }

        return $errors;
    }
}
