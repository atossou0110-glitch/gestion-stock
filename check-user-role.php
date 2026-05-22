<?php
require __DIR__ . '/api/db.php';
try {
    $pdo = db();
    // Chercher l'utilisateur admin dans la DB
    $stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE username = ?');
    $stmt->execute(['admin']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ Utilisateur trouvé:\n";
        echo "  Username: " . $user['username'] . "\n";
        echo "  Role: " . $user['role'] . "\n";
        echo "  ID: " . $user['id'] . "\n";
    } else {
        echo "❌ Utilisateur 'admin' non trouvé\n";
        echo "\nUtilisateurs disponibles:\n";
        $stmt = $pdo->query('SELECT id, username, role FROM users');
        foreach ($stmt->fetchAll() as $u) {
            echo "  - " . $u['username'] . " (role: " . $u['role'] . ")\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
