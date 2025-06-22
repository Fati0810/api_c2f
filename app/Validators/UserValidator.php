<?php
namespace App\Validators;

class UserValidator
{
    public function validate(array $data): array
    {
        $errors = [];

        // Nettoyage des données (trim, strip_tags, htmlspecialchars)
        $clean = fn($value) => htmlspecialchars(trim(strip_tags($value)), ENT_QUOTES, 'UTF-8');
        $data = array_map($clean, $data);

        // Prénom : 2 à 50 caractères, lettres (y compris accents), espaces, apostrophes, tirets
        if (empty($data['first_name']) || !preg_match("/^[\p{L} '-]{2,50}$/u", $data['first_name'])) {
            $errors['first_name'] = "Prénom invalide";
        }

        // Nom : mêmes règles que prénom
        if (empty($data['last_name']) || !preg_match("/^[\p{L} '-]{2,50}$/u", $data['last_name'])) {
            $errors['last_name'] = "Nom invalide";
        }

        // Email valide
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Email invalide";
        }

        // Mot de passe minimum 6 caractères
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors['password'] = "Mot de passe trop court (minimum 6 caractères)";
        }

        // Confirmation du mot de passe
        if (empty($data['password_confirm']) || $data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = "Confirmation du mot de passe invalide";
        }

        // Date de naissance au format 'd/m/Y' et date dans le passé
        $date = \DateTime::createFromFormat('d/m/Y', $data['birthdate'] ?? '');
        if (!$date || $date > new \DateTime()) {
            $errors['birthdate'] = "Date de naissance invalide";
        }

        return $errors;
    }

    public function validateLogin(?array $data): array
    {
        $errors = [];

        if ($data === null) {
            $errors['data'] = "Données manquantes";
            return $errors;
        }

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
