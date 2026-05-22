# ⚠️ Configuration de Sécurité - À Faire IMMÉDIATEMENT en Production

## 🔴 ÉTAPE 1: Changer les Mots de Passe Par Défaut

### Comptes actuels (DANGEREUX - À CHANGER):
```
admin    / admin123
stock    / stock123
projet   / projet123
```

### Comment les changer:

**Option 1: Directement dans la base de données**
```sql
-- Générer les hash des nouveaux mots de passe avec PHP:
-- php -r "echo password_hash('VotreNouveauMotDePasse123', PASSWORD_BCRYPT);"

UPDATE users SET password_hash = '$2y$10$...' WHERE username = 'admin';
UPDATE users SET password_hash = '$2y$10$...' WHERE username = 'stock';
UPDATE users SET password_hash = '$2y$10$...' WHERE username = 'projet';
```

**Option 2: Via script PHP**
```php
<?php
require __DIR__ . '/api/db.php';
$pdo = db();

$users = [
    'admin' => 'VotreMotAdminSecurise123!',
    'stock' => 'VotreMotStockSecurise123!', 
    'projet' => 'VotreMotProjetSecurise123!'
];

foreach ($users as $username => $password) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE username = ?');
    $stmt->execute([$hash, $username]);
    echo "✅ $username - Mot de passe changé\n";
}
?>
```

---

## 🟡 ÉTAPE 2: Configurer la Base de Données

**Fichier**: `api/config/database.php`

### Utiliser des variables d'environnement (RECOMMANDÉ):
```php
const DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
const DB_NAME = getenv('DB_NAME') ?: 'gestion_stock_atelier';
const DB_USER = getenv('DB_USER') ?: 'root';
const DB_PASS = getenv('DB_PASS') ?: ''; // ⚠️ À définir en env
```

**En XAMPP**, créer un fichier `.env` ou définir via Apache VirtualHost:
```apache
SetEnv DB_HOST "127.0.0.1"
SetEnv DB_NAME "gestion_stock_atelier"
SetEnv DB_USER "root"
SetEnv DB_PASS "VotreMotDePasseSecurise"
```

---

## 🟢 ÉTAPE 3: Configurer CORS en Production

**Fichier**: `api/config/database.php`

Remplacer:
```php
const CORS_ALLOWED_ORIGINS = [
    'http://localhost',
    'http://127.0.0.1',
];
```

Par vos domaines de production:
```php
const CORS_ALLOWED_ORIGINS = [
    'https://votre-domaine.com',
    'https://www.votre-domaine.com',
];
```

---

## 🔒 ÉTAPE 4: Forcer HTTPS

### En Apache (httpd.conf ou .htaccess):
```apache
# Redirection HTTP → HTTPS
<VirtualHost *:80>
    ServerName votre-domaine.com
    Redirect / https://votre-domaine.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName votre-domaine.com
    DocumentRoot /var/www/gestion-stock
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/votre-domaine.com.crt
    SSLCertificateKeyFile /etc/ssl/private/votre-domaine.com.key
</VirtualHost>
```

### Ou dans .htaccess:
```apache
# Forcer HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## ✅ Vérifications Post-Installation

```bash
# 1. Tester les mots de passe changés
curl -X POST http://localhost/gestion-stock/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{"username":"admin", "password":"VotreNouveauMotDePasse"}'

# 2. Vérifier HTTPS activé
curl -I https://votre-domaine.com/gestion-stock/

# 3. Vérifier CORS restreint
curl -H "Origin: http://attacker.com" \
  -H "Access-Control-Request-Method: GET" \
  https://votre-domaine.com/gestion-stock/api/bootstrap.php
# Doit refuser l'accès (pas de header Access-Control-Allow-Origin)

# 4. Vérifier SESSION_SECURE activé
php -r "var_dump(ini_get('session.cookie_secure'));"
# Doit afficher "1"
```

---

## 📋 Checklist de Sécurité

- [ ] Mots de passe par défaut changés
- [ ] Variables d'environnement configurées
- [ ] CORS restreint aux domaines autorisés
- [ ] HTTPS activé et forcé
- [ ] SESSION_SECURE = true en production
- [ ] Certificats SSL valides installés
- [ ] Sauvegardes de la base de données en place
- [ ] Tests d'accès effectués
- [ ] Logs de connexion vérifiés

---

## 🚨 Rappels Importants

1. **Ne jamais** commiter les mots de passe en code
2. **Toujours** utiliser des variables d'environnement
3. **Régulièrement** renouveler les certificats SSL
4. **Toujours** faire des sauvegardes avant modifications
5. **Monitorer** les tentatives de connexion échouées

---

**Dernière mise à jour**: 22 mai 2026
**Status**: 🔴 CRITIQUE - À faire avant production
