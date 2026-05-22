<?php
declare(strict_types=1);

/**
 * Point d'entrée unique pour l'initialisation de l'application
 * 
 * Ce fichier remplace l'inclusion directe de db.php et centralise
 * le chargement des dépendances et la configuration.
 */

// Charger la configuration
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';

// Charger les helpers
require_once __DIR__ . '/helpers/cors.php';
require_once __DIR__ . '/helpers/validation.php';

// Charger les modèles
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/User.php';

// Gérer les requêtes preflight CORS
handle_preflight_request();

// Envoyer les en-têtes CORS
send_cors_headers($_SERVER['HTTP_ORIGIN'] ?? null);

// Démarrer la session avec les paramètres de sécurité
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.cookie_httponly', SESSION_HTTPONLY ? '1' : '0');
    ini_set('session.cookie_secure', SESSION_SECURE ? '1' : '0');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    session_start();
}

try {
    $pdo = db();
    $user = require_auth($pdo);
    $role = $user['role'];
    
    $items = fetch_materials($pdo);
    $orders = [];
    $movements = [];
    $history = [];
    $suppliers = [];
    $equipments = [];
    $equipmentHistory = [];
    
    if ($role === 'admin') {
        $orders = fetch_orders($pdo);
        $movements = fetch_movements($pdo);
        $history = fetch_stock_history($pdo);
        $suppliers = fetch_suppliers($pdo);
        $equipments = fetch_equipment($pdo);
        $equipmentHistory = fetch_equipment_history($pdo);
    } elseif ($role === 'moderateur_stock') {
        $movements = fetch_movements($pdo);
        $equipments = fetch_equipment($pdo);
        $equipmentHistory = fetch_equipment_history($pdo);
    }
    
    json_response([
        'ok' => true,
        'user' => $user,
        'items' => $items,
        'orders' => $orders,
        'movements' => $movements,
        'history' => $history,
        'suppliers' => $suppliers,
        'equipments' => $equipments,
        'equipmentHistory' => $equipmentHistory,
        'params' => fetch_settings($pdo),
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => 'Connexion a la base de donnees impossible.',
        'detail' => $e->getMessage(),
    ], 500);
}
