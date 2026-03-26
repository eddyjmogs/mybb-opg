<?php
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'nefta5.php');

require_once __DIR__ . '/../global.php';
require_once "./functions/op_functions.php";

global $templates, $mybb, $db;

if ($mybb->request_method === 'post' && $mybb->get_input('action') === 'alter_add_reservas') {
    header('Content-Type: application/json; charset=utf-8');

    // // CSRF
    // verify_post_check($mybb->get_input('my_post_key'));

    // $table = 'mybb_op_fichas';

    // $db->query("ALTER TABLE `mybb_Objetos_Inframundo`
    //     MODIFY COLUMN `estado` 
    //     ENUM('activa','vendida','cancelada','finalizada_sin_venta')
    //     NOT NULL DEFAULT 'activa';
    //     ");

    // $q = $db->query("SHOW CREATE TABLE mybb_Objetos_Inframundo");
    // $row = $db->fetch_array($q);

    // echo "<script>console.log(" . json_encode($row['Create Table']) . ");</script>";


    // // Ejecuta ALTER TABLE
    // $sql = "
    //     ALTER TABLE `{$table}`
    //     ADD COLUMN `wantedGuardado` VARCHAR(255) NOT NULL DEFAULT ''
    //     AFTER `cronologia`
    // ";

    // $db->query(" DELETE FROM mybb_op_crafteo_usuarios WHERE id = 23; ");
    // $db->query(" INSERT INTO `mybb_op_crafteo_usuarios` (`uid`, `objeto_id`, `nombre`, `material_id`, `timestamp_end`, `duracion`, `costo`) 
    // VALUES 
    // ('23','LP002', 'Log Pose T2','','1757284324','129600','4000000'); ");

    // $db->query("
    // ALTER TABLE `mybb_op_fichas`
    // ADD COLUMN `movidoInframundo` BIGINT NOT NULL DEFAULT 0
    // ");

    // $dbname = $db->database;
    // $col = "movidoInframundo";


    // $query = $db->query("
    //     SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
    //     FROM INFORMATION_SCHEMA.COLUMNS
    //     WHERE TABLE_SCHEMA = DATABASE()
    //     AND TABLE_NAME = 'mybb_op_fichas'
    //     AND COLUMN_NAME = '{$col}'
    // ");

    // $colinfo = $db->fetch_array($query);

    // echo "<script>console.log('Columna {$col} en mybb_op_fichas: ', " . json_encode($colinfo, JSON_PRETTY_PRINT) . ");</script>";

    // $db->query(" DELETE FROM mybb_op_recompensas_usuarios WHERE uid = 23; ");
    // $db->query(" INSERT INTO mybb_op_recompensas_usuarios (`uid`, `nombre`, `dia`, `season`, `tiempo`) VALUES ('23', 'Juuken', '33', '33', '1758738868'); ");

    // $db->query(" DELETE FROM mybb_op_audit_recompensas WHERE uid = 23; ");

    // $db->query(" INSERT INTO `mybb_op_audit_recompensas` (`tiempo_completado`, `tiempo_nuevo`, `dia`, `season`, `uid`, `nombre`, `audit`) VALUES 
    // ('1758479008','1758479009','33','33', '23', 'Juuken', 'Este usuario no se fija al tocar botones'); ");

//     $db->query("CREATE TABLE `mybb_op_isla_eventos` (
//   `evento_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
//   `isla_id` INT UNSIGNED NOT NULL,
//   `dia` INT UNSIGNED NOT NULL,           -- 1..31 (ajusta si tu calendario es distinto)
//   `estacion` ENUM('Primavera','Verano','Otoño','Invierno') NOT NULL,
//   `ano` INT NOT NULL,                        -- año del mundo (no del calendario real)
//   `descripcion` TEXT NOT NULL,                -- texto libre del evento
//   `staff_uid` INT UNSIGNED NOT NULL,          -- autor (uid de mybb_users)
//   `created_at` INT UNSIGNED NOT NULL,         -- timestamp UNIX de creación
//   PRIMARY KEY (`evento_id`),
//   KEY `idx_isla` (`isla_id`),
//   KEY `idx_isla_fecha` (`isla_id`,`anio`,`estacion`,`dia`),
//   CONSTRAINT `fk_evento_isla` FOREIGN KEY (`isla_id`) REFERENCES `mybb_op_islas` (`isla_id`) ON DELETE CASCADE,
//   CONSTRAINT `fk_evento_staff` FOREIGN KEY (`staff_uid`) REFERENCES `mybb_users` (`uid`) ON DELETE RESTRICT
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Parámetros
$objeto_id   = 'CFR003';
$cantidad    = 1;   // dar 1P cofres a cada usuario sin ninguno
$imagen      = '';
$apodo       = '';
$autor       = 'Sistema';
$autor_uid   = '0';
$oficios     = 'null';
$especial    = 0;
$editado     = 0;
$usado       = 0;

// Sanitizar (opcional, ya que no se interpolan variables en SQL dinámico aquí)
$OBJ   = $db->escape_string($objeto_id);
$IMG   = $db->escape_string($imagen);
$APO   = $db->escape_string($apodo);
$AUT   = $db->escape_string($autor);
$AUTU  = $db->escape_string($autor_uid);
$OFI   = $db->escape_string($oficios);

// Query principal: insertar cofres a usuarios sin CFR004
$sql = "
INSERT INTO mybb_op_inventario (
    objeto_id,
    uid,
    cantidad,
    imagen,
    apodo,
    autor,
    autor_uid,
    oficios,
    especial,
    editado,
    usado
)
SELECT
    '{$OBJ}' AS objeto_id,
    f.fid    AS uid,
    {$cantidad} AS cantidad,
    '{$IMG}' AS imagen,
    '{$APO}' AS apodo,
    '{$AUT}' AS autor,
    '{$AUTU}' AS autor_uid,
    '{$OFI}' AS oficios,
    {$especial} AS especial,
    {$editado} AS editado,
    {$usado} AS usado
FROM mybb_op_fichas f
LEFT JOIN mybb_op_inventario i
    ON i.uid = f.fid
   AND i.objeto_id = '{$OBJ}'
WHERE i.id IS NULL
GROUP BY f.fid;
";

// Ejecutar
$db->write_query($sql);

// Mostrar resultado simple
echo "✅ Se añadio un cofre ({$OBJ}) a los usuarios que no tenían ninguno.";

// $db->query("DELETE FROM mybb_op_entrenamientos_usuarios WHERE uid='338'");
// $db->query("DELETE FROM mybb_op_oficios_usuarios WHERE uid='338'");

// $objeto_id = $db->escape_string('CFR004');

// // (Opcional) Eliminar filas con cantidad 0
// $db->delete_query('op_inventario', "objeto_id = '{$objeto_id}' AND cantidad = 0");
// Salida
//echo \"<pre>\";
//echo \"Cofres entregados (CFR004) a usuarios sin cofre previo: {$afectados}\\n\";
//echo \"</pre>\";

    // $sql = "
    //     DELETE FROM mybb_op_crafteo_usuarios
    //     WHERE id = 23;

    //     INSERT INTO mybb_op_crafteo_usuarios (
    //         uid,
    //         objeto_id,
    //         nombre,
    //         material_id,
    //         timestamp_end,
    //         duracion,
    //         costo
    //     ) VALUES (
    //         1,
    //         'OBJ-999',
    //         'Espada mágica',
    //         'MAT-777',
    //         1757284324,
    //         7200,
    //         3000
    //     );
    // ";

    // $sql = "
    //     ALTER TABLE `{$table}`
    //     ADD COLUMN `precio_actual` INT NOT NULL
    //     AFTER `fecha_creacion`
    // ";

    // if (!$db->table_exists('mybb_Objetos_Inframundo')) {
    //     $db->write_query("
    //         CREATE TABLE mybb_Objetos_Inframundo (
    //             id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    //             objeto_id VARCHAR(255) NOT NULL,
    //             vendedor_uid INT NOT NULL,
    //             ultimo_ofertante_uid INT DEFAULT NULL,
    //             comprador_uid INT DEFAULT NULL,
    //             precio_minimo INT NOT NULL,
    //             precio_compra INT DEFAULT NULL,
    //             fecha_final_subasta TIMESTAMP NOT NULL,
    //             estado ENUM('activa', 'vendida', 'cancelada') NOT NULL DEFAULT 'activa',
    //             notas VARCHAR(255) DEFAULT NULL,
    //             cantidad INT NOT NULL DEFAULT 0,
    //             fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    //         ) ENGINE=InnoDB
    //           DEFAULT CHARSET=utf8mb4
    //           COLLATE=utf8mb4_general_ci
    //     ");
    // }

    try {
        $db->query($sql);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

    $check = $db->query("SHOW COLUMNS FROM `{$table}` LIKE 'precio_actual'");
    if ($db->num_rows($check) > 0) {
        echo json_encode(['success' => false, 'error' => 'La columna ya existe']);
        exit;
    }

    exit;
}

// Prepara var de error por si el require la setea
$DB_EXTRA_ERROR = '';

// Cargar conexión extra (devuelve mysqli o null)
$mysqli = require __DIR__ . '/db_extra.php';

$hora_extra = '';
$db_extra_status = '❌ Sin conexión';

if ($mysqli instanceof mysqli) {
    $db_extra_status = '✅ Conectado';
    if ($res = $mysqli->query("SELECT NOW() AS ts")) {
        $row = $res->fetch_assoc();
        $hora_extra = (string)($row['ts'] ?? '');
        $res->free();
    }
    // $mysqli->close(); // si no la reutilizas
} elseif (!empty($DB_EXTRA_ERROR)) {
    $db_extra_status = '❌ '.$DB_EXTRA_ERROR;
}

// Sanea y pasa a la plantilla
$hora_extra_safe      = htmlspecialchars($hora_extra);
$db_extra_status_safe = htmlspecialchars($db_extra_status);
$db_extra_error_safe  = htmlspecialchars((string)$DB_EXTRA_ERROR);

eval('$hora_extra_tpl = "'.$hora_extra_safe.'";');
eval('$db_extra_status_tpl = "'.$db_extra_status_safe.'";');
eval('$db_extra_error_tpl = "'.$db_extra_error_safe.'";');

eval("\$page = \"".$templates->get("op_nefta5")."\";");
output_page($page);