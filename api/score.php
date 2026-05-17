<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php';

$env = parse_ini_file(__DIR__ . '/../.env');
define('OWM_API_KEY', $env['OWM_API_KEY'] ?? '');
define('OWM_URL', 'https://api.openweathermap.org/data/2.5/weather');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo no permitido']);
    exit;
}

$body       = json_decode(file_get_contents('php://input'), true);
$usuario_id = (int) ($body['usuario_id'] ?? 0);
$mood       = (int) ($body['mood'] ?? 0);

if ($usuario_id <= 0 || $mood < 1 || $mood > 10) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos invalidos. mood debe estar entre 1 y 10.']);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT id, nombre, ciudad FROM usuarios WHERE id = ?');
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuario no encontrado']);
    exit;
}

$clima_url = 'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode($usuario['ciudad']) . ',CO&appid=' . OWM_API_KEY . '&units=metric&lang=es';

$ch = curl_init($clima_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$clima_raw = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$clima_raw) {
    logEvent($usuario_id, '/api/score.php', 'error_clima', 'No se pudo obtener clima para ' . $usuario['ciudad']);
    http_response_code(502);
    echo json_encode(['error' => 'No se pudo obtener el clima. Intenta de nuevo.']);
    exit;
}

$clima_data  = json_decode($clima_raw, true);
$temperatura = round($clima_data['main']['temp'], 1);
$descripcion = $clima_data['weather'][0]['description'] ?? 'desconocido';
$clima_id    = $clima_data['weather'][0]['id'] ?? 800;

$score = $mood * 10;
if ($temperatura >= 18 && $temperatura <= 25) $score += 10;
if ($clima_id === 800)                        $score += 10;
if ($clima_id >= 500 && $clima_id < 600)      $score -= 5;
$score = max(5, min(120, $score));

$ins = $pdo->prepare('INSERT INTO registros (usuario_id, mood, score, clima, temperatura) VALUES (?, ?, ?, ?, ?)');
$ins->execute([$usuario_id, $mood, $score, $descripcion, $temperatura]);
$registro_id = $pdo->lastInsertId();

logEvent($usuario_id, '/api/score.php', 'registro_score', 'mood: ' . $mood . ' | temp: ' . $temperatura . 'C | clima: ' . $descripcion . ' | score: ' . $score);

echo json_encode([
    'ok'          => true,
    'registro_id' => (int) $registro_id,
    'usuario'     => $usuario['nombre'],
    'ciudad'      => $usuario['ciudad'],
    'mood'        => $mood,
    'clima'       => $descripcion,
    'temperatura' => $temperatura,
    'score'       => $score,
]);
