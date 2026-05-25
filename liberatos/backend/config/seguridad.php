<?php
session_start();

// Verifica si el usuario está logueado
if (!isset($_SESSION["usuario_id"]) || !isset($_SESSION["rol"])) {
    header("Location: login.php");
    exit;
}

// Función para restringir por rol
function verificarAcceso($rolesPermitidos) {
    $rolUsuario = strtolower($_SESSION["rol"]);

    if (!in_array($rolUsuario, array_map('strtolower', $rolesPermitidos))) {
        // Si el rol no está permitido, redirige con error
        header("Location: acceso_denegado.php");
        exit;
    }
}
?>
