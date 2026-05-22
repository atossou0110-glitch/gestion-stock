<?php
declare(strict_types=1);

/**
 * Classe de gestion des utilisateurs
 * 
 * Cette classe centralise les opérations liées aux utilisateurs
 * et maintient la compatibilité avec les fonctions existantes.
 */

class User
{
    private int $id;
    private string $username;
    private string $fullName;
    private string $role;
    private bool $active;
    
    /**
     * Constructeur
     */
    public function __construct(
        int $id,
        string $username,
        string $fullName,
        string $role,
        bool $active = true
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->fullName = $fullName;
        $this->role = $role;
        $this->active = $active;
    }
    
    /**
     * Crée un utilisateur à partir d'une ligne de base de données
     */
    public static function fromArray(array $row): self
    {
        return new self(
            (int) $row['id'],
            $row['username'],
            $row['full_name'],
            $row['role'],
            (bool) ($row['active'] ?? true)
        );
    }
    
    /**
     * Convertit l'utilisateur en tableau pour l'API
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'name' => $this->fullName,
            'role' => $this->role,
        ];
    }
    
    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
    
    /**
     * Vérifie si l'utilisateur a l'un des rôles spécifiés
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }
    
    /**
     * Vérifie si l'utilisateur est administrateur
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    
    /**
     * Vérifie si l'utilisateur peut accéder à une ressource
     */
    public function canAccess(string $resource): bool
    {
        if (!defined('ROLES')) {
            return true; // Si aucune configuration, on autorise par défaut
        }
        
        $permissions = ROLES[$this->role] ?? [];
        return in_array($resource, $permissions, true) || $this->isAdmin();
    }
    
    /**
     * Getters
     */
    public function getId(): int
    {
        return $this->id;
    }
    
    public function getUsername(): string
    {
        return $this->username;
    }
    
    public function getFullName(): string
    {
        return $this->fullName;
    }
    
    public function getRole(): string
    {
        return $this->role;
    }
    
    public function isActive(): bool
    {
        return $this->active;
    }
}

/**
 * Gestionnaire d'utilisateurs
 */
class UserManager
{
    private PDO $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ensureTableExists();
    }
    
    /**
     * Crée la table users si elle n'existe pas
     */
    public function ensureTableExists(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS users (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT,
              username VARCHAR(80) NOT NULL,
              password_hash VARCHAR(255) NOT NULL,
              full_name VARCHAR(120) NOT NULL,
              role VARCHAR(40) NOT NULL,
              active TINYINT(1) NOT NULL DEFAULT 1,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              UNIQUE KEY uq_users_username (username),
              INDEX idx_users_role (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        
        $this->seedDefaultUsers();
    }
    
    /**
     * Insère les utilisateurs par défaut si la table est vide
     */
    public function seedDefaultUsers(): void
    {
        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        
        if ($count === 0 && defined('DEFAULT_USERS')) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (username, password_hash, full_name, role)
                 VALUES (:username, :password_hash, :full_name, :role)'
            );
            
            foreach (DEFAULT_USERS as $userData) {
                $stmt->execute([
                    ':username' => $userData['username'],
                    ':password_hash' => password_hash($userData['password'], PASSWORD_DEFAULT),
                    ':full_name' => $userData['full_name'],
                    ':role' => $userData['role'],
                ]);
            }
        }
    }
    
    /**
     * Trouve un utilisateur par nom d'utilisateur
     */
    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, password_hash, full_name, role, active
             FROM users
             WHERE username = :username
             LIMIT 1'
        );
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        
        return $user ? User::fromArray($user) : null;
    }
    
    /**
     * Trouve un utilisateur par ID
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, full_name, role, active
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        
        return $user ? User::fromArray($user) : null;
    }
    
    /**
     * Authentifie un utilisateur
     */
    public function authenticate(string $username, string $password): ?User
    {
        $user = $this->findByUsername($username);
        
        if (!$user || !$user->isActive()) {
            return null;
        }
        
        $stmt = $this->pdo->prepare(
            'SELECT password_hash FROM users WHERE username = :username LIMIT 1'
        );
        $stmt->execute([':username' => $username]);
        $data = $stmt->fetch();
        
        if (!password_verify($password, $data['password_hash'])) {
            return null;
        }
        
        return $user;
    }
    
    /**
     * Crée un nouvel utilisateur
     */
    public function create(
        string $username,
        string $password,
        string $fullName,
        string $role
    ): User {
        // Valider le mot de passe
        $this->validatePassword($password);
        
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, password_hash, full_name, role)
             VALUES (:username, :password_hash, :full_name, :role)'
        );
        
        $stmt->execute([
            ':username' => $username,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':full_name' => $fullName,
            ':role' => $role,
        ]);
        
        return $this->findById((int) $this->pdo->lastInsertId());
    }
    
    /**
     * Valide la force d'un mot de passe
     */
    private function validatePassword(string $password): void
    {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            throw new InvalidArgumentException(
                "Le mot de passe doit contenir au moins " . PASSWORD_MIN_LENGTH . " caracteres."
            );
        }
        
        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            throw new InvalidArgumentException(
                "Le mot de passe doit contenir au moins une lettre majuscule."
            );
        }
        
        if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            throw new InvalidArgumentException(
                "Le mot de passe doit contenir au moins une lettre minuscule."
            );
        }
        
        if (PASSWORD_REQUIRE_DIGIT && !preg_match('/[0-9]/', $password)) {
            throw new InvalidArgumentException(
                "Le mot de passe doit contenir au moins un chiffre."
            );
        }
        
        if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new InvalidArgumentException(
                "Le mot de passe doit contenir au moins un caractere special."
            );
        }
    }
    
    /**
     * Met à jour le mot de passe d'un utilisateur
     */
    public function updatePassword(int $userId, string $newPassword): void
    {
        $this->validatePassword($newPassword);
        
        $stmt = $this->pdo->prepare(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id'
        );
        
        $stmt->execute([
            ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':id' => $userId,
        ]);
    }
    
    /**
     * Active ou désactive un utilisateur
     */
    public function setActive(int $userId, bool $active): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET active = :active WHERE id = :id'
        );
        
        $stmt->execute([
            ':active' => $active ? 1 : 0,
            ':id' => $userId,
        ]);
    }
    
    /**
     * Liste tous les utilisateurs
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, username, full_name, role, active FROM users ORDER BY username ASC'
        );
        
        return array_map(
            fn($row) => User::fromArray($row),
            $stmt->fetchAll()
        );
    }
}

/**
 * Fonctions helpers pour maintenir la compatibilité
 */

/**
 * Assure que la table users existe
 */
function ensure_users_table(PDO $pdo): void
{
    $userManager = new UserManager($pdo);
    $userManager->ensureTableExists();
}

/**
 * Convertit un tableau utilisateur en format application
 */
function user_to_app(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'username' => $row['username'],
        'name' => $row['full_name'],
        'role' => $row['role'],
    ];
}

/**
 * Retourne l'utilisateur courant
 */
function current_user(PDO $pdo): ?array
{
    ensure_users_table($pdo);
    
    $id = (int) ($_SESSION['user_id'] ?? 0);
    
    if ($id <= 0) {
        return null;
    }
    
    $userManager = new UserManager($pdo);
    $user = $userManager->findById($id);
    
    if (!$user || !$user->isActive()) {
        unset($_SESSION['user_id']);
        return null;
    }
    
    return $user->toArray();
}

/**
 * Nécessite qu'un utilisateur soit authentifié
 */
function require_auth(PDO $pdo): array
{
    $user = current_user($pdo);
    
    if (!$user) {
        json_response(['ok' => false, 'error' => 'Connexion requise.'], 401);
    }
    
    return $user;
}

/**
 * Nécessite que l'utilisateur ait l'un des rôles spécifiés
 */
function require_roles(PDO $pdo, array $roles): array
{
    $user = require_auth($pdo);
    
    if ($user['role'] === 'admin' || in_array($user['role'], $roles, true)) {
        return $user;
    }
    
    json_response(['ok' => false, 'error' => 'Acces refuse pour ce role.'], 403);
}
