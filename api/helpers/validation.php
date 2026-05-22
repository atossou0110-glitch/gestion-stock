<?php
declare(strict_types=1);

/**
 * Fonctions utilitaires pour la validation des données
 */

/**
 * Valide qu'une valeur est un entier
 * 
 * @param mixed $value La valeur à valider
 * @param string $label Le nom du champ pour les messages d'erreur
 * @param int|null $default Valeur par défaut si null
 * @return int
 */
function validate_integer($value, string $label, ?int $default = null): int
{
    if ($value === null || $value === '') {
        if ($default !== null) {
            return $default;
        }
        throw new InvalidArgumentException("$label est obligatoire.");
    }

    if (is_int($value)) {
        return $value;
    }

    if (is_float($value) && floor($value) === $value) {
        return (int) $value;
    }

    if (is_string($value)) {
        $value = trim($value);
        if (preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }
        if (preg_match('/^-?\d+\.0+$/', $value) === 1) {
            return (int) $value;
        }
    }

    throw new InvalidArgumentException("$label doit etre un nombre entier, sans virgule.");
}

/**
 * Valide qu'une valeur est un entier positif ou nul
 * 
 * @param mixed $value La valeur à valider
 * @param string $label Le nom du champ pour les messages d'erreur
 * @param int $default Valeur par défaut
 * @return int
 */
function validate_non_negative_integer($value, string $label, int $default = 0): int
{
    $number = validate_integer($value, $label, $default);

    if ($number < 0) {
        throw new InvalidArgumentException("$label ne peut pas etre negative.");
    }

    return $number;
}

/**
 * Valide qu'une valeur est un entier strictement positif
 * 
 * @param mixed $value La valeur à valider
 * @param string $label Le nom du champ pour les messages d'erreur
 * @return int
 */
function validate_positive_integer($value, string $label): int
{
    $number = validate_integer($value, $label);

    if ($number <= 0) {
        throw new InvalidArgumentException("$label doit etre superieure a zero.");
    }

    return $number;
}

/**
 * Valide qu'une valeur est un nombre (float)
 * 
 * @param mixed $value La valeur à valider
 * @param string $label Le nom du champ pour les messages d'erreur
 * @param float|null $default Valeur par défaut
 * @return float
 */
function validate_number($value, string $label, ?float $default = null): float
{
    if ($value === null || $value === '') {
        if ($default !== null) {
            return $default;
        }
        throw new InvalidArgumentException("$label est obligatoire.");
    }

    if (is_int($value) || is_float($value)) {
        return (float) $value;
    }

    if (is_string($value)) {
        $value = str_replace(',', '.', trim($value));
        if (is_numeric($value)) {
            return (float) $value;
        }
    }

    throw new InvalidArgumentException("$label doit etre un nombre valide.");
}

/**
 * Normalise le nom d'une unité de mesure
 * 
 * @param string $unit L'unité à normaliser
 * @return string
 */
function normalize_unit(string $unit): string
{
    return str_replace('m²', 'm2', strtolower(trim($unit)));
}

/**
 * Vérifie si une unité autorise les quantités décimales
 * 
 * @param string $unit L'unité à vérifier
 * @return bool
 */
function unit_allows_decimal(string $unit): bool
{
    return in_array(normalize_unit($unit), ['m2', 'ml', 'kg', 'litre(s)', 'litre', 'litres'], true);
}

/**
 * Formate une quantité selon l'unité
 * 
 * @param float|int $value La valeur à formater
 * @param string $unit L'unité de mesure
 * @return string
 */
function format_quantity($value, string $unit): string
{
    $decimals = unit_allows_decimal($unit) ? 2 : 0;
    return number_format((float) $value, $decimals, ',', ' ');
}

/**
 * Valide une quantité en fonction de l'unité
 * 
 * @param mixed $value La valeur à valider
 * @param string $unit L'unité de mesure
 * @param string $label Le nom du champ
 * @param float|null $default Valeur par défaut
 * @return float
 */
function validate_quantity_for_unit($value, string $unit, string $label, ?float $default = null): float
{
    if (!unit_allows_decimal($unit)) {
        return (float) validate_integer($value, $label, $default !== null ? (int) $default : null);
    }

    return validate_number($value, $label, $default);
}

/**
 * Valide une quantité positive ou nulle en fonction de l'unité
 * 
 * @param mixed $value La valeur à valider
 * @param string $unit L'unité de mesure
 * @param string $label Le nom du champ
 * @param float $default Valeur par défaut
 * @return float
 */
function validate_non_negative_quantity($value, string $unit, string $label, float $default = 0): float
{
    $number = validate_quantity_for_unit($value, $unit, $label, $default);

    if ($number < 0) {
        throw new InvalidArgumentException("$label ne peut pas etre negative.");
    }

    return $number;
}

/**
 * Valide une quantité strictement positive en fonction de l'unité
 * 
 * @param mixed $value La valeur à valider
 * @param string $unit L'unité de mesure
 * @param string $label Le nom du champ
 * @return float
 */
function validate_positive_quantity($value, string $unit, string $label): float
{
    $number = validate_quantity_for_unit($value, $unit, $label);

    if ($number <= 0) {
        throw new InvalidArgumentException("$label doit etre superieure a zero.");
    }

    return $number;
}

/**
 * Nettoie et valide une chaîne de caractères
 * 
 * @param mixed $value La valeur à nettoyer
 * @param string $label Le nom du champ
 * @param int $minLength Longueur minimale
 * @param int|null $maxLength Longueur maximale
 * @return string
 */
function validate_string($value, string $label, int $minLength = 1, ?int $maxLength = null): string
{
    if ($value === null) {
        throw new InvalidArgumentException("$label est obligatoire.");
    }

    $value = trim((string) $value);

    if ($value === '' && $minLength > 0) {
        throw new InvalidArgumentException("$label est obligatoire.");
    }

    if (strlen($value) < $minLength) {
        throw new InvalidArgumentException("$label doit contenir au moins $minLength caractere(s).");
    }

    if ($maxLength !== null && strlen($value) > $maxLength) {
        throw new InvalidArgumentException("$label ne peut pas depasser $maxLength caracteres.");
    }

    return $value;
}

/**
 * Valide une adresse email
 * 
 * @param mixed $value La valeur à valider
 * @param string $label Le nom du champ
 * @param bool $allowEmpty Si true, accepte une valeur vide
 * @return string
 */
function validate_email($value, string $label, bool $allowEmpty = false): string
{
    if ($value === null || trim((string) $value) === '') {
        if ($allowEmpty) {
            return '';
        }
        throw new InvalidArgumentException("$label est obligatoire.");
    }

    $value = trim((string) $value);

    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException("$label doit etre une adresse email valide.");
    }

    return $value;
}

/**
 * Valide une date au format YYYY-MM-DD
 * 
 * @param mixed $value La valeur à valider
 * @param string $label Le nom du champ
 * @param bool $allowEmpty Si true, accepte une valeur vide
 * @return string
 */
function validate_date($value, string $label, bool $allowEmpty = false): string
{
    if ($value === null || trim((string) $value) === '') {
        if ($allowEmpty) {
            return '';
        }
        throw new InvalidArgumentException("$label est obligatoire.");
    }

    $value = trim((string) $value);

    $date = \DateTime::createFromFormat('Y-m-d', $value);
    
    if (!$date || $date->format('Y-m-d') !== $value) {
        throw new InvalidArgumentException("$label doit etre une date valide (format YYYY-MM-DD).");
    }

    return $value;
}
