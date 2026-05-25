<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// === CONEXIÓN A LA BASE DE DATOS ===
$host = "localhost";
$db_name = "liberatos";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $db_name);

// Verificar conexión
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos: " . $conn->connect_error]);
    exit;
}

// === OBTENER LOS DATOS JSON ===
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No se recibieron datos."]);
    exit;
}

// === VALIDAR CAMPOS OBLIGATORIOS ===
$campos = ["nombre", "telefono", "direccion", "categoria", "plato", "cantidad", "fecha"];
foreach ($campos as $campo) {
    if (empty($data[$campo])) {
        echo json_encode(["success" => false, "message" => "Campo faltante: $campo"]);
        exit;
    }
}

// === NUEVO: Validar precio y subtotal ===
if (!isset($data["precio_unitario"]) || !isset($data["subtotal"])) {
    echo json_encode(["success" => false, "message" => "Falta precio_unitario o subtotal"]);
    exit;
}

// === LIMPIAR DATOS ===
$nombre     = $conn->real_escape_string($data["nombre"]);
$telefono   = $conn->real_escape_string($data["telefono"]);
$correo     = isset($data["correo"]) ? $conn->real_escape_string($data["correo"]) : "";
$direccion  = $conn->real_escape_string($data["direccion"]);
$categoria  = $conn->real_escape_string($data["categoria"]);
$plato      = $conn->real_escape_string($data["plato"]);
$cantidad   = (int)$data["cantidad"];
$fecha      = $conn->real_escape_string($data["fecha"]);

$precio_unitario = (float)$data["precio_unitario"];   // NUEVO
$subtotal        = (float)$data["subtotal"];          // NUEVO

// === CONSULTA SQL ===
// NUEVO: Agregamos precio_unitario y subtotal SIN tocar lo viejo
$sql = "INSERT INTO pedidos (nombre, telefono, correo, direccion, categoria, plato, cantidad, precio_unitario, subtotal, fecha)
        VALUES ('$nombre', '$telefono', '$correo', '$direccion', '$categoria', '$plato', '$cantidad', '$precio_unitario', '$subtotal', '$fecha')";

if ($conn->query($sql)) {

    // === NUEVO: Registrar en tabla GANANCIAS ===
    $pedido_id = $conn->insert_id;

    $sql_g = "INSERT INTO ganancias (pedido_id, monto, fecha)
              VALUES ('$pedido_id', '$subtotal', NOW())";
    $conn->query($sql_g);

    echo json_encode([
        "success" => true,
        "message" => "Pedido guardado correctamente",
        "pedido_id" => $pedido_id
    ]);

} else {
    echo json_encode(["success" => false, "message" => "Error al guardar: " . $conn->error]);
}

$conn->close();
?>
