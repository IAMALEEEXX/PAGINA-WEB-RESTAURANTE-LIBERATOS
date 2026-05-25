<?php
// backend/config/mail_config.php

return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    
    // -----------------------------------------------------
    // 1. CREDENCIALES DE ENVÍO (Tu Gmail Personal)
    // -----------------------------------------------------
    // Es quien "presta" su cuenta para enviar el correo.
    'smtp_user' => 'jqalex07@gmail.com', 
    
    // Aquí va la CONTRASEÑA DE APLICACIÓN de 16 letras de ese Gmail
    'smtp_pass' => 'tfnb usek kzvw hhjw', 

    // -----------------------------------------------------
    // 2. ¿QUIÉN APARECE COMO REMITENTE?
    // -----------------------------------------------------
    // Gmail suele obligar a que esto sea igual al smtp_user.
    'from_email' => 'jqalex07@gmail.com', 
    'from_name' => 'Sistema de Pedidos Web',

    // -----------------------------------------------------
    // 3. ¿A QUIÉN LE LLEGA EL PEDIDO? (Tu correo UCV)
    // -----------------------------------------------------
    // Aquí es donde tú leerás los pedidos.
    'to_email' => 'jejeriqu@ucvvirtual.edu.pe' 
];
?>