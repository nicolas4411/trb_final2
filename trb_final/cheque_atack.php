<?php
require_once 'db.php';

function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
    return $_SERVER['REMOTE_ADDR'];
}

$ip = getClientIP();

// Consulta tentativas nos últimos 3 minutos
$stmt = $mysqli->prepare("
    SELECT COUNT(*) as total 
    FROM login_tentativas 
    WHERE ip_address = ? 
    AND tentativa_time > (NOW() - INTERVAL 3 MINUTE)
");
$stmt->bind_param("s", $ip);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$response = [
    'blocked' => $data['total'] >= 3,
    'attempts' => $data['total']
];

header('Content-Type: application/json');
echo json_encode($response);

$mysqli->close();
?>
