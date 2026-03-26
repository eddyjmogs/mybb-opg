<?php
// op/db_extra.php
$DB_EXTRA_ERROR = null;
$DB_EXTRA_STATUS = '❌ No conectado';
$DB_EXTRA_TS = '';

$host = 'bdopg.iceiy.com';   // <-- tu host externo
$user = 'bdopg_Admin';       // <-- tu usuario
$pass = 'OPG4dmin123';       // <-- tu contraseña
$name = 'bdopg_OPG';         // <-- tu base
$port = 3306;

// --- Diagnóstico de reachability (opcional, no imprime nada) ---
$resolved = @gethostbyname($host);  // solo para logs si quieres
$errno = $errstr = null;
$t0 = microtime(true);
$sock = @fsockopen($host, $port, $errno, $errstr, 4);
$ms = number_format((microtime(true)-$t0)*1000,1);
if($sock){ fclose($sock); } // si quisieras, podrías guardar este dato en logs

// --- Conexión sin SSL ---
mysqli_report(MYSQLI_REPORT_OFF);
$mysqli = mysqli_init();
mysqli_options($mysqli, MYSQLI_OPT_CONNECT_TIMEOUT, 5);

if (!@mysqli_real_connect($mysqli, $host, $user, $pass, $name, $port)) {
    // Falló sin SSL → probamos con SSL
    $err_no_ssl = mysqli_connect_errno().': '.mysqli_connect_error();

    $mysqli = mysqli_init();
    mysqli_options($mysqli, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    @mysqli_ssl_set($mysqli, NULL, NULL, NULL, NULL, NULL);
    if (!@mysqli_real_connect($mysqli, $host, $user, $pass, $name, $port, NULL, MYSQLI_CLIENT_SSL)) {
        $err_ssl = mysqli_connect_errno().': '.mysqli_connect_error();
        $DB_EXTRA_ERROR = "Reach test: ".($errstr ? "ERROR ($errno) $errstr {$ms}ms" : "OK {$ms}ms")." | No-SSL: {$err_no_ssl} | SSL: {$err_ssl}";
        return null;
    }
}

// Éxito (sin SSL o con SSL)
$mysqli->set_charset('utf8mb4');
return $mysqli;
