<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

/**
 * Build a post bit
 *
 * @param array $post The post data
 * @param int $post_type The type of post bit we're building (1 = preview, 2 = pm, 3 = announcement, else = post)
 * @return string The built post bit
 */
function build_postbit($post, $post_type=0)
{
	global $db, $altbg, $theme, $mybb, $postcounter, $profile_fields;
	global $titlescache, $page, $templates, $forumpermissions, $attachcache;
	global $lang, $ismod, $inlinecookie, $inlinecount, $groupscache, $fid;
	global $plugins, $parser, $cache, $ignored_users, $hascustomtitle;

	$hascustomtitle = 0;

	/* Custom code for RPG */
	$user_uid = $mybb->user['uid'];

	$post['has_ficha'] = false;
	$post['has_personaje_tag'] = false;
	$user_fid = $post['uid'];
	$post_tid = $post['tid'];
	$post_pid = $post['pid'];
	$is_liked = false;
	$likes_number = 0;
	$nombresLike = "Este post le gusta a...\n";

	$likes = array();
	$query_likes = $db->query(" SELECT * FROM mybb_op_likes WHERE pid='$post_pid' ");
	$query_is_liked = $db->query(" SELECT * FROM `mybb_op_likes` WHERE pid='$post_pid' AND liked_by_uid=$user_uid; ");
	// while ($like = $db->fetch_array($query_likes)) { 
	// 	echo($liked_by_username);
	// 	$nombresLike = $nombresLike . $like['liked_by_username'] . ", \n";
	// 	array_push($likes, $like); 
	// 	// $nombresLike = "Este post le gusta a... ";	
	// }

	$likes_array = array();
	while ($like = $db->fetch_array($query_likes)) {
		$likes_array[] = $like;
	}

	// Ahora usamos un for loop
	for ($i = 0; $i < count($likes_array); $i++) {

		if ($i == count($likes_array) - 2) {
			$nombresLike = $nombresLike . $likes_array[$i]['liked_by_username'] . ", y \n";
		} else {
			$nombresLike = $nombresLike . $likes_array[$i]['liked_by_username'] . ", \n";
		}
		array_push($likes, $likes_array[$i]);
	}

	while ($like = $db->fetch_array($query_is_liked)) { $is_liked = true; }

	$likes_number = count($likes);

	if ($likes_number > 0) {
		$nombresLike = substr("$nombresLike", 0, -3); 
	}
	
	$likes_json = json_encode($likes);

	$query_users = $db->query(" SELECT * FROM mybb_users WHERE uid='$user_fid' ");
	while ($q = $db->fetch_array($query_users)) {
		$post['avatar1'] = $q['avatar'];
	}

	$post['is_liked'] = $is_liked;
	$post['likes_number'] = $likes_number;
	$post['nombresLike'] = $nombresLike;
	$post['user_uid'] = $user_uid;

    // if ($virtudes['V037']) { // Vigoroso 1 2 3
    //     $vitalidad_completa += intval($nivel) * 5; 
    // } else if ($virtudes['V038']) {
    //     $vitalidad_completa += intval($nivel) * 10; 
    // } else if ($virtudes['V039']) {
    //     $vitalidad_completa += intval($nivel) * 15; 
    // }

    // if ($virtudes['V040']) { // Hiperactivo 1 2
    //     $energia_completa += intval($nivel) * 5; 
    // } else if ($virtudes['V041']) {
    //     $energia_completa += intval($nivel) * 10; 
    // } 


	$experiencia = 0;
	$nivel = '1';
	$faccion = '';

	$has_vigoroso1 = false;
	$has_vigoroso1_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$user_fid' AND virtud_id='V037'; "); 
	while ($q = $db->fetch_array($has_vigoroso1_query)) { $has_vigoroso1 = true; }

	$has_vigoroso2 = false;
	$has_vigoroso2_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$user_fid' AND virtud_id='V038'; "); 
	while ($q = $db->fetch_array($has_vigoroso2_query)) { $has_vigoroso2 = true; }

	$has_vigoroso3 = false;
	$has_vigoroso3_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$user_fid' AND virtud_id='V039'; "); 
	while ($q = $db->fetch_array($has_vigoroso3_query)) { $has_vigoroso3 = true; }

	$has_hiperactivo1 = false;
	$has_hiperactivo1_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$user_fid' AND virtud_id='V040'; "); 
	while ($q = $db->fetch_array($has_hiperactivo1_query)) { $has_hiperactivo1 = true; }

	$has_hiperactivo2 = false;
	$has_hiperactivo2_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$user_fid' AND virtud_id='V041'; "); 
	while ($q = $db->fetch_array($has_hiperactivo2_query)) { $has_hiperactivo2 = true; }

	$has_espiritual1 = false;
	$has_espiritual1_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$user_fid' AND virtud_id='V058'; "); 
	while ($q = $db->fetch_array($has_espiritual1_query)) { $has_espiritual1 = true; }

	$has_espiritual2 = false;
	$has_espiritual2_query = $db->query(" SELECT * FROM `mybb_op_virtudes_usuarios` WHERE uid='$user_fid' AND virtud_id='V059'; "); 
	while ($q = $db->fetch_array($has_espiritual2_query)) { $has_espiritual2 = true; }

	$vitalidad_completa = 0;
	$energia_completa = 0;
	$haki_completo = 0;

	$query_ficha = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$user_fid' ");
	$query_post = $db->query("SELECT * FROM mybb_posts WHERE pid='".intval($post['pid'])."'");
	$query_secreta = $db->query("SELECT * FROM mybb_op_fichas_secret WHERE fid='".intval($user_fid)."' AND secret_number='1' LIMIT 1"); 
	$faccion_secreta = '';
	$rango_secreto = '';

	while ($q = $db->fetch_array($query_secreta)) {
		$rango_secreto = $q['rango'];
	}

	while ($q = $db->fetch_array($query_post)) {
		$faccion_secreta = $q['faccionsecreta'];
	}

	while ($q = $db->fetch_array($query_ficha)) {
		$post['berries'] = number_format($q['berries'], 0, ',', '.');
		$post['fama'] = $q['fama'];
		$post['reputacion'] = $q['reputacion'];

		// Condición rango Identidad secreta
		if ($faccion_secreta != '') {
			$post['rango'] = $rango_secreto;	
		} else {
			$post['rango'] = $q['rango'];
		}

		$post['apodo'] = $q['apodo'];
		$nivel = $q['nivel'];
		$post['avatar'] = $q['avatar3'];
		$post['has_ficha'] = true;		
		$post['ficha'] = $q;

        $vitalidad_extra = 0;
		$energia_extra = 0;
		$haki_extra = 0;
		
		//Condición para Facción en Identidad secreta al postear
		if ($faccion_secreta != '') {
			$faccion = $faccion_secreta;	
		} else {
			$faccion = $q['faccion'];
		}
		
		$sum_stats = intval($q['fuerza']) + intval($q['resistencia']) + intval($q['reflejos']) + 
		intval($q['punteria']) + intval($q['voluntad']) + intval($q['agilidad']) + intval($q['destreza']);

		$vitalidad_extra = floor(((intval($q['fuerza_pasiva']) * 6) + (intval($q['resistencia_pasiva']) * 15) + (intval($q['destreza_pasiva']) * 4) +
			(intval($q['agilidad_pasiva']) * 3) + (intval($q['voluntad_pasiva']) * 1) + (intval($q['punteria_pasiva']) * 2) + (intval($q['reflejos_pasiva']) * 1)));
			
		if (intval($q['fuerza_pasiva'])) {
			$energia_extra += (intval($q['fuerza_pasiva']) * 2);
		}

		if (intval($q['resistencia_pasiva'])) {
			$energia_extra += (intval($q['resistencia_pasiva']) * 4);
		}

		if (intval($q['punteria_pasiva'])) {
			$energia_extra += (intval($q['punteria_pasiva']) * 5);
		}

		if (intval($q['destreza_pasiva'])) {
			$energia_extra += (intval($q['destreza_pasiva']) * 4);
		}

		if (intval($q['agilidad_pasiva'])) {
			$energia_extra += (intval($q['agilidad_pasiva']) * 5);
		}

		if (intval($q['reflejos_pasiva'])) {
			$energia_extra += (intval($q['reflejos_pasiva']) * 1);
		}

		if (intval($q['voluntad_pasiva'])) {
			$energia_extra += (intval($q['voluntad_pasiva']) * 1);
			$haki_extra += (intval($q['voluntad_pasiva']) * 10);
		}

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
		
		$post['vitalidad_completa'] = intval($q['vitalidad']) + $vitalidad_extra + intval($q['vitalidad_pasiva']) + $vitalidad_completa;
		$post['energia_completa'] = intval($q['energia']) + $energia_extra + intval($q['energia_pasiva']) + $energia_completa;
		$post['haki_completo'] = intval($q['haki']) + $haki_extra + intval($q['haki_pasiva']) + $haki_completo;

		$post['fuerza_completa'] = intval($q['fuerza']) + (intval($q['fuerza_pasiva']) ? intval($q['fuerza_pasiva']) : 0);
		$post['resistencia_completa'] = intval($q['resistencia']) + (intval($q['resistencia_pasiva']) ? intval($q['resistencia_pasiva']) : 0);
		$post['destreza_completa'] = intval($q['destreza']) + (intval($q['destreza_pasiva']) ? intval($q['destreza_pasiva']) : 0);
		$post['punteria_completa'] = intval($q['punteria']) + (intval($q['punteria_pasiva']) ? intval($q['punteria_pasiva']) : 0);
		$post['agilidad_completa'] = intval($q['agilidad']) + (intval($q['agilidad_pasiva']) ? intval($q['agilidad_pasiva']) : 0);
		$post['reflejos_completa'] = intval($q['reflejos']) + (intval($q['reflejos_pasiva']) ? intval($q['reflejos_pasiva']) : 0);
		$post['voluntad_completa'] = intval($q['voluntad']) + (intval($q['voluntad_pasiva']) ? intval($q['voluntad_pasiva']) : 0);
	}

	$query_personaje = $db->query("
		SELECT * FROM mybb_op_thread_personaje WHERE tid='$post_tid' AND uid='$user_fid'
	");
	while ($q = $db->fetch_array($query_personaje)) {
		$post['has_personaje_tag'] = true;
		// $post['personaje_tag'] = $q;

		$vitalidad_extra = 0;
		$energia_extra = 0;
		$haki_extra = 0;
		// $faccion = $q['faccion'];

		$sum_stats = intval($q['fuerza']) + intval($q['resistencia']) + intval($q['reflejos']) + 
		intval($q['punteria']) + intval($q['voluntad']) + intval($q['agilidad']) + intval($q['destreza']);

		$vitalidad_extra = floor(((intval($q['fuerza_pasiva']) * 6) + (intval($q['resistencia_pasiva']) * 15) + (intval($q['destreza_pasiva']) * 4) +
			(intval($q['agilidad_pasiva']) * 3) + (intval($q['voluntad_pasiva']) * 1) + (intval($q['punteria_pasiva']) * 2) + (intval($q['reflejos_pasiva']) * 1)));
		
		if (intval($q['fuerza_pasiva'])) {
			$energia_extra += (intval($q['fuerza_pasiva']) * 2);
		}

		if (intval($q['resistencia_pasiva'])) {
			$energia_extra += (intval($q['resistencia_pasiva']) * 4);
		}

		if (intval($q['punteria_pasiva'])) {
			$energia_extra += (intval($q['punteria_pasiva']) * 5);
		}

		if (intval($q['destreza_pasiva'])) {
			$energia_extra += (intval($q['destreza_pasiva']) * 4);
		}

		if (intval($q['agilidad_pasiva'])) {
			$energia_extra += (intval($q['agilidad_pasiva']) * 5);
		}

		if (intval($q['reflejos_pasiva'])) {
			$energia_extra += (intval($q['reflejos_pasiva']) * 2);
		}

		if (intval($q['voluntad_pasiva'])) {
			$energia_extra += (intval($q['voluntad_pasiva']) * 1);
			$haki_extra += (intval($q['voluntad_pasiva']) * 10);
		}

		$nivelPersonaje = $q['nivel'];
		$vitalidad_completa = 0;
		$energia_completa = 0;
		$haki_completo = 0;

		if ($has_vigoroso1) { // Vigoroso 1 2 3
			$vitalidad_completa += intval($nivelPersonaje) * 10; 
		} else if ($has_vigoroso2) {
			$vitalidad_completa += intval($nivelPersonaje) * 15; 
		} else if ($has_vigoroso3) {
			$vitalidad_completa += intval($nivelPersonaje) * 20; 
		}
	
		if ($has_hiperactivo1) { // Hiperactivo 1 2
			$energia_completa += intval($nivelPersonaje) * 10; 
		} else if ($has_hiperactivo2) {
			$energia_completa += intval($nivelPersonaje) * 15; 
		} 

		if ($has_espiritual1) { // Espiritual 1 2 
			$haki_completo += intval($nivelPersonaje) * 5;
		} else if ($has_espiritual2) {            
			$haki_completo += intval($nivelPersonaje) * 10;
		}

		$post['vitalidad_completa'] = intval($q['vitalidad']) + $vitalidad_extra + intval($q['vitalidad_pasiva']) + $vitalidad_completa;
		$post['energia_completa'] = intval($q['energia']) + $energia_extra + intval($q['energia_pasiva']) + $energia_completa;
		$post['haki_completo'] = intval($q['haki']) + $haki_extra + intval($q['haki_pasiva']) + $haki_completo;

		$post['fuerza_completa'] = intval($q['fuerza']) + (intval($q['fuerza_pasiva']) ? intval($q['fuerza_pasiva']) : 0);
		$post['resistencia_completa'] = intval($q['resistencia']) + (intval($q['resistencia_pasiva']) ? intval($q['resistencia_pasiva']) : 0);
		$post['destreza_completa'] = intval($q['destreza']) + (intval($q['destreza_pasiva']) ? intval($q['destreza_pasiva']) : 0);
		$post['punteria_completa'] = intval($q['punteria']) + (intval($q['punteria_pasiva']) ? intval($q['punteria_pasiva']) : 0);
		$post['agilidad_completa'] = intval($q['agilidad']) + (intval($q['agilidad_pasiva']) ? intval($q['agilidad_pasiva']) : 0);
		$post['reflejos_completa'] = intval($q['reflejos']) + (intval($q['reflejos_pasiva']) ? intval($q['reflejos_pasiva']) : 0);
		$post['voluntad_completa'] = intval($q['voluntad']) + (intval($q['voluntad_pasiva']) ? intval($q['voluntad_pasiva']) : 0);
	}

	// Pirata, Marina CipherPol Revolucionario Cazadores Civil

	if ($faccion == 'Staff') { $post['faccionColor'] = '#ff0026'; }
	if ($faccion == 'Pirata') { $post['faccionColor'] = '#ff0026'; }
	if ($faccion == 'Marina') { $post['faccionColor'] = '#4560ff'; }
	if ($faccion == 'Revolucionario') { $post['faccionColor'] = '#d26806'; }
	if ($faccion == 'CipherPol') { $post['faccionColor'] = '#6920fa'; }
	if ($faccion == 'Civil') { $post['faccionColor'] = '#f33de4'; }
	if ($faccion == 'Cazadores') { $post['faccionColor'] = '#1cb31c'; }
	if ($faccion == 'Staff') { $post['faccionColor'] = '#9c48cf'; }
	$post['faccion'] = $faccion;

	$query_users = $db->query(" SELECT * FROM mybb_users WHERE uid='$user_fid' ");
	while ($q = $db->fetch_array($query_users)) {
		$experiencia = $q['newpoints'];
	}

	$post['nivelPorcentaje'] = '100';

	$nivelPorcentaje = 100; 
	$expMax = 50;

	if ($experiencia >= 0 && $experiencia < 50 && $nivel == '1') {
        $expMin = 0;
		$expMax = 50;
	} else if ($experiencia >= 50 && $experiencia < 125 && $nivel == '2') {
        $expMin = 50;
		$expMax = 125;
	} else if ($experiencia >= 125 && $experiencia < 225 && $nivel == '3') {
        $expMin = 125;
		$expMax = 225;
	} else if ($experiencia >= 225 && $experiencia < 350 && $nivel == '4') {
        $expMin = 225;
		$expMax = 350;
	} else if ($experiencia >= 350 && $experiencia < 500 && $nivel == '5') {
        $expMin = 350;
		$expMax = 500;
	} else if ($experiencia >= 500 && $experiencia < 675 && $nivel == '6') {
        $expMin = 500;
		$expMax = 675;
	} else if ($experiencia >= 675 && $experiencia < 875 && $nivel == '7') {
        $expMin = 675;
		$expMax = 875;
	} else if ($experiencia >= 875 && $experiencia < 1100 && $nivel == '8') {
        $expMin = 875;
		$expMax = 1100;
	} else if ($experiencia >= 1100 && $experiencia < 1350 && $nivel == '9') {
        $expMin = 1100;
		$expMax = 1350;
	} else if ($experiencia >= 1350 && $experiencia < 1625 && $nivel == '10') {
        $expMin = 1350;
		$expMax = 1625;
	} else if ($experiencia >= 1625 && $experiencia < 1925 && $nivel == '11') {
        $expMin = 1625;
		$expMax = 1925;
	} else if ($experiencia >= 1925 && $experiencia < 2250 && $nivel == '12') {
        $expMin = 1925;
		$expMax = 2250;
	} else if ($experiencia >= 2250 && $experiencia < 2600 && $nivel == '13') {
        $expMin = 2250;
		$expMax = 2600;
	} else if ($experiencia >= 2600 && $experiencia < 2975 && $nivel == '14') {
        $expMin = 2600;
		$expMax = 2975;
	} else if ($experiencia >= 2975 && $experiencia < 3375 && $nivel == '15') {
        $expMin = 2975;
		$expMax = 3375;
	} else if ($experiencia >= 3375 && $experiencia < 3800 && $nivel == '16') {
        $expMin = 3375;
		$expMax = 3800;
	} else if ($experiencia >= 3800 && $experiencia < 4250 && $nivel == '17') {
        $expMin = 3800;
		$expMax = 4250;
	} else if ($experiencia >= 4250 && $experiencia < 4725 && $nivel == '18') {
        $expMin = 4250;
		$expMax = 4725;
	} else if ($experiencia >= 4725 && $experiencia < 5225 && $nivel == '19') {
        $expMin = 4725;
		$expMax = 5225;
	} else if ($experiencia >= 5225 && $experiencia < 5750 && $nivel == '20') {
        $expMin = 5225;
		$expMax = 5750;
	} else if ($experiencia >= 5750 && $experiencia < 6300 && $nivel == '21') {
        $expMin = 5750;
		$expMax = 6300;
	} else if ($experiencia >= 6300 && $experiencia < 6870 && $nivel == '22') {
        $expMin = 6300;
		$expMax = 6870;
	} else if ($experiencia >= 6870 && $experiencia < 7460 && $nivel == '23') {
        $expMin = 6870;
		$expMax = 7460;
	} else if ($experiencia >= 7460 && $experiencia < 8070 && $nivel == '24') {
        $expMin = 7460;
		$expMax = 8070;
	} else if ($experiencia >= 8070 && $experiencia < 8700 && $nivel == '25') {
        $expMin = 8070;
		$expMax = 8700;
	} else if ($experiencia >= 8700 && $experiencia < 9350 && $nivel == '26') {
        $expMin = 8700;
		$expMax = 9350;
	} else if ($experiencia >= 9350 && $experiencia < 10020 && $nivel == '27') {
        $expMin = 9350;
		$expMax = 10020;
	} else if ($experiencia >= 10020 && $experiencia < 10700 && $nivel == '28') {
        $expMin = 10020;
		$expMax = 10700;
	} else if ($experiencia >= 10700 && $experiencia < 11400 && $nivel == '29') {
        $expMin = 10700;
		$expMax = 11400;
	} else if ($experiencia >= 11400 && $experiencia < 12120 && $nivel == '30') {
        $expMin = 11400;
		$expMax = 12120;
	} else if ($experiencia >= 12120 && $experiencia < 12860 && $nivel == '31') {
        $expMin = 12120;
		$expMax = 12860;
	} else if ($experiencia >= 12860 && $experiencia < 13620 && $nivel == '32') {
        $expMin = 12860;
		$expMax = 13620;
	} else if ($experiencia >= 13620 && $experiencia < 14400 && $nivel == '33') {
        $expMin = 13620;
		$expMax = 14400;
	} else if ($experiencia >= 14400 && $experiencia < 15200 && $nivel == '34') {
        $expMin = 14400;
		$expMax = 15200;
	} else if ($experiencia >= 15200 && $experiencia < 16020 && $nivel == '35') {
        $expMin = 15200;
		$expMax = 16020;
	} else if ($experiencia >= 16020 && $experiencia < 16860 && $nivel == '36') {
        $expMin = 16020;
		$expMax = 16860;
	} else if ($experiencia >= 16860 && $experiencia < 17720 && $nivel == '37') {
        $expMin = 16860;
		$expMax = 17720;
	} else if ($experiencia >= 17720 && $experiencia < 18600 && $nivel == '38') {
        $expMin = 17720;
		$expMax = 18600;
	} else if ($experiencia >= 18600 && $experiencia < 19500 && $nivel == '39') {
        $expMin = 18600;
		$expMax = 19500;
	} else if ($experiencia >= 19500 && $experiencia < 20425 && $nivel == '40') {
        $expMin = 19500;
		$expMax = 20425;
	} else if ($experiencia >= 20425 && $experiencia < 21375 && $nivel == '41') {
        $expMin = 20425;
		$expMax = 21375;
	} else if ($experiencia >= 21375 && $experiencia < 22350 && $nivel == '42') {
        $expMin = 21375;
		$expMax = 22350;
	} else if ($experiencia >= 22350 && $experiencia < 23350 && $nivel == '43') {
        $expMin = 22350;
		$expMax = 23350;
	} else if ($experiencia >= 23350 && $experiencia < 24375 && $nivel == '44') {
        $expMin = 23350;
		$expMax = 24375;
	} else if ($experiencia >= 24375 && $experiencia < 25425 && $nivel == '45') {
        $expMin = 24375;
		$expMax = 25425;
	} else if ($experiencia >= 25425 && $experiencia < 26500 && $nivel == '46') {
        $expMin = 25425;
		$expMax = 26500;
	} else if ($experiencia >= 26500 && $experiencia < 27600 && $nivel == '47') {
        $expMin = 26500;
		$expMax = 27600;
	} else if ($experiencia >= 27600 && $experiencia < 28750 && $nivel == '48') {
        $expMin = 27600;
		$expMax = 28750;
	} else if ($experiencia >= 28750 && $experiencia < 30000 && $nivel == '49') {
        $expMin = 28750;
		$expMax = 30000;
	} else if ($experiencia >= 30000 && $nivel == '50') {
        $expMin = 30000;
		$expMax = 30001;
	} 

	
	$nivelPorcentaje = floor(($experiencia - $expMin) / ($expMax - $expMin) * 100);	
	// $nivelPorcentaje = floor((($expMax - ($expMax - $experiencia)) / $expMax) * 100);
	if ($nivelPorcentaje == 0) { $nivelPorcentaje = 1; }
	$post['nivelPorcentaje'] = $nivelPorcentaje;

	/* End Custom code for RPG */

	// Set default values for any fields not provided here
	foreach(array('pid', 'aid', 'pmid', 'posturl', 'button_multiquote', 'subject_extra', 'attachments', 'button_rep', 'button_warn', 'button_purgespammer', 'button_pm', 'button_reply_pm', 'button_replyall_pm', 'button_forward_pm', 'button_delete_pm', 'replink', 'warninglevel') as $post_field)
	{
		if(empty($post[$post_field]))
		{
			$post[$post_field] = '';
		}
	}

	// Set up the message parser if it doesn't already exist.
	if(!$parser)
	{
		require_once MYBB_ROOT."inc/class_parser.php";
		$parser = new postParser;
	}

	if(!function_exists("purgespammer_show"))
	{
		require_once MYBB_ROOT."inc/functions_user.php";
	}

	$unapproved_shade = '';
	if(isset($post['visible']) && $post['visible'] == 0 && $post_type == 0)
	{
		$altbg = $unapproved_shade = 'unapproved_post';
	}
	elseif(isset($post['visible']) && $post['visible'] == -1 && $post_type == 0)
	{
		$altbg = $unapproved_shade = 'unapproved_post deleted_post';
	}
	elseif($altbg == 'trow1')
	{
		$altbg = 'trow2';
	}
	else
	{
		$altbg = 'trow1';
	}
	$post['fid'] = $fid;
	switch($post_type)
	{
		case 1: // Message preview
			global $forum;
			$parser_options['allow_html'] = $forum['allowhtml'];
			$parser_options['allow_mycode'] = $forum['allowmycode'];
			$parser_options['allow_smilies'] = $forum['allowsmilies'];
			$parser_options['allow_imgcode'] = $forum['allowimgcode'];
			$parser_options['allow_videocode'] = $forum['allowvideocode'];
			$parser_options['me_username'] = $post['username'];
			$parser_options['filter_badwords'] = 1;
			$id = 0;
			break;
		case 2: // Private message
			global $message, $pmid;
			$idtype = 'pmid';
			$parser_options['allow_html'] = $mybb->settings['pmsallowhtml'];
			$parser_options['allow_mycode'] = $mybb->settings['pmsallowmycode'];
			$parser_options['allow_smilies'] = $mybb->settings['pmsallowsmilies'];
			$parser_options['allow_imgcode'] = $mybb->settings['pmsallowimgcode'];
			$parser_options['allow_videocode'] = $mybb->settings['pmsallowvideocode'];
			$parser_options['me_username'] = $post['username'];
			$parser_options['filter_badwords'] = 1;
			$id = $pmid;
			break;
		case 3: // Announcement
			global $announcementarray, $message;
			$parser_options['allow_html'] = $mybb->settings['announcementshtml'] && $announcementarray['allowhtml'];
			$parser_options['allow_mycode'] = $announcementarray['allowmycode'];
			$parser_options['allow_smilies'] = $announcementarray['allowsmilies'];
			$parser_options['allow_imgcode'] = 1;
			$parser_options['allow_videocode'] = 1;
			$parser_options['me_username'] = $post['username'];
			$parser_options['filter_badwords'] = 1;
			$id = $announcementarray['aid'];
			break;
		default: // Regular post
			global $forum, $thread, $tid;
			$oldforum = $forum;
			$id = (int)$post['pid'];
			$idtype = 'pid';
			$parser_options['allow_html'] = $forum['allowhtml'];
			$parser_options['allow_mycode'] = $forum['allowmycode'];
			$parser_options['allow_smilies'] = $forum['allowsmilies'];
			$parser_options['allow_imgcode'] = $forum['allowimgcode'];
			$parser_options['allow_videocode'] = $forum['allowvideocode'];
			$parser_options['filter_badwords'] = 1;
			break;
	}

	if(!$post['username'])
	{
		$post['username'] = $lang->guest; // htmlspecialchars_uni'd below
	}

	if($post['userusername'])
	{
		$parser_options['me_username'] = $post['userusername'];
	}
	else
	{
		$parser_options['me_username'] = $post['username'];
	}

	$post['username'] = htmlspecialchars_uni($post['username']);
	$post['userusername'] = htmlspecialchars_uni($post['userusername']);

	if(!$postcounter)
	{ // Used to show the # of the post
		if($page > 1)
		{
			if(!$mybb->settings['postsperpage'] || (int)$mybb->settings['postsperpage'] < 1)
			{
				$mybb->settings['postsperpage'] = 20;
			}

			$postcounter = $mybb->settings['postsperpage']*($page-1);
		}
		else
		{
			$postcounter = 0;
		}
		$post_extra_style = "border-top-width: 0;";
	}
	elseif($mybb->get_input('mode') == "threaded")
	{
		$post_extra_style = "border-top-width: 0;";
	}
	else
	{
		$post_extra_style = "margin-top: 5px;";
	}

	if(!$altbg)
	{ // Define the alternate background colour if this is the first post
		$altbg = "trow1";
	}
	$postcounter++;

	// Format the post date and time using my_date
	$post['postdate'] = my_date('relative', $post['dateline']);

	// Dont want any little 'nasties' in the subject
	$post['subject'] = $parser->parse_badwords($post['subject']);

	// Pm's have been htmlspecialchars_uni()'ed already.
	if($post_type != 2)
	{
		$post['subject'] = htmlspecialchars_uni($post['subject']);
	}

	if(empty($post['subject']))
	{
		$post['subject'] = '&nbsp;';
	}

	$post['author'] = $post['uid'];
	$post['subject_title'] = $post['subject'];

	// Get the usergroup
	if($post['usergroup'])
	{
		$usergroup = usergroup_permissions($post['usergroup']);
	}
	else
	{
		$usergroup = usergroup_permissions(1);
	}

	// Fetch display group data.
	$displaygroupfields = array("title", "description", "namestyle", "usertitle", "stars", "starimage", "image");

	if(empty($post['displaygroup']))
	{
		$post['displaygroup'] = $post['usergroup'];
	}

	// Set to hardcoded Guest usergroup ID (1) for guest author or deleted user.
	if(empty($post['usergroup']))
	{
		$post['usergroup'] = 1;
	}
	if(empty($post['displaygroup']))
	{
		$post['displaygroup'] = 1;
	}

	$displaygroup = usergroup_displaygroup($post['displaygroup']);
	if(is_array($displaygroup))
	{
		$usergroup = array_merge($usergroup, $displaygroup);
	}

	if(!is_array($titlescache))
	{
		$cached_titles = $cache->read("usertitles");
		if(!empty($cached_titles))
		{
			foreach($cached_titles as $usertitle)
			{
				$titlescache[$usertitle['posts']] = $usertitle;
			}
		}

		if(is_array($titlescache))
		{
			krsort($titlescache);
		}
		unset($usertitle, $cached_titles);
	}

	// Work out the usergroup/title stuff
	$post['groupimage'] = '';
	if(!empty($usergroup['image']))
	{
		$language = $mybb->settings['bblanguage'];
		if(!empty($mybb->user['language']))
		{
			$language = $mybb->user['language'];
		}

		$usergroup['image'] = str_replace("{lang}", $language, $usergroup['image']);
		$usergroup['image'] = str_replace("{theme}", $theme['imgdir'], $usergroup['image']);
		eval("\$post['groupimage'] = \"".$templates->get("postbit_groupimage")."\";");

		if($mybb->settings['postlayout'] == "classic")
		{
			$post['groupimage'] .= "<br />";
		}
	}

	if($post['userusername'])
	{
		// This post was made by a registered user
		$post['username'] = $post['userusername'];
		$post['profilelink_plain'] = get_profile_link($post['uid']);
		$post['username_formatted'] = format_name($post['username'], $post['usergroup'], $post['displaygroup']);
		$post['profilelink'] = build_profile_link($post['username_formatted'], $post['uid']);

		if(trim($post['usertitle']) != "")
		{
			$hascustomtitle = 1;
		}

		if($usergroup['usertitle'] != "" && !$hascustomtitle)
		{
			$post['usertitle'] = $usergroup['usertitle'];
		}
		elseif(is_array($titlescache) && !$usergroup['usertitle'])
		{
			reset($titlescache);
			foreach($titlescache as $key => $titleinfo)
			{
				if($post['postnum'] >= $key)
				{
					if(!$hascustomtitle)
					{
						$post['usertitle'] = $titleinfo['title'];
					}
					$post['stars'] = $titleinfo['stars'];
					$post['starimage'] = $titleinfo['starimage'];
					break;
				}
			}
		}

		$post['usertitle'] = htmlspecialchars_uni($post['usertitle']);

		if($usergroup['stars'])
		{
			$post['stars'] = $usergroup['stars'];
		}

		if(empty($post['starimage']))
		{
			$post['starimage'] = $usergroup['starimage'];
		}

		$post['userstars'] = '';
		if($post['starimage'] && isset($post['stars']))
		{
			// Only display stars if we have an image to use...
			$post['starimage'] = str_replace("{theme}", $theme['imgdir'], $post['starimage']);

			for($i = 0; $i < $post['stars']; ++$i)
			{
				eval("\$post['userstars'] .= \"".$templates->get("postbit_userstar", 1, 0)."\";");
			}

			$post['userstars'] .= "<br />";
		}

		$postnum = $post['postnum'];
		$post['postnum'] = my_number_format($post['postnum']);
		$post['threadnum'] = my_number_format($post['threadnum']);

		// Determine the status to show for the user (Online/Offline/Away)
		$timecut = TIME_NOW - $mybb->settings['wolcutoff'];
		if($post['lastactive'] > $timecut && ($post['invisible'] != 1 || $mybb->usergroup['canviewwolinvis'] == 1) && $post['lastvisit'] != $post['lastactive'])
		{
			eval("\$post['onlinestatus'] = \"".$templates->get("postbit_online")."\";");
		}
		else
		{
			if($post['away'] == 1 && $mybb->settings['allowaway'] != 0)
			{
				eval("\$post['onlinestatus'] = \"".$templates->get("postbit_away")."\";");
			}
			else
			{
				eval("\$post['onlinestatus'] = \"".$templates->get("postbit_offline")."\";");
			}
		}

		$post['useravatar'] = '';
		if(isset($mybb->user['showavatars']) && $mybb->user['showavatars'] != 0 || $mybb->user['uid'] == 0)
		{
			$useravatar = format_avatar($post['avatar'], $post['avatardimensions'], $mybb->settings['postmaxavatarsize']);
			eval("\$post['useravatar'] = \"".$templates->get("postbit_avatar")."\";");
		}

		$post['button_find'] = '';
		if($mybb->usergroup['cansearch'] == 1)
		{
			eval("\$post['button_find'] = \"".$templates->get("postbit_find")."\";");
		}

		if($mybb->settings['enablepms'] == 1 && $post['uid'] != $mybb->user['uid'] && (($post['receivepms'] != 0 && $usergroup['canusepms'] != 0 && $mybb->usergroup['cansendpms'] == 1 && my_strpos(",".$post['ignorelist'].",", ",".$mybb->user['uid'].",") === false) || $mybb->usergroup['canoverridepm'] == 1))
		{
			eval("\$post['button_pm'] = \"".$templates->get("postbit_pm")."\";");
		}

		$post['button_rep'] = '';
		if($post_type != 3 && $mybb->settings['enablereputation'] == 1 && $mybb->settings['postrep'] == 1 && $mybb->usergroup['cangivereputations'] == 1 && $usergroup['usereputationsystem'] == 1 && ($mybb->settings['posrep'] || $mybb->settings['neurep'] || $mybb->settings['negrep']) && $post['uid'] != $mybb->user['uid'] && (!isset($post['visible']) || $post['visible'] == 1) && (!isset($thread['visible']) || $thread['visible'] == 1))
		{
			if(!$post['pid'])
			{
				$post['pid'] = 0;
			}

			eval("\$post['button_rep'] = \"".$templates->get("postbit_rep_button")."\";");
		}

		if($post['website'] != "" && !is_member($mybb->settings['hidewebsite']) && $usergroup['canchangewebsite'] == 1)
		{
			$post['website'] = htmlspecialchars_uni($post['website']);
			eval("\$post['button_www'] = \"".$templates->get("postbit_www")."\";");
		}
		else
		{
			$post['button_www'] = "";
		}

		if($post['hideemail'] != 1 && $post['uid'] != $mybb->user['uid'] && $mybb->usergroup['cansendemail'] == 1)
		{
			eval("\$post['button_email'] = \"".$templates->get("postbit_email")."\";");
		}
		else
		{
			$post['button_email'] = "";
		}

		$post['userregdate'] = my_date($mybb->settings['regdateformat'], $post['regdate']);

		// Work out the reputation this user has (only show if not announcement)
		if($post_type != 3 && $usergroup['usereputationsystem'] != 0 && $mybb->settings['enablereputation'] == 1)
		{
			$post['userreputation'] = get_reputation($post['reputation'], $post['uid']);
			eval("\$post['replink'] = \"".$templates->get("postbit_reputation")."\";");
		}

		// Showing the warning level? (only show if not announcement)
		if($post_type != 3 && $mybb->settings['enablewarningsystem'] != 0 && $usergroup['canreceivewarnings'] != 0 && ($mybb->usergroup['canwarnusers'] != 0 || ($mybb->user['uid'] == $post['uid'] && $mybb->settings['canviewownwarning'] != 0)))
		{
			if($mybb->settings['maxwarningpoints'] < 1)
			{
				$mybb->settings['maxwarningpoints'] = 10;
			}

			$warning_level = round($post['warningpoints']/$mybb->settings['maxwarningpoints']*100);
			if($warning_level > 100)
			{
				$warning_level = 100;
			}
			$warning_level = get_colored_warning_level($warning_level);

			// If we can warn them, it's not the same person, and we're in a PM or a post.
			if($mybb->usergroup['canwarnusers'] != 0 && $post['uid'] != $mybb->user['uid'] && ($post_type == 0 || $post_type == 2))
			{
				eval("\$post['button_warn'] = \"".$templates->get("postbit_warn")."\";");
				$warning_link = "warnings.php?uid={$post['uid']}";
			}
			else
			{
				$post['button_warn'] = '';
				$warning_link = "usercp.php";
			}
			eval("\$post['warninglevel'] = \"".$templates->get("postbit_warninglevel")."\";");
		}

		if($post_type != 3 && $post_type != 1 && purgespammer_show($post['postnum'], $post['usergroup'], $post['uid']))
		{
			eval("\$post['button_purgespammer'] = \"".$templates->get('postbit_purgespammer')."\";");
		}

		if(!isset($profile_fields))
		{
			$profile_fields = array();

			// Fetch profile fields to display
			$pfcache = $cache->read('profilefields');
		
			if(is_array($pfcache))
			{
				foreach($pfcache as $profilefield)
				{
					if($profilefield['postbit'] != 1)
					{
						continue;
					}
		
					$profile_fields[$profilefield['fid']] = $profilefield;
				}
			}
		}

		// Display profile fields on posts - only if field is filled in
		$post['profilefield'] = '';
		if(!empty($profile_fields))
		{
			foreach($profile_fields as $field)
			{
				$fieldfid = "fid{$field['fid']}";
				if(!empty($post[$fieldfid]))
				{
					$post['fieldvalue'] = '';
					$post['fieldname'] = htmlspecialchars_uni($field['name']);

					$thing = explode("\n", $field['type'], "2");
					$type = trim($thing[0]);
					$useropts = explode("\n", $post[$fieldfid]);

					if(is_array($useropts) && ($type == "multiselect" || $type == "checkbox"))
					{
						$post['fieldvalue_option'] = '';

						foreach($useropts as $val)
						{
							if($val != '')
							{
								eval("\$post['fieldvalue_option'] .= \"".$templates->get("postbit_profilefield_multiselect_value")."\";");
							}
						}
						if($post['fieldvalue_option'] != '')
						{
							eval("\$post['fieldvalue'] .= \"".$templates->get("postbit_profilefield_multiselect")."\";");
						}
					}
					else
					{
						$field_parser_options = array(
							"allow_html" => $field['allowhtml'],
							"allow_mycode" => $field['allowmycode'],
							"allow_smilies" => $field['allowsmilies'],
							"allow_imgcode" => $field['allowimgcode'],
							"allow_videocode" => $field['allowvideocode'],
							#"nofollow_on" => 1,
							"filter_badwords" => 1
						);

						if($field['type'] == "textarea")
						{
							$field_parser_options['me_username'] = $post['username'];
						}
						else
						{
							$field_parser_options['nl2br'] = 0;
						}

						if($mybb->user['uid'] != 0 && $mybb->user['showimages'] != 1 || $mybb->settings['guestimages'] != 1 && $mybb->user['uid'] == 0)
						{
							$field_parser_options['allow_imgcode'] = 0;
						}

						$post['fieldvalue'] = $parser->parse_message($post[$fieldfid], $field_parser_options);
					}

					eval("\$post['profilefield'] .= \"".$templates->get("postbit_profilefield")."\";");
				}
			}
		}

		eval("\$post['user_details'] = \"".$templates->get("postbit_author_user")."\";");
	}
	else
	{ // Message was posted by a guest or an unknown user
		$post['profilelink'] = format_name($post['username'], 1);

		if($usergroup['usertitle'])
		{
			$post['usertitle'] = $usergroup['usertitle'];
		}
		else
		{
			$post['usertitle'] = $lang->guest;
		}

		$post['usertitle'] = htmlspecialchars_uni($post['usertitle']);
		$post['userstars'] = '';
		$post['useravatar'] = '';

		$usergroup['title'] = $lang->na;

		$post['userregdate'] = $lang->na;
		$post['postnum'] = $lang->na;
		$post['button_profile'] = '';
		$post['button_email'] = '';
		$post['button_www'] = '';
		$post['signature'] = '';
		$post['button_pm'] = '';
		$post['button_find'] = '';
		$post['onlinestatus'] = '';
		$post['replink'] = '';
		eval("\$post['user_details'] = \"".$templates->get("postbit_author_guest")."\";");
	}

	$post['input_editreason'] = '';
	$post['button_edit'] = '';
	$post['button_quickdelete'] = '';
	$post['button_quickrestore'] = '';
	$post['button_quote'] = '';
	$post['button_quickquote'] = '';
	$post['button_report'] = '';
	$post['button_reply_pm'] = '';
	$post['button_replyall_pm'] = '';
	$post['button_forward_pm']  = '';
	$post['button_delete_pm'] = '';

	// For private messages, fetch the reply/forward/delete icons
	if($post_type == 2 && $post['pmid'])
	{
		global $replyall;

		eval("\$post['button_reply_pm'] = \"".$templates->get("postbit_reply_pm")."\";");
		eval("\$post['button_forward_pm'] = \"".$templates->get("postbit_forward_pm")."\";");
		eval("\$post['button_delete_pm'] = \"".$templates->get("postbit_delete_pm")."\";");

		if($replyall == true)
		{
			eval("\$post['button_replyall_pm'] = \"".$templates->get("postbit_replyall_pm")."\";");
		}
	}

	$post['editedmsg'] = '';
	if(!$post_type)
	{
		if(!isset($forumpermissions))
		{
			$forumpermissions = forum_permissions($fid);
		}

		// Figure out if we need to show an "edited by" message
		if($post['edituid'] != 0 && $post['edittime'] != 0 && $post['editusername'] != "" && ($mybb->settings['showeditedby'] != 0 && $usergroup['cancp'] == 0 && !is_moderator($post['fid'], "", $post['uid']) || ($mybb->settings['showeditedbyadmin'] != 0 && ($usergroup['cancp'] == 1 || is_moderator($post['fid'], "", $post['uid'])))))
		{
			$post['editdate'] = my_date('relative', $post['edittime']);
			$post['editnote'] = $lang->sprintf($lang->postbit_edited, $post['editdate']);
			$post['editusername'] = htmlspecialchars_uni($post['editusername']);
			$post['editedprofilelink'] = build_profile_link($post['editusername'], $post['edituid']);
			$editreason = "";
			if($post['editreason'] != "")
			{
				$post['editreason'] = $parser->parse_badwords($post['editreason']);
				$post['editreason'] = htmlspecialchars_uni($post['editreason']);
				eval("\$editreason = \"".$templates->get("postbit_editedby_editreason")."\";");
			}
			eval("\$post['editedmsg'] = \"".$templates->get("postbit_editedby")."\";");
		}

		$time = TIME_NOW;
		if((is_moderator($fid, "caneditposts") || ($forumpermissions['caneditposts'] == 1 && $mybb->user['uid'] == $post['uid'] && $thread['closed'] != 1 && ($mybb->usergroup['edittimelimit'] == 0 || $mybb->usergroup['edittimelimit'] != 0 && $post['dateline'] > ($time-($mybb->usergroup['edittimelimit']*60))))) && $mybb->user['uid'] != 0)
		{
			eval("\$post['input_editreason'] = \"".$templates->get("postbit_editreason")."\";");
			eval("\$post['button_edit'] = \"".$templates->get("postbit_edit")."\";");
		}

		// Quick Delete button
		$can_delete_thread = $can_delete_post = 0;
		if($mybb->user['uid'] == $post['uid'] && $thread['closed'] == 0)
		{
			if($forumpermissions['candeletethreads'] == 1 && $postcounter == 1)
			{
				$can_delete_thread = 1;
			}
			else if($forumpermissions['candeleteposts'] == 1 && $postcounter != 1)
			{
				$can_delete_post = 1;
			}
		}

		$postbit_qdelete = $postbit_qrestore = '';
		if($mybb->user['uid'] != 0)
		{
			if((is_moderator($fid, "candeleteposts") || is_moderator($fid, "cansoftdeleteposts") || $can_delete_post == 1) && $postcounter != 1)
			{
				$postbit_qdelete = $lang->postbit_qdelete_post;
				$display = '';
				if($post['visible'] == -1)
				{
					$display = "none";
				}
				eval("\$post['button_quickdelete'] = \"".$templates->get("postbit_quickdelete")."\";");
			}
			else if((is_moderator($fid, "candeletethreads") || is_moderator($fid, "cansoftdeletethreads") || $can_delete_thread == 1) && $postcounter == 1)
			{
				$postbit_qdelete = $lang->postbit_qdelete_thread;
				$display = '';
				if($post['visible'] == -1)
				{
					$display = "none";
				}
				eval("\$post['button_quickdelete'] = \"".$templates->get("postbit_quickdelete")."\";");
			}

			// Restore Post
			if(is_moderator($fid, "canrestoreposts") && $postcounter != 1)
			{
				$display = "none";
				if($post['visible'] == -1)
				{
					$display = '';
				}
				$postbit_qrestore = $lang->postbit_qrestore_post;
				eval("\$post['button_quickrestore'] = \"".$templates->get("postbit_quickrestore")."\";");
			}

			// Restore Thread
			else if(is_moderator($fid, "canrestorethreads") && $postcounter == 1)
			{
				$display = "none";
				if($post['visible'] == -1)
				{
					$display = "";
				}
				$postbit_qrestore = $lang->postbit_qrestore_thread;
				eval("\$post['button_quickrestore'] = \"".$templates->get("postbit_quickrestore")."\";");
			}
		}

		if(!isset($ismod))
		{
			$ismod = is_moderator($fid);
		}

		// Inline moderation stuff
		if($ismod)
		{
			if(isset($mybb->cookies[$inlinecookie]) && my_strpos($mybb->cookies[$inlinecookie], "|".$post['pid']."|") !== false)
			{
				$inlinecheck = "checked=\"checked\"";
				$inlinecount++;
			}
			else
			{
				$inlinecheck = "";
			}

			eval("\$post['inlinecheck'] = \"".$templates->get("postbit_inlinecheck")."\";");

			if($post['visible'] == 0)
			{
				$invisiblepost = 1;
			}
		}
		else
		{
			$post['inlinecheck'] = "";
		}
		$post['postlink'] = get_post_link($post['pid'], $post['tid']);
		$post_number = my_number_format($postcounter);
		eval("\$post['posturl'] = \"".$templates->get("postbit_posturl")."\";");
		global $forum, $thread;

		if($forum['open'] != 0 && ($thread['closed'] != 1 || is_moderator($forum['fid'], "canpostclosedthreads")) && ($thread['uid'] == $mybb->user['uid'] || empty($forumpermissions['canonlyreplyownthreads'])))
		{
			eval("\$post['button_quote'] = \"".$templates->get("postbit_quote")."\";");
		}

		if($forumpermissions['canpostreplys'] != 0 && ($thread['uid'] == $mybb->user['uid'] || empty($forumpermissions['canonlyreplyownthreads'])) && ($thread['closed'] != 1 || is_moderator($fid, "canpostclosedthreads")) && $mybb->settings['multiquote'] != 0 && $forum['open'] != 0 && !$post_type)
		{
			eval("\$post['button_multiquote'] = \"".$templates->get("postbit_multiquote")."\";");
		}

		if(isset($post['reporters']))
		{
			$skip_report = my_unserialize($post['reporters']);
			if(is_array($skip_report))
			{
				$skip_report[] = 0;
			}
			else
			{
				$skip_report = array(0);
			}
		}
		else
		{
			$skip_report = array(0);
		}

		$reportable = user_permissions($post['uid']);
		if(!in_array($mybb->user['uid'], $skip_report) && !empty($reportable['canbereported']))
		{
			eval("\$post['button_report'] = \"".$templates->get("postbit_report")."\";");
		}
	}
	elseif($post_type == 3) // announcement
	{
		if($mybb->usergroup['canmodcp'] == 1 && $mybb->usergroup['canmanageannounce'] == 1 && is_moderator($fid, "canmanageannouncements"))
		{
			eval("\$post['button_edit'] = \"".$templates->get("announcement_edit")."\";");
			eval("\$post['button_quickdelete'] = \"".$templates->get("announcement_quickdelete")."\";");
		}
	}

	$post['iplogged'] = '';
	$show_ips = $mybb->settings['logip'];
	
	// Show post IP addresses... PMs now can have IP addresses too as of 1.8!
	if($post_type == 2)
	{
		$show_ips = $mybb->settings['showpmip'];
	}
	if(!$post_type || $post_type == 2)
	{
		if($show_ips != "no" && !empty($post['ipaddress']))
		{
			$ipaddress = my_inet_ntop($db->unescape_binary($post['ipaddress']));

			if($show_ips == "show")
			{
				eval("\$post['iplogged'] = \"".$templates->get("postbit_iplogged_show")."\";");
			}
			else if($show_ips == "hide" && (is_moderator($fid, "canviewips") || $mybb->usergroup['issupermod']))
			{
				$action = 'getip';
				$javascript = 'getIP';

				if($post_type == 2)
				{
					$action = 'getpmip';
					$javascript = 'getPMIP';
				}

				eval("\$post['iplogged'] = \"".$templates->get("postbit_iplogged_hiden")."\";");
			}
		}
	}

	$post['poststatus'] = '';
	if(!$post_type && $post['visible'] != 1)
	{
		if(is_moderator($fid, "canviewdeleted") && $postcounter != 1 && $post['visible'] == -1)
		{
			$status_type = $lang->postbit_post_deleted;
		}
		else if(is_moderator($fid, "canviewunapprove") && $postcounter != 1 && $post['visible'] == 0)
		{
			$status_type = $lang->postbit_post_unapproved;
		}
		else if(is_moderator($fid, "canviewdeleted") && $postcounter == 1 && $post['visible'] == -1)
		{
			$status_type = $lang->postbit_thread_deleted;
		}
		else if(is_moderator($fid, "canviewunapprove") && $postcounter == 1 && $post['visible'] == 0)
		{
			$status_type = $lang->postbit_thread_unapproved;
		}

		eval("\$post['poststatus'] = \"".$templates->get("postbit_status")."\";");
	}

	if(isset($post['smilieoff']) && $post['smilieoff'] == 1)
	{
		$parser_options['allow_smilies'] = 0;
	}

	if($mybb->user['uid'] != 0 && $mybb->user['showimages'] != 1 || $mybb->settings['guestimages'] != 1 && $mybb->user['uid'] == 0)
	{
		$parser_options['allow_imgcode'] = 0;
	}

	if($mybb->user['uid'] != 0 && $mybb->user['showvideos'] != 1 || $mybb->settings['guestvideos'] != 1 && $mybb->user['uid'] == 0)
	{
		$parser_options['allow_videocode'] = 0;
	}

	// If we have incoming search terms to highlight - get it done.
	if(!empty($mybb->input['highlight']))
	{
		$parser_options['highlight'] = $mybb->input['highlight'];
		$post['subject'] = $parser->highlight_message($post['subject'], $parser_options['highlight']);
	}

	$post['message'] = $parser->parse_message($post['message'], $parser_options);

	$post['attachments'] = '';
	if($mybb->settings['enableattachments'] != 0)
	{
		get_post_attachments($id, $post);
	}

	if(isset($post['includesig']) && $post['includesig'] != 0 && $post['username'] && $post['signature'] != "" && ($mybb->user['uid'] == 0 || $mybb->user['showsigs'] != 0)
	&& ($post['suspendsignature'] == 0 || $post['suspendsignature'] == 1 && $post['suspendsigtime'] != 0 && $post['suspendsigtime'] < TIME_NOW) && $usergroup['canusesig'] == 1
	&& ($usergroup['canusesigxposts'] == 0 || $usergroup['canusesigxposts'] > 0 && $postnum > $usergroup['canusesigxposts']) && !is_member($mybb->settings['hidesignatures']))
	{
		$sig_parser = array(
			"allow_html" => $mybb->settings['sightml'],
			"allow_mycode" => $mybb->settings['sigmycode'],
			"allow_smilies" => $mybb->settings['sigsmilies'],
			"allow_imgcode" => $mybb->settings['sigimgcode'],
			"me_username" => $parser_options['me_username'],
			"filter_badwords" => 1
		);

		if($usergroup['signofollow'])
		{
			$sig_parser['nofollow_on'] = 1;
		}

		if($mybb->user['uid'] != 0 && $mybb->user['showimages'] != 1 || $mybb->settings['guestimages'] != 1 && $mybb->user['uid'] == 0)
		{
			$sig_parser['allow_imgcode'] = 0;
		}

		$post['signature'] = $parser->parse_message($post['signature'], $sig_parser);
		eval("\$post['signature'] = \"".$templates->get("postbit_signature")."\";");
	}
	else
	{
		$post['signature'] = "";
	}

	$icon_cache = $cache->read("posticons");

	if(isset($post['icon']) && $post['icon'] > 0 && $icon_cache[$post['icon']])
	{
		$icon = $icon_cache[$post['icon']];

		$icon['path'] = htmlspecialchars_uni($icon['path']);
		$icon['path'] = str_replace("{theme}", $theme['imgdir'], $icon['path']);
		$icon['name'] = htmlspecialchars_uni($icon['name']);
		eval("\$post['icon'] = \"".$templates->get("postbit_icon")."\";");
	}
	else
	{
		$post['icon'] = "";
	}

	$post_visibility = $ignore_bit = $deleted_bit = '';
	switch($post_type)
	{
		case 1: // Message preview
			$post = $plugins->run_hooks("postbit_prev", $post);
			break;
		case 2: // Private message
			$post = $plugins->run_hooks("postbit_pm", $post);
			break;
		case 3: // Announcement
			$post = $plugins->run_hooks("postbit_announcement", $post);
			break;
		default: // Regular post
			$post = $plugins->run_hooks("postbit", $post);

			if(!isset($ignored_users))
			{
				$ignored_users = array();
				if($mybb->user['uid'] > 0 && $mybb->user['ignorelist'] != "")
				{
					$ignore_list = explode(',', $mybb->user['ignorelist']);
					foreach($ignore_list as $uid)
					{
						$ignored_users[$uid] = 1;
					}
				}
			}

			// Has this post been deleted but can be viewed? Hide this post
			if($post['visible'] == -1 && is_moderator($fid, "canviewdeleted"))
			{
				$deleted_message = $lang->sprintf($lang->postbit_deleted_post_user, $post['username']);
				eval("\$deleted_bit = \"".$templates->get("postbit_deleted")."\";");
				$post_visibility = "display: none;";
			}

			// Is the user (not moderator) logged in and have unapproved posts?
			if($mybb->user['uid'] && $post['visible'] == 0 && $post['uid'] == $mybb->user['uid'] && !is_moderator($fid, "canviewunapprove"))
			{
				$ignored_message = $lang->sprintf($lang->postbit_post_under_moderation, $post['username']);
				eval("\$ignore_bit = \"".$templates->get("postbit_ignored")."\";");
				$post_visibility = "display: none;";
			}

			// Is this author on the ignore list of the current user? Hide this post
			if(is_array($ignored_users) && $post['uid'] != 0 && isset($ignored_users[$post['uid']]) && $ignored_users[$post['uid']] == 1 && empty($deleted_bit))
			{
				$ignored_message = $lang->sprintf($lang->postbit_currently_ignoring_user, $post['username']);
				eval("\$ignore_bit = \"".$templates->get("postbit_ignored")."\";");
				$post_visibility = "display: none;";
			}
			break;
	}

	if($post_type == 0 && $forumpermissions['canviewdeletionnotice'] == 1 && $post['visible'] == -1 && !is_moderator($fid, "canviewdeleted"))
	{
		eval("\$postbit = \"".$templates->get("postbit_deleted_member")."\";");
	}
	else
	{
		if($mybb->settings['postlayout'] == "classic")
		{
			eval("\$postbit = \"".$templates->get("postbit_classic")."\";");
		}
		else
		{
			eval("\$postbit = \"".$templates->get("postbit")."\";");
		}
	}

	$GLOBALS['post'] = "";

	return $postbit;
}

/**
 * Fetch the attachments for a specific post and parse inline [attachment=id] code.
 * Note: assumes you have $attachcache, an array of attachments set up.
 *
 * @param int $id The ID of the item.
 * @param array $post The post or item passed by reference.
 */
function get_post_attachments($id, &$post)
{
	global $attachcache, $mybb, $theme, $templates, $forumpermissions, $lang;

	$validationcount = 0;
	$tcount = 0;
	$post['attachmentlist'] = $post['thumblist'] = $post['imagelist'] = '';
	if(!isset($forumpermissions))
	{
		$forumpermissions = forum_permissions($post['fid']);
	}

	if(isset($attachcache[$id]) && is_array($attachcache[$id]))
	{ // This post has 1 or more attachments
		foreach($attachcache[$id] as $aid => $attachment)
		{
			if($attachment['visible'])
			{ // There is an attachment thats visible!
				$attachment['filename'] = htmlspecialchars_uni($attachment['filename']);
				$attachment['filesize'] = get_friendly_size($attachment['filesize']);
				$ext = get_extension($attachment['filename']);
				if($ext == "jpeg" || $ext == "gif" || $ext == "bmp" || $ext == "png" || $ext == "jpg")
				{
					$isimage = true;
				}
				else
				{
					$isimage = false;
				}
				$attachment['icon'] = get_attachment_icon($ext);
				$attachment['downloads'] = my_number_format($attachment['downloads']);

				if(!$attachment['dateuploaded'])
				{
					$attachment['dateuploaded'] = $attachment['dateline'];
				}
				$attachdate = my_date('normal', $attachment['dateuploaded']);
				// Support for [attachment=id] code
				if(stripos($post['message'], "[attachment=".$attachment['aid']."]") !== false)
				{
					// Show as thumbnail IF image is big && thumbnail exists && setting=='thumb'
					// Show as full size image IF setting=='fullsize' || (image is small && permissions allow)
					// Show as download for all other cases
					if($attachment['thumbnail'] != "SMALL" && $attachment['thumbnail'] != "" && $mybb->settings['attachthumbnails'] == "yes")
					{
						eval("\$attbit = \"".$templates->get("postbit_attachments_thumbnails_thumbnail")."\";");
					}
					elseif((($attachment['thumbnail'] == "SMALL" && $forumpermissions['candlattachments'] == 1) || $mybb->settings['attachthumbnails'] == "no") && $isimage)
					{
						eval("\$attbit = \"".$templates->get("postbit_attachments_images_image")."\";");
					}
					else
					{
						eval("\$attbit = \"".$templates->get("postbit_attachments_attachment")."\";");
					}
					$post['message'] = preg_replace("#\[attachment=".$attachment['aid']."]#si", $attbit, $post['message']);
				}
				else
				{
					// Show as thumbnail IF image is big && thumbnail exists && setting=='thumb'
					// Show as full size image IF setting=='fullsize' || (image is small && permissions allow)
					// Show as download for all other cases
					if($attachment['thumbnail'] != "SMALL" && $attachment['thumbnail'] != "" && $mybb->settings['attachthumbnails'] == "yes")
					{
						eval("\$post['thumblist'] .= \"".$templates->get("postbit_attachments_thumbnails_thumbnail")."\";");
						if($tcount == 5)
						{
							$thumblist .= "<br />";
							$tcount = 0;
						}
						++$tcount;
					}
					elseif((($attachment['thumbnail'] == "SMALL" && $forumpermissions['candlattachments'] == 1) || $mybb->settings['attachthumbnails'] == "no") && $isimage)
					{
						if ($forumpermissions['candlattachments'])
						{
							eval("\$post['imagelist'] .= \"".$templates->get("postbit_attachments_images_image")."\";");
						} 
						else 
						{
							eval("\$post['thumblist'] .= \"".$templates->get("postbit_attachments_thumbnails_thumbnail")."\";");
							if($tcount == 5)
							{
								$thumblist .= "<br />";
								$tcount = 0;
							}
							++$tcount;
						}
					}
					else
					{
						eval("\$post['attachmentlist'] .= \"".$templates->get("postbit_attachments_attachment")."\";");
					}
				}
			}
			else
			{
				$validationcount++;
			}
		}
		if($validationcount > 0 && is_moderator($post['fid'], "canviewunapprove"))
		{
			if($validationcount == 1)
			{
				$postbit_unapproved_attachments = $lang->postbit_unapproved_attachment;
			}
			else
			{
				$postbit_unapproved_attachments = $lang->sprintf($lang->postbit_unapproved_attachments, $validationcount);
			}
			eval("\$post['attachmentlist'] .= \"".$templates->get("postbit_attachments_attachment_unapproved")."\";");
		}
		if($post['thumblist'])
		{
			eval("\$post['attachedthumbs'] = \"".$templates->get("postbit_attachments_thumbnails")."\";");
		}
		else
		{
			$post['attachedthumbs'] = '';
		}
		if($post['imagelist'])
		{
			eval("\$post['attachedimages'] = \"".$templates->get("postbit_attachments_images")."\";");
		}
		else
		{
			$post['attachedimages'] = '';
		}
		if($post['attachmentlist'] || $post['thumblist'] || $post['imagelist'])
		{
			eval("\$post['attachments'] = \"".$templates->get("postbit_attachments")."\";");
		}
	}
}

/**
 * Returns bytes count from human readable string
 * Used to parse ini_get human-readable values to int
 *
 * @param string $val Human-readable value
 */
function return_bytes($val) {
	$val = trim($val);
	if ($val == "")
	{
		return 0;
	}

	$last = strtolower($val[strlen($val)-1]);

	$val = intval($val);

	switch($last)
	{
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}

	return $val;
}

/**
 * Detects whether an attachment removal/approval/unapproval
 * submit button was pressed (without triggering an AJAX request)
 * and sets inputs accordingly (as for an AJAX request).
 */
function detect_attachmentact()
{
	global $mybb;

	foreach($mybb->input as $key => $val)
	{
		if(strpos($key, 'rem_') === 0)
		{
			$mybb->input['attachmentaid'] = (int)substr($key, 4);
			$mybb->input['attachmentact'] = 'remove';
			break;
		}
		elseif(strpos($key, 'approveattach_') === 0)
		{
			$mybb->input['attachmentaid'] = (int)substr($key, 14);
			$mybb->input['attachmentact'] = 'approve';
			break;
		}
		elseif(strpos($key, 'unapproveattach_') === 0)
		{
			$mybb->input['attachmentaid'] = (int)substr($key, 16);
			$mybb->input['attachmentact'] = 'unapprove';
			break;
		}
	}
}
