<?php

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

require_once MYBB_ROOT."inc/class_parser.php";
$plugins->add_hook("parse_message", "codigo_hide_run");
$plugins->add_hook('datahandler_post_insert_post_end', 'codigo_hide_newpost');
$plugins->add_hook('datahandler_post_insert_thread_end', 'codigo_hide_newpost');
$parser = new postParser;

function codigo_hide_info()
{
global $mybb;
	return array(
		"name"				=> "Código Hide",
		"description"		=> "Código para Foros de Rol.",
		"website"			=> "",
		"author"			=> "Kurosame",
		"authorsite"		=> "https://www.shinobigaiden.net",
		"version"			=> "1.0.0",
		"codename"			=> "codigo_hide",
		"compatibility"		=> "*",
	);
}


function codigo_hide_activate()
{

}


function codigo_hide_deactivate()
{

}


function codigo_hide_run(&$message)
{
	global $mybb, $db, $post, $thread, $parser;

	$user_uid = $mybb->user['uid'];

	// Set up the parser options.
	$parser_options = array(
		"allow_html" => 1,
		"allow_mycode" => 1,
		"allow_imgcode" => 1,
		"allow_videocode" => 1,
	);

	while(preg_match('#\[hide=(.*?)\](.*?)\[\/hide\]#si',$message,$matches))
	{
		$hide_uids = $matches[1];
		$hide_content = $matches[2];
		$message = preg_replace('#\[hide=(.*?)\](.*?)\[\/susurro\]#si','<div class="spoiler">
			<div class="spoiler_title"><span class="spoiler_button" onclick="javascript: if(parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display == \'block\'){ parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display = \'none\'; this.innerHTML=\'Contenido Oculto (Vista Previa)\'; } else { parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display = \'block\'; this.innerHTML=\'Contenido Oculto (Vista Previa)\'; }">Contenido Oculto (Vista Previa)</span></div>
			<div class="spoiler_content" style="display: none;">'.$hide_content.'</div>
		</div>',$message, 1);
	}

	while(preg_match('#\[hide\](.*?)\[\/hide\]#si',$message,$matches))
	{
		$hide_content = $matches[1];
		$message = preg_replace('#\[hide\](.*?)\[\/hide\]#si','<div class="spoiler">
			<div class="spoiler_title"><span class="spoiler_button" onclick="javascript: if(parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display == \'block\'){ parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display = \'none\'; this.innerHTML=\'Contenido Oculto (Vista Previa)\'; } else { parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display = \'block\'; this.innerHTML=\'Contenido Oculto (Vista Previa)\'; }">Contenido Oculto (Vista Previa)</span></div>
			<div class="spoiler_content" style="display: none;">'.$hide_content.'</div>
		</div>',$message, 1);
	}

	while(preg_match('#\[hide=(.*?)\]#si',$message,$matches))
	{
		$uid = $post['uid'];
		$pid = $post['pid'];
		$tid = $post['tid'];
		$is_edited = $post['edittime'];
		$is_closed = $thread['closed'] == 1;
		$is_user_same = $mybb->user['uid'] == $uid;
		$hide_counter = $matches[1];
		$is_staff = false;

		$query_hide = $db->query("
			SELECT * FROM mybb_op_hide WHERE pid='$pid' AND tid='$tid' AND hide_counter='$hide_counter'
		");

		$hide = null;
		while ($h = $db->fetch_array($query_hide)) {
			$hide = $h;
		}

		$hide_uids = $hide['hide_uids'];
		$hide_uids_arr = explode(",", $hide_uids);
		$show_private_hide = false;
		$susurro_text = '';
		$has_susurro = false;

		if ($hide_uids != '') {
			$has_susurro = true;
			$susurro_text = '(Susurro)';
			foreach ($hide_uids_arr as $hide_uid) {
				
				if ($hide_uid == $user_uid) { 
					$show_private_hide = true; }
			}
		}

		$show_hide = $hide['show_hide'];
		$hide_id = $hide['hid'];
		$hide_content = $hide['hide_content'];

		$contenido = '';
		$hide_button = '<div style="text-align: center;"><button class="hide-button" onclick="javascript: document.getElementById(\'hideform'.$hide_id.'\').submit()">Mostrar Hide</button></div>';
		$hidden_form = '<div style="display: none"><form id="hideform'.$hide_id.'" method="post" action="/op/hide.php"><input type="text" name="hid" value="'.$hide_id.'" /><input type="text" name="show_hide" value="1" /><input type="text" name="tid" value="'.$tid.'" /></form></div>';

		if ($is_closed || $show_hide || $show_private_hide || $is_staff) {
			$contenido = $parser->parse_message($hide_content, $parser_options);
		} else {
			$contenido = $hidden_form . $hide_button . '<br /><hr />' . $parser->parse_message($hide_content, $parser_options);
		}

		if (!$is_user_same && !$show_hide && !$show_private_hide && !$is_closed && !$is_staff) {
			$message = preg_replace('#\[hide=(.*?)\]#si','',$message, 1);
		} else {
			$message = preg_replace('#\[hide=(.*?)\]#si','<div class="spoiler">
			<div class="spoiler_title"><span class="spoiler_button" onclick="javascript: if(parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display == \'block\'){ parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display = \'none\'; this.innerHTML=\'Contenido Oculto\'; } else { parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display = \'block\'; this.innerHTML=\'Contenido Oculto '.$susurro_text.'\'; }">Contenido Oculto '.$susurro_text.'</span></div>
			<div class="spoiler_content" style="display: none;">'.$contenido.'</div>
		</div>',$message, 1);
		}
	}

	while(preg_match('#\[ocultado=(.*?)\]#si',$message,$matches))
	{
		$uid = $post['uid'];
		$pid = $post['pid'];
		$tid = $post['tid'];
		$is_edited = $post['edittime'];
		$is_closed = $thread['closed'] == 1;
		$is_user_same = $mybb->user['uid'] == $uid;
		$hide_counter = $matches[1];
		$is_staff = false;

		$query_hide = $db->query("
			SELECT * FROM mybb_op_hide WHERE pid='$pid' AND tid='$tid' AND hide_counter='$hide_counter'
		");

		$hide = null;
		while ($h = $db->fetch_array($query_hide)) {
			$hide = $h;
		}

		$hide_uids = $hide['hide_uids'];
		$hide_uids_arr = explode(",", $hide_uids);
		$show_private_hide = false;
		$susurro_text = '';
		$has_susurro = false;

		if ($hide_uids != '') {
			$has_susurro = true;
			$susurro_text = '(Susurro)';
			foreach ($hide_uids_arr as $hide_uid) {
				
				if ($hide_uid == $user_uid) { 
					$show_private_hide = true; }
			}
		}

		$show_hide = $hide['show_hide'];
		$hide_id = $hide['hid'];
		$hide_content = $hide['hide_content'];

		$contenido = '';
		$hide_button = '<div style="text-align: center;"><button class="hide-button" onclick="javascript: document.getElementById(\'hideform'.$hide_id.'\').submit()">Mostrar Hide</button></div>';
		$hidden_form = '<div style="display: none"><form id="hideform'.$hide_id.'" method="post" action="/op/hide.php"><input type="text" name="hid" value="'.$hide_id.'" /><input type="text" name="show_hide" value="1" /><input type="text" name="tid" value="'.$tid.'" /></form></div>';

		if ($is_closed || $show_hide || $show_private_hide || $is_staff) {
			$contenido = $parser->parse_message($hide_content, $parser_options);
		} else {
			$contenido = $hidden_form . $hide_button . '<br /><hr />' . $parser->parse_message($hide_content, $parser_options);
		}

		if (!$is_user_same && !$show_hide && !$show_private_hide && !$is_closed && !$is_staff) {
			$message = preg_replace('#\[ocultado=(.*?)\]#si','',$message, 1);
		} else {
			$message = preg_replace('#\[ocultado=(.*?)\]#si','<div class="spoiler">
			<div class="spoiler_title"><span class="spoiler_button" onclick="javascript: if(parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display == \'block\'){ parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display = \'none\'; this.innerHTML=\'Contenido Oculto\'; } else { parentNode.parentNode.getElementsByTagName(\'div\')[1].style.display = \'block\'; this.innerHTML=\'Contenido Oculto '.$susurro_text.'\'; }">Contenido Oculto '.$susurro_text.'</span></div>
			<div class="spoiler_content" style="display: none;">'.$contenido.'</div>
		</div>',$message, 1);
		}
	}

	return $message;
}

function codigo_hide_newpost(&$data)
{
	global $db, $mybb, $post;

	$uid = $data->post_insert_data['uid'];
	$pid = $data->return_values['pid'];
	$tid = $data->post_insert_data['tid'];
	$message = $data->post_insert_data['message'];
	$hide_counter = 0;

	while(preg_match('#\[hide\](.*?)\[\/hide\]#si',$message,$matches))
	{
		$hide_content = $matches[1];
		$hide_counter += 1;
		$message = preg_replace('#\[hide\](.*?)\[\/hide\]#si',"[hide=$hide_counter]",$message, 1);

		$db->query(" 
			INSERT INTO `mybb_op_hide` (`tid`, `pid`, `uid`, `hide_counter`, `show_hide`, `hide_content`) VALUES ('$tid','$pid','$uid', '$hide_counter', 0,'$hide_content');
		");


	}

	while(preg_match('#\[oculto\](.*?)\[\/oculto\]#si',$message,$matches))
	{
		$hide_content = $matches[1];
		$hide_counter += 1;
		$message = preg_replace('#\[oculto\](.*?)\[\/oculto\]#si',"[ocultado=$hide_counter]",$message, 1);

		$db->query(" 
			INSERT INTO `mybb_op_hide` (`tid`, `pid`, `uid`, `hide_counter`, `show_hide`, `hide_content`) VALUES ('$tid','$pid','$uid', '$hide_counter', 0,'$hide_content');
		");


	}

	while(preg_match('#\[oculto=(.*?)\](.*?)\[\/oculto\]#si',$message,$matches))
	{
		$hide_uids = $matches[1];
		$hide_content = $matches[2];
		$hide_counter += 1;
		$message = preg_replace('#\[oculto=(.*?)\](.*?)\[\/oculto\]#si','[ocultado='.$hide_counter.']',$message, 1);

		$db->query(" 
			INSERT INTO `mybb_op_hide` (`tid`, `pid`, `uid`, `hide_counter`, `show_hide`, `hide_uids`, `hide_content`) VALUES ('$tid','$pid','$uid', '$hide_counter', 0,'$hide_uids','$hide_content');
		");

	}


	if ($hide_counter > 0) {
		$db->query(" 
			UPDATE `mybb_posts` SET message='$message' WHERE pid='$pid';
		");
	}


	
}