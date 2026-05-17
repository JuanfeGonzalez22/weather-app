<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "PHP funciona";

try {
    require_once __DIR__ . '/db.php';
    $pdo = getDB();
    echo " | BD conectada";
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM usuarios');
    echo " | Usuarios: " . $stmt->fetch()['total'];
} catch (Throwable $e) {
    echo " | ERROR: " . $e->getMessage();
}
