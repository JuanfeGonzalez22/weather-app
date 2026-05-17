<?php
define('LOG_PATH', __DIR__ . '/../logs/app.log');

function logEvent(int $usuario_id, string $ruta, string $accion, string $detalle = ''): void {
    $timestamp = date('Y-m-d H:i:s');
    $linea     = "[$timestamp] | usuario_id: $usuario_id | ruta: $ruta | accion: $accion | $detalle" . PHP_EOL;

    file_put_contents(LOG_PATH, $linea, FILE_APPEND | LOCK_EX);

    try {
        require_once __DIR__ . '/db.php';
        $pdo  = getDB();
        $stmt = $pdo->prepare(
            'INSERT INTO event_logs (usuario_id, ruta, accion, detalle) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$usuario_id, $ruta, $accion, $detalle]);
    } catch (Throwable $e) {
        file_put_contents(LOG_PATH, "[$timestamp] ERROR guardando log en BD: {$e->getMessage()}" . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}