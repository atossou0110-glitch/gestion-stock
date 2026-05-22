<?php
declare(strict_types=1);

/**
 * Gestion des en-têtes CORS (Cross-Origin Resource Sharing)
 * 
 * Ce fichier centralise la configuration CORS pour améliorer la sécurité.
 * En production, il est recommandé de restreindre les origines autorisées.
 */

require_once __DIR__ . '/database.php';

/**
 * Envoie les en-têtes CORS appropriés
 * 
 * @param string|null $origin L'origine de la requête
 */
function send_cors_headers(?string $origin = null): void
{
    // Si aucune origine n'est fournie, utiliser le tableau par défaut
    if ($origin === null || $origin === '') {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    }
    
    // Vérifier si l'origine est autorisée
    $allowed = false;
    if ($origin !== '' && in_array($origin, CORS_ALLOWED_ORIGINS, true)) {
        $allowed = true;
    }
    
    // En développement, on peut être plus permissif
    if (getenv('APP_ENV') === 'development') {
        $allowed = true;
    }
    
    if ($allowed && $origin !== '') {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    } else {
        // Origine non autorisée - ne pas envoyer de header Access-Control-Allow-Origin
        // ou utiliser une origine spécifique en production
        if (defined('CORS_PRODUCTION_ORIGIN')) {
            header('Access-Control-Allow-Origin: ' . CORS_PRODUCTION_ORIGIN);
        }
    }
    
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400'); // 24 heures
}

/**
 * Gère les requêtes preflight OPTIONS
 */
function handle_preflight_request(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        send_cors_headers();
        http_response_code(204);
        exit;
    }
}

/**
 * Valide l'origine de la requête
 * 
 * @return bool True si l'origine est autorisée
 */
function validate_origin(): bool
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if ($origin === '') {
        return true; // Requêtes same-origin
    }
    
    return in_array($origin, CORS_ALLOWED_ORIGINS, true);
}
