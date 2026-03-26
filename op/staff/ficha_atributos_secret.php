<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 * Gestión de Fichas Secretas - Staff Panel
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'ficha_atributos_secret.php');
require_once "./../../global.php";
require "./../functions/op_functions.php";

global $templates, $mybb, $db;

// Verificar permisos de staff
if (!is_staff($mybb->user['uid'])) {
    error_no_permission();
}

$query_uid = $mybb->get_input('fid');
$user_uid = $mybb->user['uid'];

if (!$query_uid) {
    error("No se ha especificado un usuario válido.");
}

// Variables para los campos de la ficha secreta
$cambiar_avatar1S1 = addslashes($_POST["cambiar_avatar1S1"]);
$cambiar_avatar2S1 = addslashes($_POST["cambiar_avatar2S1"]);
$cambiar_aparienciaS1 = addslashes($_POST["cambiar_aparienciaS1"]);
$cambiar_personalidadS1 = addslashes($_POST["cambiar_personalidadS1"]);
$cambiar_historiaS1 = addslashes($_POST["cambiar_historiaS1"]);
$cambiar_extrasS1 = addslashes($_POST["cambiar_extrasS1"]);
$cambiar_apodoS1 = addslashes($_POST["cambiar_apodoS1"]);
$cambiar_nombreS1 = addslashes($_POST["cambiar_nombreS1"]);
$cambiar_rangoS1 = addslashes($_POST["cambiar_rangoS1"]);
$cambiar_faccionS1 = addslashes($_POST["cambiar_faccionS1"]);
$cambiar_visibleS1 = addslashes($_POST["cambiar_visibleS1"]);

// Procesar actualizaciones de la ficha secreta
if ($cambiar_avatar1S1 != '') {
    $db->query("UPDATE `mybb_op_fichas_secret` SET `avatar1`='$cambiar_avatar1S1' WHERE `fid`='$query_uid'");
}

if ($cambiar_avatar2S1 != '') {
    $db->query("UPDATE `mybb_op_fichas_secret` SET `avatar2`='$cambiar_avatar2S1' WHERE `fid`='$query_uid'");
}

if ($cambiar_aparienciaS1 != '') {
    $db->query("UPDATE `mybb_op_fichas_secret` SET `apariencia`='$cambiar_aparienciaS1' WHERE `fid`='$query_uid'");
}

if ($cambiar_personalidadS1 != '') {
    $db->query("UPDATE `mybb_op_fichas_secret` SET `personalidad`='$cambiar_personalidadS1' WHERE `fid`='$query_uid'");
}

if ($cambiar_historiaS1 != '') {
    $db->query("UPDATE `mybb_op_fichas_secret` SET `historia`='$cambiar_historiaS1' WHERE `fid`='$query_uid'");
}

if ($cambiar_extrasS1 != '') {
    $db->query("UPDATE `mybb_op_fichas_secret` SET `extra`='$cambiar_extrasS1' WHERE `fid`='$query_uid'");
}

if ($cambiar_apodoS1 != '') {
    $db->query("UPDATE `mybb_op_fichas_secret` SET `apodo`='$cambiar_apodoS1' WHERE `fid`='$query_uid'");
}

if ($cambiar_nombreS1 != '') {
    $db->query("UPDATE `mybb_op_fichas_secret` SET `nombre`='$cambiar_nombreS1' WHERE `fid`='$query_uid'");
}

if ($cambiar_rangoS1 != '') {
    $db->query("UPDATE `mybb_op_fichas_secret` SET `rango`='$cambiar_rangoS1' WHERE `fid`='$query_uid'");
}

if ($cambiar_faccionS1 != '') {
    $db->query("UPDATE `mybb_op_fichas_secret` SET `faccion`='$cambiar_faccionS1' WHERE `fid`='$query_uid'");
}

if ($cambiar_visibleS1 != '') {
    $db->query("UPDATE `mybb_op_fichas_secret` SET `es_visible`='$cambiar_visibleS1' WHERE `fid`='$query_uid'");
}

// Obtener datos del usuario
$query_usuario = $db->query("SELECT * FROM mybb_users WHERE uid='$query_uid'");
$usuario = null;
while ($u = $db->fetch_array($query_usuario)) {
    $usuario = $u;
}

if (!$usuario) {
    error("Usuario no encontrado.");
}

// Verificar si existe ficha secreta, si no crearla
$query_ficha_secret = $db->query("SELECT * FROM mybb_op_fichas_secret WHERE fid='$query_uid'");
$ficha_secret = null;
$ficha_exists = false;

while ($f = $db->fetch_array($query_ficha_secret)) {
    $ficha_secret = $f;
    $ficha_exists = true;
}

// Si no existe la ficha secreta, crear una nueva
if (!$ficha_exists) {
    $db->query("INSERT INTO `mybb_op_fichas_secret` (`fid`, `nombre`, `apodo`, `avatar1`, `avatar2`, `apariencia`, `personalidad`, `historia`, `extra`, `faccion`, `rango`, `es_visible`) 
                VALUES ('$query_uid', '', '', '', '', '', '', '', '', 'Civil', 'civil', '0')");
    
    // Recargar datos
    $query_ficha_secret = $db->query("SELECT * FROM mybb_op_fichas_secret WHERE fid='$query_uid'");
    while ($f = $db->fetch_array($query_ficha_secret)) {
        $ficha_secret = $f;
    }
}

// Obtener lista de facciones disponibles
$facciones = [
    'Civil' => 'Civil',
    'Marine' => 'Marine', 
    'Pirata' => 'Pirata',
    'Revolucionario' => 'Revolucionario',
    'Gobierno Mundial' => 'Gobierno Mundial'
];

// Obtener lista de rangos disponibles
$rangos = [
    'civil' => 'Civil',
    'marine_recluta' => 'Marine Recluta',
    'marine_soldado' => 'Marine Soldado',
    'marine_cabo' => 'Marine Cabo',
    'marine_sargento' => 'Marine Sargento',
    'marine_teniente' => 'Marine Teniente',
    'marine_capitan' => 'Marine Capitán',
    'marine_comodoro' => 'Marine Comodoro',
    'marine_contraalmirante' => 'Marine Contraalmirante',
    'marine_vicealmirante' => 'Marine Vicealmirante',
    'marine_almirante' => 'Marine Almirante',
    'pirata_novato' => 'Pirata Novato',
    'pirata_experimentado' => 'Pirata Experimentado',
    'pirata_veterano' => 'Pirata Veterano',
    'pirata_capitan' => 'Pirata Capitán',
    'pirata_supernova' => 'Pirata Supernova',
    'shichibukai' => 'Shichibukai',
    'yonko' => 'Yonko'
];

// Preparar variables para la plantilla
$username = $usuario['username'];
$ficha_secret_json = json_encode($ficha_secret);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Gestión Ficha Secreta - <?php echo $username; ?></title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background: #2c3e50; color: white; padding: 15px; margin: -20px -20px 20px -20px; border-radius: 8px 8px 0 0; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 6px; }
        .section h3 { margin-top: 0; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input, .form-group textarea, .form-group select { 
            width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; 
        }
        .form-group textarea { height: 100px; resize: vertical; }
        .btn { 
            background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 4px; 
            cursor: pointer; font-size: 14px; margin-right: 10px; 
        }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .current-value { 
            background: #ecf0f1; padding: 10px; border-radius: 4px; margin-bottom: 10px; 
            border-left: 4px solid #3498db; 
        }
        .avatar-preview { max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #3498db; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gestión de Ficha Secreta</h1>
            <p>Usuario: <strong><?php echo $username; ?></strong> (UID: <?php echo $query_uid; ?>)</p>
        </div>

        <a href="../ficha_atributos.php?fid=<?php echo $query_uid; ?>" class="back-link">← Volver a Ficha Principal</a>

        <form method="POST">
            
            <!-- Información Básica -->
            <div class="section">
                <h3>Información Básica</h3>
                
                <div class="form-group">
                    <label for="cambiar_nombreS1">Nombre Secreto:</label>
                    <div class="current-value">Actual: <?php echo htmlspecialchars($ficha_secret['nombre']); ?></div>
                    <input type="text" name="cambiar_nombreS1" id="cambiar_nombreS1" placeholder="Nuevo nombre secreto">
                </div>

                <div class="form-group">
                    <label for="cambiar_apodoS1">Apodo Secreto:</label>
                    <div class="current-value">Actual: <?php echo htmlspecialchars($ficha_secret['apodo']); ?></div>
                    <input type="text" name="cambiar_apodoS1" id="cambiar_apodoS1" placeholder="Nuevo apodo secreto">
                </div>

                <div class="form-group">
                    <label for="cambiar_faccionS1">Facción:</label>
                    <div class="current-value">Actual: <?php echo htmlspecialchars($ficha_secret['faccion']); ?></div>
                        <select name="cambiar_faccionS1" id="faccion-secreta">
                            <option value="Civil" <if $ficha_secret['faccion'] == 'Civil' then>selected="selected"</if>>Civil</option>
                            <option value="Pirata" <if $ficha_secret['faccion'] == 'Pirata' then>selected="selected"</if>>Pirata</option>
                            <option value="Marine" <if $ficha_secret['faccion'] == 'Marine' then>selected="selected"</if>>Marine</option>
                            <option value="Gobierno Mundial" <if $ficha_secret['faccion'] == 'Gobierno Mundial' then>selected="selected"</if>>Gobierno Mundial</option>
                            <option value="Cazador" <if $ficha_secret['faccion'] == 'Cazador' then>selected="selected"</if>>Cazador</option>
                            <option value="Revolucionario" <if $ficha_secret['faccion'] == 'Revolucionario' then>selected="selected"</if>>Revolucionario</option>
                        </select>
                </div>

                <div class="form-group">
                    <label for="cambiar_rangoS1">Rango:</label>
                    <div class="current-value">Actual: <?php echo htmlspecialchars($ficha_secret['rango']); ?></div>
                    <select name="cambiar_rangoS1" id="cambiar_rangoS1">
                        <option value="">-- Seleccionar nuevo rango --</option>
                        <?php foreach ($rangos as $key => $value): ?>
                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="cambiar_visibleS1">Visibilidad:</label>
                    <div class="current-value">Actual: <?php echo $ficha_secret['es_visible'] == '1' ? 'Visible' : 'Oculto'; ?></div>
                    <select name="cambiar_visibleS1" id="cambiar_visibleS1">
                        <option value="">-- Sin cambios --</option>
                        <option value="1">Visible</option>
                        <option value="0">Oculto</option>
                    </select>
                </div>
            </div>

            <!-- Avatares -->
            <div class="section">
                <h3>Avatares</h3>
                
                <div class="form-group">
                    <label for="cambiar_avatar1S1">Avatar 1 (Principal):</label>
                    <div class="current-value">
                        Actual: <?php echo htmlspecialchars($ficha_secret['avatar1']); ?>
                        <?php if ($ficha_secret['avatar1']): ?>
                            <br><img src="<?php echo htmlspecialchars($ficha_secret['avatar1']); ?>" class="avatar-preview" alt="Avatar 1">
                        <?php endif; ?>
                    </div>
                    <input type="url" name="cambiar_avatar1S1" id="cambiar_avatar1S1" placeholder="URL del nuevo avatar 1">
                </div>

                <div class="form-group">
                    <label for="cambiar_avatar2S1">Avatar 2 (Secundario):</label>
                    <div class="current-value">
                        Actual: <?php echo htmlspecialchars($ficha_secret['avatar2']); ?>
                        <?php if ($ficha_secret['avatar2']): ?>
                            <br><img src="<?php echo htmlspecialchars($ficha_secret['avatar2']); ?>" class="avatar-preview" alt="Avatar 2">
                        <?php endif; ?>
                    </div>
                    <input type="url" name="cambiar_avatar2S1" id="cambiar_avatar2S1" placeholder="URL del nuevo avatar 2">
                </div>
            </div>

            <!-- Descripción del Personaje -->
            <div class="section">
                <h3>Descripción del Personaje</h3>
                
                <div class="form-group">
                    <label for="cambiar_aparienciaS1">Apariencia:</label>
                    <div class="current-value">Actual: <?php echo nl2br(htmlspecialchars($ficha_secret['apariencia'])); ?></div>
                    <textarea name="cambiar_aparienciaS1" id="cambiar_aparienciaS1" placeholder="Nueva apariencia"></textarea>
                </div>

                <div class="form-group">
                    <label for="cambiar_personalidadS1">Personalidad:</label>
                    <div class="current-value">Actual: <?php echo nl2br(htmlspecialchars($ficha_secret['personalidad'])); ?></div>
                    <textarea name="cambiar_personalidadS1" id="cambiar_personalidadS1" placeholder="Nueva personalidad"></textarea>
                </div>

                <div class="form-group">
                    <label for="cambiar_historiaS1">Historia:</label>
                    <div class="current-value">Actual: <?php echo nl2br(htmlspecialchars($ficha_secret['historia'])); ?></div>
                    <textarea name="cambiar_historiaS1" id="cambiar_historiaS1" placeholder="Nueva historia" style="height: 150px;"></textarea>
                </div>

                <div class="form-group">
                    <label for="cambiar_extrasS1">Información Extra:</label>
                    <div class="current-value">Actual: <?php echo nl2br(htmlspecialchars($ficha_secret['extra'])); ?></div>
                    <textarea name="cambiar_extrasS1" id="cambiar_extrasS1" placeholder="Nueva información extra"></textarea>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="section">
                <button type="submit" class="btn">Guardar Cambios</button>
                <a href="../ficha_atributos.php?fid=<?php echo $query_uid; ?>" class="btn" style="text-decoration: none; display: inline-block; background: #95a5a6;">Cancelar</a>
            </div>

        </form>
    </div>

    <script>
    // Confirmación antes de enviar el formulario
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!confirm('¿Estás seguro de que quieres realizar estos cambios en la ficha secreta?')) {
            e.preventDefault();
        }
    });
    </script>
</body>
</html>
