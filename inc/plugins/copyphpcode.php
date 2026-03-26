<?php
/**
 * MyBB 1.8
 * Copy PHP CODE
 * Author: JLP423 or Joey_Pham423
 * http://mybb.vn
**/
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

function copyphpcode_info()
{
	return array(
		"name"			=> "Copy PHP CODE",
		"description"	=> "Adds the ability to copy text to clipboard with a tooltip for [PHP] and [CODE] blocks.",
		"website"		=> "https://mybb.vn",
		"author"		=> "JLP423",
		"authorsite"	=> "https://mybb.vn",
		"version"		=> "1.0",
		"codename"		=> "copyphpcode",
		"compatibility" => "18*"
	);
}

function copyphpcode_activate()
{
	global $db;

	$stylesheet = ".tooltip {
  position: fixed;
  background-color: #333;
  color: #fff;
  padding: 6px 12px; /* Adjust padding to make the tooltip shorter */
  font-size: 14px; /* Adjust font size */
  border-radius: 4px;
  bottom: 20px;
	margin-bottom: 3px;
  left: 50%;
  transform: translateX(-50%);
  max-width: 200px; /* Limit the width of the tooltip */
}";

	$query = $db->simple_select('themes', 'tid');

	while($theme = $db->fetch_array($query))
	{
		$copyphpcode_stylesheet = array(
			'name'			=> 'copyphpcode.css',
			'tid'			=> (int) $theme['tid'],
			'stylesheet'	=> $db->escape_string($stylesheet),
			'cachefile'		=> 'copyphpcode.css',
			'lastmodified'	=> TIME_NOW,
		);
		$db->insert_query('themestylesheets', $copyphpcode_stylesheet);

		require_once MYBB_ADMIN_DIR.'/inc/functions_themes.php';
		cache_stylesheet($copyphpcode_stylesheet['tid'], $copyphpcode_stylesheet['cachefile'], $stylesheet);
		update_theme_stylesheet_list(1, false, true);
	}
	
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
		find_replace_templatesets("mycode_php", "#".preg_quote('</div><div class="body">')."#i", '<button class="copyButton">Copiar</button></div><div class="body textToCopy">');
		find_replace_templatesets("mycode_code", "#".preg_quote('</div><div class="body" dir="ltr">')."#i", ' <button class="copyButton">Copiar</button></div><div class="body textToCopy" dir="ltr">');
		find_replace_templatesets("headerinclude", "#".preg_quote('{$stylesheets}')."#i", '{$stylesheets}
<script type="text/javascript" src="{$mybb->asset_url}/jscripts/copyphpcode/copyphpcode.js"></script>');
}

function copyphpcode_deactivate()
{
	global $db;
	$db->delete_query('themestylesheets', "name='copyphpcode.css'");

	$query = $db->simple_select('themes', 'tid');

	while($theme = $db->fetch_array($query))
	{
		require_once MYBB_ADMIN_DIR.'/inc/functions_themes.php';
		update_theme_stylesheet_list($theme['tid']);
	}
	
	
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
		find_replace_templatesets("mycode_php", "#".preg_quote('<button class="copyButton">Copy</button></div><div class="body textToCopy">')."#i", '</div><div class="body">');
		find_replace_templatesets("mycode_code", "#".preg_quote(' <button class="copyButton">Copiar</button></div><div class="body textToCopy" dir="ltr">')."#i", '</div><div class="body" dir="ltr">');
		find_replace_templatesets("headerinclude", "#".preg_quote('{$stylesheets}
<script type="text/javascript" src="{$mybb->asset_url}/jscripts/copyphpcode/copyphpcode.js"></script>')."#i", '{$stylesheets}');
}



