<?php
// backend/Clases/Pedido.php

// --- 1. CONFIGURACIÓN Y LIBRERÍAS ---
$baseDir = dirname(__DIR__); 

// Cargar PHPMailer
require_once $baseDir . '/vendor/phpmailer/phpmailer/src/Exception.php';
require_once $baseDir . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once $baseDir . '/vendor/phpmailer/phpmailer/src/SMTP.php';

// Cargar configuración de correo
// (Solo lo cargamos una vez aquí arriba para evitar errores)
$mailConfig = require_once $baseDir . '/config/mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// --- 2. CONEXIÓN DB ---
$host = "localhost"; $db_name = "liberatos"; $username = "root"; $password = "";
$conn = new mysqli($host, $username, $password, $db_name);

if ($conn->connect_error) { 
    echo json_encode(["success"=>false, "message"=>"Error de conexión DB"]); 
    exit; 
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success"=>false, "message"=>"Método inválido"]); 
    exit;
}

// --- 3. DATOS DEL FORMULARIO ---
$nombre     = $_POST["nombre"] ?? "";
$telefono   = $_POST["telefono"] ?? "";
$correo     = $_POST["correo"] ?? "";
$direccion  = $_POST["direccion"] ?? "";
$categoria  = $_POST["categoria"] ?? "";
$plato      = $_POST["plato"] ?? "";
$cantidad   = (int)($_POST["cantidad"] ?? 0);
$precio     = (float)($_POST["precio_unitario"] ?? 0);
$subtotal   = (float)($_POST["subtotal"] ?? 0);
$fecha      = date("Y-m-d H:i:s");

// --- 4. MANEJO DE IMAGEN ---
$uploadDir = realpath(__DIR__ . "/..") . "/comprobantes/";
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

$ruta_bd = "";
$ruta_adjunto = null;

if (isset($_FILES["imagen_pago"]) && $_FILES["imagen_pago"]["error"] === UPLOAD_ERR_OK) {
    $fileName = "pago_" . time() . "_" . basename($_FILES["imagen_pago"]["name"]);
    $destPath = $uploadDir . $fileName;
    if (move_uploaded_file($_FILES["imagen_pago"]["tmp_name"], $destPath)) {
        $ruta_adjunto = $destPath;
        $ruta_bd = "comprobantes/" . $fileName;
    }
}

// --- 5. GUARDAR EN BD PRIMERO (ESTADO 'PENDIENTE') ---
// Modificamos el INSERT para incluir el estado 'Pendiente'
$sql = "INSERT INTO pedidos (nombre, telefono, correo, direccion, categoria, plato, cantidad, precio_unitario, subtotal, fecha, imagen_pago, estado)
        VALUES ('$nombre', '$telefono', '$correo', '$direccion', '$categoria', '$plato', $cantidad, $precio, $subtotal, '$fecha', '$ruta_bd', 'Pendiente')";

if ($conn->query($sql) === TRUE) {
    
    // Obtenemos el ID del pedido recién creado para ponerlo en el botón
    $pedido_id = $conn->insert_id;

    // Guardar Ganancia (Se mantiene igual)
    $conn->query("INSERT INTO ganancias (pedido_id, monto, fecha) VALUES ($pedido_id, $subtotal, NOW())");


    // --- 6. PREPARAR Y ENVIAR CORREO CON BOTÓN ---
    
    // Generar el enlace de confirmación
    // ⚠️ IMPORTANTE: Asegúrate de que esta ruta coincida con tu carpeta real
    $linkConfirmar = "http://localhost/liberatos/backend/Clases/confirmar.php?id=$pedido_id";

    $mail = new PHPMailer(true);

    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host       = $mailConfig['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailConfig['smtp_user'];
        $mail->Password   = $mailConfig['smtp_pass'];
        $mail->SMTPSecure = $mailConfig['smtp_secure'];
        $mail->Port       = $mailConfig['smtp_port'];

        // Destinatarios
        $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
        $mail->addAddress($mailConfig['to_email']); 

        // Contenido HTML con Botón
        $mail->isHTML(true);
        $mail->Subject = "Verificar Pago - Pedido #$pedido_id - $nombre";
        
        $mensajeHTML = "
        <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd; max-width: 600px;'>
            <h2 style='color: #ff7f11;'>Nuevo Pedido #$pedido_id (Pendiente)</h2>
            <p><b>Cliente:</b> $nombre</p>
            <p><b>Teléfono:</b> $telefono</p>
            <p><b>Plato:</b> $plato (x$cantidad)</p>
            <p><b>Total a Pagar:</b> S/ $subtotal</p>
            <hr>
            <p>Revisa la imagen adjunta. Si el pago es correcto, haz clic abajo:</p>
            
            <a href='$linkConfirmar' 
               style='background-color: #2ecc71; color: white; padding: 15px 25px; text-decoration: none; font-weight: bold; border-radius: 5px; display: inline-block; margin-top: 10px;'>
               ✅ CONFIRMAR Y ACEPTAR PEDIDO
            </a>
            
            <p style='color: #888; font-size: 12px; margin-top: 20px;'>Si la imagen es falsa, ignora este correo.</p>
        </div>
        ";
        
        $mail->Body = $mensajeHTML;

        // Adjuntar imagen si existe
        if ($ruta_adjunto) {
            $mail->addAttachment($ruta_adjunto);
        }

        $mail->send();

        // Éxito Total: BD guardada + Correo enviado
        echo json_encode([
            "success" => true, 
            "message" => "Pedido registrado. Revisa tu correo para confirmarlo.",
            "pedido_id" => $pedido_id
        ]);

    } catch (Exception $e) {
        // El pedido SE GUARDÓ en la BD, pero falló el correo.
        // Respondemos success=true para que la web no de error, pero avisamos en el mensaje.
        echo json_encode([
            "success" => true, 
            "message" => "Pedido guardado, pero hubo un error enviando el correo de aviso."
        ]);
    }

} else {
    // Falló la Base de Datos
    echo json_encode(["success" => false, "message" => "Error BD: " . $conn->error]);
}

$conn->close();
?>