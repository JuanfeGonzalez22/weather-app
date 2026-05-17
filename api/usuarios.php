<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';

$pdo  = getDB();
$stmt = $pdo->query('SELECT id, nombre, ciudad FROM usuarios ORDER BY nombre');

echo json_encode([
    'ok'       => true,
    'usuarios' => $stmt->fetchAll(),
]);
