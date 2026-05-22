<?php
declare(strict_types=1);

/**
 * Configuration des paramètres de sécurité
 */

// Mots de passe par défaut - À CHANGER impérativement en production
const DEFAULT_USERS = [
    [
        'username' => 'admin',
        'password' => 'admin123', // ⚠️ Changer ce mot de passe
        'full_name' => 'Administrateur',
        'role' => 'admin',
    ],
    [
        'username' => 'stock',
        'password' => 'stock123', // ⚠️ Changer ce mot de passe
        'full_name' => 'Moderateur stock',
        'role' => 'moderateur_stock',
    ],
    [
        'username' => 'projet',
        'password' => 'projet123', // ⚠️ Changer ce mot de passe
        'full_name' => 'Gestionnaire projet',
        'role' => 'gestionnaire_projet',
    ],
];

// Rôles disponibles et leurs permissions
const ROLES = [
    'admin' => ['materials', 'movements', 'equipment', 'suppliers', 'orders', 'quotes', 'projects', 'users', 'settings'],
    'moderateur_stock' => ['materials', 'movements', 'equipment', 'suppliers', 'orders'],
    'gestionnaire_projet' => ['quotes', 'projects', 'simulation'],
];

// Politique de mot de passe
const PASSWORD_MIN_LENGTH = 8;
const PASSWORD_REQUIRE_UPPERCASE = true;
const PASSWORD_REQUIRE_LOWERCASE = true;
const PASSWORD_REQUIRE_DIGIT = true;
const PASSWORD_REQUIRE_SPECIAL = false;

// Tentatives de connexion maximales avant verrouillage
const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_LOCKOUT_DURATION = 900; // 15 minutes
