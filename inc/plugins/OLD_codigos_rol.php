<?php



if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("parse_message", "codigos_rol_run");
$plugins->add_hook("parse_message_end", "codigos_rol_run");
$plugins->add_hook("postbit", "codigos_rol_postbit_secret");
$plugins->add_hook('datahandler_post_insert_post_end', 'codigos_rol_newpost');
$plugins->add_hook('datahandler_post_insert_thread_end', 'codigos_rol_newpost');

function codigos_rol_info()
{
global $mybb;
	return array(
		"name"				=> "Códigos de Rol",
		"description"		=> "Códigos para Foros de Rol.",
		"website"			=> "",
		"author"			=> "Kurosame",
		"authorsite"		=> "https://shinobigaiden.net",
		"version"			=> "1.0.0",
		"codename"			=> "codigosrol",
		"compatibility"		=> "*",
	);
}


function codigos_rol_activate()
{
	global $db, $mybb;
	$query = $db->simple_select('themes', 'tid');
	while($theme = $db->fetch_array($query))
	{
		$estilo = array(
				'name'         => 'spoiler.css',
				'tid'          => $theme['tid'],
				'attachedto'   => 'showthread.php|newthread.php|newreply.php|editpost.php|private.php|announcements.php',
				'stylesheet'   => '.spoiler {background: #f5f5f5;border: 1px solid #bbb;margin-bottom: 5px;border-radius: 5px}
.spoiler_button {background-color: #bab7b7;border-radius: 4px 4px 0 0;border: 1px solid #c2bfbf;display: block;color: #605d5d;font-family: Tahoma;font-size: 11px;font-weight: bold;padding: 10px;text-align: center;text-shadow: 1px 1px 0px #b4b3b3;margin: auto auto;cursor: pointer}
.spoiler_title {text-align: center}
.spoiler_content_title{font-weight: bold;border-bottom:1px dashed #bab7b7}
.spoiler_content {padding: 5px;height: auto;overflow:hidden;width:95%;background: #f5f5f5;word-wrap: break-word}',
			'lastmodified' => TIME_NOW
		);
		$sid = $db->insert_query('themestylesheets', $estilo);
		$db->update_query('themestylesheets', array('cachefile' => "css.php?stylesheet={$sid}"), "sid='{$sid}'", 1);
		require_once MYBB_ADMIN_DIR.'inc/functions_themes.php';
		update_theme_stylesheet_list($theme['tid']);
	}
	
	require MYBB_ROOT.'inc/adminfunctions_templates.php';

    find_replace_templatesets("codebuttons", '#'.preg_quote('<script type="text/javascript">
var partialmode = {$mybb->settings[\'partialmode\']},').'#siU', '<script type="text/javascript" src="{$mybb->asset_url}/jscripts/spoiler.js?ver=1804"></script>
<script type="text/javascript">
var partialmode = {$mybb->settings[\'partialmode\']},');	
    find_replace_templatesets("codebuttons", '#'.preg_quote('{$link}').'#', '{$link},spoiler');
}


function codigos_rol_deactivate()
{
	global $db;
	$db->delete_query('themestylesheets', "name='spoiler.css'");
	$query = $db->simple_select('themes', 'tid');
	while($theme = $db->fetch_array($query))
	{
		require_once MYBB_ADMIN_DIR.'inc/functions_themes.php';
		update_theme_stylesheet_list($theme['tid']);
	}
   	require MYBB_ROOT.'inc/adminfunctions_templates.php';
    find_replace_templatesets("codebuttons", '#'.preg_quote('<script type="text/javascript" src="{$mybb->asset_url}/jscripts/spoiler.js?ver=1804"></script>').'#', '',0);
    find_replace_templatesets("codebuttons", '#'.preg_quote(',spoiler').'#', '',0);
}


function codigos_rol_run(&$message)
{
	// DEBUG PRIMERA LÍNEA - ABSOLUTAMENTE VISIBLE
	$message = "<div style=\"background: red; color: yellow; padding: 20px; font-size: 30px; border: 10px solid black; margin: 20px; text-align: center; font-weight: bold;\">🚨 PLUGIN CODIGOS_ROL ACTIVO 🚨<br>ARCHIVO CARGADO CORRECTAMENTE</div>" . $message;
	
	global $db, $post;
	require_once MYBB_ROOT."inc/class_parser.php";
	$parser = new postParser;

	// while(preg_match('#\[cerrado\]#si',$message))
	// {
	// 	$tid = $post['tid'];
	// 	$db->query("
	// 		UPDATE mybb_threads SET `closed`='1' WHERE tid='$tid'
	// 	");
	// 	$message = preg_replace('#\[cerrado\]#si',"
	// 		<div style='text-align: center;border: 3px dotted #2c82c9;color: #2c82c9;width: 400px;margin: auto;'>
	// 			<h1 style='margin: auto;'>Este tema ha sido cerrado.</h1>
	// 		</div>",$message);
	// }

	while(preg_match('#\[tecnica=(.*?)\]#si',$message,$matches))
	{
		$tecnica = null;
		$tec_tid = $matches[1];
		$uid = $post['uid'];

		$query_tecnica = $db->query("
			SELECT * FROM mybb_op_tecnicas WHERE tid='".$tec_tid."'
		");
		
		while ($tec = $db->fetch_array($query_tecnica)) {
			$tecnica = $tec;
		}

		if ($tecnica != null) {

			$query_tec_aprendida = $db->query("
				SELECT * FROM mybb_op_tec_aprendidas WHERE uid='$uid' AND tid='$tec_tid'
			");

			$tecnica_aprendida = '';
			$tecnica_aprendida = 'No Aprendida';

			while ($q = $db->fetch_array($query_tec_aprendida)) {
				$tecnica_aprendida = date('j/n/Y', strtotime($q['tiempo']));
			}

			$codigo = strtoupper($tec_tid);
			$tec_nombre = $tecnica['nombre'];
			$tec_estilo = $tecnica['estilo'];
			$tec_requisitos = $tecnica['requisitos'];
			$tec_clase = strtoupper($tecnica['clase']);
			$tec_tier = $tecnica['tier'];
			$tec_tiempo = $tecnica['tiempo'];

			$tec_energia = $tecnica['energia'];
			$tec_energia_turno = $tecnica['energia_turno'];
			$tec_haki = $tecnica['haki'];
			$tec_haki_turno = $tecnica['haki_turno'];

			$tec_efectos = $tecnica['efectos'];

			$tec_enfriamiento = $tecnica['enfriamiento'];
			$tec_descripcion = nl2br($tecnica['descripcion']);

			$tec_energia_mostrar = '';
			$tec_energia_turno_mostrar = '';
			$tec_haki_mostrar = '';
			$tec_haki_turno_mostrar = '';
			$tec_enfriamiento_mostrar = '';
			$tec_requisitos_mostrar = '';

			if ($tec_energia) {
				$tec_energia_mostrar = "
				<div style=\" display: flex; flex-direction: row; \" title=\"Costo de Energía\">
					<div style=\" font-size: 26px; font-family: lemonMilkMedium; color: #ffa100; -webkit-text-stroke-width: 1px; -webkit-text-stroke-color: black; text-shadow: 1px 1px 2px black; \">$tec_energia</div>
					<img src=\"/images/op/uploads/Energia_One_Piece_Gaiden_Foro_Rol.png\" style=\"height: 27px;margin-top: 5px;\" alt=\"Costo de Energía\" /> 
				</div>";
			}

			if ($tec_energia_turno) {
				$tec_energia_turno_mostrar = "
				<div style=\" display: flex; flex-direction: row; \" title=\"Costo de Energía por Turno\">
					<div style=\" font-size: 26px; font-family: lemonMilkMedium; color: #ffa100; -webkit-text-stroke-width: 1px; -webkit-text-stroke-color: black; text-shadow: 1px 1px 2px black; \">$tec_energia_turno</div>
					<img src=\"/images/op/uploads/EnergiaPorTurno_One_Piece_Gaiden_Foro_Rol.png\" style=\"height: 27px;margin-top: 5px;\" alt=\"Costo de Energía por Turno\" /> 
				</div>";
			}

			if ($tec_haki) {
				$tec_haki_mostrar = "
				<div style=\" display: flex; flex-direction: row; \" title=\"Costo de Haki\">
					<div style=\" font-size: 26px; font-family: lemonMilkMedium; color: #21DEFF; -webkit-text-stroke-width: 1px; -webkit-text-stroke-color: black; text-shadow: 1px 1px 2px black; \">$tec_haki</div>
					<img src=\"/images/op/uploads/PuntosHaki_One_Piece_Gaiden_Foro_Rol.png\" style=\"height: 27px;margin-top: 5px;\" alt=\"Costo de Haki\" /> 
				</div>";
			}

			if ($tec_haki_turno) {
				$tec_haki_turno_mostrar = "
				<div style=\" display: flex; flex-direction: row; \" title=\"Costo de Haki por Turno\">
					<div style=\" font-size: 26px; font-family: lemonMilkMedium; color: #21DEFF; -webkit-text-stroke-width: 1px; -webkit-text-stroke-color: black; text-shadow: 1px 1px 2px black; \">$tec_haki_turno</div>
					<img src=\"/images/op/uploads/PuntosHakiMantenido_One_Piece_Gaiden_Foro_Rol.png\" style=\"height: 27px;margin-top: 5px;\" alt=\"Costo de Haki por Turno\" /> 
				</div>";
			}

			if ($tec_enfriamiento) {
				$tec_enfriamiento_mostrar = "
				<div style=\" display: flex; flex-direction: row; \" title=\"Enfriamiento\">
					<div style=\" font-size: 26px; font-family: lemonMilkMedium; color: #D8EC13; -webkit-text-stroke-width: 1px; -webkit-text-stroke-color: black; text-shadow: 1px 1px 2px black; \">$tec_enfriamiento</div>
					
					<img src=\"/images/op/uploads/CD_One_Piece_Gaiden_Foro_Rol.png\" style=\"height: 30px;margin-top: 4px;\" alt=\"Enfriamiento\" />
				</div>
				
				";
			}

			if ($tec_requisitos) {
				$tec_requisitos_mostrar = "<div class=\"tecnica_requisito\">$tec_requisitos</div>";
			}

			$message = preg_replace('#\[tecnica='.$tec_tid.'\]#si',"
			
			<div class=\"tecnica_spoiler\">
				<div class=\"spoiler_title\">
					<span class=\"tecnica_spoiler_button\" onclick=\"javascript: if(parentNode.parentNode.getElementsByTagName('div')[1].style.height == parentNode.parentNode.querySelector('.tecnica_spoiler_content2').offsetHeight + 'px'){ parentNode.parentNode.getElementsByTagName('div')[1].style.height = '0px'; this.innerHTML='$tec_nombre';parentNode.parentNode.querySelector('.tecnica_spoiler_content').style.overflow = 'hidden';sleep(150).then(() => { parentNode.parentNode.querySelector('.tecnica_spoiler_content').style.overflow = 'hidden'; });} else { parentNode.parentNode.getElementsByTagName('div')[1].style.height = parentNode.parentNode.querySelector('.tecnica_spoiler_content2').offsetHeight + 'px'; this.innerHTML='$tec_nombre'; parentNode.parentNode.querySelector('.tecnica_spoiler_content').style.overflow = 'hidden';sleep(150).then(() => { parentNode.parentNode.querySelector('.tecnica_spoiler_content').style.overflow = 'visible'; });}\">$tec_nombre</span>
				</div>
			
				<div class=\"tecnica_spoiler_content\" style=\"height: 0px;\">
					
					<div class=\"tecnica_spoiler_content2\" style='display: flex;flex-direction: row;'>
						
						<div style=\"display: flex; flex-direction: column;width: 20%; margin-right: 26px;\">
							<div class=\"tecnica_field\">$tec_tid</div>
							<div class=\"tecnica_field\">$tec_clase</div>
							<div class=\"tecnica_field\">$tec_estilo</div>
							<div class=\"tecnica_field\">Tier $tec_tier</div>
							<div class=\"tecnica_field\">$tecnica_aprendida</div>
							
							<div style=\"display: flex;flex-direction: row;justify-content: space-around;margin-top: 7px;width: 160px;\">
								
								$tec_energia_mostrar
								
								$tec_energia_turno_mostrar

								$tec_haki_mostrar
								
								$tec_haki_turno_mostrar
									
								$tec_enfriamiento_mostrar
							</div>
							
						</div>
						
						<div style=\"display: flex; flex-direction: column;width: 80%;\">
							$tec_requisitos_mostrar
							<div class=\"tecnica_descripcion\">$tec_descripcion</div>
							<div class=\"tecnica_efecto\">$tec_efectos</div>
						</div>
						
					</div>
					
				</div>
			</div>
			
			
			",$message);
		} else {
			$message = preg_replace('#\[tecnica='.$tec_tid.'\]#si','[tecnicainvalida='.$matches[1].']',$message);
		}
	}

	// Código [mantenida=TID] - Marcador invisible para tracking de técnicas mantenidas
	while(preg_match('#\[mantenida=([^\]]+)\]#si',$message,$matches))
	{
		$tec_tid = $matches[1];
		// Marcador invisible - solo sirve para detección del sistema de combate
		$message = preg_replace('#\[mantenida='.$tec_tid.'\]#si','<!-- mantenida:'.$tec_tid.' -->',$message);
	}

	// Código [mantenida_fin=TID] - Marcador invisible para técnicas que dejan de mantenerse
	while(preg_match('#\[mantenida_fin=([^\]]+)\]#si',$message,$matches))
	{
		$tec_tid = $matches[1];
		// Marcador invisible - solo sirve para detección del sistema de combate
		$message = preg_replace('#\[mantenida_fin='.$tec_tid.'\]#si','<!-- mantenida_fin:'.$tec_tid.' -->',$message);
	}

	while(preg_match('#\[mytest\]#si',$message))
	{
		$personaje_message = "[spoiler=Mytest]Mytest[/spoiler]";

		$message = preg_replace('#\[mytest\]#si',$personaje_message,$message);

	}

	while(preg_match('#\[leido\]#si',$message))
	{
		global $db, $mybb;
		$leido = false;
		$uid = $post['uid'];
		$tid = $post['tid'];
		$pid = $post['pid'];

		$user_uid = $mybb->user['uid'];

		$query_leido= $db->query(" SELECT * FROM mybb_op_leidos WHERE pid='$pid' AND uid='$user_uid' ");
		while ($q = $db->fetch_array($query_leido)) { $leido = true; }

		if ($leido) {
			$message = preg_replace('#\[leido\]#si',"Esta guia ha sido leída.",$message);
		} else {
			$message = preg_replace('#\[leido\]#si',"<a href='/op/leido.php?pid=$pid'>Leer guia</a> ",$message);
		}
	}

	while(preg_match('#\[cerrado\]#si',$message))
	{
		$tid = $post['tid'];
		$db->query("
			UPDATE mybb_threads SET `closed`='1' WHERE tid='$tid'
		");
		$message = preg_replace('#\[cerrado\]#si','<div style=" text-align: center;border: 1px dotted #61567e; "><h1>Este tema ha sido cerrado.</h1></div>',$message);
	}

	while(preg_match('#\[movilidad=(.*?)\]#si',$message,$matches))
	{
		$movilidad = $matches[1];
		$agi_res = explode(",", $movilidad);
		if (count($agi_res) == 2) {
			$agi = intval(trim($agi_res[0]));
			$res = intval(trim($agi_res[1]));

			if ($agi > 0 && $res > 0) {
				$calc = ($agi / 2.0) + ($res / 4.0);
				$texto_movi = "[Tienes $agi de Agilidad y $res de Resistencia. Tu capacidad de movimiento es $calc metros en un turno.]";

				$message = preg_replace("#\[movilidad=$movilidad\]#si","$texto_movi",$message);
			} else {
				$message = preg_replace("#\[movilidad=$movilidad\]#si","[movilidadinvalido=$movilidad]",$message);
			}


		} else {
			$message = preg_replace("#\[movilidad=$movilidad\]#si","[movilidadinvalido=$movilidad]",$message);
		}
	}

	while(preg_match('#\[salto=(.*?)\]#si',$message,$matches))
	{
		$salto = $matches[1];
		$agi_fue = explode(",", $salto);
		if (count($agi_fue) == 2) {
			$agi = intval(trim($agi_fue[0]));
			$fue = intval(trim($agi_fue[1]));

			if ($agi > 0 && $fue > 0) {
				$calc = 1 + (($agi + $fue) / 5);
				$texto_salto = "[Tienes $agi de Agilidad y $fue de Fuerza. Tu capacidad de salto es $calc metros en un turno.]";

				$message = preg_replace("#\[salto=$salto\]#si","$texto_salto",$message);
			} else {
				$message = preg_replace("#\[salto=$salto\]#si","[saltoinvalido=$salto]",$message);
			}


		} else {
			$message = preg_replace("#\[salto=$salto\]#si","[saltoinvalido=$salto]",$message);
		}
	}

	while(preg_match('#\[trepar=(.*?)\]#si',$message,$matches))
	{
		$trepar = $matches[1];
		$agi_des = explode(",", $trepar);
		if (count($agi_des) == 2) {
			$agi = intval(trim($agi_des[0]));
			$des = intval(trim($agi_des[1]));

			if ($agi > 0 && $des > 0) {
				$calc = 1 + (($agi + $des) / 10);
				$texto_trepar = "[Tienes $agi de Agilidad y $des de Destreza. Tu capacidad para trepar es $calc metros en un turno.]";

				$message = preg_replace("#\[trepar=$trepar\]#si","$texto_trepar",$message);
			} else {
				$message = preg_replace("#\[trepar=$trepar\]#si","[treparinvalido=$trepar]",$message);
			}


		} else {
			$message = preg_replace("#\[trepar=$trepar\]#si","[treparinvalido=$trepar]",$message);
		}
	}

	while(preg_match('#\[vida=(.*?)\]#si',$message,$matches))
	{
		$pid = $post['pid'];
		
		$vida = $matches[1];
		$vida_pt = explode(",", $vida);

		if (count($vida_pt) == 2) {
			$vida_actual = $vida_pt[0];
			$vida_max = trim($vida_pt[1]);

			if (intval($vida_max) > 0) {
				$max_width = 294;
				$max_width_avatar = 293;

				$actual_width = "0px";
				$remainingVida = "293px";

				if (intval($vida_actual) <= intval($vida_max)) {
					if (intval($vida_actual) > 0) {
						$actual_width = strval(intval(($vida_actual / $vida_max) * $max_width)) . 'px';
						$remainingVida = strval(intval(($vida_actual / $vida_max) * $max_width_avatar)) . 'px';
					} 
				} else if (intval($vida_actual) > intval($vida_max)) {
					$actual_width = "294px";
				}
				
				$vida_avatar = "<script>
					$('#post_$pid .personaje_vida2')[0].innerText = '$vida_actual / $vida_max';
					$('#post_$pid .subBarraVida').css('width', '$remainingVida');
				</script>";

				$barra = "
			<div class=\"vidaStatusBar\">
				<div class=\"barrasVida\">
					<div class=\"barraVidaRoja\" style=\"width: 294px\"></div>
					<div class=\"barraVidaVerde\" style=\"width: $actual_width\"></div>
					<span class=\"barraVidaText\">Vida: $vida_actual / $vida_max</span><br />
				</div>
			</div> $vida_avatar";
			} else {
				$message = preg_replace("#\[vida=$vida\]#si","[vidainvalida=$vida]",$message);
			}
	
			$message = preg_replace("#\[vida=$vida\]#si","$barra",$message);
		} else {
			$message = preg_replace("#\[vida=$vida\]#si","[vidainvalida=$vida]",$message);
		}
	}

	while(preg_match('#\[energia=(.*?)\]#si',$message,$matches))
	{

		$pid = $post['pid'];
		$energia = $matches[1];
		$energia_pt = explode(",", $energia);

		if (count($energia_pt) == 2) {
			$energia_actual = $energia_pt[0];
			$energia_max = trim($energia_pt[1]);

			if (intval($energia_max) > 0) {
				$max_width = 294;
				$max_width_avatar = 293;

				$actual_width = "0px";
				$remainingEnergia = "293px";

				if (intval($energia_actual) <= intval($energia_max)) {
					if (intval($energia_actual) > 0) {
						$actual_width = strval(intval(($energia_actual / $energia_max) * $max_width)) . 'px';
						$remainingEnergia = strval(intval(($energia_actual / $energia_max) * $max_width_avatar)) . 'px';
					} 
				} else if (intval($energia_actual) > intval($energia_max)) {
					$actual_width = "294px";
				}

				$energia_avatar = "<script>
					$('#post_$pid .personaje_energia2')[0].innerText = '$energia_actual / $energia_max';
					$('#post_$pid .subBarraEnergia').css('width', '$remainingEnergia');
				
					</script>";

				$barra = "
			<div class=\"energiaStatusBar\">
				<div class=\"barrasEnergia\">
					<div class=\"barraEnergiaRoja\" style=\"width: 294px\"></div>
					<div class=\"barraEnergiaVerde\" style=\"width: $actual_width\"></div>
					<span class=\"barraEnergiaText\">Energia: $energia_actual / $energia_max</span><br />
				</div>
			</div> $energia_avatar";
			} else {
				$message = preg_replace("#\[energia=$energia\]#si","[energiainvalido=$energia]",$message);
			}
	
			$message = preg_replace("#\[energia=$energia\]#si","$barra",$message);
		} else {
			$message = preg_replace("#\[energia=$energia\]#si","[energiainvalido=$energia]",$message);
		}
	}

	while(preg_match('#\[haki=(.*?)\]#si',$message,$matches))
	{

		$pid = $post['pid'];
		$haki = $matches[1];
		$haki_pt = explode(",", $haki);

		if (count($haki_pt) == 2) {
			$haki_actual = $haki_pt[0];
			$haki_max = trim($haki_pt[1]);

			if (intval($haki_max) > 0) {
				$max_width = 294;
				$max_width_avatar = 293;

				$actual_width = "0px";
				$remainingHaki = "293px";

				if (intval($haki_actual) <= intval($haki_max)) {
					if (intval($haki_actual) > 0) {
						$actual_width = strval(intval(($haki_actual / $haki_max) * $max_width)) . 'px';
						$remainingHaki = strval(intval(($haki_actual / $haki_max) * $max_width_avatar)) . 'px';
					} 
				} else if (intval($haki_actual) > intval($haki_max)) {
					$actual_width = "294px";
				}

				$haki_avatar = "<script>
					$('#post_$pid .personaje_haki2')[0].innerText = '$haki_actual / $haki_max';
					$('#post_$pid .subBarraHaki').css('width', '$remainingHaki');
				
					</script>";

				$barra = "
			<div class=\"hakiStatusBar\">
				<div class=\"barrasHaki\">
					<div class=\"barraHakiRoja\" style=\"width: 294px\"></div>
					<div class=\"barraHakiVerde\" style=\"width: $actual_width\"></div>
					<span class=\"barraHakiText\">Haki: $haki_actual / $haki_max</span><br />
				</div>
			</div> $haki_avatar";
			} else {
				$message = preg_replace("#\[haki=$haki\]#si","[hakiinvalido=$haki]",$message);
			}
	
			$message = preg_replace("#\[haki=$haki\]#si","$barra",$message);
		} else {
			$message = preg_replace("#\[haki=$haki\]#si","[hakiinvalido=$haki]",$message);
		}
	}

	while(preg_match('#\[tiempo=(.*?)\]#si',$message,$matches))
	{
		$dateline = $post['dateline'];
		$tiempo = $matches[1];

		if (intval($tiempo) > 0 && intval($tiempo) < 8760) {

			$timeNow = time();
			$timeAfter = intval($dateline) + (intval($tiempo) * 3600);

			// <div style=" text-align: center;border: 1px dotted #61567e; "><h1>Este tema ha sido cerrado.</h1></div>

			if ($timeNow > $timeAfter) {
				$texto = "<div style=' text-align: center;border: 1px dotted #61567e; '><h3>El tiempo para postear de $tiempo horas ya ha expirado.</h3></div>";
			} else {
				$timeLeft =  round(($timeAfter - $timeNow) / 3600, 2);
				$texto = "<div style=' text-align: center;border: 1px dotted #61567e; '><h3>Este post tiene un límite de $tiempo horas.</h3><h3>Quedan $timeLeft horas para postear.</h3></div>";
			}

			$message = preg_replace("#\[tiempo=$tiempo\]#si","$texto",$message);
		} else {
			$message = preg_replace("#\[tiempo=$tiempo\]#si","[tiempoinvalido=$tiempo]",$message);
		}
	}

	while(preg_match('#\[equipamiento\]#si',$message))
	{
		$uid = $post['uid'];
		$tid = $post['tid'];
		$pid = $post['pid'];

		$ficha = null;
		$equipamiento_message = "";
		$equipamiento_json = "";
		$equipamiento = null;

		$query_equipamiento = $db->query("
			SELECT * FROM mybb_op_equipamiento_personaje WHERE tid='$tid' AND uid='$uid'
		");

		while ($q = $db->fetch_array($query_equipamiento)) {
			$equipamiento = $q;
		}

		if (!$equipamiento) {
			$query_ficha = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$uid' ");
			while ($q = $db->fetch_array($query_ficha)) { $ficha = $q; }

			$equipamiento_json = $ficha['equipamiento'];

			if ($tid && $pid) {
				$db->query(" 
					INSERT INTO `mybb_op_equipamiento_personaje` (`tid`, `pid`, `uid`, `equipamiento`) 
					VALUES ('$tid', '$pid', '$uid', '$equipamiento_json');
				");
			}
		} else {
			$equipamiento_json = $equipamiento['equipamiento'];
		}

		// Procesar equipamiento y mostrar nombres de objetos
		$equipamiento_data = json_decode($equipamiento_json, true);
		$equipamiento_formatted = "";
		$espacios_totales_usados = 0;
		
		// Procesar bolsa
		if ($equipamiento_data && isset($equipamiento_data['bolsa']) && !empty($equipamiento_data['bolsa'])) {
			$bolsa_id = $equipamiento_data['bolsa'];
			$bolsa_query = $db->query("SELECT nombre FROM mybb_op_objetos WHERE objeto_id='$bolsa_id'");
			$bolsa_nombre = $bolsa_id;
			while ($q = $db->fetch_array($bolsa_query)) { $bolsa_nombre = $q['nombre']; }
			$equipamiento_formatted .= "<strong>Bolsa:</strong> $bolsa_nombre<br>";
		}
		
		// Procesar ropa
		if ($equipamiento_data && isset($equipamiento_data['ropa']) && !empty($equipamiento_data['ropa'])) {
			$ropa_id = $equipamiento_data['ropa'];
			$ropa_query = $db->query("SELECT nombre FROM mybb_op_objetos WHERE objeto_id='$ropa_id'");
			$ropa_nombre = $ropa_id;
			while ($q = $db->fetch_array($ropa_query)) { $ropa_nombre = $q['nombre']; }
			$equipamiento_formatted .= "<strong>Ropa:</strong> $ropa_nombre<br>";
		}
		
		// Procesar espacios de equipamiento
		if ($equipamiento_data && isset($equipamiento_data['espacios']) && is_array($equipamiento_data['espacios'])) {
			foreach ($equipamiento_data['espacios'] as $espacio_id => $objeto_info) {
				if (isset($objeto_info['objetoId'])) {
					$objeto_id = $objeto_info['objetoId'];
					$objeto_query = $db->query("SELECT nombre, espacios FROM mybb_op_objetos WHERE objeto_id='$objeto_id'");
					$objeto_nombre = $objeto_id;
					$objeto_espacios = 1; // Valor por defecto si no existe el campo espacios
					while ($q = $db->fetch_array($objeto_query)) { 
						$objeto_nombre = $q['nombre']; 
						$objeto_espacios = isset($q['espacios']) && is_numeric($q['espacios']) ? intval($q['espacios']) : 1;
					}
					$equipamiento_formatted .= "<strong>Espacio $espacio_id:</strong> $objeto_nombre (ocupa $objeto_espacios espacios)<br>";
					$espacios_totales_usados += $objeto_espacios;
				}
			}
		}
		
		if (empty($equipamiento_formatted)) {
			$equipamiento_formatted = "<em>No hay objetos equipados</em>";
		} else if ($espacios_totales_usados > 0) {
			$equipamiento_formatted .= "<br><strong>Total de espacios utilizados:</strong> $espacios_totales_usados";
		}

		$equipamiento_message = "
			<div style='text-align: left; padding: 10px;'>$equipamiento_formatted</div>		
		";

		$equipamiento_spoiler = "
			<div class='spoiler'>
				<div class='spoiler_title'>
					<span class='spoiler_button' onclick=\"javascript: if(parentNode.parentNode.getElementsByTagName('div')[1].style.display == 'block'){ parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'none'; this.innerHTML='Equipamiento'; } else { parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'block'; this.innerHTML='Equipamiento'; }\">Equipamiento</span>
				</div>
				<div class='spoiler_content' style='display: none;'>$equipamiento_message</div>
			</div>";

		$message = preg_replace('#\[equipamiento\]#si',$equipamiento_spoiler,$message);

	}

	while(preg_match('#\[personaje\]#si',$message))
	{
		$uid = $post['uid'];
		$tid = $post['tid'];
		$pid = $post['pid'];
		
		$ficha = null;
		$thread_ficha = null;
		$personaje_message = "";

		$has_vigoroso1 = false;
		$has_vigoroso1_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V037'; "); 
		while ($q = $db->fetch_array($has_vigoroso1_query)) { $has_vigoroso1 = true; }
	
		$has_vigoroso2 = false;
		$has_vigoroso2_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V038'; "); 
		while ($q = $db->fetch_array($has_vigoroso2_query)) { $has_vigoroso2 = true; }
	
		$has_vigoroso3 = false;
		$has_vigoroso3_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V039'; "); 
		while ($q = $db->fetch_array($has_vigoroso3_query)) { $has_vigoroso3 = true; }
	
		$has_hiperactivo1 = false;
		$has_hiperactivo1_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V040'; "); 
		while ($q = $db->fetch_array($has_hiperactivo1_query)) { $has_hiperactivo1 = true; }
	
		$has_hiperactivo2 = false;
		$has_hiperactivo2_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V041'; "); 
		while ($q = $db->fetch_array($has_hiperactivo2_query)) { $has_hiperactivo2 = true; }

		$has_espiritual1 = false;
		$has_espiritual1_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V058'; "); 
		while ($q = $db->fetch_array($has_espiritual1_query)) { $has_espiritual1 = true; }
	
		$has_espiritual2 = false;
		$has_espiritual2_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V059'; "); 
		while ($q = $db->fetch_array($has_espiritual2_query)) { $has_espiritual2 = true; }
	
		$vitalidad_completa = 0;
		$energia_completa = 0;
		$haki_completo = 0;

		$query_personaje = $db->query("
			SELECT * FROM mybb_op_thread_personaje WHERE tid='$tid' AND uid='$uid'
		");

		while ($q = $db->fetch_array($query_personaje)) {
			$thread_ficha = $q;
		}

		if (!$thread_ficha) {

			$query_ficha = $db->query("
				SELECT * FROM mybb_op_fichas WHERE fid='$uid'
			");

			while ($q = $db->fetch_array($query_ficha)) {
				$ficha = $q;
			}

			$nivel = $ficha['nivel'];
			$nombre = $ficha['nombre'];
			$fuerza = $ficha['fuerza'];
			$resistencia = $ficha['resistencia'];
			$destreza = $ficha['destreza'];
			$punteria = $ficha['punteria'];
			$agilidad = $ficha['agilidad'];
			$reflejos = $ficha['reflejos'];
			$voluntad = $ficha['voluntad'];
			$control_akuma = $ficha['control_akuma'];
			$vitalidad = $ficha['vitalidad'];
			$energia = $ficha['energia'];
			$haki = $ficha['haki'];
			$vitalidad_pasiva = $ficha['vitalidad_pasiva'];
			$energia_pasiva = $ficha['energia_pasiva'];
			$haki_pasiva = $ficha['haki_pasiva'];

			$fuerza_pasiva = $ficha['fuerza_pasiva'];
			$resistencia_pasiva = $ficha['resistencia_pasiva'];
			$destreza_pasiva = $ficha['destreza_pasiva'];
			$punteria_pasiva = $ficha['punteria_pasiva'];
			$agilidad_pasiva = $ficha['agilidad_pasiva'];
			$reflejos_pasiva = $ficha['reflejos_pasiva'];
			$voluntad_pasiva = $ficha['voluntad_pasiva'];
			$control_akuma_pasiva = $ficha['control_akuma_pasiva'];

			if ($tid && $pid) {
				$db->query(" 
					INSERT INTO `mybb_op_thread_personaje` (`tid`, `pid`, `uid`, `nombre`,
						`vitalidad`, `energia`, `haki`, 
						`fuerza`, `resistencia`, `destreza`, `punteria`, `agilidad`, 
						`reflejos`, `voluntad`, `control_akuma`,
						`fuerza_pasiva`, `resistencia_pasiva`, `destreza_pasiva`, `punteria_pasiva`, `agilidad_pasiva`, 
						`reflejos_pasiva`, `voluntad_pasiva`, `control_akuma_pasiva`, `vitalidad_pasiva`, `energia_pasiva`, `haki_pasiva`, `nivel`
						) 
					VALUES ('$tid', '$pid', '$uid', '$nombre',
						'$vitalidad', '$energia', '$haki', 
						'$fuerza', '$resistencia', '$destreza', '$punteria', '$agilidad', 
						'$reflejos', '$voluntad', '$control_akuma',
						'$fuerza_pasiva', '$resistencia_pasiva', '$destreza_pasiva', '$punteria_pasiva', '$agilidad_pasiva', 
						'$reflejos_pasiva', '$voluntad_pasiva', '$control_akuma_pasiva', '$vitalidad_pasiva', '$energia_pasiva', '$haki_pasiva', '$nivel'
					);
				");
			}

		} else {
			$nivel = $thread_ficha['nivel'];
			$nombre = $thread_ficha['nombre'];
			$fuerza = $thread_ficha['fuerza'];
			$resistencia = $thread_ficha['resistencia'];
			$destreza = $thread_ficha['destreza'];
			$punteria = $thread_ficha['punteria'];
			$agilidad = $thread_ficha['agilidad'];
			$reflejos = $thread_ficha['reflejos'];
			$voluntad = $thread_ficha['voluntad'];
			$control_akuma = $thread_ficha['control_akuma'];
			$vitalidad = $thread_ficha['vitalidad'];
			$energia = $thread_ficha['energia'];
			$haki = $thread_ficha['haki'];
			$vitalidad_pasiva = $thread_ficha['vitalidad_pasiva'];
			$energia_pasiva = $thread_ficha['energia_pasiva'];
			$haki_pasiva = $thread_ficha['haki_pasiva'];

			$fuerza_pasiva = $thread_ficha['fuerza_pasiva'];
			$resistencia_pasiva = $thread_ficha['resistencia_pasiva'];
			$destreza_pasiva = $thread_ficha['destreza_pasiva'];
			$punteria_pasiva = $thread_ficha['punteria_pasiva'];
			$agilidad_pasiva = $thread_ficha['agilidad_pasiva'];
			$reflejos_pasiva = $thread_ficha['reflejos_pasiva'];
			$voluntad_pasiva = $thread_ficha['voluntad_pasiva'];
			$control_akuma_pasiva = $thread_ficha['control_akuma_pasiva'];
		}

		$sum_stats = intval($fuerza) + intval($resistencia) + intval($destreza) + intval($punteria) + intval($agilidad) + intval($reflejos) + intval($voluntad) + intval($control_akuma); 

        $vitalidad_extra = 0;
		$energia_extra = 0;
		$haki_extra = 0;

		$vitalidad_extra = floor(((intval($fuerza_pasiva) * 6) + (intval($resistencia_pasiva) * 15) + (intval($destreza_pasiva) * 4) +
		intval($agilidad_pasiva * 3) + (intval($voluntad_pasiva) * 1)) + (intval($punteria_pasiva) * 2) + (intval($reflejos_pasiva) * 1));

		$energia_extra = floor(((intval($destreza_pasiva) * 4) + (intval($agilidad_pasiva) * 5) + (intval($voluntad_pasiva) * 1)) + 
		(intval($fuerza_pasiva) * 2) + (intval($resistencia_pasiva) * 4) + (intval($punteria_pasiva) * 5) + (intval($reflejos_pasiva) * 1)); // + (intval($control_akuma_pasiva) * 4) Ya no existe

		$haki_extra = floor(intval($voluntad_pasiva) * 10);

/*
		$vitalidad_extra = floor(((intval($fuerza_pasiva) * 3) + (intval($resistencia_pasiva) * 10) + (intval($destreza_pasiva) * 2) +
		intval($agilidad_pasiva) + (intval($voluntad_pasiva) * 1)) + (intval($punteria_pasiva) * 1) + (intval($reflejos_pasiva) * 1));

		if (intval($fuerza_pasiva)) {
			$energia_extra += (intval($fuerza_pasiva) * 1);
		}

		if (intval($resistencia_pasiva)) {
			$energia_extra += (intval($resistencia_pasiva) * 1);
		}

		if (intval($punteria_pasiva)) {
			$energia_extra += (intval($punteria_pasiva) * 1);
		}

		if (intval($destreza_pasiva)) {
			$energia_extra += (intval($destreza_pasiva) * 1);
		}

		if (intval($agilidad_pasiva)) {
			$energia_extra += (intval($agilidad_pasiva) * 2);
		}

		if (intval($reflejos_pasiva)) {
			$energia_extra += (intval($reflejos_pasiva) * 2);
		}

		if (intval($voluntad_pasiva)) {
			// $energia_extra += (intval($voluntad_pasiva) * 1);
			$haki_extra += (intval($voluntad_pasiva) * 5);
		}
	
		if (intval($control_akuma_pasiva)) {
			$energia_extra += (intval($control_akuma_pasiva) * 4);
		}
*/

		$fuerza_completa = intval($fuerza) + (intval($fuerza_pasiva) ? intval($fuerza_pasiva) : 0);
		$resistencia_completa = intval($resistencia) + (intval($resistencia_pasiva) ? intval($resistencia_pasiva) : 0);
		$destreza_completa = intval($destreza) + (intval($destreza_pasiva) ? intval($destreza_pasiva) : 0);
		$punteria_completa = intval($punteria) + (intval($punteria_pasiva) ? intval($punteria_pasiva) : 0);
		$agilidad_completa = intval($agilidad) + (intval($agilidad_pasiva) ? intval($agilidad_pasiva) : 0);
		$reflejos_completa = intval($reflejos) + (intval($reflejos_pasiva) ? intval($reflejos_pasiva) : 0);
		$voluntad_completa = intval($voluntad) + (intval($voluntad_pasiva) ? intval($voluntad_pasiva) : 0);
		$control_akuma_completa = intval($control_akuma) + (intval($control_akuma_pasiva) ? intval($control_akuma_pasiva) : 0);

		$vitalidad_completa = intval($vitalidad) + $vitalidad_extra + $vitalidad_pasiva;
		$energia_completa = intval($energia) + $energia_extra + $energia_pasiva;
		$haki_completo = intval($haki) + $haki_extra + $haki_pasiva;

		if ($has_vigoroso1) { // Vigoroso 1 2 3
			$vitalidad_completa += intval($nivel) * 10; 
		} else if ($has_vigoroso2) {
			$vitalidad_completa += intval($nivel) * 15; 
		} else if ($has_vigoroso3) {
			$vitalidad_completa += intval($nivel) * 20; 
		}
	
		if ($has_hiperactivo1) { // Hiperactivo 1 2
			$energia_completa += intval($nivel) * 10; 
		} else if ($has_hiperactivo2) {
			$energia_completa += intval($nivel) * 15; 
		} 

		if ($has_espiritual1) { // Espiritual 1 2 
			$haki_completo += intval($nivel) * 5;
		} else if ($has_espiritual2) {            
			$haki_completo += intval($nivel) * 10;
		}

		$personaje_message = "
			<div class='personaje_container' style='border-radius: 15px; padding: 20px; margin: 10px 0; box-shadow: 0 8px 32px rgba(0,0,0,0.3); color: white; background-color: #2c2c2c;'>
				<div class='personaje_header' style='text-align: center; margin-bottom: 20px;'>
					<h2 class='personaje_stats_title' style='margin: 0; font-size: 24px; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); background: linear-gradient(45deg, #ffd700, #ffed4e); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;'>⚔️ $nombre ⚔️</h2>
					<div class='personaje_nivel' style='margin-top: 5px; font-size: 16px; opacity: 0.9; display: flex; justify-content: center; align-items: center; gap: 15px;'>
						<span style='background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; font-weight: bold;'>Nivel $nivel</span>
					</div>
				</div>
				
				<div class='personaje_stats_container' style='display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 10px; margin: 20px 0; text-align: center;'>
					<div class='stat_item' style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
						<div style='font-size: 18px; font-weight: bold; color: white;'>$fuerza_completa</div>
						<div style='font-size: 12px; opacity: 0.8;'>FUE</div>
					</div>
					<div class='stat_item' style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
						<div style='font-size: 18px; font-weight: bold; color: white;'>$resistencia_completa</div>
						<div style='font-size: 12px; opacity: 0.8;'>RES</div>
					</div>
					<div class='stat_item' style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
						<div style='font-size: 18px; font-weight: bold; color: white;'>$destreza_completa</div>
						<div style='font-size: 12px; opacity: 0.8;'>DES</div>
					</div>
					<div class='stat_item' style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
						<div style='font-size: 18px; font-weight: bold; color: white;'>$punteria_completa</div>
						<div style='font-size: 12px; opacity: 0.8;'>PUN</div>
					</div>
					<div class='stat_item' style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
						<div style='font-size: 18px; font-weight: bold; color: white;'>$agilidad_completa</div>
						<div style='font-size: 12px; opacity: 0.8;'>AGI</div>
					</div>
					<div class='stat_item' style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
						<div style='font-size: 18px; font-weight: bold; color: white;'>$reflejos_completa</div>
						<div style='font-size: 12px; opacity: 0.8;'>REF</div>
					</div>
					<div class='stat_item' style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
						<div style='font-size: 18px; font-weight: bold; color: white;'>$voluntad_completa</div>
						<div style='font-size: 12px; opacity: 0.8;'>VOL</div>
					</div>
				</div>
				
				<div class='personaje_vitals' style='margin-top: 20px;'>
					<div class='vital_row' style='display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #00aa00; border-radius: 8px; backdrop-filter: blur(5px);'>
						<span style='font-weight: bold; display: flex; align-items: center;'>
							❤️ Vitalidad:
						</span>
						<span class='personaje_vitalidad' style='font-size: 18px; font-weight: bold; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$vitalidad_completa</span>
					</div>
					<div class='vital_row' style='display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #ff8800; border-radius: 8px; backdrop-filter: blur(5px);'>
						<span style='font-weight: bold; display: flex; align-items: center;'>
							⚡ Energía:
						</span>
						<span class='personaje_energia' style='font-size: 18px; font-weight: bold; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$energia_completa</span>
					</div>
					<div class='vital_row' style='display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #0066ff; border-radius: 8px; backdrop-filter: blur(5px);'>
						<span style='font-weight: bold; display: flex; align-items: center;'>
							🔮 Haki:
						</span>
						<span class='personaje_espiritu' style='font-size: 18px; font-weight: bold; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$haki_completo</span>
					</div>
				</div>
			</div>
		";

		$personaje_spoiler = "
			<div class='spoiler' style='border: none; background: transparent; box-shadow: none;'>
				<div class='spoiler_title'>
					<span class='spoiler_button' onclick=\"javascript: if(parentNode.parentNode.getElementsByTagName('div')[1].style.display == 'block'){ parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'none'; this.innerHTML='👤 Personaje'; } else { parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'block'; this.innerHTML='👤 Personaje'; }\" style='background-color: #2c2c2c; color: white; border: none; border-radius: 25px; padding: 12px 25px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s ease; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);'>👤 Personaje</span>
				</div>
				<div class='spoiler_content' style='display: none; background: transparent; border: none; box-shadow: none; margin-top: 10px;'>$personaje_message</div>
			</div>";

		$message = preg_replace('#\[personaje\]#si',$personaje_spoiler,$message);
	}

	while(preg_match('#\[personaje=(.*?)\]#si',$message,$matches))
	{
		$tid_t = $post['tid'];
		$pid_t = $post['pid'];

		$uid = $post['uid'];
		$tid = $matches[1];
		$thread_ficha = null;
		$thread_ficha_current = null;

		$personaje_message = "[personajeinvalido=$tid]";

		$query_personaje = $db->query(" SELECT * FROM mybb_op_thread_personaje WHERE tid='$tid' AND uid='$uid' ");
		while ($q = $db->fetch_array($query_personaje)) { $thread_ficha = $q; }

		$query_personaje_current = $db->query(" SELECT * FROM mybb_op_thread_personaje WHERE tid='$tid_t' AND uid='$uid' ");
		while ($q = $db->fetch_array($query_personaje_current)) { $thread_ficha_current = $q; }

		$has_vigoroso1 = false;
		$has_vigoroso1_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V037'; "); 
		while ($q = $db->fetch_array($has_vigoroso1_query)) { $has_vigoroso1 = true; }
	
		$has_vigoroso2 = false;
		$has_vigoroso2_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V038'; "); 
		while ($q = $db->fetch_array($has_vigoroso2_query)) { $has_vigoroso2 = true; }
	
		$has_vigoroso3 = false;
		$has_vigoroso3_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V039'; "); 
		while ($q = $db->fetch_array($has_vigoroso3_query)) { $has_vigoroso3 = true; }
	
		$has_hiperactivo1 = false;
		$has_hiperactivo1_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V040'; "); 
		while ($q = $db->fetch_array($has_hiperactivo1_query)) { $has_hiperactivo1 = true; }
	
		$has_hiperactivo2 = false;
		$has_hiperactivo2_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V041'; "); 
		while ($q = $db->fetch_array($has_hiperactivo2_query)) { $has_hiperactivo2 = true; }

		$has_espiritual1 = false;
		$has_espiritual1_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V058'; "); 
		while ($q = $db->fetch_array($has_espiritual1_query)) { $has_espiritual1 = true; }
	
		$has_espiritual2 = false;
		$has_espiritual2_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V059'; "); 
		while ($q = $db->fetch_array($has_espiritual2_query)) { $has_espiritual2 = true; }

		if (!$thread_ficha) {
			$message = preg_replace("#\[personaje=$tid\]#si","$personaje_message",$message);
		} else {
			// Obtener datos de la ficha original para el avatar
			$ficha_original = null;
			$query_ficha_original = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$uid' ");
			while ($q = $db->fetch_array($query_ficha_original)) { $ficha_original = $q; }
			$nivel = $thread_ficha['nivel'];
			$nombre = $thread_ficha['nombre'];
			$fuerza = $thread_ficha['fuerza'];
			$resistencia = $thread_ficha['resistencia'];
			$destreza = $thread_ficha['destreza'];
			$punteria = $thread_ficha['punteria'];
			$agilidad = $thread_ficha['agilidad'];
			$reflejos = $thread_ficha['reflejos'];
			$voluntad = $thread_ficha['voluntad'];
			$control_akuma = $thread_ficha['control_akuma'];
			$vitalidad = $thread_ficha['vitalidad'];
			$energia = $thread_ficha['energia'];
			$haki = $thread_ficha['haki'];
			$vitalidad_pasiva = $thread_ficha['vitalidad_pasiva'];
			$energia_pasiva = $thread_ficha['energia_pasiva'];
			$haki_pasiva = $thread_ficha['haki_pasiva'];

			$fuerza_pasiva = $thread_ficha['fuerza_pasiva'];
			$resistencia_pasiva = $thread_ficha['resistencia_pasiva'];
			$destreza_pasiva = $thread_ficha['destreza_pasiva'];
			$punteria_pasiva = $thread_ficha['punteria_pasiva'];
			$agilidad_pasiva = $thread_ficha['agilidad_pasiva'];
			$reflejos_pasiva = $thread_ficha['reflejos_pasiva'];
			$voluntad_pasiva = $thread_ficha['voluntad_pasiva'];
			$control_akuma_pasiva = $thread_ficha['control_akuma_pasiva'];

			if (!$thread_ficha_current) {
				$db->query(" 
					INSERT INTO `mybb_op_thread_personaje` (`tid`, `pid`, `uid`, `nombre`,
						`vitalidad`, `energia`, `haki`, 
						`fuerza`, `resistencia`, `destreza`, `punteria`, `agilidad`, 
						`reflejos`, `voluntad`, `control_akuma`,
						`fuerza_pasiva`, `resistencia_pasiva`, `destreza_pasiva`, `punteria_pasiva`, `agilidad_pasiva`, 
						`reflejos_pasiva`, `voluntad_pasiva`, `control_akuma_pasiva`, `vitalidad_pasiva`, `energia_pasiva`, `haki_pasiva`, `nivel`
						) 
					VALUES ('$tid_t', '$pid_t', '$uid', '$nombre',
						'$vitalidad', '$energia', '$haki', 
						'$fuerza', '$resistencia', '$destreza', '$punteria', '$agilidad', 
						'$reflejos', '$voluntad', '$control_akuma',
						'$fuerza_pasiva', '$resistencia_pasiva', '$destreza_pasiva', '$punteria_pasiva', '$agilidad_pasiva', 
						'$reflejos_pasiva', '$voluntad_pasiva', '$control_akuma_pasiva', '$vitalidad_pasiva', '$energia_pasiva', '$haki_pasiva', '$nivel'
					);
				");
			}

			$sum_stats = intval($fuerza) + intval($resistencia) + intval($destreza) + intval($punteria) + intval($agilidad) + intval($reflejos) + intval($voluntad) + intval($control_akuma); 

			$vitalidad_extra = 0;
			$energia_extra = 0;
			$haki_extra = 0;
	
			$vitalidad_extra = floor(((intval($fuerza_pasiva) * 6) + (intval($resistencia_pasiva) * 15) + (intval($destreza_pasiva) * 4) +
				(intval($agilidad_pasiva) * 3) + (intval($voluntad_pasiva) * 1)) + (intval($punteria_pasiva) * 2) + (intval($reflejos_pasiva) * 1));

			if (intval($fuerza_pasiva)) {
				$energia_extra += (intval($fuerza_pasiva) * 2);
			}
	
			if (intval($resistencia_pasiva)) {
				$energia_extra += (intval($resistencia_pasiva) * 4);
			}
	
			if (intval($punteria_pasiva)) {
				$energia_extra += (intval($punteria_pasiva) * 5);
			}
	
			if (intval($destreza_pasiva)) {
				$energia_extra += (intval($destreza_pasiva) * 4);
			}
	
			if (intval($agilidad_pasiva)) {
				$energia_extra += (intval($agilidad_pasiva) * 5);
			}
	
			if (intval($reflejos_pasiva)) {
				$energia_extra += (intval($reflejos_pasiva) * 1);
			}
	
			if (intval($voluntad_pasiva)) {
				$energia_extra += (intval($voluntad_pasiva) * 1);
				$haki_extra += (intval($voluntad_pasiva) * 5);
			}
		
			if (intval($control_akuma_pasiva)) {
				$energia_extra += (intval($control_akuma_pasiva) * 4);
			}
	
			$fuerza_completa = intval($fuerza) + (intval($fuerza_pasiva) ? intval($fuerza_pasiva) : 0);
			$resistencia_completa = intval($resistencia) + (intval($resistencia_pasiva) ? intval($resistencia_pasiva) : 0);
			$destreza_completa = intval($destreza) + (intval($destreza_pasiva) ? intval($destreza_pasiva) : 0);
			$punteria_completa = intval($punteria) + (intval($punteria_pasiva) ? intval($punteria_pasiva) : 0);
			$agilidad_completa = intval($agilidad) + (intval($agilidad_pasiva) ? intval($agilidad_pasiva) : 0);
			$reflejos_completa = intval($reflejos) + (intval($reflejos_pasiva) ? intval($reflejos_pasiva) : 0);
			$voluntad_completa = intval($voluntad) + (intval($voluntad_pasiva) ? intval($voluntad_pasiva) : 0);
			$control_akuma_completa = intval($control_akuma) + (intval($control_akuma_pasiva) ? intval($control_akuma_pasiva) : 0);
	
			$vitalidad_completa = intval($vitalidad) + $vitalidad_extra + $vitalidad_pasiva;
			$energia_completa = intval($energia) + $energia_extra + $energia_pasiva;
			$haki_completo = intval($haki) + $haki_extra + $haki_pasiva;

			if ($has_vigoroso1) { // Vigoroso 1 2 3 
				$vitalidad_completa += intval($nivel) * 10; 
			} else if ($has_vigoroso2) {
				$vitalidad_completa += intval($nivel) * 15; 
			} else if ($has_vigoroso3) {
				$vitalidad_completa += intval($nivel) * 20; 
			}
		
			if ($has_hiperactivo1) { // Hiperactivo 1 2 
				$energia_completa += intval($nivel) * 10; 
			} else if ($has_hiperactivo2) {
				$energia_completa += intval($nivel) * 15; 
			} 

			if ($has_espiritual1) { // Espiritual 1 2 
				$haki_completo += intval($nivel) * 5;
			} else if ($has_espiritual2) {     
				
				$haki_completo += 500;
				$haki_completo += intval($nivel) * 10;
			}
	
			$personaje_message = "
				<div class='personaje_container' style='border-radius: 15px; padding: 20px; margin: 10px 0; box-shadow: 0 8px 32px rgba(0,0,0,0.3); color: white; background-color: #2c2c2c;'>
					<div class='personaje_header' style='text-align: center; margin-bottom: 20px;'>
						<h2 class='personaje_stats_title' style='margin: 0; font-size: 24px; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); background: linear-gradient(45deg, #ffd700, #ffed4e); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;'>⚔️ $nombre ⚔️</h2>
						<div class='personaje_nivel' style='margin-top: 5px; font-size: 16px; opacity: 0.9; display: flex; justify-content: center; align-items: center; gap: 15px;'>
							<span style='background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; font-weight: bold;'>Nivel $nivel</span>
						</div>
					</div>
					
					<div class='personaje_stats_container' style='display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 10px; margin: 20px 0; text-align: center;'>
						<div class='stat_item' style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$fuerza_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>FUE</div>
						</div>
						<div class='stat_item' style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$resistencia_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>RES</div>
						</div>
						<div class='stat_item' style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$destreza_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>DES</div>
						</div>
						<div class='stat_item' style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$punteria_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>PUN</div>
						</div>
						<div class='stat_item' style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$agilidad_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>AGI</div>
						</div>
						<div class='stat_item' style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$reflejos_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>REF</div>
						</div>
						<div class='stat_item' style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$voluntad_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>VOL</div>
						</div>
					</div>
					
					<div class='personaje_vitals' style='margin-top: 20px;'>
						<div class='vital_row' style='display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #00aa00; border-radius: 8px; backdrop-filter: blur(5px);'>
							<span style='font-weight: bold; display: flex; align-items: center;'>
								❤️ Vitalidad:
							</span>
							<span class='personaje_vitalidad' style='font-size: 18px; font-weight: bold; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$vitalidad_completa</span>
						</div>
						<div class='vital_row' style='display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #ff8800; border-radius: 8px; backdrop-filter: blur(5px);'>
							<span style='font-weight: bold; display: flex; align-items: center;'>
								⚡ Energía:
							</span>
							<span class='personaje_energia' style='font-size: 18px; font-weight: bold; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$energia_completa</span>
						</div>
						<div class='vital_row' style='display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #0066ff; border-radius: 8px; backdrop-filter: blur(5px);'>
							<span style='font-weight: bold; display: flex; align-items: center;'>
								🔮 Haki:
							</span>
							<span class='personaje_espiritu' style='font-size: 18px; font-weight: bold; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$haki_completo</span>
						</div>
					</div>
				</div>
			";

			$personaje_spoiler = "
				<div class='spoiler' style='border: none; background: transparent; box-shadow: none;'>
					<div class='spoiler_title'>
						<span class='spoiler_button' onclick=\"javascript: if(parentNode.parentNode.getElementsByTagName('div')[1].style.display == 'block'){ parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'none'; this.innerHTML='👤 Personaje'; } else { parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'block'; this.innerHTML='👤 Personaje'; }\" style='background-color: #2c2c2c; color: white; border: none; border-radius: 25px; padding: 12px 25px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s ease; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);'>👤 Personaje</span>
					</div>
					<div class='spoiler_content' style='display: none; background: transparent; border: none; box-shadow: none; margin-top: 10px;'>$personaje_message</div>
				</div>";
		
			$message = preg_replace("#\[personaje=$tid\]#si","$personaje_spoiler",$message);
		}
	}

	while(preg_match('#\[dado_guardado=(.*?)\]#si',$message,$matches))
	{
		$dado_counter = $matches[1];

		$uid = $post['uid'];
		$pid = $post['pid'];
		$tid = $post['tid'];
		$is_edited = $post['edittime'];
		
		$post_editado = "";
		if ($is_edited) {
			$post_editado = "[[Pilas. Este post ha sido editado.]]<br />";
		}

		$query_dado = $db->query("
			SELECT * FROM mybb_op_dados WHERE pid='".$pid."' AND tid='".$tid."' AND dado_counter='".$dado_counter."'
		");

		$dado = null;
		while ($d = $db->fetch_array($query_dado)) {
			$dado = $d;
		}

		$dado_content = $dado['dado_content'];

		$message = preg_replace('#\[dado_guardado=(.*?)\]#si',"$post_editado $dado_content",$message, 1);
	}

	while(preg_match('#\[tiradanaval_guardada=(.*?)\]#si',$message,$matches))
	{
		$tirada_counter = $matches[1];

		$uid = $post['uid'];
		$pid = $post['pid'];
		$tid = $post['tid'];
		$is_edited = $post['edittime'];
		
		$post_editado = "";
		if ($is_edited) {
			$post_editado = "[[Pilas. Este post ha sido editado.]]<br />";
		}

		$query_tirada = $db->query("
			SELECT * FROM mybb_op_tiradanaval WHERE pid='".$pid."' AND tid='".$tid."' AND counter='".$tirada_counter."'
		");

		$tirada = null;
		while ($q = $db->fetch_array($query_tirada)) {
			$tirada = $q;
		}

		$content = $tirada['content'];

		$message = preg_replace('#\[tiradanaval_guardada=(.*?)\]#si',"$post_editado $content",$message, 1);
	}

	while(preg_match('#\[consumido=(.*?)\]#si',$message,$matches))
	{
		$counter = $matches[1];

		$uid = $post['uid'];
		$pid = $post['pid'];
		$tid = $post['tid'];
		$is_edited = $post['edittime'];
		
		$post_editado = "";
		if ($is_edited) {
			$post_editado = "";
		}

		$query_consumir = $db->query("
			SELECT * FROM mybb_op_consumir WHERE pid='".$pid."' AND tid='".$tid."' AND counter='".$counter."'
		");

		$content = '';
		while ($q = $db->fetch_array($query_consumir)) {
			$content = $q['content'];
		}

		$message = preg_replace('#\[consumido=(.*?)\]#si',"$post_editado $content",$message, 1);
	}

	while(preg_match('#\[objeto=(.*?)\]#si',$message,$matches))
	{
		$objeto_id = $matches[1];
		$objeto_exists = false;

		$objeto_query = $db->query("SELECT * FROM mybb_op_objetos WHERE objeto_id='$objeto_id'");
		$objeto = null;
		while ($q = $db->fetch_array($objeto_query)) { $objeto = $q; $objeto_exists = true; }
	
		if ($objeto_exists) {
			$objetoNombre = $objeto['nombre'];
			$objetoImagen = $objeto['imagen'];
			$objetoDescripcion = $objeto['descripcion'];
			$objetoImagenId = $objeto['imagen_id'];
			$objetoImagenAvatar = $objeto['imagen_avatar'];
			$objetoSubcategoriaPretty = $objeto['subcategoria'];
			$objetoSubcategoria = strtolower(str_replace(' ', '_', $objeto['subcategoria']));
			$objetoTier = $objeto['tier'];


			$colorTier = '#faa500';

			if ($objetoImagenId == '1') { $colorTier = '#808080'; }
			if ($objetoImagenId == '2') { $colorTier = '#4dfe45'; }	
			if ($objetoImagenId == '3') { $colorTier = '#457bfe'; }
			if ($objetoImagenId == '4') { $colorTier = '#cf44ff'; }
			if ($objetoImagenId == '5') { $colorTier = '#febb46'; }

			$requisitos = '';
			$escalado = '';
			$dano = '';
			$efecto = '';

			$objetoRequisitos = $objeto['requisitos'];
			$objetoEscalado = $objeto['escalado'];
			$objetoDano = $objeto['dano'];
			$objetoEfecto = $objeto['efecto'];

			if ($objetoRequisitos != '') {
				$requisitos = "<div style=\"font-family: InterRegular;padding: 5px;text-align: justify;border-bottom: 1px solid #5e5e5e;\">$objetoRequisitos</div>";
			}

			if ($objetoEscalado != '') {
				$escalado = "<div style=\"font-family: InterRegular;padding: 5px;text-align: justify;border-bottom: 1px solid #5e5e5e;\">$objetoEscalado</div>";
			}

			if ($objetoDano != '') {
				$dano = "<div style=\"font-family: InterRegular;padding: 5px;text-align: justify;border-bottom: 1px solid #5e5e5e;\">$objetoDano</div>";
			}
			
			if ($objetoEfecto != '') {
				$efecto = "<div style=\"font-family: InterRegular;padding: 5px;text-align: center;border-top: 1px solid #5e5e5e;\">$objetoEfecto</div>";
			}
			
			$img_type = 'jpg';
			
			if ($objetoSubcategoria == 'tecnicas' || $objetoSubcategoria == 'tecnicas' || $objetoSubcategoria == 'tecnicas') { $img_type = 'gif'; }

			$imagen_nombre = "$objetoSubcategoria" . "_" . "$objetoImagenId" . "_One_Piece_Gaiden_Foro_Rol.$img_type";
			$imagenAvatar = "/images/op/iconos/$imagen_nombre";
			if ($objetoImagenAvatar != '') {
				$imagenAvatar = $objetoImagenAvatar;
			}
			
			$objetoHtml = "
				<div>
					<div class=\"item-outer\" style=\" margin: auto; \">
						<div class=\"item-nombre\">$objetoNombre</div>
						<div class=\"item-image\">
						
						<div class=\"tooltip\">
							<img id=\"imagen-item\" src=\"$imagenAvatar\" />
							<div class=\"tooltiptext item-tooltip\" style=\"top: 77px; left: -158px;\">
							
								<div style=\"font-size: 15px;font-family: moonGetHeavy;letter-spacing: 1px;text-align: center;background: $colorTier;border-top-left-radius: 6px;border: 0px;border-top-right-radius: 6px;padding: 3px;text-shadow: 1px 1px 3px black;\">$objetoNombre ($objeto_id)</div>

								$requisitos
								$escalado
								<div class=\"mydescripcion\" style=\"font-family: InterRegular;padding: 5px;text-align: justify;\">$objetoDescripcion</div>
								$dano	
								$efecto

								<div style=\"font-size: 9px;font-family: moonGetHeavy;letter-spacing: 1px;text-align: center;background: $colorTier;border-bottom-left-radius: 6px;border: 0px;border-bottom-right-radius: 6px;padding: 3px;text-shadow: 1px 1px 3px black;\">$objetoSubcategoriaPretty - Tier $objetoTier</div>

							</div>
						</div>

						</div>	
					</div>
				</div>
		
			";

			$objeto_content = "<hr><div style=\"text-align: center;\">Código de Objeto <strong>$objeto_id</strong>.$objetoHtml</div><hr>";

			$message = preg_replace("#\[objeto=$objeto_id\]#si","$objeto_content",$message, 1);
			// $message = preg_replace("#\[objeto=$objeto_id\]#si","[objetoinvalido=123]",$message, 1);
		} else {
			$message = preg_replace("#\[objeto=$objeto_id\]#si","[objetoinvalido=$objeto_id]",$message, 1);
		}

	}

	while(preg_match('#\[viaje=(.*?)\]#si',$message,$matches))
	{
		$viaje_id = $matches[1];
		$viaje = null;
		$has_viaje = false;

		$viaje_query = $db->query("SELECT * FROM `mybb_op_viajes` WHERE id='$viaje_id'");
		while ($q = $db->fetch_array($viaje_query)) { $has_viaje = true; $viaje = $q; }

		if ($has_viaje) {

			$viajeHtml = "ID de Viaje: <strong>$viaje_id</strong><br>" . $viaje['log'];

			$viaje_content = "<div style=\"text-align: left;border: 2px solid #3f4a9780;border-style: dotted;padding: 8px;border-radius: 4px;background-color: #86878f2e;\">$viajeHtml</div>";
			
			$message = preg_replace("#\[viaje=$viaje_id\]#si","$viaje_content",$message);
		} else {
			$message = preg_replace("#\[viaje=$viaje_id\]#si","[viajeinvalido=$viaje_id]",$message);
		}

		// $message = preg_replace("#\[viaje=$viaje_id\]#si","[viajeinvalido=$viaje_id]",$message);
		
	}

	while(preg_match('#\[mapa\]#si',$message,$matches))
	{
		$tid = $post['tid'];
		
		// Obtener información del thread para acceder a su fid (isla)
		$query_thread = $db->query("SELECT fid FROM mybb_threads WHERE tid='$tid'");
		$thread = $db->fetch_array($query_thread);
		$isla_id = (int)$thread['fid'];
		
		if ($isla_id > 0) {
			// Buscar mapas en el directorio de la isla
			$mapas_dir = MYBB_ROOT . 'images/op/islas/' . $isla_id . '/';
			$mapa_url = '';
			
			if (is_dir($mapas_dir)) {
				$files = scandir($mapas_dir);
				foreach ($files as $file) {
					if ($file !== '.' && $file !== '..' && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
						$mapa_url = '/images/op/islas/' . $isla_id . '/' . $file;
						break; // Tomar el primer mapa encontrado
					}
				}
			}
			
			if ($mapa_url !== '') {
				// Obtener nombre de la isla
				$query_forum = $db->query("SELECT name FROM mybb_forums WHERE fid='$isla_id'");
				$forum = $db->fetch_array($query_forum);
				$isla_nombre = htmlspecialchars_uni($forum['name']);
				
				$mapa_content = "
					<div style='text-align: center; margin: 10px auto; max-width: 800px;'>
						<div style='background: linear-gradient(135deg, #ff7019 0%, #d15306 100%); 
									padding: 12px; 
									border-radius: 8px 8px 0 0; 
									font-family: moonGetHeavy; 
									font-size: 18px; 
									color: white; 
									text-shadow: 2px 2px 4px rgba(0,0,0,0.5);'>
							📍 Mapa de {$isla_nombre}
						</div>
						<div style='background: #ffbc88; padding: 10px; border-radius: 0 0 8px 8px;'>
							<a href='{$mapa_url}' target='_blank' style='display: block;'>
								<img src='{$mapa_url}' 
									 style='max-width: 100%; 
											height: auto; 
											border-radius: 6px; 
											box-shadow: 0 4px 8px rgba(0,0,0,0.3);
											cursor: pointer;
											transition: transform 0.2s;'
									 onmouseover='this.style.transform=\"scale(1.02)\"'
									 onmouseout='this.style.transform=\"scale(1)\"'
									 alt='Mapa de {$isla_nombre}'>
							</a>
						</div>
					</div>
				";
				
				$mapa_spoiler = "
					<div class='spoiler' style='border: none; background: transparent; box-shadow: none;'>
						<div class='spoiler_title'>
							<span class='spoiler_button' onclick=\"javascript: if(parentNode.parentNode.getElementsByTagName('div')[1].style.display == 'block'){ parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'none'; this.innerHTML='🗺️ Mapa de {$isla_nombre}'; } else { parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'block'; this.innerHTML='🗺️ Mapa de {$isla_nombre}'; }\" style='background: linear-gradient(135deg, #ff7019 0%, #d15306 100%); color: white; border: none; border-radius: 25px; padding: 12px 25px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s ease; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);'>🗺️ Mapa de {$isla_nombre}</span>
						</div>
						<div class='spoiler_content' style='display: none; background: transparent; border: none; box-shadow: none; margin-top: 10px;'>$mapa_content</div>
					</div>";
				
				$message = preg_replace("#\[mapa\]#si", $mapa_spoiler, $message, 1);
			} else {
				$mapa_error = "
					<div class='spoiler' style='border: none; background: transparent; box-shadow: none;'>
						<div class='spoiler_title'>
							<span class='spoiler_button' style='background: #999; color: white; border: none; border-radius: 25px; padding: 12px 25px; font-weight: bold; cursor: not-allowed; box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-shadow: 1px 1px 2px rgba(0,0,0,0.3);'>🗺️ Mapa no disponible</span>
						</div>
					</div>";
				$message = preg_replace("#\[mapa\]#si", $mapa_error, $message, 1);
			}
		} else {
			$mapa_error = "
				<div class='spoiler' style='border: none; background: transparent; box-shadow: none;'>
					<div class='spoiler_title'>
						<span class='spoiler_button' style='background: #999; color: white; border: none; border-radius: 25px; padding: 12px 25px; font-weight: bold; cursor: not-allowed; box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-shadow: 1px 1px 2px rgba(0,0,0,0.3);'>🗺️ Mapa - Ubicación no detectada</span>
				</div>
			</div>";
			$message = preg_replace("#\[mapa\]#si", $mapa_error, $message, 1);
		}
	}

	while(preg_match('#\[akuma\]#si',$message,$matches))
	{
		$uid = $post['uid'];
		// Set up the parser options.
		$parser_options = array(
			"allow_html" => 1,
			"allow_mycode" => 1,
			"allow_smilies" => 0,
			"allow_imgcode" => 0,
			"allow_videocode" => 0,
			"filter_badwords" => 0
		);

		$ficha = null;
		$query_ficha = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$uid' ");
		while ($q = $db->fetch_array($query_ficha)) { $ficha = $q; }

		$akuma = $ficha['akuma'];
		if ($akuma != '') {
			$akuma_db = null;
			$query_akumas = $db->query(" SELECT * FROM mybb_op_akumas WHERE nombre='$akuma' ");
			while ($q = $db->fetch_array($query_akumas)) { $akuma_db = $q; }
			$detalles = $akuma_db['detalles'];

			$akumaHtml = $parser->parse_message("$detalles", $parser_options);
			$message = preg_replace("#\[akuma\]#si","$akumaHtml",$message);
			// $message = preg_replace("#\[akuma\]#si","$detalles",$message);
		} else {
			$message = preg_replace("#\[akuma\]#si","[akumainvalida=notienes]",$message);
		}

	}

	while(preg_match('#\[virtudes\]#si',$message,$matches))
	{
		$uid = $post['uid'];
		
		$virtudes = null;
		$virtudesHtml = '';
		$defectosHtml = '';

		$query_virtudes = $db->query(" 
			SELECT mybb_op_virtudes_usuarios.virtud_id, mybb_op_virtudes.nombre, mybb_op_virtudes.descripcion
			FROM `mybb_op_virtudes_usuarios`
			INNER JOIN `mybb_op_virtudes` ON mybb_op_virtudes.virtud_id=mybb_op_virtudes_usuarios.virtud_id
			WHERE mybb_op_virtudes_usuarios.uid = $uid AND mybb_op_virtudes_usuarios.virtud_id LIKE 'V%'
			ORDER BY `mybb_op_virtudes`.`nombre` ASC;
		");

		$query_defectos = $db->query(" 
			SELECT mybb_op_virtudes_usuarios.virtud_id, mybb_op_virtudes.nombre, mybb_op_virtudes.descripcion
			FROM `mybb_op_virtudes_usuarios`
			INNER JOIN `mybb_op_virtudes` ON mybb_op_virtudes.virtud_id=mybb_op_virtudes_usuarios.virtud_id
			WHERE mybb_op_virtudes_usuarios.uid = $uid AND mybb_op_virtudes_usuarios.virtud_id LIKE 'D%'
			ORDER BY `mybb_op_virtudes`.`nombre` ASC;
		");

		while ($q = $db->fetch_array($query_virtudes)) { 
			$virtudes = $q; 
			$nombre = $q['nombre'];
			$descripcion = $q['descripcion'];

			$virtudesHtml .= "<span style='color: #008e02;font-weight: bold;'>$nombre</span>: $descripcion<br>";
		}

		$virtudesHtml .= "<br>";

		while ($q = $db->fetch_array($query_defectos)) { 
			$virtudes = $q; 
			$nombre = $q['nombre'];
			$descripcion = $q['descripcion'];

			$virtudesHtml .= "<span style='color: #c10300;font-weight: bold;'>$nombre</span>: $descripcion<br>";
		}

		$virtudes_message = "
			<div class='virtudes_container' style='border-radius: 15px; padding: 20px; margin: 10px 0; box-shadow: 0 8px 32px rgba(0,0,0,0.3); color: white; background-color: #2c2c2c;'>
				<div class='virtudes_header' style='text-align: center; margin-bottom: 20px;'>
					<h2 style='margin: 0; font-size: 24px; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); background: linear-gradient(45deg, #ffd700, #ffed4e); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;'>✨ VIRTUDES Y DEFECTOS ✨</h2>
				</div>
				<div class='virtudes_content' style='line-height: 1.6; font-size: 14px;'>
					$virtudesHtml
				</div>
			</div>
		";

		$virtudes_spoiler = "
			<div class='spoiler' style='border: none; background: transparent; box-shadow: none;'>
				<div class='spoiler_title'>
					<span class='spoiler_button' onclick=\"javascript: if(parentNode.parentNode.getElementsByTagName('div')[1].style.display == 'block'){ parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'none'; this.innerHTML='✨ VIRTUDES Y DEFECTOS ✨'; } else { parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'block'; this.innerHTML='✨ VIRTUDES Y DEFECTOS ✨'; }\" style='background-color: #2c2c2c; color: white; border: none; border-radius: 25px; padding: 12px 25px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s ease; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);'>✨ VIRTUDES Y DEFECTOS ✨</span>
				</div>
				<div class='spoiler_content' style='display: none; background: transparent; border: none; box-shadow: none; margin-top: 10px;'>$virtudes_message</div>
			</div>";

		$message = preg_replace("#\[virtudes\]#si","$virtudes_spoiler",$message);

	}

	while(preg_match('#\[pasivas\]#si',$message,$matches))
	{
		$uid = $post['uid'];
		$tid = $post['tid'];
		
		// Obtener el nombre del personaje
		$nombre = '';
		$thread_ficha = null;
		
		$query_personaje = $db->query("
			SELECT * FROM mybb_op_thread_personaje WHERE tid='$tid' AND uid='$uid'
		");

		while ($q = $db->fetch_array($query_personaje)) {
			$thread_ficha = $q;
		}

		if (!$thread_ficha) {
			$query_ficha = $db->query("
				SELECT * FROM mybb_op_fichas WHERE fid='$uid'
			");

			while ($q = $db->fetch_array($query_ficha)) {
				$nombre = $q['nombre'];
			}
		} else {
			$nombre = $thread_ficha['nombre'];
		}
		
		$tecnicas_pasivas = null;
		$pasivasHtml = '';

		$query_pasivas = $db->query(" 
			SELECT `mybb_op_tecnicas`.* 
			FROM `mybb_op_tecnicas` 
			INNER JOIN `mybb_op_tec_aprendidas` 
			ON `mybb_op_tecnicas`.`tid`=`mybb_op_tec_aprendidas`.`tid` 
			WHERE `mybb_op_tec_aprendidas`.`uid`='$uid' 
			AND (`mybb_op_tecnicas`.`estilo` LIKE '%pasiva%' 
				OR `mybb_op_tecnicas`.`clase` LIKE '%pasiva%' 
				OR `mybb_op_tecnicas`.`tipo` LIKE '%pasiva%')
			AND `mybb_op_tecnicas`.`nombre` NOT LIKE 'senda%'
			ORDER BY CAST(`mybb_op_tecnicas`.`tier` AS UNSIGNED) ASC, `mybb_op_tecnicas`.`nombre` ASC
		");

		while ($q = $db->fetch_array($query_pasivas)) {
			$tecnicas_pasivas = $q;
			$nombre_tecnica = $q['nombre'];
			$descripcion_tecnica = $q['descripcion'];
			$rama_tecnica = $q['rama'];
			$tier_tecnica = $q['tier'];
			$efectos_tecnica = $q['efectos'];
			$tid_tecnica = $q['tid'];

			// Construir el título del spoiler
			$titulo_spoiler = $nombre_tecnica;
			if ($rama_tecnica) {
				$titulo_spoiler .= " ($rama_tecnica)";
			}
			if ($tier_tecnica) {
				$titulo_spoiler .= " - Tier $tier_tecnica";
			}

			// Construir el contenido interno del spoiler
			$contenido_interno = "<div style='padding: 15px; background-color: #f8f9fa; border-radius: 8px; margin: 5px 0;'>";
			
			$contenido_interno .= "<div style='margin-bottom: 12px;'>
									<strong style='color: #9370db; font-size: 18px;'>$nombre_tecnica</strong>";
			
			if ($tid_tecnica) {
				$contenido_interno .= " <span style='color: #666; font-size: 12px; background-color: #e9ecef; padding: 2px 6px; border-radius: 4px; margin-left: 8px;'>ID: $tid_tecnica</span>";
			}
			
			$contenido_interno .= "</div>";

			if ($rama_tecnica) {
				$contenido_interno .= "<div style='margin-bottom: 10px;'>
										<strong style='color: #495057;'>Rama:</strong> 
										<span style='color: #6c757d; font-style: italic;'>$rama_tecnica</span>
									  </div>";
			}
			
			if ($tier_tecnica) {
				$contenido_interno .= "<div style='margin-bottom: 10px;'>
										<strong style='color: #495057;'>Tier:</strong> 
										<span style='color: #ff6600; font-weight: bold; background-color: #fff3cd; padding: 3px 8px; border-radius: 12px;'>$tier_tecnica</span>
									  </div>";
			}
			
			if ($efectos_tecnica) {
				$contenido_interno .= "<div style='margin-bottom: 12px; padding: 10px; background-color: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;'>
										<strong style='color: #1976d2;'>Efecto:</strong><br>
										<div style='color: #424242; margin-top: 5px; line-height: 1.5;'>$efectos_tecnica</div>
									  </div>";
			}
			
			if ($descripcion_tecnica) {
				$contenido_interno .= "<div style='margin-bottom: 8px; padding: 10px; background-color: #f3e5f5; border-left: 4px solid #9c27b0; border-radius: 4px;'>
										<strong style='color: #7b1fa2;'>Descripción:</strong><br>
										<div style='color: #424242; margin-top: 5px; line-height: 1.4; font-style: italic;'>$descripcion_tecnica</div>
									  </div>";
			}
			
			$contenido_interno .= "</div>";

			// Crear el spoiler individual para cada técnica
			$pasivasHtml .= "<div class='spoiler' style='border: 1px solid #2c2c2c; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); box-shadow: 0 2px 8px rgba(44, 44, 44, 0.2); margin-bottom: 10px; border-radius: 8px;'>
								<div class='spoiler_title'>
									<span class='spoiler_button' onclick=\"javascript: if(parentNode.parentNode.getElementsByTagName('div')[1].style.display == 'block'){ parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'none'; this.innerHTML='▶ $titulo_spoiler'; } else { parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'block'; this.innerHTML='▼ $titulo_spoiler'; }\" style='background: #2c2c2c; color: white; border: none; border-radius: 0px; padding: 10px 15px; font-weight: bold; cursor: pointer; box-shadow: 0 2px 6px rgba(44, 44, 44, 0.3); transition: all 0.3s ease; text-shadow: 1px 1px 2px rgba(0,0,0,0.2); width: 98%; text-align: left; display: block;'>▶ $titulo_spoiler</span>
								</div>
								<div class='spoiler_content' style='display: none; background: white; border: none; box-shadow: none; margin-top: 0px; border-radius: 0 0 0 0;'>$contenido_interno</div>
							</div>";
		}

		if (empty($pasivasHtml)) {
			$pasivasHtml = "<div style='text-align: center; color: #a0aec0; font-style: italic; padding: 40px; font-size: 16px;'>⚡ No tienes técnicas pasivas aprendidas ⚡</div>";
		}

		$pasivas_message = "
			<div class='pasivas_container' style='border-radius: 15px; padding: 20px; margin: 10px 0; box-shadow: 0 8px 32px rgba(0,0,0,0.3); color: white; background-color: #2c2c2c;'>
				<div class='pasivas_header' style='text-align: center; margin-bottom: 20px;'>
					<h2 style='margin: 0; font-size: 24px; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); background: linear-gradient(45deg, #ffd700, #ffed4e); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;'>⚡ TÉCNICAS PASIVAS ⚡</h2>
				</div>
				<div class='pasivas_content' style='line-height: 1.6; font-size: 14px;'>
					$pasivasHtml
				</div>
			</div>
		";

		$pasivas_spoiler = "
			<div class='spoiler' style='border: none; background: transparent; box-shadow: none;'>
				<div class='spoiler_title'>
					<span class='spoiler_button' onclick=\"javascript: if(parentNode.parentNode.getElementsByTagName('div')[1].style.display == 'block'){ parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'none'; this.innerHTML='⚡ PASIVAS $nombre'; } else { parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'block'; this.innerHTML='⚡ PASIVAS $nombre'; }\" style='background-color: #2c2c2c; color: white; border: none; border-radius: 25px; padding: 12px 25px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s ease; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);'>⚡ PASIVAS $nombre</span>
				</div>
				<div class='spoiler_content' style='display: none; background: transparent; border: none; box-shadow: none; margin-top: 10px;'>$pasivas_message</div>
			</div>";

		$message = preg_replace("#\[pasivas\]#si","$pasivas_spoiler",$message);

	}

	while(preg_match('#\[ficha\]#si',$message))
	{
		// DEBUG VISIBLE FUERA DEL SPOILER
		$message = str_replace('[ficha]', '<!-- FICHA BBCode detectado --><div style="background: #00ff00; color: #000; padding: 20px; margin: 20px; font-size: 20px; font-weight: bold; border: 5px solid #ff0000;">⚠️ PLUGIN EJECUTÁNDOSE - BBCode [ficha] DETECTADO ⚠️</div>[ficha]', $message);
		
		// DEBUG: Mostrar información visible
		$debug_info = "<div style=\"background: #ffeb3b; border: 2px solid #ff9800; padding: 10px; margin: 10px 0; color: #000;\"><strong>DEBUG FICHA:</strong><br>";
		
		try {
			$uid = $post['uid'];
			$tid = $post['tid'];
			$pid = $post['pid'];
			
			$debug_info .= "UID: $uid | TID: $tid | PID: $pid<br>";
			
			// ========== DATOS DEL PERSONAJE ==========
			$ficha = null;
			$thread_ficha = null;

			$has_vigoroso1 = false;
			$has_vigoroso1_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V037'; "); 
			while ($q = $db->fetch_array($has_vigoroso1_query)) { $has_vigoroso1 = true; }
		
			$has_vigoroso2 = false;
			$has_vigoroso2_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V038'; "); 
			while ($q = $db->fetch_array($has_vigoroso2_query)) { $has_vigoroso2 = true; }
		
			$has_vigoroso3 = false;
			$has_vigoroso3_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V039'; "); 
			while ($q = $db->fetch_array($has_vigoroso3_query)) { $has_vigoroso3 = true; }
		
			$has_hiperactivo1 = false;
			$has_hiperactivo1_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V040'; "); 
			while ($q = $db->fetch_array($has_hiperactivo1_query)) { $has_hiperactivo1 = true; }
		
			$has_hiperactivo2 = false;
			$has_hiperactivo2_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V041'; "); 
			while ($q = $db->fetch_array($has_hiperactivo2_query)) { $has_hiperactivo2 = true; }

			$has_espiritual1 = false;
			$has_espiritual1_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V058'; "); 
			while ($q = $db->fetch_array($has_espiritual1_query)) { $has_espiritual1 = true; }
		
			$has_espiritual2 = false;
			$has_espiritual2_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V059'; "); 
			while ($q = $db->fetch_array($has_espiritual2_query)) { $has_espiritual2 = true; }

			$query_personaje = $db->query("
				SELECT * FROM mybb_op_thread_personaje WHERE tid='$tid' AND uid='$uid'
			");

			while ($q = $db->fetch_array($query_personaje)) {
				$thread_ficha = $q;
			}
			
			$debug_info .= "Thread Ficha: " . ($thread_ficha ? "SÍ" : "NO") . "<br>";

			if (!$thread_ficha) {
				$debug_info .= "Buscando ficha general...<br>";
				
				$query_ficha = $db->query("
					SELECT * FROM mybb_op_fichas WHERE fid='$uid'
				");

				while ($q = $db->fetch_array($query_ficha)) {
					$ficha = $q;
				}
				
				$debug_info .= "Ficha encontrada: " . ($ficha ? "SÍ" : "NO") . "<br>";
				
				if (!$ficha) {
					$debug_info .= "<strong style='color: red;'>ERROR: No existe ficha en BD para UID $uid</strong></div>";
					$message = preg_replace('#\[ficha\]#si', $debug_info, $message, 1);
					continue;
				}
				
				$debug_info .= "Nombre: " . htmlspecialchars($ficha['nombre']) . " | Nivel: " . $ficha['nivel'] . "<br>";

				$nivel = $ficha['nivel'];
			$nombre = $ficha['nombre'];
			$fuerza = $ficha['fuerza'];
			$resistencia = $ficha['resistencia'];
			$destreza = $ficha['destreza'];
			$punteria = $ficha['punteria'];
			$agilidad = $ficha['agilidad'];
			$reflejos = $ficha['reflejos'];
			$voluntad = $ficha['voluntad'];
			$control_akuma = $ficha['control_akuma'];
			$vitalidad = $ficha['vitalidad'];
			$energia = $ficha['energia'];
			$haki = $ficha['haki'];
			$vitalidad_pasiva = $ficha['vitalidad_pasiva'];
			$energia_pasiva = $ficha['energia_pasiva'];
			$haki_pasiva = $ficha['haki_pasiva'];

			$fuerza_pasiva = $ficha['fuerza_pasiva'];
			$resistencia_pasiva = $ficha['resistencia_pasiva'];
			$destreza_pasiva = $ficha['destreza_pasiva'];
			$punteria_pasiva = $ficha['punteria_pasiva'];
			$agilidad_pasiva = $ficha['agilidad_pasiva'];
			$reflejos_pasiva = $ficha['reflejos_pasiva'];
			$voluntad_pasiva = $ficha['voluntad_pasiva'];
			$control_akuma_pasiva = $ficha['control_akuma_pasiva'];

			if ($tid && $pid) {
				$db->query(" 
					INSERT INTO `mybb_op_thread_personaje` (`tid`, `pid`, `uid`, `nombre`,
						`vitalidad`, `energia`, `haki`, 
						`fuerza`, `resistencia`, `destreza`, `punteria`, `agilidad`, 
						`reflejos`, `voluntad`, `control_akuma`,
						`fuerza_pasiva`, `resistencia_pasiva`, `destreza_pasiva`, `punteria_pasiva`, `agilidad_pasiva`, 
						`reflejos_pasiva`, `voluntad_pasiva`, `control_akuma_pasiva`, `vitalidad_pasiva`, `energia_pasiva`, `haki_pasiva`, `nivel`
						) 
					VALUES ('$tid', '$pid', '$uid', '$nombre',
						'$vitalidad', '$energia', '$haki', 
						'$fuerza', '$resistencia', '$destreza', '$punteria', '$agilidad', 
						'$reflejos', '$voluntad', '$control_akuma',
						'$fuerza_pasiva', '$resistencia_pasiva', '$destreza_pasiva', '$punteria_pasiva', '$agilidad_pasiva', 
						'$reflejos_pasiva', '$voluntad_pasiva', '$control_akuma_pasiva', '$vitalidad_pasiva', '$energia_pasiva', '$haki_pasiva', '$nivel'
					);
				");
			}

		} else {
			$nivel = $thread_ficha['nivel'];
			$nombre = $thread_ficha['nombre'];
			$fuerza = $thread_ficha['fuerza'];
			$resistencia = $thread_ficha['resistencia'];
			$destreza = $thread_ficha['destreza'];
			$punteria = $thread_ficha['punteria'];
			$agilidad = $thread_ficha['agilidad'];
			$reflejos = $thread_ficha['reflejos'];
			$voluntad = $thread_ficha['voluntad'];
			$control_akuma = $thread_ficha['control_akuma'];
			$vitalidad = $thread_ficha['vitalidad'];
			$energia = $thread_ficha['energia'];
			$haki = $thread_ficha['haki'];
			$vitalidad_pasiva = $thread_ficha['vitalidad_pasiva'];
			$energia_pasiva = $thread_ficha['energia_pasiva'];
			$haki_pasiva = $thread_ficha['haki_pasiva'];

			$fuerza_pasiva = $thread_ficha['fuerza_pasiva'];
			$resistencia_pasiva = $thread_ficha['resistencia_pasiva'];
			$destreza_pasiva = $thread_ficha['destreza_pasiva'];
			$punteria_pasiva = $thread_ficha['punteria_pasiva'];
			$agilidad_pasiva = $thread_ficha['agilidad_pasiva'];
			$reflejos_pasiva = $thread_ficha['reflejos_pasiva'];
			$voluntad_pasiva = $thread_ficha['voluntad_pasiva'];
			$control_akuma_pasiva = $thread_ficha['control_akuma_pasiva'];
		}

		$vitalidad_extra = 0;
		$energia_extra = 0;
		$haki_extra = 0;

		$vitalidad_extra = floor(((intval($fuerza_pasiva) * 6) + (intval($resistencia_pasiva) * 15) + (intval($destreza_pasiva) * 4) +
		intval($agilidad_pasiva * 3) + (intval($voluntad_pasiva) * 1)) + (intval($punteria_pasiva) * 2) + (intval($reflejos_pasiva) * 1));

		$energia_extra = floor(((intval($destreza_pasiva) * 4) + (intval($agilidad_pasiva) * 5) + (intval($voluntad_pasiva) * 1)) + 
		(intval($fuerza_pasiva) * 2) + (intval($resistencia_pasiva) * 4) + (intval($punteria_pasiva) * 5) + (intval($reflejos_pasiva) * 1));

		$haki_extra = floor(intval($voluntad_pasiva) * 10);

		$fuerza_completa = intval($fuerza) + (intval($fuerza_pasiva) ? intval($fuerza_pasiva) : 0);
		$resistencia_completa = intval($resistencia) + (intval($resistencia_pasiva) ? intval($resistencia_pasiva) : 0);
		$destreza_completa = intval($destreza) + (intval($destreza_pasiva) ? intval($destreza_pasiva) : 0);
		$punteria_completa = intval($punteria) + (intval($punteria_pasiva) ? intval($punteria_pasiva) : 0);
		$agilidad_completa = intval($agilidad) + (intval($agilidad_pasiva) ? intval($agilidad_pasiva) : 0);
		$reflejos_completa = intval($reflejos) + (intval($reflejos_pasiva) ? intval($reflejos_pasiva) : 0);
		$voluntad_completa = intval($voluntad) + (intval($voluntad_pasiva) ? intval($voluntad_pasiva) : 0);
		$control_akuma_completa = intval($control_akuma) + (intval($control_akuma_pasiva) ? intval($control_akuma_pasiva) : 0);

		$vitalidad_completa = intval($vitalidad) + $vitalidad_extra + $vitalidad_pasiva;
		$energia_completa = intval($energia) + $energia_extra + $energia_pasiva;
		$haki_completo = intval($haki) + $haki_extra + $haki_pasiva;

		if ($has_vigoroso1) {
			$vitalidad_completa += intval($nivel) * 10; 
		} else if ($has_vigoroso2) {
			$vitalidad_completa += intval($nivel) * 15; 
		} else if ($has_vigoroso3) {
			$vitalidad_completa += intval($nivel) * 20; 
		}
	
		if ($has_hiperactivo1) {
			$energia_completa += intval($nivel) * 10; 
		} else if ($has_hiperactivo2) {
			$energia_completa += intval($nivel) * 15; 
		} 

		if ($has_espiritual1) {
			$haki_completo += intval($nivel) * 5;
		} else if ($has_espiritual2) {
			$haki_completo += intval($nivel) * 10;
		}

		// ========== EQUIPAMIENTO ==========
		$equipamiento = null;
		$equipamiento_json = "";

		$query_equipamiento = $db->query("
			SELECT * FROM mybb_op_equipamiento_personaje WHERE tid='$tid' AND uid='$uid'
		");

		while ($q = $db->fetch_array($query_equipamiento)) {
			$equipamiento = $q;
		}

		if (!$equipamiento) {
			if (!$ficha) {
				$query_ficha = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$uid' ");
				while ($q = $db->fetch_array($query_ficha)) { $ficha = $q; }
			}
			$equipamiento_json = $ficha['equipamiento'];

			if ($tid && $pid) {
				$db->query(" 
					INSERT INTO `mybb_op_equipamiento_personaje` (`tid`, `pid`, `uid`, `equipamiento`) 
					VALUES ('$tid', '$pid', '$uid', '$equipamiento_json');
				");
			}
		} else {
			$equipamiento_json = $equipamiento['equipamiento'];
		}

		// Procesar equipamiento y colocar objetos en sus posiciones
		$equipamiento_data = json_decode($equipamiento_json, true);
		
		// Inicializar slots
		$slot_bolsa_html = "<span style='color: rgba(255,255,255,0.5); font-size: 12px;'>Vacío</span>";
		$slot_ropa_html = "<span style='color: rgba(255,255,255,0.5); font-size: 12px;'>Vacío</span>";
		$espacios_html = "";
		$espacios_usados = 0;
		
		// Procesar bolsa
		if ($equipamiento_data && isset($equipamiento_data['bolsa']) && !empty($equipamiento_data['bolsa'])) {
			$bolsa_id = $equipamiento_data['bolsa'];
			$bolsa_query = $db->query("SELECT nombre FROM mybb_op_objetos WHERE objeto_id='$bolsa_id'");
			$bolsa_nombre = $bolsa_id;
			while ($q = $db->fetch_array($bolsa_query)) { $bolsa_nombre = $q['nombre']; }
			$slot_bolsa_html = "
				<div style='display: flex; flex-direction: column; align-items: center;'>
					<div style='font-size: 10px; color: #ffd700; font-weight: bold; margin-bottom: 2px;'>$bolsa_id</div>
					<div style='font-size: 9px; color: white; text-align: center; line-height: 12px;'>$bolsa_nombre</div>
				</div>
			";
		}
		
		// Procesar ropa
		if ($equipamiento_data && isset($equipamiento_data['ropa']) && !empty($equipamiento_data['ropa'])) {
			$ropa_id = $equipamiento_data['ropa'];
			$ropa_query = $db->query("SELECT nombre FROM mybb_op_objetos WHERE objeto_id='$ropa_id'");
			$ropa_nombre = $ropa_id;
			while ($q = $db->fetch_array($ropa_query)) { $ropa_nombre = $q['nombre']; }
			$slot_ropa_html = "
				<div style='display: flex; flex-direction: column; align-items: center;'>
					<div style='font-size: 10px; color: #ffd700; font-weight: bold; margin-bottom: 2px;'>$ropa_id</div>
					<div style='font-size: 9px; color: white; text-align: center; line-height: 12px;'>$ropa_nombre</div>
				</div>
			";
		}
		
		// Procesar espacios de equipamiento
		if ($equipamiento_data && isset($equipamiento_data['espacios']) && is_array($equipamiento_data['espacios'])) {
			foreach ($equipamiento_data['espacios'] as $espacio_id => $objeto_info) {
				if (isset($objeto_info['objetoId'])) {
					$objeto_id = $objeto_info['objetoId'];
					$objeto_query = $db->query("SELECT nombre, espacios FROM mybb_op_objetos WHERE objeto_id='$objeto_id'");
					$objeto_nombre = $objeto_id;
					$objeto_espacios = 1; // Valor por defecto si no existe el campo espacios
					while ($q = $db->fetch_array($objeto_query)) { 
						$objeto_nombre = $q['nombre']; 
						$objeto_espacios = isset($q['espacios']) && is_numeric($q['espacios']) ? intval($q['espacios']) : 1;
					}
					$espacios_html .= "
						<div class='objeto_equipado' style='background: rgba(255,255,255,0.1); border: 2px solid #4a90e2; border-radius: 8px; padding: 8px; text-align: center; backdrop-filter: blur(5px);'>
							<div style='font-size: 8px; color: #ffd700; font-weight: bold; margin-bottom: 2px;'>$objeto_id</div>
							<div style='font-size: 9px; color: white; font-weight: bold; margin-bottom: 2px;'>$objeto_nombre</div>
							<div style='font-size: 8px; color: rgba(255,255,255,0.8);'>Espacio $espacio_id ($objeto_espacios esp.)</div>
						</div>
					";
					$espacios_usados += $objeto_espacios;
				}
			}
		}
		
		if (empty($espacios_html)) {
			$espacios_html = "<div style='grid-column: 1 / -1; text-align: center; color: rgba(255,255,255,0.5); font-style: italic; padding: 20px;'>No hay objetos equipados</div>";
		}

		// ========== VIRTUDES Y DEFECTOS ==========
		$virtudes_html_ficha = '';

		$query_virtudes_ficha = $db->query(" 
			SELECT mybb_op_virtudes_usuarios.virtud_id, mybb_op_virtudes.nombre, mybb_op_virtudes.descripcion
			FROM `mybb_op_virtudes_usuarios`
			INNER JOIN `mybb_op_virtudes` ON mybb_op_virtudes.virtud_id=mybb_op_virtudes_usuarios.virtud_id
			WHERE mybb_op_virtudes_usuarios.uid = $uid AND mybb_op_virtudes_usuarios.virtud_id LIKE 'V%'
			ORDER BY `mybb_op_virtudes`.`nombre` ASC;
		");

		$query_defectos_ficha = $db->query(" 
			SELECT mybb_op_virtudes_usuarios.virtud_id, mybb_op_virtudes.nombre, mybb_op_virtudes.descripcion
			FROM `mybb_op_virtudes_usuarios`
			INNER JOIN `mybb_op_virtudes` ON mybb_op_virtudes.virtud_id=mybb_op_virtudes_usuarios.virtud_id
			WHERE mybb_op_virtudes_usuarios.uid = $uid AND mybb_op_virtudes_usuarios.virtud_id LIKE 'D%'
			ORDER BY `mybb_op_virtudes`.`nombre` ASC;
		");

		while ($q = $db->fetch_array($query_virtudes_ficha)) { 
			$nombre_v = $q['nombre'];
			$descripcion_v = $q['descripcion'];
			$virtudes_html_ficha .= "<span style='color: #008e02;font-weight: bold;'>$nombre_v</span>: $descripcion_v<br>";
		}

		$virtudes_html_ficha .= "<br>";

		while ($q = $db->fetch_array($query_defectos_ficha)) { 
			$nombre_d = $q['nombre'];
			$descripcion_d = $q['descripcion'];
			$virtudes_html_ficha .= "<span style='color: #c10300;font-weight: bold;'>$nombre_d</span>: $descripcion_d<br>";
		}

		// Cerrar debug_info
		$debug_info .= "OK - Iniciando generación HTML</div>";

		// ========== GENERAR FICHA COMPLETA ==========
		$ficha_completa = <<<HTML
			<div class='ficha_container' style='border-radius: 15px; padding: 20px; margin: 10px 0; box-shadow: 0 8px 32px rgba(0,0,0,0.3); color: white; background-color: #2c2c2c;'>
				
				<!-- PERSONAJE -->
				<div class='ficha_personaje' style='margin-bottom: 30px;'>
					<div class='personaje_header' style='text-align: center; margin-bottom: 20px;'>
						<h2 style='margin: 0; font-size: 24px; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); background: linear-gradient(45deg, #ffd700, #ffed4e); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;'>⚔️ $nombre ⚔️</h2>
						<div style='margin-top: 5px; font-size: 16px; opacity: 0.9;'>
							<span style='background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; font-weight: bold;'>Nivel $nivel</span>
						</div>
					</div>
					
					<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 10px; margin: 20px 0; text-align: center;'>
						<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$fuerza_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>FUE</div>
						</div>
						<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$resistencia_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>RES</div>
						</div>
						<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$destreza_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>DES</div>
						</div>
						<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$punteria_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>PUN</div>
						</div>
						<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$agilidad_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>AGI</div>
						</div>
						<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$reflejos_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>REF</div>
						</div>
						<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$voluntad_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>VOL</div>
						</div>
					</div>
					
					<div style='margin-top: 20px;'>
						<div style='display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #00aa00; border-radius: 8px;'>
							<span style='font-weight: bold;'>❤️ Vitalidad:</span>
							<span style='font-size: 18px; font-weight: bold; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$vitalidad_completa</span>
						</div>
						<div style='display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #ff8800; border-radius: 8px;'>
							<span style='font-weight: bold;'>⚡ Energía:</span>
							<span style='font-size: 18px; font-weight: bold; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$energia_completa</span>
						</div>
						<div style='display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #0066ff; border-radius: 8px;'>
							<span style='font-weight: bold;'>🔮 Haki:</span>
							<span style='font-size: 18px; font-weight: bold; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$haki_completo</span>
						</div>
					</div>
				</div>

				<!-- EQUIPAMIENTO -->
				<div class='ficha_equipamiento' style='margin-bottom: 30px;'>
					<h3 style='text-align: center; color: #4a90e2; font-size: 20px; margin-bottom: 20px; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>⚔️ EQUIPAMIENTO</h3>
					
					<div style='display: flex; flex-direction: row; gap: 20px; justify-content: center; margin: 20px 0;'>
						<div style='display: flex; flex-direction: column; gap: 10px;'>
							<div style='display: flex; align-items: center; gap: 15px;'>
								<div style='min-width: 80px; font-weight: bold; color: #ffd700; font-size: 14px;'>BOLSA:</div>
								<div style='width: 120px; height: 80px; background: rgba(255,255,255,0.1); border: 2px solid #4a90e2; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-top: 50px;'>
									$slot_bolsa_html
								</div>
							</div>
							<div style='display: flex; align-items: center; gap: 15px;'>
								<div style='min-width: 80px; font-weight: bold; color: #ffd700; font-size: 14px;'>ROPA:</div>
								<div style='width: 120px; height: 80px; background: rgba(255,255,255,0.1); border: 2px solid #4a90e2; border-radius: 10px; display: flex; align-items: center; justify-content: center;'>
									$slot_ropa_html
								</div>
							</div>
						</div>
						
						<div style='flex: 1; min-width: 300px;'>
							<div style='text-align: center; margin-bottom: 15px;'>
								<h4 style='margin: 0; color: #4a90e2; font-size: 16px;'>ESPACIOS DE EQUIPAMIENTO</h4>
							</div>
							<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; max-height: 200px; overflow-y: auto; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 10px;'>
								$espacios_html
							</div>
							<div style='margin-top: 10px; text-align: center; font-size: 12px; color: #4a90e2;'>
								<strong>Espacios utilizados: $espacios_usados</strong>
							</div>
						</div>
					</div>
				</div>

				<!-- VIRTUDES Y DEFECTOS -->
				<div class='ficha_virtudes'>
					<h3 style='text-align: center; color: #4a90e2; font-size: 20px; margin-bottom: 20px; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>✨ VIRTUDES Y DEFECTOS</h3>
					<div style='line-height: 1.6; font-size: 14px; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 10px;'>
						$virtudes_html_ficha
					</div>
				</div>
			</div>
HTML;

		$ficha_spoiler = <<<HTML
			<div class="spoiler" style="border: none; background: transparent; box-shadow: none;">
				<div class="spoiler_title">
					<span class="spoiler_button" onclick="javascript: if(parentNode.parentNode.getElementsByTagName('div')[1].style.display == 'block'){ parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'none'; this.innerHTML='📋 $nombre'; } else { parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'block'; this.innerHTML='📋 $nombre'; }" style="background-color: #2c2c2c; color: white; border: none; border-radius: 25px; padding: 12px 25px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s ease; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">📋 $nombre</span>
				</div>
				<div class="spoiler_content" style="display: none; background: transparent; border: none; box-shadow: none; margin-top: 10px;">$debug_info$ficha_completa</div>
			</div>
HTML;

		$message = preg_replace('#\[ficha\]#si',$ficha_spoiler,$message);
		
		} catch (Exception $e) {
			$error_msg = "<div style=\"background: #f44336; color: white; padding: 10px; margin: 10px 0; border-radius: 5px;\"><strong>ERROR FICHA:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
			$message = preg_replace('#\[ficha\]#si', $error_msg, $message, 1);
		}

	}

	while(preg_match('#\[fichasecreta\]#si',$message))
	{
		$uid = $post['uid'];
		$tid = $post['tid'];
		$pid = $post['pid'];
		
		// ========== OBTENER NOMBRE SECRETO ==========
		static $secret_cache = array();
		$ficha_secreta = null;

		if (array_key_exists($uid, $secret_cache)) {
			$ficha_secreta = $secret_cache[$uid];
		} else {
			$query = $db->query(" SELECT * FROM mybb_op_fichas_secret WHERE fid='$uid' AND secret_number='1' LIMIT 1 ");
			while ($q = $db->fetch_array($query)) { $ficha_secreta = $q; }
			$secret_cache[$uid] = $ficha_secreta;
		}

		$secret_nombre = 'Personaje Secreto';
		if ($ficha_secreta) {
			$secret_nombre = trim($ficha_secreta['nombre']);
			if (empty($secret_nombre)) {
				$secret_nombre = 'Personaje Secreto';
			}
		}
		
		// ========== DATOS DEL PERSONAJE ==========
		$ficha = null;
		$thread_ficha = null;

		$has_vigoroso1 = false;
		$has_vigoroso1_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V037'; "); 
		while ($q = $db->fetch_array($has_vigoroso1_query)) { $has_vigoroso1 = true; }
	
		$has_vigoroso2 = false;
		$has_vigoroso2_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V038'; "); 
		while ($q = $db->fetch_array($has_vigoroso2_query)) { $has_vigoroso2 = true; }
	
		$has_vigoroso3 = false;
		$has_vigoroso3_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V039'; "); 
		while ($q = $db->fetch_array($has_vigoroso3_query)) { $has_vigoroso3 = true; }
	
		$has_hiperactivo1 = false;
		$has_hiperactivo1_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V040'; "); 
		while ($q = $db->fetch_array($has_hiperactivo1_query)) { $has_hiperactivo1 = true; }
	
		$has_hiperactivo2 = false;
		$has_hiperactivo2_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V041'; "); 
		while ($q = $db->fetch_array($has_hiperactivo2_query)) { $has_hiperactivo2 = true; }

		$has_espiritual1 = false;
		$has_espiritual1_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V058'; "); 
		while ($q = $db->fetch_array($has_espiritual1_query)) { $has_espiritual1 = true; }
	
		$has_espiritual2 = false;
		$has_espiritual2_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$uid' AND virtud_id='V059'; "); 
		while ($q = $db->fetch_array($has_espiritual2_query)) { $has_espiritual2 = true; }

		$query_personaje = $db->query("
			SELECT * FROM mybb_op_thread_personaje WHERE tid='$tid' AND uid='$uid'
		");

		while ($q = $db->fetch_array($query_personaje)) {
			$thread_ficha = $q;
		}

		if (!$thread_ficha) {
			$query_ficha = $db->query("
				SELECT * FROM mybb_op_fichas WHERE fid='$uid'
			");

			while ($q = $db->fetch_array($query_ficha)) {
				$ficha = $q;
			}

			$nivel = $ficha['nivel'];
			$nombre = $ficha['nombre'];
			$fuerza = $ficha['fuerza'];
			$resistencia = $ficha['resistencia'];
			$destreza = $ficha['destreza'];
			$punteria = $ficha['punteria'];
			$agilidad = $ficha['agilidad'];
			$reflejos = $ficha['reflejos'];
			$voluntad = $ficha['voluntad'];
			$control_akuma = $ficha['control_akuma'];
			$vitalidad = $ficha['vitalidad'];
			$energia = $ficha['energia'];
			$haki = $ficha['haki'];
			$vitalidad_pasiva = $ficha['vitalidad_pasiva'];
			$energia_pasiva = $ficha['energia_pasiva'];
			$haki_pasiva = $ficha['haki_pasiva'];

			$fuerza_pasiva = $ficha['fuerza_pasiva'];
			$resistencia_pasiva = $ficha['resistencia_pasiva'];
			$destreza_pasiva = $ficha['destreza_pasiva'];
			$punteria_pasiva = $ficha['punteria_pasiva'];
			$agilidad_pasiva = $ficha['agilidad_pasiva'];
			$reflejos_pasiva = $ficha['reflejos_pasiva'];
			$voluntad_pasiva = $ficha['voluntad_pasiva'];
			$control_akuma_pasiva = $ficha['control_akuma_pasiva'];

			if ($tid && $pid) {
				$db->query(" 
					INSERT INTO `mybb_op_thread_personaje` (`tid`, `pid`, `uid`, `nombre`,
						`vitalidad`, `energia`, `haki`, 
						`fuerza`, `resistencia`, `destreza`, `punteria`, `agilidad`, 
						`reflejos`, `voluntad`, `control_akuma`,
						`fuerza_pasiva`, `resistencia_pasiva`, `destreza_pasiva`, `punteria_pasiva`, `agilidad_pasiva`, 
						`reflejos_pasiva`, `voluntad_pasiva`, `control_akuma_pasiva`, `vitalidad_pasiva`, `energia_pasiva`, `haki_pasiva`, `nivel`
						) 
					VALUES ('$tid', '$pid', '$uid', '$nombre',
						'$vitalidad', '$energia', '$haki', 
						'$fuerza', '$resistencia', '$destreza', '$punteria', '$agilidad', 
						'$reflejos', '$voluntad', '$control_akuma',
						'$fuerza_pasiva', '$resistencia_pasiva', '$destreza_pasiva', '$punteria_pasiva', '$agilidad_pasiva', 
						'$reflejos_pasiva', '$voluntad_pasiva', '$control_akuma_pasiva', '$vitalidad_pasiva', '$energia_pasiva', '$haki_pasiva', '$nivel'
					);
				");
			}

		} else {
			$nivel = $thread_ficha['nivel'];
			$nombre = $thread_ficha['nombre'];
			$fuerza = $thread_ficha['fuerza'];
			$resistencia = $thread_ficha['resistencia'];
			$destreza = $thread_ficha['destreza'];
			$punteria = $thread_ficha['punteria'];
			$agilidad = $thread_ficha['agilidad'];
			$reflejos = $thread_ficha['reflejos'];
			$voluntad = $thread_ficha['voluntad'];
			$control_akuma = $thread_ficha['control_akuma'];
			$vitalidad = $thread_ficha['vitalidad'];
			$energia = $thread_ficha['energia'];
			$haki = $thread_ficha['haki'];
			$vitalidad_pasiva = $thread_ficha['vitalidad_pasiva'];
			$energia_pasiva = $thread_ficha['energia_pasiva'];
			$haki_pasiva = $thread_ficha['haki_pasiva'];

			$fuerza_pasiva = $thread_ficha['fuerza_pasiva'];
			$resistencia_pasiva = $thread_ficha['resistencia_pasiva'];
			$destreza_pasiva = $thread_ficha['destreza_pasiva'];
			$punteria_pasiva = $thread_ficha['punteria_pasiva'];
			$agilidad_pasiva = $thread_ficha['agilidad_pasiva'];
			$reflejos_pasiva = $thread_ficha['reflejos_pasiva'];
			$voluntad_pasiva = $thread_ficha['voluntad_pasiva'];
			$control_akuma_pasiva = $thread_ficha['control_akuma_pasiva'];
		}

		$vitalidad_extra = 0;
		$energia_extra = 0;
		$haki_extra = 0;

		$vitalidad_extra = floor(((intval($fuerza_pasiva) * 6) + (intval($resistencia_pasiva) * 15) + (intval($destreza_pasiva) * 4) +
		intval($agilidad_pasiva * 3) + (intval($voluntad_pasiva) * 1)) + (intval($punteria_pasiva) * 2) + (intval($reflejos_pasiva) * 1));

		$energia_extra = floor(((intval($destreza_pasiva) * 4) + (intval($agilidad_pasiva) * 5) + (intval($voluntad_pasiva) * 1)) + 
		(intval($fuerza_pasiva) * 2) + (intval($resistencia_pasiva) * 4) + (intval($punteria_pasiva) * 5) + (intval($reflejos_pasiva) * 1));

		$haki_extra = floor(intval($voluntad_pasiva) * 10);

		$fuerza_completa = intval($fuerza) + (intval($fuerza_pasiva) ? intval($fuerza_pasiva) : 0);
		$resistencia_completa = intval($resistencia) + (intval($resistencia_pasiva) ? intval($resistencia_pasiva) : 0);
		$destreza_completa = intval($destreza) + (intval($destreza_pasiva) ? intval($destreza_pasiva) : 0);
		$punteria_completa = intval($punteria) + (intval($punteria_pasiva) ? intval($punteria_pasiva) : 0);
		$agilidad_completa = intval($agilidad) + (intval($agilidad_pasiva) ? intval($agilidad_pasiva) : 0);
		$reflejos_completa = intval($reflejos) + (intval($reflejos_pasiva) ? intval($reflejos_pasiva) : 0);
		$voluntad_completa = intval($voluntad) + (intval($voluntad_pasiva) ? intval($voluntad_pasiva) : 0);
		$control_akuma_completa = intval($control_akuma) + (intval($control_akuma_pasiva) ? intval($control_akuma_pasiva) : 0);

		$vitalidad_completa = intval($vitalidad) + $vitalidad_extra + $vitalidad_pasiva;
		$energia_completa = intval($energia) + $energia_extra + $energia_pasiva;
		$haki_completo = intval($haki) + $haki_extra + $haki_pasiva;

		if ($has_vigoroso1) {
			$vitalidad_completa += intval($nivel) * 10; 
		} else if ($has_vigoroso2) {
			$vitalidad_completa += intval($nivel) * 15; 
		} else if ($has_vigoroso3) {
			$vitalidad_completa += intval($nivel) * 20; 
		}
	
		if ($has_hiperactivo1) {
			$energia_completa += intval($nivel) * 10; 
		} else if ($has_hiperactivo2) {
			$energia_completa += intval($nivel) * 15; 
		} 

		if ($has_espiritual1) {
			$haki_completo += intval($nivel) * 5;
		} else if ($has_espiritual2) {
			$haki_completo += intval($nivel) * 10;
		}

		// ========== EQUIPAMIENTO ==========
		$equipamiento = null;
		$equipamiento_json = "";

		$query_equipamiento = $db->query("
			SELECT * FROM mybb_op_equipamiento_personaje WHERE tid='$tid' AND uid='$uid'
		");

		while ($q = $db->fetch_array($query_equipamiento)) {
			$equipamiento = $q;
		}

		if (!$equipamiento) {
			if (!$ficha) {
				$query_ficha = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$uid' ");
				while ($q = $db->fetch_array($query_ficha)) { $ficha = $q; }
			}
			$equipamiento_json = $ficha['equipamiento'];

			if ($tid && $pid) {
				$db->query(" 
					INSERT INTO `mybb_op_equipamiento_personaje` (`tid`, `pid`, `uid`, `equipamiento`) 
					VALUES ('$tid', '$pid', '$uid', '$equipamiento_json');
				");
			}
		} else {
			$equipamiento_json = $equipamiento['equipamiento'];
		}

		// Procesar equipamiento y colocar objetos en sus posiciones
		$equipamiento_data = json_decode($equipamiento_json, true);
		
		// Inicializar slots
		$slot_bolsa_html = "<span style='color: rgba(255,255,255,0.5); font-size: 12px;'>Vacío</span>";
		$slot_ropa_html = "<span style='color: rgba(255,255,255,0.5); font-size: 12px;'>Vacío</span>";
		$espacios_html = "";
		$espacios_usados = 0;
		
		// Procesar bolsa
		if ($equipamiento_data && isset($equipamiento_data['bolsa']) && !empty($equipamiento_data['bolsa'])) {
			$bolsa_id = $equipamiento_data['bolsa'];
			$bolsa_query = $db->query("SELECT nombre FROM mybb_op_objetos WHERE objeto_id='$bolsa_id'");
			$bolsa_nombre = $bolsa_id;
			while ($q = $db->fetch_array($bolsa_query)) { $bolsa_nombre = $q['nombre']; }
			$slot_bolsa_html = "
				<div style='display: flex; flex-direction: column; align-items: center;'>
					<div style='font-size: 10px; color: #ffd700; font-weight: bold; margin-bottom: 2px;'>$bolsa_id</div>
					<div style='font-size: 9px; color: white; text-align: center; line-height: 12px;'>$bolsa_nombre</div>
				</div>
			";
		}
		
		// Procesar ropa
		if ($equipamiento_data && isset($equipamiento_data['ropa']) && !empty($equipamiento_data['ropa'])) {
			$ropa_id = $equipamiento_data['ropa'];
			$ropa_query = $db->query("SELECT nombre FROM mybb_op_objetos WHERE objeto_id='$ropa_id'");
			$ropa_nombre = $ropa_id;
			while ($q = $db->fetch_array($ropa_query)) { $ropa_nombre = $q['nombre']; }
			$slot_ropa_html = "
				<div style='display: flex; flex-direction: column; align-items: center;'>
					<div style='font-size: 10px; color: #ffd700; font-weight: bold; margin-bottom: 2px;'>$ropa_id</div>
					<div style='font-size: 9px; color: white; text-align: center; line-height: 12px;'>$ropa_nombre</div>
				</div>
			";
		}
		
		// Procesar espacios de equipamiento
		if ($equipamiento_data && isset($equipamiento_data['espacios']) && is_array($equipamiento_data['espacios'])) {
			foreach ($equipamiento_data['espacios'] as $espacio_id => $objeto_info) {
				if (isset($objeto_info['objetoId'])) {
					$objeto_id = $objeto_info['objetoId'];
					$objeto_query = $db->query("SELECT nombre, espacios FROM mybb_op_objetos WHERE objeto_id='$objeto_id'");
					$objeto_nombre = $objeto_id;
					$objeto_espacios = 1; // Valor por defecto si no existe el campo espacios
					while ($q = $db->fetch_array($objeto_query)) { 
						$objeto_nombre = $q['nombre']; 
						$objeto_espacios = isset($q['espacios']) && is_numeric($q['espacios']) ? intval($q['espacios']) : 1;
					}
					$espacios_html .= "
						<div class='objeto_equipado' style='background: rgba(255,255,255,0.1); border: 2px solid #4a90e2; border-radius: 8px; padding: 8px; text-align: center; backdrop-filter: blur(5px);'>
							<div style='font-size: 8px; color: #ffd700; font-weight: bold; margin-bottom: 2px;'>$objeto_id</div>
							<div style='font-size: 9px; color: white; font-weight: bold; margin-bottom: 2px;'>$objeto_nombre</div>
							<div style='font-size: 8px; color: rgba(255,255,255,0.8);'>Espacio $espacio_id ($objeto_espacios esp.)</div>
						</div>
					";
					$espacios_usados += $objeto_espacios;
				}
			}
		}
		
		if (empty($espacios_html)) {
			$espacios_html = "<div style='grid-column: 1 / -1; text-align: center; color: rgba(255,255,255,0.5); font-style: italic; padding: 20px;'>No hay objetos equipados</div>";
		}

		// ========== VIRTUDES Y DEFECTOS ==========
		$virtudes_html_ficha = '';

		$query_virtudes_ficha = $db->query(" 
			SELECT mybb_op_virtudes_usuarios.virtud_id, mybb_op_virtudes.nombre, mybb_op_virtudes.descripcion
			FROM `mybb_op_virtudes_usuarios`
			INNER JOIN `mybb_op_virtudes` ON mybb_op_virtudes.virtud_id=mybb_op_virtudes_usuarios.virtud_id
			WHERE mybb_op_virtudes_usuarios.uid = $uid AND mybb_op_virtudes_usuarios.virtud_id LIKE 'V%'
			ORDER BY `mybb_op_virtudes`.`nombre` ASC;
		");

		$query_defectos_ficha = $db->query(" 
			SELECT mybb_op_virtudes_usuarios.virtud_id, mybb_op_virtudes.nombre, mybb_op_virtudes.descripcion
			FROM `mybb_op_virtudes_usuarios`
			INNER JOIN `mybb_op_virtudes` ON mybb_op_virtudes.virtud_id=mybb_op_virtudes_usuarios.virtud_id
			WHERE mybb_op_virtudes_usuarios.uid = $uid AND mybb_op_virtudes_usuarios.virtud_id LIKE 'D%'
			ORDER BY `mybb_op_virtudes`.`nombre` ASC;
		");

		while ($q = $db->fetch_array($query_virtudes_ficha)) { 
			$nombre_v = $q['nombre'];
			$descripcion_v = $q['descripcion'];
			$virtudes_html_ficha .= "<span style='color: #008e02;font-weight: bold;'>$nombre_v</span>: $descripcion_v<br>";
		}

		$virtudes_html_ficha .= "<br>";

		while ($q = $db->fetch_array($query_defectos_ficha)) { 
			$nombre_d = $q['nombre'];
			$descripcion_d = $q['descripcion'];
			$virtudes_html_ficha .= "<span style='color: #c10300;font-weight: bold;'>$nombre_d</span>: $descripcion_d<br>";
		}

		// ========== GENERAR FICHA COMPLETA ==========
		$ficha_completa = "
			<div class='ficha_container' style='border-radius: 15px; padding: 20px; margin: 10px 0; box-shadow: 0 8px 32px rgba(0,0,0,0.3); color: white; background-color: #2c2c2c;'>
				
				<!-- PERSONAJE -->
				<div class='ficha_personaje' style='margin-bottom: 30px;'>
					<div class='personaje_header' style='text-align: center; margin-bottom: 20px;'>
						<h2 style='margin: 0; font-size: 24px; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); background: linear-gradient(45deg, #ffd700, #ffed4e); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;'>⚔️ $secret_nombre ⚔️</h2>
						<div style='margin-top: 5px; font-size: 16px; opacity: 0.9;'>
							<span style='background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; font-weight: bold;'>Nivel $nivel</span>
						</div>
					</div>
					
					<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 10px; margin: 20px 0; text-align: center;'>
						<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$fuerza_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>FUE</div>
						</div>
						<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$resistencia_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>RES</div>
						</div>
						<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$destreza_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>DES</div>
						</div>
						<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$punteria_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>PUN</div>
						</div>
						<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$agilidad_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>AGI</div>
						</div>
						<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$reflejos_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>REF</div>
						</div>
						<div style='background: rgba(255,255,255,0.1); border-radius: 8px; padding: 10px; backdrop-filter: blur(5px);'>
							<div style='font-size: 18px; font-weight: bold; color: white;'>$voluntad_completa</div>
							<div style='font-size: 12px; opacity: 0.8;'>VOL</div>
						</div>
					</div>
					
					<div style='margin-top: 20px;'>
						<div style='display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #00aa00; border-radius: 8px;'>
							<span style='font-weight: bold;'>❤️ Vitalidad:</span>
							<span style='font-size: 18px; font-weight: bold; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$vitalidad_completa</span>
						</div>
						<div style='display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #ff8800; border-radius: 8px;'>
							<span style='font-weight: bold;'>⚡ Energía:</span>
							<span style='font-size: 18px; font-weight: bold; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$energia_completa</span>
						</div>
						<div style='display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #0066ff; border-radius: 8px;'>
							<span style='font-weight: bold;'>🔮 Haki:</span>
							<span style='font-size: 18px; font-weight: bold; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$haki_completo</span>
						</div>
					</div>
				</div>

				<!-- EQUIPAMIENTO -->
				<div class='ficha_equipamiento' style='margin-bottom: 30px;'>
					<h3 style='text-align: center; color: #4a90e2; font-size: 20px; margin-bottom: 20px; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>⚔️ EQUIPAMIENTO</h3>
					
					<div style='display: flex; flex-direction: row; gap: 20px; justify-content: center; margin: 20px 0;'>
						<div style='display: flex; flex-direction: column; gap: 10px;'>
							<div style='display: flex; align-items: center; gap: 15px;'>
								<div style='min-width: 80px; font-weight: bold; color: #ffd700; font-size: 14px;'>BOLSA:</div>
								<div style='width: 120px; height: 80px; background: rgba(255,255,255,0.1); border: 2px solid #4a90e2; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-top: 50px;'>
									$slot_bolsa_html
								</div>
							</div>
							<div style='display: flex; align-items: center; gap: 15px;'>
								<div style='min-width: 80px; font-weight: bold; color: #ffd700; font-size: 14px;'>ROPA:</div>
								<div style='width: 120px; height: 80px; background: rgba(255,255,255,0.1); border: 2px solid #4a90e2; border-radius: 10px; display: flex; align-items: center; justify-content: center;'>
									$slot_ropa_html
								</div>
							</div>
						</div>
						
						<div style='flex: 1; min-width: 300px;'>
							<div style='text-align: center; margin-bottom: 15px;'>
								<h4 style='margin: 0; color: #4a90e2; font-size: 16px;'>ESPACIOS DE EQUIPAMIENTO</h4>
							</div>
							<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; max-height: 200px; overflow-y: auto; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 10px;'>
								$espacios_html
							</div>
							<div style='margin-top: 10px; text-align: center; font-size: 12px; color: #4a90e2;'>
								<strong>Espacios utilizados: $espacios_usados</strong>
							</div>
						</div>
					</div>
				</div>

				<!-- VIRTUDES Y DEFECTOS -->
				<div class='ficha_virtudes'>
					<h3 style='text-align: center; color: #4a90e2; font-size: 20px; margin-bottom: 20px; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>✨ VIRTUDES Y DEFECTOS</h3>
					<div style='line-height: 1.6; font-size: 14px; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 10px;'>
						$virtudes_html_ficha
					</div>
				</div>
			</div>
		";

		$ficha_spoiler = "
			<div class='spoiler' style='border: none; background: transparent; box-shadow: none;'>
				<div class='spoiler_title'>
					<span class='spoiler_button' onclick=\"javascript: if(parentNode.parentNode.getElementsByTagName('div')[1].style.display == 'block'){ parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'none'; this.innerHTML='📋 $secret_nombre'; } else { parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'block'; this.innerHTML='📋 $secret_nombre'; }\" style='background-color: #2c2c2c; color: white; border: none; border-radius: 25px; padding: 12px 25px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s ease; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);'>📋 $secret_nombre</span>
				</div>
				<div class='spoiler_content' style='display: none; background: transparent; border: none; box-shadow: none; margin-top: 10px;'>$ficha_completa</div>
			</div>";

		$message = preg_replace('#\[fichasecreta\]#si',$ficha_spoiler,$message);

	}

	while(preg_match('#\[npc=(.*?)\]#si',$message,$matches))
	{
		$npc_parameter = $matches[1];
		$npc_from_db = null;
		$npc_exists_in_db = false;
		
		// Verificar si es una URL o un ID de la base de datos
		if (!filter_var($npc_parameter, FILTER_VALIDATE_URL) && !strpos($npc_parameter, 'generador')) {
			// Es un ID de NPC de la base de datos, no una URL del generador
			$npc_query = $db->query("SELECT * FROM mybb_op_npcs WHERE npc_id='$npc_parameter'");
			while ($q = $db->fetch_array($npc_query)) { 
				$npc_from_db = $q; 
				$npc_exists_in_db = true; 
			}
			
			if ($npc_exists_in_db) {
				// Procesar campos ocultos
				if (substr($npc_from_db['nombre'], 0, 3) == "???") { $npc_from_db['nombre'] = '???'; }
				if (substr($npc_from_db['etiqueta'], 0, 3) == "???") { $npc_from_db['etiqueta'] = '???'; }
				if (substr($npc_from_db['extra'], 0, 3) == "???") { $npc_from_db['extra'] = '???'; }
				if (substr($npc_from_db['vitalidad'], 0, 3) == "???") { $npc_from_db['vitalidad'] = '???/???'; }
				if (substr($npc_from_db['energia'], 0, 3) == "???") { $npc_from_db['energia'] = '???/???'; }
				if (substr($npc_from_db['haki'], 0, 3) == "???") { $npc_from_db['haki'] = '???/???'; }
				
				// Estadísticas
				if (substr($npc_from_db['fuerza'], 0, 3) == "???") { $npc_from_db['fuerza'] = '???'; }
				if (substr($npc_from_db['resistencia'], 0, 3) == "???") { $npc_from_db['resistencia'] = '???'; }
				if (substr($npc_from_db['destreza'], 0, 3) == "???") { $npc_from_db['destreza'] = '???'; }
				if (substr($npc_from_db['agilidad'], 0, 3) == "???") { $npc_from_db['agilidad'] = '???'; }
				if (substr($npc_from_db['voluntad'], 0, 3) == "???") { $npc_from_db['voluntad'] = '???'; }
				if (substr($npc_from_db['reflejos'], 0, 3) == "???") { $npc_from_db['reflejos'] = '???'; }
				if (substr($npc_from_db['punteria'], 0, 3) == "???") { $npc_from_db['punteria'] = '???'; }

				$npc_content = "
				<div class='spoiler' style='border: none; background: transparent; box-shadow: none;'>
					<div class='spoiler_title'>
						<span class='spoiler_button' onclick=\"javascript: if(parentNode.parentNode.getElementsByTagName('div')[1].style.display == 'block'){ parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'none'; this.innerHTML='🎭 NPC: {$npc_from_db['nombre']}'; } else { parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'block'; this.innerHTML='🎭 NPC: {$npc_from_db['nombre']}'; }\" style='background: linear-gradient(135deg, #8B4513, #654321); color: white; border: 2px solid #ff7b00; border-radius: 25px; padding: 12px 25px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-shadow: 1px 1px 2px rgba(0,0,0,0.3);'>🎭 NPC: {$npc_from_db['nombre']}</span>
					</div>
					<div class='spoiler_content' style='display: none; background: transparent; border: none; box-shadow: none; margin-top: 10px;'>
						<div style='border: 2px solid #8B4513; border-radius: 10px; background: linear-gradient(135deg, #2c2c2c, #1a1a1a); padding: 15px; margin: 10px 0; color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.5);'>
							<div style='text-align: center; margin-bottom: 15px;'>
								<h3 style='color: #ff7b00; font-family: moonGetHeavy; font-size: 24px; margin: 0; text-shadow: 2px 2px 4px black;'>{$npc_from_db['nombre']}</h3>
								" . (!empty($npc_from_db['etiqueta']) && $npc_from_db['etiqueta'] != '???' ? "<div style='color: #cccccc; font-size: 14px; font-style: italic; margin-top: 5px;'>{$npc_from_db['etiqueta']}</div>" : "") . "
							</div>
							
							<div style='display: flex; justify-content: space-between; margin-bottom: 15px;'>
								<div style='width: 48%;'>
									<div style='background: rgba(255,123,0,0.2); padding: 10px; border-radius: 5px; border-left: 4px solid #ff7b00; margin-bottom: 10px;'>
										<h4 style='margin: 0 0 8px 0; color: #ff7b00; font-size: 16px;'>ESTADÍSTICAS</h4>
										<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 5px; font-size: 13px;'>
											<div><strong>FUE:</strong> {$npc_from_db['fuerza']}</div>
											<div><strong>RES:</strong> {$npc_from_db['resistencia']}</div>
											<div><strong>DES:</strong> {$npc_from_db['destreza']}</div>
											<div><strong>AGI:</strong> {$npc_from_db['agilidad']}</div>
											<div><strong>VOL:</strong> {$npc_from_db['voluntad']}</div>
											<div><strong>REF:</strong> {$npc_from_db['reflejos']}</div>
											<div style='grid-column: span 2;'><strong>PUN:</strong> {$npc_from_db['punteria']}</div>
										</div>
									</div>
								</div>
								
								<div style='width: 48%;'>
									<div style='background: rgba(0,150,136,0.2); padding: 10px; border-radius: 5px; border-left: 4px solid #009688; margin-bottom: 5px;'>
										<h4 style='margin: 0 0 5px 0; color: #009688; font-size: 14px;'>VITALIDAD</h4>
										<div style='font-size: 13px; text-align: center;'>{$npc_from_db['vitalidad']}</div>
									</div>
									<div style='background: rgba(255,152,0,0.2); padding: 10px; border-radius: 5px; border-left: 4px solid #ff9800; margin-bottom: 5px;'>
										<h4 style='margin: 0 0 5px 0; color: #ff9800; font-size: 14px;'>ENERGÍA</h4>
										<div style='font-size: 13px; text-align: center;'>{$npc_from_db['energia']}</div>
									</div>
									<div style='background: rgba(33,150,243,0.2); padding: 10px; border-radius: 5px; border-left: 4px solid #2196f3;'>
										<h4 style='margin: 0 0 5px 0; color: #2196f3; font-size: 14px;'>HAKI</h4>
										<div style='font-size: 13px; text-align: center;'>{$npc_from_db['haki']}</div>
									</div>
								</div>
							</div>
							
							" . (!empty($npc_from_db['extra']) && $npc_from_db['extra'] != '???' ? "
							<div style='background: rgba(139,69,19,0.2); padding: 10px; border-radius: 5px; border-left: 4px solid #8B4513;'>
								<h4 style='margin: 0 0 8px 0; color: #8B4513; font-size: 16px;'>INFORMACIÓN ADICIONAL</h4>
								<div style='font-size: 13px; line-height: 1.4;'>{$npc_from_db['extra']}</div>
							</div>
							" : "") . "
						</div>
					</div>
				</div>
				";

				$message = preg_replace("#\[npc=$npc_parameter\]#si", $npc_content, $message);
				continue; // Saltar al siguiente match
			} else {
				$message = preg_replace("#\[npc=$npc_parameter\]#si", "[npcinvalido=$npc_parameter]", $message);
				continue; // Saltar al siguiente match
			}
		}
		
		// Si llegamos aquí, es una URL del generador (código original)
		$url = $npc_parameter;
		
		// Extraer datos de la URL del generador de personajes
		$url_components = parse_url($url);
		$query_params = [];
		$npc_data = null;
		
		// Manejo mejorado para URLs con fragmento (#)
		if (isset($url_components['fragment'])) {
			// Si hay un fragmento, parsear desde ahí
			$fragment_parts = explode('?', $url_components['fragment']);
			if (count($fragment_parts) > 1) {
				parse_str($fragment_parts[1], $query_params);
			}
		} elseif (isset($url_components['query'])) {
			// Si no hay fragmento, usar query normal
			parse_str($url_components['query'], $query_params);
		}
		
		if (isset($query_params['data']) && !empty($query_params['data'])) {
			// Decodificar los datos (variantes soportadas: Base64 + URL encode, base64url, espacios por '+', etc.)
			$encoded_data = $query_params['data'];
			
			// Preparar posibles variantes a probar
			$candidates = array();
			// 1) urldecode then base64
			$candidates[] = base64_decode(urldecode($encoded_data));
			// 2) rawurldecode then base64
			$candidates[] = base64_decode(rawurldecode($encoded_data));
			// 3) replace spaces with + (caso en que '+' fue transformado en ' '), then urldecode
			$candidates[] = base64_decode(urldecode(str_replace(' ', '+', $encoded_data)));
			// 4) try direct base64 (por si ya viene sin urlencode)
			$candidates[] = base64_decode(str_replace(' ', '+', $encoded_data));
			
			// 5) Try base64url variant (RFC4648) -> replace - and _
			$base64url = strtr($encoded_data, '-_', '+/');
			$pad = strlen($base64url) % 4;
			if ($pad > 0) {
				$base64url .= str_repeat('=', 4 - $pad);
			}
			$candidates[] = base64_decode(urldecode($base64url));
			$candidates[] = base64_decode($base64url);
			
			// Intentar cada candidato y distintas conversiones de codificación si es necesario
			$npc_data = null;
			foreach ($candidates as $cand) {
				if ($cand === false || $cand === null) continue;
				// Limpiar BOM UTF-8 si existe
				$cand = preg_replace('/\xEF\xBB\xBF/', '', $cand);
				// Intentar parseo directo
				$try = json_decode($cand, true);
				if (is_array($try)) { $npc_data = $try; break; }
				// Si falla, intentar forzar codificación ISO-8859-1 -> UTF-8
				$try2 = json_decode(utf8_encode($cand), true);
				if (is_array($try2)) { $npc_data = $try2; break; }
				// Intentar convertir desde UTF-16LE/BE si parece texto binario
				if (strlen($cand) > 2) {
					// Detectar si contiene bytes nulos (posible UTF-16)
					if (strpos($cand, "\x00") !== false) {
						// Probar iconv
						$conv = @iconv('UTF-16', 'UTF-8', $cand);
						if ($conv !== false) {
							$try3 = json_decode($conv, true);
							if (is_array($try3)) { $npc_data = $try3; break; }
						}
					}
				}
			}
		}
		
		// Preview de primer candidato decodificado (si existe)
		$last_attempt_preview = '';
		if (isset($candidates) && is_array($candidates)) {
			foreach ($candidates as $c) {
				if ($c !== false && $c !== null) { $last_attempt_preview = substr(preg_replace('/\s+/', ' ', $c), 0, 120); break; }
			}
		}
		if ($npc_data && is_array($npc_data)) {
			// Obtener datos del NPC
			$nombre = isset($npc_data['nombre']) ? htmlspecialchars($npc_data['nombre']) : 'NPC Sin Nombre';
			$nivel = isset($npc_data['nivel']) ? intval($npc_data['nivel']) : 1;
			$oficio = isset($npc_data['oficio']) ? htmlspecialchars($npc_data['oficio']) : 'Navegante';
			$disciplina = isset($npc_data['disciplina']) ? htmlspecialchars($npc_data['disciplina']) : 'Combatiente';
			
			// Estadísticas base
			$estadisticas = isset($npc_data['estadisticas']) ? $npc_data['estadisticas'] : [];
			$niv = isset($estadisticas['NIV']) ? intval($estadisticas['NIV']) : $nivel;
			$fue = isset($estadisticas['FUE']) ? intval($estadisticas['FUE']) : 0;
			$agi = isset($estadisticas['AGI']) ? intval($estadisticas['AGI']) : 0;
			$pun = isset($estadisticas['PUN']) ? intval($estadisticas['PUN']) : 0;
			$des = isset($estadisticas['DES']) ? intval($estadisticas['DES']) : 0;
			$res = isset($estadisticas['RES']) ? intval($estadisticas['RES']) : 0;
			$ref = isset($estadisticas['REF']) ? intval($estadisticas['REF']) : 0;
			$vol = isset($estadisticas['VOL']) ? intval($estadisticas['VOL']) : 0;
			
			// Calcular estadísticas derivadas (usando las mismas fórmulas del sistema)
			$vida = ($agi * 3) + ($des * 4) + ($fue * 6) + ($pun * 1) + ($ref * 1) + ($res * 15) + ($vol * 1);
			$energia = ($agi * 4) + ($des * 3) + ($fue * 1) + ($pun * 6) + ($ref * 1) + ($res * 3) + ($vol * 1);
			$haki = ($vol * 10);
			
			// Armas equipadas
			$armas_html = '';
			$armas_data = isset($npc_data['armas']) ? $npc_data['armas'] : [];
			$armas_nombres = ['Principal', 'Secundaria', 'Terciaria'];
			
			for ($i = 0; $i < 3; $i++) {
				if (isset($armas_data[$i]) && !empty($armas_data[$i])) {
					// Buscar información del arma en la base de datos
					$arma_id = $armas_data[$i];
					$arma_info = null;
					$query_arma = $db->query("SELECT * FROM mybb_op_objetos WHERE objeto_id='$arma_id'");
					while ($q = $db->fetch_array($query_arma)) {
						$arma_info = $q;
					}
					
					if ($arma_info) {
						$arma_nombre = !empty($arma_info['apodo']) ? $arma_info['apodo'] : $arma_info['nombre'];
						$arma_tier = $arma_info['tier'];
						$arma_dano = $arma_info['dano'];
						
						// Color del tier
						$color_tier = '#808080';
						switch($arma_tier) {
							case '2': $color_tier = '#4dfe45'; break;
							case '3': $color_tier = '#457bfe'; break;
							case '4': $color_tier = '#cf44ff'; break;
							case '5': $color_tier = '#febb46'; break;
							default: if (intval($arma_tier) >= 6) { $color_tier = '#faa500'; } break;
						}
						
						$armas_html .= "
							<div style='margin: 5px 0; padding: 8px; background: rgba(255,255,255,0.1); border: 2px solid $color_tier; border-radius: 8px;'>
								<div style='font-weight: bold; color: $color_tier;'>{$armas_nombres[$i]}: $arma_nombre</div>
								<div style='font-size: 12px; color: rgba(255,255,255,0.8);'>Tier $arma_tier | $arma_dano</div>
							</div>
						";
					} else {
						$armas_html .= "
							<div style='margin: 5px 0; padding: 8px; background: rgba(255,255,255,0.1); border-radius: 8px;'>
								<div style='color: rgba(255,255,255,0.6);'>{$armas_nombres[$i]}: $arma_id (No encontrada)</div>
							</div>
						";
					}
				} else {
					$armas_html .= "
						<div style='margin: 5px 0; padding: 8px; background: rgba(255,255,255,0.05); border-radius: 8px;'>
							<div style='color: rgba(255,255,255,0.4);'>{$armas_nombres[$i]}: Sin equipar</div>
						</div>
					";
				}
			}
			
			// Técnica seleccionada
			$tecnica_html = "<div style='color: rgba(255,255,255,0.6); font-style: italic;'>Sin técnica seleccionada</div>";
			if (isset($npc_data['tecnica']) && !empty($npc_data['tecnica'])) {
				$tecnica_id = $npc_data['tecnica'];
				// Buscar técnica en la base de datos
				$tecnica_info = null;
				$query_tecnica = $db->query("SELECT * FROM mybb_op_tecnicas WHERE tid='$tecnica_id'");
				while ($q = $db->fetch_array($query_tecnica)) {
					$tecnica_info = $q;
				}
				
				if ($tecnica_info) {
					$tecnica_nombre = $tecnica_info['nombre'];
					$tecnica_clase = $tecnica_info['clase'];
					$tecnica_tier = $tecnica_info['tier'];
					$tecnica_html = "
						<div style='background: rgba(138, 43, 226, 0.2); padding: 8px; border-radius: 8px; border: 1px solid #8a2be2;'>
							<div style='font-weight: bold; color: #dda0dd;'>$tecnica_nombre</div>
							<div style='font-size: 12px; color: rgba(255,255,255,0.8);'>$tecnica_clase | Tier $tecnica_tier</div>
						</div>
					";
				} else {
					$tecnica_html = "<div style='color: rgba(255,255,255,0.6);'>$tecnica_id (Técnica no encontrada)</div>";
				}
			}
			
			// Virtudes y defectos
			$virtudes_html = '';
			$defectos_html = '';
			
			if (isset($npc_data['virtudes']) && is_array($npc_data['virtudes'])) {
				foreach ($npc_data['virtudes'] as $virtud_id) {
					if (empty($virtud_id) || $virtud_id === "0") continue; // Saltar virtudes vacías
					
					// Si es numérico, convertir a formato V con padding
					if (is_numeric($virtud_id)) {
						$virtud_id = 'V' . str_pad($virtud_id, 3, '0', STR_PAD_LEFT);
					}
					
					$virtud_info = null;
					$query_virtud = $db->query("SELECT * FROM mybb_op_virtudes WHERE virtud_id='$virtud_id'");
					while ($q = $db->fetch_array($query_virtud)) {
						$virtud_info = $q;
					}
					
					if ($virtud_info) {
						$virtudes_html .= "<span style='color: #008e02; font-weight: bold;'>" . $virtud_info['nombre'] . "</span>: " . $virtud_info['descripcion'] . "<br>";
					}
				}
			}
			
			if (isset($npc_data['defectos']) && is_array($npc_data['defectos'])) {
				foreach ($npc_data['defectos'] as $defecto_id) {
					if (empty($defecto_id) || $defecto_id === "0") continue; // Saltar defectos vacíos
					
					// Si es numérico, convertir a formato D con padding
					if (is_numeric($defecto_id)) {
						$defecto_id = 'D' . str_pad($defecto_id, 3, '0', STR_PAD_LEFT);
					}
					
					$defecto_info = null;
					$query_defecto = $db->query("SELECT * FROM mybb_op_virtudes WHERE virtud_id='$defecto_id'");
					while ($q = $db->fetch_array($query_defecto)) {
						$defecto_info = $q;
					}
					
					if ($defecto_info) {
						$defectos_html .= "<span style='color: #c10300; font-weight: bold;'>" . $defecto_info['nombre'] . "</span>: " . $defecto_info['descripcion'] . "<br>";
					}
				}
			}
			
			$virtudes_defectos_html = $virtudes_html . $defectos_html;
			if (empty($virtudes_defectos_html)) {
				$virtudes_defectos_html = "<div style='color: rgba(255,255,255,0.6); font-style: italic;'>Sin virtudes ni defectos</div>";
			}
			
			// Descripción del personaje
			$apariencia = isset($npc_data['apariencia']) ? nl2br(htmlspecialchars($npc_data['apariencia'])) : '';
			$personalidad = isset($npc_data['personalidad']) ? nl2br(htmlspecialchars($npc_data['personalidad'])) : '';
			$historia = isset($npc_data['historia']) ? nl2br(htmlspecialchars($npc_data['historia'])) : '';
			
			$descripcion_html = '';
			if (!empty($apariencia)) {
				$descripcion_html .= "<div style='margin-bottom: 15px;'><strong style='color: #4a90e2;'>Apariencia:</strong><br>$apariencia</div>";
			}
			if (!empty($personalidad)) {
				$descripcion_html .= "<div style='margin-bottom: 15px;'><strong style='color: #4a90e2;'>Personalidad:</strong><br>$personalidad</div>";
			}
			if (!empty($historia)) {
				$descripcion_html .= "<div style='margin-bottom: 15px;'><strong style='color: #4a90e2;'>Historia:</strong><br>$historia</div>";
			}
			
			if (empty($descripcion_html)) {
				$descripcion_html = "<div style='color: rgba(255,255,255,0.6); font-style: italic;'>Sin descripción</div>";
			}
			
			// Calcular valores máximos y porcentajes para barras
			$vida_max = ($res * 10) + 100;
			$energia_max = ($vol * 10) + 100; 
			$haki_max = ($vol * 5) + 50;
			
			$vida_porcentaje = min(100, ($vida / $vida_max) * 100);
			$energia_porcentaje = min(100, ($energia / $energia_max) * 100);
			$haki_porcentaje = min(100, ($haki / $haki_max) * 100);
			
			// Generar el HTML del NPC como spoiler desplegable
			$npc_spoiler = "
			<div class='spoiler' style='border: none; background: transparent; box-shadow: none;'>
				<div class='spoiler_title'>
					<span class='spoiler_button' onclick=\"javascript: if(parentNode.parentNode.getElementsByTagName('div')[1].style.display == 'block'){ parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'none'; this.innerHTML='🎭 NPC: $nombre'; } else { parentNode.parentNode.getElementsByTagName('div')[1].style.display = 'block'; this.innerHTML='🎭 NPC: $nombre'; }\" style='background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460); color: white; border: 2px solid #ffd700; border-radius: 25px; padding: 12px 25px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s ease; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);'>🎭 NPC: $nombre</span>
				</div>
				<div class='spoiler_content' style='display: none; background: transparent; border: none; box-shadow: none; margin-top: 10px;'>
					<div class='npc_container' style='border-radius: 15px; padding: 20px; margin: 10px 0; box-shadow: 0 8px 32px rgba(0,0,0,0.3); color: white; background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460); border: 2px solid #ffd700;'>
						<div class='npc_header' style='text-align: center; margin-bottom: 20px;'>
							<div style='margin-top: 8px; font-size: 16px; opacity: 0.9; display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;'>
								<span style='background: rgba(255,215,0,0.2); padding: 6px 15px; border-radius: 20px; font-weight: bold; border: 1px solid #ffd700;'>Nivel $nivel</span>
								<span style='background: rgba(70,130,180,0.2); padding: 6px 15px; border-radius: 20px; font-weight: bold; border: 1px solid #4682b4;'>$oficio</span>
								<span style='background: rgba(220,20,60,0.2); padding: 6px 15px; border-radius: 20px; font-weight: bold; border: 1px solid #dc143c;'>$disciplina</span>
							</div>
						</div>
						
						<div class='npc_stats_container' style='display: grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap: 12px; margin: 25px 0; text-align: center;'>
							<div style='background: rgba(255,255,255,0.1); border: 2px solid #ccc; border-radius: 10px; padding: 12px; backdrop-filter: blur(5px);'>
								<div style='font-size: 20px; font-weight: bold; color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$fue</div>
								<div style='font-size: 11px; opacity: 0.9; font-weight: bold;'>FUE</div>
							</div>
							<div style='background: rgba(255,255,255,0.1); border: 2px solid #ccc; border-radius: 10px; padding: 12px; backdrop-filter: blur(5px);'>
								<div style='font-size: 20px; font-weight: bold; color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$res</div>
								<div style='font-size: 11px; opacity: 0.9; font-weight: bold;'>RES</div>
							</div>
							<div style='background: rgba(255,255,255,0.1); border: 2px solid #ccc; border-radius: 10px; padding: 12px; backdrop-filter: blur(5px);'>
								<div style='font-size: 20px; font-weight: bold; color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$des</div>
								<div style='font-size: 11px; opacity: 0.9; font-weight: bold;'>DES</div>
							</div>
							<div style='background: rgba(255,255,255,0.1); border: 2px solid #ccc; border-radius: 10px; padding: 12px; backdrop-filter: blur(5px);'>
								<div style='font-size: 20px; font-weight: bold; color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$pun</div>
								<div style='font-size: 11px; opacity: 0.9; font-weight: bold;'>PUN</div>
							</div>
							<div style='background: rgba(255,255,255,0.1); border: 2px solid #ccc; border-radius: 10px; padding: 12px; backdrop-filter: blur(5px);'>
								<div style='font-size: 20px; font-weight: bold; color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$agi</div>
								<div style='font-size: 11px; opacity: 0.9; font-weight: bold;'>AGI</div>
							</div>
							<div style='background: rgba(255,255,255,0.1); border: 2px solid #ccc; border-radius: 10px; padding: 12px; backdrop-filter: blur(5px);'>
								<div style='font-size: 20px; font-weight: bold; color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$ref</div>
								<div style='font-size: 11px; opacity: 0.9; font-weight: bold;'>REF</div>
							</div>
							<div style='background: rgba(255,255,255,0.1); border: 2px solid #ccc; border-radius: 10px; padding: 12px; backdrop-filter: blur(5px);'>
								<div style='font-size: 20px; font-weight: bold; color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>$vol</div>
								<div style='font-size: 11px; opacity: 0.9; font-weight: bold;'>VOL</div>
							</div>
						</div>
						
						<div class='npc_vitals' style='margin: 25px 0; display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;'>
							<div style='text-align: center; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 12px; border: 2px solid #ccc; backdrop-filter: blur(10px);'>
								<div style='font-size: 14px; font-weight: bold; margin-bottom: 8px; color: #fff;'>❤️ VIDA</div>
								<div style='background: rgba(0,0,0,0.3); border-radius: 10px; overflow: hidden; height: 20px; margin-bottom: 5px;'>
									<div style='background: linear-gradient(90deg, #4CAF50, #8BC34A); height: 100%; width: {$vida_porcentaje}%; border-radius: 10px; transition: width 0.3s ease;'></div>
								</div>
								<div style='font-size: 12px; color: #fff; font-weight: bold;'>$vida</div>
							</div>
							<div style='text-align: center; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 12px; border: 2px solid #ccc; backdrop-filter: blur(10px);'>
								<div style='font-size: 14px; font-weight: bold; margin-bottom: 8px; color: #fff;'>⚡ ENERGÍA</div>
								<div style='background: rgba(0,0,0,0.3); border-radius: 10px; overflow: hidden; height: 20px; margin-bottom: 5px;'>
									<div style='background: linear-gradient(90deg, #FF9800, #FFC107); height: 100%; width: {$energia_porcentaje}%; border-radius: 10px; transition: width 0.3s ease;'></div>
								</div>
								<div style='font-size: 12px; color: #fff; font-weight: bold;'>$energia</div>
							</div>
							<div style='text-align: center; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 12px; border: 2px solid #ccc; backdrop-filter: blur(10px);'>
								<div style='font-size: 14px; font-weight: bold; margin-bottom: 8px; color: #fff;'>🔮 HAKI</div>
								<div style='background: rgba(0,0,0,0.3); border-radius: 10px; overflow: hidden; height: 20px; margin-bottom: 5px;'>
									<div style='background: linear-gradient(90deg, #2196F3, #03A9F4); height: 100%; width: {$haki_porcentaje}%; border-radius: 10px; transition: width 0.3s ease;'></div>
								</div>
								<div style='font-size: 12px; color: #fff; font-weight: bold;'>$haki</div>
							</div>
						</div>
						
						<div class='npc_equipment' style='margin: 25px 0;'>
							<h3 style='color: #ffd700; font-size: 18px; margin-bottom: 15px; text-align: center; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>⚔️ EQUIPAMIENTO</h3>
							<div style='background: rgba(0,0,0,0.2); padding: 15px; border-radius: 10px; backdrop-filter: blur(5px);'>
								$armas_html
							</div>
						</div>
						
						<div class='npc_technique' style='margin: 25px 0;'>
							<h3 style='color: #ffd700; font-size: 18px; margin-bottom: 15px; text-align: center; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>✨ TÉCNICA</h3>
							<div style='background: rgba(0,0,0,0.2); padding: 15px; border-radius: 10px; backdrop-filter: blur(5px);'>
								$tecnica_html
							</div>
						</div>
						
						<div class='npc_traits' style='margin: 25px 0;'>
							<h3 style='color: #ffd700; font-size: 18px; margin-bottom: 15px; text-align: center; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>🌟 VIRTUDES Y DEFECTOS</h3>
							<div style='background: rgba(0,0,0,0.2); padding: 15px; border-radius: 10px; backdrop-filter: blur(5px); line-height: 1.6;'>
								$virtudes_defectos_html
							</div>
						</div>
						
						<div class='npc_description' style='margin: 25px 0;'>
							<h3 style='color: #ffd700; font-size: 18px; margin-bottom: 15px; text-align: center; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);'>📜 DESCRIPCIÓN</h3>
							<div style='background: rgba(0,0,0,0.2); padding: 15px; border-radius: 10px; backdrop-filter: blur(5px); line-height: 1.6;'>
								$descripcion_html
							</div>
						</div>
						
						<div style='text-align: center; margin-top: 20px; font-size: 12px; color: rgba(255,255,255,0.6);'>
							<em>Generado desde el Generador de Personajes</em>
						</div>
					</div>
				</div>
			</div>
			";
			
			$message = preg_replace("#\[npc=" . preg_quote($url, '#') . "\]#si", $npc_spoiler, $message);
		} else {
			// Crear mensaje de debug temporal para entender qué está fallando
			$debug_info = "URL: " . substr($url, 0, 100) . "... | ";
			$debug_info .= "URL válida: " . (isset($url_components['host']) ? "Sí" : "No") . " | ";
			$debug_info .= "Tiene data: " . (isset($query_params['data']) ? "Sí" : "No") . " | ";
			$debug_info .= "JSON válido: " . (is_array($npc_data) ? "Sí" : "No");
			// Agregar preview del primer intento decodificado (si disponible)
			$debug_info .= " | LastDecoded: " . (isset($last_attempt_preview) && $last_attempt_preview ? $last_attempt_preview : "-");
			
			$message = preg_replace("#\[npc=" . preg_quote($url, '#') . "\]#si", "[npcinvalido=debug: $debug_info]", $message);
		}
	}

	while(preg_match('#\[akuma=(.*?)\]#si',$message,$matches))
	{
		$akuma_id = $matches[1];
		// Set up the parser options.
		$parser_options = array(
			"allow_html" => 1,
			"allow_mycode" => 1,
			"allow_smilies" => 0,
			"allow_imgcode" => 0,
			"allow_videocode" => 0,
			"filter_badwords" => 0
		);

		$akuma_existe = false;

		$akuma_db = null;
		$query_akumas = $db->query(" SELECT * FROM mybb_op_akumas WHERE akuma_id='$akuma_id' ");
		while ($q = $db->fetch_array($query_akumas)) { $akuma_existe = true; $akuma_db = $q; }

		if ($akuma_existe) {
			$detalles = $akuma_db['detalles'];

			$akumaHtml = $parser->parse_message("$detalles", $parser_options);
			$message = preg_replace("#\[akuma=$akuma_id\]#si","$akumaHtml",$message);
		} else {
			$message = preg_replace("#\[akuma=$akuma_id\]#si","[akumainvalida=$akuma_id]",$message);
		}
	}
	
	// return $message;
}

function codigos_rol_postbit_secret(&$post)
{
	global $db;

	if (!isset($post['message']) || stripos($post['message'], '[personajesecreto]') === false) {
		return;
	}

	$uid = isset($post['uid']) ? intval($post['uid']) : 0;
	if ($uid <= 0) {
		return;
	}

	static $secret_cache = array();
	$ficha_secreta = null;

	if (array_key_exists($uid, $secret_cache)) {
		$ficha_secreta = $secret_cache[$uid];
	} else {
		$query = $db->query(" SELECT * FROM mybb_op_fichas_secret WHERE fid='$uid' AND secret_number='1' LIMIT 1 ");
		while ($q = $db->fetch_array($query)) { $ficha_secreta = $q; }
		$secret_cache[$uid] = $ficha_secreta;
	}

	if (!$ficha_secreta) {
		$error_html = "<div class=\"op-ficha-secreta-error\" style=\"background:#c0392b;color:#fff;font-family:InterRegular,Arial,sans-serif;padding:10px;border-radius:6px;text-align:center;margin:10px 0;\">No tienes una ficha secreta configurada.</div>";
		$post['message'] = preg_replace('#\[personajesecreto\]#si',$error_html,$post['message'],1);
		return;
	}

	$secret_nombre = trim($ficha_secreta['nombre']);
	$secret_apodo = trim($ficha_secreta['apodo']);
	$secret_rango = trim($ficha_secreta['rango']);
	$secret_avatar = trim($ficha_secreta['avatar1']);
	$secret_avatar_alt = trim($ficha_secreta['avatar2']);

	if (!$secret_avatar && $secret_avatar_alt) {
		$secret_avatar = $secret_avatar_alt;
	}

	if (!$secret_avatar && !empty($post['avatar'])) {
		$secret_avatar = $post['avatar'];
	}

	$post['secret_identity_active'] = true;
	$post['secret_identity_nombre'] = $secret_nombre ? htmlspecialchars_uni($secret_nombre) : htmlspecialchars_uni($post['username']);
	$post['secret_identity_apodo'] = $secret_apodo ? htmlspecialchars_uni($secret_apodo) : htmlspecialchars_uni(isset($post['apodo']) ? $post['apodo'] : '');
	$post['secret_identity_rango'] = $secret_rango ? htmlspecialchars_uni($secret_rango) : htmlspecialchars_uni(isset($post['rango']) ? $post['rango'] : '');
	$post['secret_identity_avatar'] = $secret_avatar ? htmlspecialchars_uni($secret_avatar) : '';

	$post['message'] = preg_replace('#\[personajesecreto\]#si','',$post['message']);
}

function codigos_rol_newpost(&$data)
{
	global $db, $mybb, $post;

	$uid = $data->post_insert_data['uid'];
	$pid = $data->return_values['pid'];
	$tid = $data->post_insert_data['tid'];
	$username = $data->post_insert_data['username'];
	$my_uid = $data->post_insert_data['uid'];
	$message = $data->post_insert_data['message'];
	
	$dado_counter = 0;
	$consumir_counter = 0;
	$tiradanaval_counter = 0;

	while(preg_match('#\[dado=(.*?)\]#si',$message,$matches))
	{

		$dadosTexto = $matches[1];
		$dadosArr = explode("d", $dadosTexto);
		$dados = $dadosArr[0];
		$caras = $dadosArr[1];

		$outputText = "Dado inválido.";

		if (count($dadosArr) == 2 && is_int(intval($dados)) && is_int(intval($caras)) && intval($dados) <= 20 && intval($caras) <= 100000) {
			$dado_counter += 1;
			$outputText = "";
			$outputText .= "[ID $dado_counter] $username ha lanzado $dados dados de $caras caras. El resultado es: <br />";

			for ($x = 1; $x <= intval($dados); $x++) {
				$dadoResultado = rand(1, intval($caras));
				$outputText .= "- Dado $x: $dadoResultado<br />";
			}
			
			
			$message = preg_replace('#\[dado=(.*?)\]#si','[dado_guardado='.$dado_counter.']',$message, 1);
			$db->query(" 
				INSERT INTO `mybb_op_dados` (`tid`, `pid`, `uid`, `dado_counter`, `dado_content`) VALUES ('".$tid."','".$pid."','".$uid."', '".$dado_counter."', '".$outputText."');
			");
	
		} else {
			$message = preg_replace('#\[dado=(.*?)\]#si','[dadoinvalido='.$dado_counter.']',$message, 1);
		}

		if ($dado_counter > 0) {
			$db->query(" 
				UPDATE `mybb_posts` SET message='".$message."' WHERE pid='".$pid."';
			");
		}
	}

	while(preg_match('#\[tiradanaval=(.*?)\]#si',$message,$matches))
	{
		$tirada = $matches[1];
		$tirada_array = explode(",", $tirada);

		$mar = $tirada_array[0];
		$viento = $tirada_array[1];
		$clima = $tirada_array[2];
		$encuentro = $tirada_array[3];
		$kairoseki = $tirada_array[4];

		$hasKairoseki = $kairoseki == '1';

		function tirarDireccionViento() {
			$resultado = rand(1, 10);
			$direcciones = [
				"En calma", "Norte", "Noreste", "Este", "Sureste", 
				"Sur", "Suroeste", "Oeste", "Noroeste", "En calma"
			];
			return [
				"tirada" => $resultado,
				"resultado" => $direcciones[$resultado - 1]
			];
		}
		
		// Función para determinar el clima según el mar seleccionado
		function tirarClima($mar) {
			$tirada = rand(1, 100);
			$resultado = "Clima normal";
		
			switch($mar) {
				case "blue":
					$umbral = 75;
					break;
				case "paraiso":
					$umbral = 60;
					break;
				case "nuevo_mundo":
					$umbral = 40;
					break;
				case "calm_belt":
					$umbral = 95;
					break;
				default:
					$umbral = 75;
			}
		
			if ($tirada > $umbral) {
				$resultado = "Clima tormentoso";
			}
		
			return [
				"tirada" => $tirada,
				"resultado" => $resultado
			];
		}
		
		// Función para determinar encuentros especiales
		function tirarEncuentro($mar, $conKairouseki = false) {
			$tirada = rand(1, 100);
			$resultado = "Todo en calma";
		
			if ($mar === "blue") {
				if ($tirada >= 91 && $tirada <= 92) $resultado = "Aparición de navío pirata";
				elseif ($tirada >= 93 && $tirada <= 94) $resultado = "Aparición de buque marine";
				elseif ($tirada >= 95 && $tirada <= 96) $resultado = "Aparición de navío de facción predominante";
				elseif ($tirada >= 97 && $tirada <= 98) $resultado = "Basura/Naufragio/Iceberg...";
				elseif ($tirada === 99) $resultado = "Shichibukai/Vicealmirante/Comandante/CP9 1D4 = " . rand(1, 4);
				elseif ($tirada === 100) $resultado = "Yonkou/Almirante/CP0/Bestia Marina 1D4 = " . rand(1, 4);
			} elseif ($mar === "paraiso") {
				if ($tirada >= 81 && $tirada <= 87) $resultado = "Aparición de buque marine";
				elseif ($tirada >= 88 && $tirada <= 89) $resultado = "Aparición de navío pirata";
				elseif ($tirada >= 90 && $tirada <= 91) $resultado = "Aparición de barco Kuja";
				elseif ($tirada >= 92 && $tirada <= 93) $resultado = "Aparición de grupo Gyojin";
				elseif ($tirada >= 94 && $tirada <= 95) $resultado = "Aparición de Islote/Naufragio";
				elseif ($tirada === 96) $resultado = "Aparición de Bestia Marina";
				elseif ($tirada === 97) $resultado = "Shichibukai/Vicealmirante/Comandante/CP9 1D4 = " . rand(1, 4);
				elseif ($tirada === 98) $resultado = "Yonkou/Almirante/CP0/Bestia Marina 1D4 = " . rand(1, 4);
				elseif ($tirada === 99) $resultado = "Aparición de Rey Marino";
				elseif ($tirada === 100) $resultado = "Aparición de 2 Reyes Marinos";
			} elseif ($mar === "nuevo_mundo") {
				if ($tirada >= 71 && $tirada <= 80) $resultado = "Aparición de navío pirata";
				elseif ($tirada >= 81 && $tirada <= 84) $resultado = "Aparición de buque marine";
				elseif ($tirada === 85) $resultado = "Aparición de barco Mink";
				elseif ($tirada === 86) $resultado = "Aparición de " . rand(1, 10) . " Bestias Marinas";
				elseif ($tirada === 87) $resultado = "Zunesha crea maremoto y golpea el barco";
				elseif ($tirada === 88) $resultado = "Shichibukai/Vicealmirante/Comandante/CP9 1D4 = " . rand(1, 4);
				elseif ($tirada === 89) $resultado = "Almirante/CP0 1D2 = " . rand(1, 2);
				elseif ($tirada >= 90 && $tirada <= 96) $resultado = "Yonkou";
				elseif ($tirada >= 97 && $tirada <= 98) $resultado = "Aparición de Rey Marino";
				elseif ($tirada === 99) $resultado = "Aparición de 2 Reyes Marinos";
				elseif ($tirada === 100) $resultado = "Aparición de 3 Reyes Marinos";
			} elseif ($mar === "calm_belt") {
				if ($conKairouseki) {
					if ($tirada >= 91 && $tirada <= 94) $resultado = "Aparece 1 Rey Marino";
					elseif ($tirada >= 95 && $tirada <= 96) $resultado = "Aparecen 2 Reyes Marinos";
					elseif ($tirada >= 97 && $tirada <= 98) $resultado = "Aparecen 3 Reyes Marinos";
					elseif ($tirada >= 99) $resultado = "Aparecen " . rand(1, 20) . " Reyes Marinos";
				} else {
					if ($tirada >= 21 && $tirada <= 85) $resultado = "Aparece 1 Rey Marino";
					elseif ($tirada >= 86 && $tirada <= 90) $resultado = "Aparecen 2 Reyes Marinos";
					elseif ($tirada >= 91 && $tirada <= 95) $resultado = "Aparecen 3 Reyes Marinos";
					elseif ($tirada >= 96) $resultado = "Aparecen " . rand(1, 20) . " Reyes Marinos";
				}
			}
		
			return [
				"tirada" => $tirada,
				"resultado" => $resultado
			];
		}

		$hasValidMar = $mar == "blue" || $mar == "paraiso" || $mar == "nuevo_mundo" || $mar == "calm_belt";
		$hasValidViento = $viento == "true" || $viento == "false";
		$hasValidClima = $clima == "true" || $clima == "false";
		$hasValidEncuentro = $encuentro == "true" || $encuentro == "false";
		$hasValidKairoseki = $kairoseki == "true" || $kairoseki == "false";

		$outputText = "Tirada inválida.";

		if ($hasValidMar && $hasValidViento && $hasValidClima && $hasValidEncuentro && $hasValidKairoseki) {

			if ($mar == 'nuevo_mundo') {
				$mar_mostrar = 'Nuevo Mundo';
			} else if ($mar == 'paraiso') {
				$mar_mostrar = 'Paraíso';
			} else if ($mar == 'calm_belt') {
				$mar_mostrar = 'Calm Belt';
			} else {
				$mar_mostrar = 'Blue';
			}

			$tiradanaval_counter += 1;
			$outputText = "<strong><span style=\"color:#006699\">TIRADA NAVAL</span></strong><br>";
			$outputText .= "<strong>Mar:</strong> {$mar_mostrar}<br>";

			if ($viento == "true") {
				$viento_resultado = tirarDireccionViento();
				$outputText .= "<br><strong>Dirección del viento:</strong> " . $viento_resultado['resultado'] . " <em>(Tirada: " . $viento_resultado['tirada'] . "/10)</em>";
			}

			if ($clima == "true") {
				$clima_resultado = tirarClima($mar);
				$outputText .= "<br><strong>Clima:</strong> " . $clima_resultado['resultado'] . " <em>(Tirada: " . $clima_resultado['tirada'] . "/100)</em>";
			}

			if ($encuentro == "true") {
				$encuentro_resultado = tirarEncuentro($mar, $hasKairoseki);
				$outputText .= "<br><strong>Encuentro especial:</strong> " . $encuentro_resultado['resultado'] . " <em>(Tirada: " . $encuentro_resultado['tirada'] . "/100)</em>";
				if ($mar === 'calm_belt') {
					$outputText .= "<strong>Barco " . ($hasKairoseki ? "" : "sin ") . "Kairouseki en el casco</strong>";
				}
			}
            
			// $message = preg_replace('#\[dado=(.*?)\]#si','[dado_guardado='.$dado_counter.']',$message, 1);
			$message = preg_replace('#\[tiradanaval=(.*?)\]#si',"[tiradanaval_guardada=$tiradanaval_counter]",$message, 1);
			$db->query(" 
				INSERT INTO `mybb_op_tiradanaval` (`tid`, `pid`, `uid`, `counter`, `content`) VALUES ('".$tid."','".$pid."','".$uid."', '$tiradanaval_counter','".$outputText."');
			");
	
		} else {
			$message = preg_replace('#\[tiradanaval=(.*?)\]#si',"[tiradanavalinvalida=$mar,$viento,$clima,$encuentro]",$message, 1);
		}

		$db->query("
			UPDATE `mybb_posts` SET message='".$message."' WHERE pid='".$pid."';
		");
	}

	while(preg_match('#\[consumir=(.*?)\]#si',$message,$matches))
	{
		$objeto_id = $matches[1];
	
		$cantidadActual = '0';
		$has_objeto = false;
	
		$inventario_actual = $db->query("SELECT * FROM mybb_op_inventario WHERE uid='$uid' AND objeto_id='$objeto_id'");
		while ($q = $db->fetch_array($inventario_actual)) {  $has_objeto = true; $cantidadActual = $q['cantidad']; }
	
		if ($has_objeto) {
			$objeto = null;
			$objeto_query = $db->query("SELECT * FROM mybb_op_objetos WHERE objeto_id='$objeto_id'");
			while ($q = $db->fetch_array($objeto_query)) { $objeto = $q; }
			$objetoNombre = $objeto['nombre'];
			$objetoImagen = $objeto['imagen'];
			$objetoDescripcion = $objeto['descripcion'];
			$objetoImagenId = $objeto['imagen_id'];
			$objetoImagenAvatar = $objeto['imagen_avatar'];
			$objetoSubcategoriaPretty = $objeto['subcategoria'];
			$objetoSubcategoria = strtolower(str_replace(' ', '_', $objeto['subcategoria']));
			$objetoTier = $objeto['tier'];

			

			$consumir_counter += 1;
			$cantidadNueva = intval($cantidadActual) - 1;
			if (intval($cantidadActual) >= 2) {
				
				$db->query(" 
					UPDATE `mybb_op_inventario` SET `cantidad`='$cantidadNueva' WHERE objeto_id='$objeto_id' AND uid='$uid'
				");
			} else {
				$db->query(" 
					DELETE FROM `mybb_op_inventario` WHERE objeto_id='$objeto_id' AND uid='$uid'
				");
			}

			$colorTier = '#faa500';

			if ($objetoImagenId == '1') { $colorTier = '#808080'; }
			if ($objetoImagenId == '2') { $colorTier = '#4dfe45'; }	
			if ($objetoImagenId == '3') { $colorTier = '#457bfe'; }
			if ($objetoImagenId == '4') { $colorTier = '#cf44ff'; }
			if ($objetoImagenId == '5') { $colorTier = '#febb46'; }

			$requisitos = '';
			$escalado = '';
			$dano = '';
			$efecto = '';

			$objetoRequisitos = $objeto['requisitos'];
			$objetoEscalado = $objeto['escalado'];
			$objetoDano = $objeto['dano'];
			$objetoEfecto = $objeto['efecto'];

			if ($objetoRequisitos != '') {
				$requisitos = "<div style=\"font-family: InterRegular;padding: 5px;text-align: justify;border-bottom: 1px solid #5e5e5e;\">$objetoRequisitos</div>";
			}

			if ($objetoEscalado != '') {
				$escalado = "<div style=\"font-family: InterRegular;padding: 5px;text-align: justify;border-bottom: 1px solid #5e5e5e;\">$objetoEscalado</div>";
			}

			if ($objetoDano != '') {
				$dano = "<div style=\"font-family: InterRegular;padding: 5px;text-align: justify;border-bottom: 1px solid #5e5e5e;\">$objetoDano</div>";
			}
			
			if ($objetoEfecto != '') {
				$efecto = "<div style=\"font-family: InterRegular;padding: 5px;text-align: center;border-top: 1px solid #5e5e5e;\">$objetoEfecto</div>";
			}
			
			$img_type = 'jpg';
			
			if ($objetoSubcategoria == 'tecnicas' || $objetoSubcategoria == 'tecnicas' || $objetoSubcategoria == 'tecnicas') { $img_type = 'gif'; }

			$imagen_nombre = "$objetoSubcategoria" . "_" . "$objetoImagenId" . "_One_Piece_Gaiden_Foro_Rol.$img_type";
			$imagenAvatar = "/images/op/iconos/$imagen_nombre";
			if ($objetoImagenAvatar != '') {
				$imagenAvatar = $objetoImagenAvatar;
			}
			
			$objetoHtml = "
				<div>
					<div class=\"item-outer\" style=\" margin: auto; \">
						<div class=\"item-nombre\">$objetoNombre</div>
						<div class=\"item-image\">
						
						<div class=\"tooltip\">
							<img id=\"imagen-item\" src=\"$imagenAvatar\" />
							<div class=\"tooltiptext item-tooltip\" style=\"top: 77px; left: -158px;\">
							
								<div style=\"font-size: 15px;font-family: moonGetHeavy;letter-spacing: 1px;text-align: center;background: $colorTier;border-top-left-radius: 6px;border: 0px;border-top-right-radius: 6px;padding: 3px;text-shadow: 1px 1px 3px black;\">$objetoNombre ($objeto_id)</div>

								$requisitos
								$escalado
								<div class=\"mydescripcion\" style=\"font-family: InterRegular;padding: 5px;text-align: justify;\">$objetoDescripcion</div>
								$dano	
								$efecto

								<div style=\"font-size: 9px;font-family: moonGetHeavy;letter-spacing: 1px;text-align: center;background: $colorTier;border-bottom-left-radius: 6px;border: 0px;border-bottom-right-radius: 6px;padding: 3px;text-shadow: 1px 1px 3px black;\">$objetoSubcategoriaPretty - Tier $objetoTier</div>

							</div>
						</div>

						</div>	
					</div>
				</div>
		
			";

			$consumir_content = "<hr><div style=\"text-align: center;\"><strong>$username</strong> ha consumido <strong>$objetoNombre</strong>. Cantidad restante: <strong>$cantidadNueva</strong></div><br>$objetoHtml<hr>";

			$db->query(" 
				INSERT INTO `mybb_op_consumir` (`tid`, `pid`, `uid`, `counter`, `objeto_id`, `content`) VALUES ('$tid','$pid','$uid', '$consumir_counter', '$objeto_id', '$consumir_content');
			");
			$message = preg_replace("#\[consumir=$objeto_id\]#si","[consumido=$consumir_counter]",$message, 1);
	
		} else {
			$message = preg_replace("#\[consumir=$objeto_id\]#si","[consumibleinvalido=$objeto_id]",$message, 1);
		}

		$db->query(" 
			UPDATE `mybb_posts` SET message='".$message."' WHERE pid='".$pid."';
		");
	}



}