# Guide d'Amélioration de Sécurité et de Modularisation

## 📋 Points d'Amélioration Implémentés

### 1. ✅ Sécurisation des Accès CORS

**Problème :** Les en-têtes CORS étaient trop permissifs (`Access-Control-Allow-Origin: *`)

**Solution implémentée :**
- Création du fichier `/api/helpers/cors.php` avec gestion centralisée des origines
- Configuration des origines autorisées dans `/api/config/database.php`
- Validation dynamique des origines selon l'environnement (dev/prod)

**Fichiers créés :**
- `api/config/database.php` - Configuration des origines CORS
- `api/helpers/cors.php` - Fonctions de gestion CORS

### 2. ✅ Mots de Passe par Défaut

**Problème :** Mots de passe faibles et connus (admin/admin123, stock/stock123, projet/projet123)

**Solution implémentée :**
- Centralisation dans `/api/config/security.php` avec avertissements clairs
- Politique de mot de passe configurable (longueur minimale, caractères requis)
- Limitation des tentatives de connexion (5 essais max, verrouillage 15 min)
- Validation renforcée dans la classe `UserManager`

**Recommandations :**
```bash
# En production, changez IMMÉDIATEMENT ces mots de passe
# Ou mieux, utilisez des variables d'environnement :
DB_PASS=votre_mot_de_passe_securise
```

### 3. ✅ Modularisation de db.php

**Problème :** Fichier db.php de 916 lignes avec responsabilités multiples

**Solution implémentée :**
- Séparation en modules spécialisés :
  - `api/config/database.php` - Configuration DB
  - `api/config/security.php` - Configuration sécurité
  - `api/helpers/cors.php` - Gestion CORS
  - `api/helpers/validation.php` - Fonctions de validation
  - `api/models/Database.php` - Classe Database (pattern Singleton)
  - `api/models/User.php` - Classes User et UserManager

- Nouveau point d'entrée : `api/bootstrap.php` qui charge tous les modules
- Maintien de la compatibilité avec l'ancien code via des fonctions wrapper

### 4. 📁 Nouvelle Architecture

```
api/
├── bootstrap.php          # Point d'entrée principal (mis à jour)
├── config/
│   ├── database.php       # Configuration DB et CORS
│   └── security.php       # Configuration sécurité
├── helpers/
│   ├── cors.php           # Gestion des en-têtes CORS
│   └── validation.php     # Fonctions de validation
├── models/
│   ├── Database.php       # Classe Database
│   └── User.php           # Classes User et UserManager
└── [autres fichiers API]  # Compatibilité maintenue
```

## 🔧 Comment Utiliser la Nouvelle Architecture

### Pour les nouveaux développements :

```php
// Au lieu de : require __DIR__ . '/db.php';
// Utilisez :
require_once __DIR__ . '/bootstrap.php';

// Ou pour plus de contrôle :
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/helpers/cors.php';
require_once __DIR__ . '/helpers/validation.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/User.php';
```

### Exemple d'utilisation des nouvelles classes :

```php
// Utilisation de Database
$db = Database::getInstance();
$pdo = $db->getConnection();

// Ou avec les méthodes helper
$results = $db->select('SELECT * FROM users WHERE active = :active', ['active' => 1]);
$user = $db->selectOne('SELECT * FROM users WHERE id = :id', ['id' => 1]);

// Utilisation de UserManager
$userManager = new UserManager($pdo);
$user = $userManager->authenticate('username', 'password');
$users = $userManager->findAll();
$userManager->updatePassword($userId, 'newSecurePassword123!');
```

## ⚠️ Actions Requises pour la Production

### 1. Changer les mots de passe par défaut

```sql
-- Exécutez ces commandes dans phpMyAdmin ou MySQL
UPDATE users SET password_hash = '$2y$10$votre_hash_securise' WHERE username = 'admin';
UPDATE users SET password_hash = '$2y$10$votre_hash_securise' WHERE username = 'stock';
UPDATE users SET password_hash = '$2y$10$votre_hash_securise' WHERE username = 'projet';
```

Ou via l'interface d'administration une fois connecté.

### 2. Configurer les variables d'environnement

Créez un fichier `.env` (non versionné) :
```bash
DB_HOST=127.0.0.1
DB_NAME=gestion_stock_atelier
DB_USER=root
DB_PASS=votre_mot_de_passe_tres_securise
APP_ENV=production
```

### 3. Restreindre les origines CORS

Dans `api/config/database.php`, modifiez :
```php
const CORS_ALLOWED_ORIGINS = [
    'https://votre-domaine.com',
    'https://www.votre-domaine.com',
];
```

### 4. Activer HTTPS

Dans `api/config/database.php` :
```php
const SESSION_SECURE = true; // Obligatoire en HTTPS
```

## 📝 Prochaines Améliorations Recommandées

1. **Journalisation (Logging)** : Ajouter un système de logs pour tracer les actions sensibles
2. **Rate Limiting** : Limiter le nombre de requêtes par IP
3. **Validation CSRF** : Ajouter des tokens CSRF pour les formulaires
4. **Sanitization** : Nettoyer toutes les entrées utilisateur
5. **Prepared Statements** : Vérifier que toutes les requêtes SQL utilisent des prepared statements
6. **Tests unitaires** : Ajouter des tests pour valider les fonctionnalités critiques

## 🔄 Compatibilité

Toutes les modifications sont **rétro-compatibles**. L'application continue de fonctionner avec l'ancienne architecture grâce aux fonctions wrapper dans les nouveaux fichiers.

Les fichiers existants (`auth.php`, `materials.php`, etc.) n'ont pas besoin d'être modifiés pour fonctionner avec la nouvelle architecture.
