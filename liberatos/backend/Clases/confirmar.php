<?php
// backend/Clases/confirmar.php

header("Content-Type: text/html; charset=UTF-8");

$host = "localhost"; $db_name = "liberatos"; $username = "root"; $password = "";
$conn = new mysqli($host, $username, $password, $db_name);

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Actualizamos el estado a 'Confirmado'
    $sql = "UPDATE pedidos SET estado = 'Confirmado' WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        // Diseño bonito para cuando des clic
        echo "
        <div style='font-family: Arial, sans-serif; text-align: center; margin-top: 50px;'>
            <h1 style='color: #2ecc71; font-size: 50px;'>✅</h1>
            <h2 style='color: #333;'>¡Pedido #$id Confirmado!</h2>
            <p>El pedido ha sido validado y guardado como venta oficial.</p>
            <a href='http://localhost/liberatos/' style='text-decoration: none; background: #333; color: #fff; padding: 10px 20px; border-radius: 5px;'>Volver a la Web</a>
        </div>";
    } else {
        echo "Error al confirmar: " . $conn->error;
    }
} else {
    echo "No se especificó ningún pedido.";
}
$conn->close();
?>