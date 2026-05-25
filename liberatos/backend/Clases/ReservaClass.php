<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

class ReservaClass {

    private $conn;

    const HORARIO_APERTURA = 9;   // 09:00
    const HORARIO_CIERRE   = 23;  // 23:00

    public function __construct($db) {
        $this->conn = $db;
    }

    /* ------------------------------------------
       NORMALIZAR TEXTO
    ------------------------------------------- */
    private function normalizar($t){
        $t = mb_strtolower(trim($t),'UTF-8');
        $t = str_replace(['á','é','í','ó','ú','ñ'], ['a','e','i','o','u','n'], $t);
        return preg_replace('/\s+/', ' ', $t);
    }

    /* ------------------------------------------
       PARSE FECHA NATURAL
    ------------------------------------------- */
    public function parseFecha($texto) {
        $texto = $this->normalizar($texto);
        $hoy = new DateTime("now", new DateTimeZone("America/Lima"));

        if (strpos($texto, "hoy") !== false) return $hoy->format("Y-m-d");

        if (strpos($texto, "mañana") !== false || strpos($texto, "mnn") !== false) {
            $hoy->modify("+1 day");
            return $hoy->format("Y-m-d");
        }

        if (strpos($texto, "pasado") !== false) {
            $hoy->modify("+2 day");
            return $hoy->format("Y-m-d");
        }

        if (preg_match("/en (\d+) dias/", $texto, $m)) {
            $hoy->modify("+{$m[1]} day");
            return $hoy->format("Y-m-d");
        }

        if (preg_match("/(\d{4})-(\d{2})-(\d{2})/", $texto, $m))
            return $m[0];

        if (preg_match("/(\d{2})\/(\d{2})\/(\d{4})/", $texto, $m))
            return "{$m[3]}-{$m[2]}-{$m[1]}";

        return null;
    }

    /* ------------------------------------------
       PARSE HORA NATURAL
    ------------------------------------------- */
    public function parseHora($texto) {
        $texto = $this->normalizar($texto);
        $now = new DateTime("now", new DateTimeZone("America/Lima"));

        if (preg_match("/en (\d+) horas?/", $texto, $m)) {
            $now->modify("+{$m[1]} hour");
            return $now->format("H:i");
        }

        if (preg_match("/en (\d+) minutos?/", $texto, $m)) {
            $now->modify("+{$m[1]} minutes");
            return $now->format("H:i");
        }

        if (preg_match("/(\d{1,2}):(\d{2})/", $texto, $m)) {
            return sprintf("%02d:%02d", $m[1], $m[2]);
        }

        if (preg_match("/(\d{1,2})\s?(am|pm)?/", $texto, $m)) {
            $h = intval($m[1]);
            $minutos = "00";

            if (isset($m[2])) {
                if ($m[2] === "pm" && $h < 12) $h += 12;
                if ($m[2] === "am" && $h == 12) $h = 0;
            }

            return sprintf("%02d:%02d", $h, $minutos);
        }

        return null;
    }

    /* ------------------------------------------
       VALIDAR HORARIO DEL LOCAL
    ------------------------------------------- */
    public function validarHorario($hora) {
        list($h, $m) = explode(":", $hora);
        return ($h >= self::HORARIO_APERTURA && $h <= self::HORARIO_CIERRE);
    }

    /* ------------------------------------------
       GUARDAR RESERVA (FUNCIONAL PARA BOT Y WEB)
    ------------------------------------------- */
    public function guardarReserva($nombre, $correo, $personas, $fecha, $mensaje) {

        // Asegurar formato correcto de fecha final
        $fecha_final = date("Y-m-d H:i:s", strtotime($fecha));

        $sql = "INSERT INTO reservas (nombre, correo, personas, fecha, mensaje, fecha_registro, estado)
                VALUES (:nombre, :correo, :personas, :fecha, :mensaje, NOW(), 'pendiente')";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':personas', $personas);
        $stmt->bindParam(':fecha', $fecha_final);
        $stmt->bindParam(':mensaje', $mensaje);

        return $stmt->execute();
    }
}
?>
