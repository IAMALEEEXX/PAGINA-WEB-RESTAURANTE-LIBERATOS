<?php
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/database.php';
require_once 'ReservaClass.php';

$db  = new Database();
$conn = $db->connect();
$class = new ReservaClass($conn);

// Recibir texto
$data  = json_decode(file_get_contents("php://input"), true);
$texto = strtolower($data["texto"] ?? "");

// Extraer datos automáticos
$fecha = $class->parseFecha($texto);
$hora  = $class->parseHora($texto);

// Combinar fecha + hora si ambas existen
$fecha_final = null;
if ($fecha && $hora) $fecha_final = $fecha . " " . $hora;

// Extraer número de personas
$personas = 1;
if (preg_match("/(\d+)\s*personas?/", $texto, $m)) {
    $personas = (int)$m[1];
}

echo json_encode([
    "texto"   => $texto,
    "fecha"   => $fecha,
    "hora"    => $hora,
    "fecha_final" => $fecha_final,
    "personas" => $personas
]);
?>
