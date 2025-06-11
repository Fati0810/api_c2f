<?php // app/Validators/UserValidator.php
namespace App\Validators;

class UserValidator
{
    /**
     * Valide les données utilisateur et retourne un tableau d'erreurs.
     * @param array $data Données à valider
     * @return array Tableau d'erreurs (vide si aucune erreur)
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Fonction pour nettoyer les données (protection contre XSS)
        $clean = fn($value) => htmlspecialchars(trim(strip_tags($value)), ENT_QUOTES, 'UTF-8');

        // Nettoyage de toutes les valeurs du tableau
        $data = array_map($clean, $data);

        // Validation prénom (lettres, apostrophes, espaces, 2 à 50 caractères)
        if (empty($data['first_name']) || !preg_match("/^[\p{L} '-]{2,50}$/u", $data['first_name'])) {
            $errors['first_name'] = "Prénom invalide";
        }

        // Validation nom (lettres, apostrophes, espaces, 2 à 50 caractères)
        if (empty($data['last_name']) || !preg_match("/^[\p{L} '-]{2,50}$/u", $data['last_name'])) {
            $errors['last_name'] = "Nom invalide";
        }

        // Validation email
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Email invalide";
        }

        // Validation mot de passe (minimum 6 caractères)
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors['password'] = "Mot de passe trop court";
        }

        // Validation confirmation mot de passe (doit être identique)
        if (empty($data['password_confirm']) || $data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = "Confirmation invalide";
        }

        // Validation date de naissance au format dd/mm/yyyy et pas dans le futur
        $date = \DateTime::createFromFormat('d/m/Y', $data['birthdate'] ?? '');
        if (!$date || $date > new \DateTime()) {
            $errors['birthdate'] = "Date invalide";
        }

        // TODO : Valider adresse, code postal, ville, pays selon besoins

        return $errors;
    }
}
