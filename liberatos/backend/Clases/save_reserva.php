<?php
// ===============================
// 🔓 CORS PARA CHATBOT (localhost:3000)
// ===============================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ===============================
// 🔌 CONEXIÓN A LA BASE DE DATOS
// ===============================
$host = "localhost";
$db_name = "liberatos";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $db_name);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión: " . $conn->connect_error]);
    exit;
}

// ===============================
// 📥 OBTENER DATOS (JSON o POST)
// ===============================
$inputJSON = file_get_contents("php://input");
$data = json_decode($inputJSON, true);

if (!$data) { 
    $data = $_POST;
}

if (!$data) {
    echo json_encode(["success" => false, "message" => "❌ No se recibieron datos"]);
    exit;
}

// ===============================
// 🧪 VALIDAR DATOS
// ===============================
$nombre    = trim($data["nombre"] ?? "");
$correo    = trim($data["correo"] ?? "");
$personas  = isset($data["personas"]) ? (int)$data["personas"] : 1;
$fecha     = trim($data["fecha"] ?? "");
$hora      = trim($data["hora"] ?? "");
$mensaje   = trim($data["mensaje"] ?? "");

// Validar nombre
if ($nombre === "") {
    echo json_encode(["success" => false, "message" => "❌ Falta el nombre"]);
    exit;
}

// Correo opcional
if ($correo === "") {
    $correo = "no-proporcionado";
}

// ===============================
// 🔥 UNIR FECHA + HORA
// ===============================
if ($fecha !== "" && $hora !== "") {
    
    // Combinar fecha y hora correctamente
    $fechaCompleta = $fecha . " " . $hora . ":00";

} else {
    // Si falta algo, asignar actual
    $fechaCompleta = date("Y-m-d H:i:s");
}

// Validar formato
if (strtotime($fechaCompleta) === false) {
    $fechaCompleta = date("Y-m-d H:i:s");
} else {
    $fechaCompleta = date("Y-m-d H:i:s", strtotime($fechaCompleta));
}

// ===============================
// 💾 GUARDAR EN BD
// ===============================
$sql = "INSERT INTO reservas (nombre, correo, personas, fecha, mensaje, fecha_registro)
        VALUES (?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssiss", $nombre, $correo, $personas, $fechaCompleta, $mensaje);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "✅ Reserva guardada correctamente",
        "data" => [
            "nombre" => $nombre,
            "correo" => $correo,
            "personas" => $personas,
            "fecha" => $fechaCompleta,
            "mensaje" => $mensaje
        ]
    ]);

} else {
    echo json_encode([
        "success" => false,
        "message" => "❌ Error al guardar la reserva: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
