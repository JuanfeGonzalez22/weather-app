<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php';

$pdo  = getDB();
$tipo = $_GET['tipo']        ?? 'todo';
$uid  = (int)($_GET['usuario_id'] ?? 0);

$where_tiempo = match($tipo) {
    'semana' => 'AND r.fecha_registro >= DATE_SUB(NOW(), INTERVAL 1 WEEK)',
    'mes'    => 'AND r.fecha_registro >= DATE_SUB(NOW(), INTERVAL 1 MONTH)',
    default  => '',
};

$where_usuario = $uid > 0 ? 'AND r.usuario_id = :uid' : '';

$sql = "
    SELECT
        u.nombre,
        u.ciudad,
        COUNT(r.id)            AS total_registros,
        ROUND(AVG(r.score), 2) AS score_promedio,
        MAX(r.score)           AS score_maximo,
        MIN(r.score)           AS score_minimo,
        ROUND(AVG(r.mood), 1)  AS mood_promedio,
        r.clima                AS ultimo_clima
    FROM registros r
    JOIN usuarios u ON u.id = r.usuario_id
    WHERE 1=1
    $where_tiempo
    $where_usuario
    GROUP BY r.usuario_id, u.nombre, u.ciudad, r.clima
    ORDER BY score_promedio DESC
";

$stmt = $pdo->prepare($sql);
if ($uid > 0) $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
$stmt->execute();
$resumen = $stmt->fetchAll();

$detalle_sql = "
    SELECT
        r.id,
        u.nombre,
        r.mood,
        r.score,
        r.clima,
        r.temperatura,
        DATE_FORMAT(r.fecha_registro, '%d/%m/%Y %H:%i') AS fecha
    FROM registros r
    JOIN usuarios u ON u.id = r.usuario_id
    WHERE 1=1
    $where_tiempo
    $where_usuario
    ORDER BY r.fecha_registro DESC
    LIMIT 50
";

$stmt2 = $pdo->prepare($detalle_sql);
if ($uid > 0) $stmt2->bindValue(':uid', $uid, PDO::PARAM_INT);
$stmt2->execute();
$detalle = $stmt2->fetchAll();

logEvent(
    $uid ?: 0,
    '/api/reports.php',
    'consulta_reporte',
    "tipo: $tipo | usuario_id: $uid"
);

echo json_encode([
    'ok'      => true,
    'tipo'    => $tipo,
    'resumen' => $resumen,
    'detalle' => $detalle,
]);