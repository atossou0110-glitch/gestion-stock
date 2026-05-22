<?php
declare(strict_types=1);

/**
 * Classe de gestion de la connexion à la base de données
 * 
 * Cette classe remplace les fonctions globales db() et fournit
 * une interface orientée objet pour la gestion de la base de données.
 */

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;
    
    /**
     * Constructeur privé pour le pattern Singleton
     */
    private function __construct()
    {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    
    /**
     * Empêche le clonage de l'instance
     */
    private function __clone() {}
    
    /**
     * Empêche la désérialisation de l'instance
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
    
    /**
     * Retourne l'instance unique de la classe Database
     * 
     * @return Database
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Retourne l'instance PDO
     * 
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }
    
    /**
     * Exécute une requête SELECT et retourne les résultats
     * 
     * @param string $sql La requête SQL
     * @param array $params Les paramètres de la requête
     * @return array Les résultats
     */
    public function select(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Exécute une requête SELECT et retourne un seul résultat
     * 
     * @param string $sql La requête SQL
     * @param array $params Les paramètres de la requête
     * @return array|null Le résultat ou null si aucun
     */
    public function selectOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Exécute une requête INSERT, UPDATE ou DELETE
     * 
     * @param string $sql La requête SQL
     * @param array $params Les paramètres de la requête
     * @return int Le nombre de lignes affectées
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    
    /**
     * Commence une transaction
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Valide une transaction
     * 
     * @return bool
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }
    
    /**
     * Annule une transaction
     * 
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }
    
    /**
     * Retourne le dernier ID inséré
     * 
     * @param string|null $name Nom de la séquence (optionnel)
     * @return string
     */
    public function lastInsertId(?string $name = null): string
    {
        return $this->pdo->lastInsertId($name);
    }
}

/**
 * Fonction helper pour obtenir une instance de la base de données
 * Maintient la compatibilité avec l'ancien code
 * 
 * @return PDO
 */
function db(): PDO
{
    return Database::getInstance()->getConnection();
}
