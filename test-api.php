<?php
session_start();
// Simuler une session utilisateur connecté
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

// Inclure bootstrap pour tester directement
require __DIR__ . '/api/bootstrap.php';
