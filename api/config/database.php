<?php
declare(strict_types=1);

/**
 * Configuration de la base de données
 * 
 * Ce fichier contient les paramètres de connexion à la base de données.
 * Pour des raisons de sécurité, ces valeurs devraient être définies via
 * des variables d'environnement en production.
 * 
 * Exemple pour .env (non inclus dans le dépôt):
 * DB_HOST=127.0.0.1
 * DB_NAME=gestion_stock_atelier
 * DB_USER=root
 * DB_PASS=votre_mot_de_passe_securise
 */

const DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
const DB_NAME = getenv('DB_NAME') ?: 'gestion_stock_atelier';
const DB_USER = getenv('DB_USER') ?: 'root';
const DB_PASS = getenv('DB_PASS') ?: '';

// Configuration CORS - À restreindre en production
const CORS_ALLOWED_ORIGINS = [
    'http://localhost',
    'http://127.0.0.1',
];

// Configuration de session
const SESSION_LIFETIME = 3600; // 1 heure
const SESSION_SECURE = false; // Mettre à true en HTTPS
const SESSION_HTTPONLY = true;
