<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
require_once 'ReservaClass.php';

$db  = new Database();
$conn = $db->connect();
$reserva = new ReservaClass($conn);

$nombre   = $_POST["nombre"];
$correo   = $_POST["correo"];
$personas = $_POST["personas"];
$fecha    = $_POST["fecha"];
$mensaje  = $_POST["mensaje"] ?? "";

$ok = $reserva->guardarReserva($nombre, $correo, $personas, $fecha, $mensaje);

echo $ok ? "✅ Reserva guardada correctamente" : "❌ Error al guardar la reserva";
?>
