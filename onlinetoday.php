<?php

/**
 * Online Today 2.0.4
 * Compatobyllity MyBB 1.8.x
 * Contact: neogeoman@gmail.com
 * Website: http://www.mybb.com
 * Author:  Dark Neo
 */
 
define("IN_MYBB", 1);
$filename = substr($_SERVER['SCRIPT_NAME'], -strpos(strrev($_SERVER['SCRIPT_NAME']), "/"));
define('THIS_SCRIPT', $filename);
$templatelist = "online_today_all, online_today_index,online_today_rows,multipage_page_current, multipage_page, multipage_end, multipage_nextpage, multipage_jump_page, multipage";
require_once "./global.php";
require_once './inc/plugins/onlinetoday.php';
if($mybb->settings['onlinetoday_active'] == 0)
{
	return false;
}
$lang->load('onlinetoday', false, true);	
add_breadcrumb($lang->whos_online_today, THIS_SCRIPT);
if(!$mybb->user['uid'])
{
	if(!verify_post_check($mybb->input['my_post_key'])){
		error($lang->online_note_error);
	}	
}
$online_today = '';
if(!is_array($spiders))
{
	global $cache;
	$spiders = $cache->read('spiders');
}	
if($mybb->settings['showwol'] != 0 && $mybb->usergroup['canviewonline'] != 0)
{
	$perpage = (int)$mybb->settings['onlinetoday_items_all'];
	if($perpage == 0)
	{
		$perpage = 50;
	}
	if($mybb->settings['onlinetoday_time'] == 1)
	{
		$time_rest = 86400;	
		$lang->online_title = $lang->online_title_today;
	}
	else if($mybb->settings['onlinetoday_time'] == 2)
	{		
		$time_rest = 604800;
		$lang->online_title = $lang->online_title_weekly;
	}
	else if($mybb->settings['onlinetoday_time'] == 3)
	{			
		$time_rest = 2592000;
		$lang->online_title = $lang->online_title_monthly;
	}
	else
	{
		$time_rest = 86400;			
		$lang->online_title = $lang->online_title_today;
	}		
	$timesearch = TIME_NOW - $time_rest;			
	if($mybb->settings['onlinetoday_orderby'] == 1)
		$order_by = " ORDER BY u.username ASC, s.time DESC";
	else if($mybb->settings['onlinetoday_orderby'] == 2)
		$order_by = " ORDER BY u.lastactive DESC, s.time DESC";
	else
		$order_by = " ORDER BY u.username ASC, s.time DESC";			
	$page = (int)$mybb->input['page'];
	if($page < 1) $page = 1;	
	$numusers = $db->fetch_field($db->simple_select('users', 'COUNT(*) AS numtot', "uid>0 AND lastactive>'{$timesearch}'"), 'numtot');
	$numguests = $db->fetch_field($db->simple_select('sessions', 'COUNT(DISTINCT ip) AS numguests', "uid=0 AND nopermission != 1 AND time>'{$timesearch}'"), 'numguests');		
	$numtot = (int)$numusers;		
	$multipage = multipage($numtot, $perpage, $page, $_SERVER['PHP_SELF']."?my_post_key={$mybb->post_code}");

	$queries = array();

	$queries[] = $db->simple_select(
		"users u LEFT JOIN ".TABLE_PREFIX."sessions s ON (u.uid=s.uid)", 
		"s.sid, s.ip, s.time, s.location, u.uid, u.username, u.invisible, u.usergroup, u.displaygroup, u.avatar, u.lastactive",
		"u.lastactive>'{$timesearch}'{$order_by} LIMIT ".(($page-1)*$perpage).", {$perpage}"
	);
	$queries[] = $db->simple_select(
		"sessions s LEFT JOIN ".TABLE_PREFIX."users u ON (s.uid=u.uid)",
		"s.sid, s.ip, s.uid, s.time, s.location, u.username, u.invisible, u.usergroup, u.displaygroup, u.avatar, u.lastactive",
		"(s.uid != 0 OR SUBSTR(s.sid,4,1) = '=') AND s.time>'{$timesearch}'{$order_by} LIMIT ".(($page-1)*$perpage).", {$perpage}"
	);
	$online_today_all = "";
	$onlinemembers = '';
	$membercount = $guestcount = $anoncount = 0;
	$doneusers = $ips = array();
	foreach($queries as $query)
	{
		while($user = $db->fetch_array($query))
		{
			if(isset($user['sid']))
			{
				$botkey = my_strtolower(str_replace("bot=", '', $user['sid']));
			}
			if($user['uid'] > 0)
			{
				if($doneusers[$user['uid']] < $user['time'] || !$doneusers[$user['uid']])
				{
					if($user['invisible'] == 1)
					{
						++$anoncount;
					}
					++$membercount;
					if($user['invisible'] != 1 || $mybb->usergroup['canviewwolinvis'] == 1 || $user['uid'] == $mybb->user['uid'])
					{
						$invisiblemark = ($user['invisible'] == 1) ? "*" : "";
						$compare = TIME_NOW - $mybb->settings['wolcutoffmins']*60;		
						if(isset($user['time']) && $compare <= $user['time'])
						{
							$status = '<img src="images/buddy_online.png" alt="Online" style="position: absolute; margin-left: -20px;" />';
							$class = "ot_online";
						}
						else
						{
							$status = '<img src="images/buddy_offline.png" alt="Offline" style="position: absolute; margin-left: -20px;" />';								
							$class = "ot_offline";								
						}															
						$user['username'] = htmlspecialchars_uni($user['username']);
						$user['lastactive'] = my_date('normal', $user['lastactive']);						
						if($mybb->settings['onlinetoday_formatname'] == 1)
						{
							$user['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);	
							$user['link'] = $mybb->settings['bburl']."/".get_profile_link($user['uid']);
							$user['profilelink'] = $comma."<a href=\"{$user['link']}\" alt=\"{$user['lastactive']}\" title=\"{$user['lastactive']}\">{$user['username']}</a>";
						}
						if($mybb->settings['onlinetoday_formatname'] == 0)
						{
							$user['link'] = $mybb->settings['bburl']."/".get_profile_link($user['uid']);
							$user['profilelink'] = $comma."<a href=\"{$user['link']}\" alt=\"{$user['lastactive']}\" title=\"{$user['lastactive']}\">{$user['username']}</a>";
						}
						if($mybb->settings['onlinetoday_avatars'] == 0)
						$comma = ", ";
						if($mybb->settings['onlinetoday_avatars'] == 1)
						{
							$user['avatar'] = htmlspecialchars_uni($user['avatar']);
							if(empty($user['avatar']))
							$user['avatar'] = "images/default_avatar.png";							
						}
						if($mybb->settings['onlinetoday_avatars'] == 0)
						eval("\$onlinemembers .= \"".$templates->get("online_today_rows_nor", 1, 0)."\";");
						else if($mybb->settings['onlinetoday_avatars'] == 1)
						eval("\$onlinemembers .= \"".$templates->get("online_today_rows", 1, 0)."\";");								
					}
					if(isset($user['time']))
					{
						$doneusers[$user['uid']] = $user['time'];
					}
					else
					{
						$doneusers[$user['uid']] = $user['lastactive'];
					}
				}
			}
			// Otherwise this session is a bot
			else if(my_strpos($user['sid'], "bot=") !== false && $spiders[$botkey])
			{
				$user['bot'] = $spiders[$botkey]['name'];
				$user['usergroup'] = $spiders[$botkey]['usergroup'];
				$guests[] = $user;
				$user['avatar'] = "images/".$user['bot'].".png";			
				$compare = TIME_NOW - $mybb->settings['wolcutoffmins']*60;		
				if(isset($user['time']) && $compare <= $user['time'])
				{
					$status = '<img src="images/buddy_online.png" alt="Online" style="position: absolute; margin-left: -20px;" />';
					$class = "ot_online";
				}
				else
				{
					$status = '<img src="images/buddy_offline.png" alt="Offline" style="position: absolute; margin-left: -20px;" />';								
					$class = "ot_offline";								
				}	
				$guests[] = $user;
				$uname = htmlspecialchars_uni($user['bot']);
				$uname = trim($user['bot']);
				$uname = strtolower($user['bot']);					
				if($mybb->settings['onlinetoday_formatname'] == 1)					
					$user['username'] .= format_name($user['bot'], $user['usergroup']);
				else
					$user['username'] .= $comma.$user['bot'];
				if($mybb->settings['onlinetoday_formatname'] == 0 && $mybb->settings['onlinetoday_avatars'] == 0)
					$comma = ", ";
				if($mybb->settings['onlinetoday_avatars'] == 1)
					$template = "<div class=\"avatarep_online_row\"><span><img src=\"images/onlinetoday/".$uname.".png\" class=\"avatarep_image ".$class."\" /></span><span class=\"avatarep_image_sp\">".$user['username']."</span></div>";
				else
					$template = "<span class=\"avatarep_image_sp\">".$user['username']."</span>";
				++$botcount;					
			}
		}
	}		
	$membercount = (int)$numusers;	
	$guestcount = (int)$numguests;
	$onlinecount = (int)$numtot + (int)$guestcount;
	$numtotal = $membercount + $guestcount;
	$onlinebit = ($onlinecount != 1) ? $lang->online_plural : $lang->online_singular;
	$memberbit = ($membercount != 1) ? $lang->online_member_plural : $lang->online_member_singular;
	$anonbit = ($anoncount != 1) ? $lang->online_anon_plural : $lang->online_anon_singular;
	$guestbit = ($guestcount != 1) ? $lang->online_guest_plural : $lang->online_guest_singular;
	$my_time = dnt_my_time($time_rest);
	$lang->online_today_note = $lang->sprintf($lang->online_today_note, my_number_format($onlinecount), $onlinebit, $my_time, my_number_format($membercount), $memberbit, my_number_format($anoncount), $anonbit, my_number_format($guestcount), $guestbit);
	eval("\$online_today = \"".$templates->get("online_today_index")."\";");
}
if(!$online_today)
{
	$online_today = $lang->online_note_error;
}

eval("\$online_today_res = \"".$templates->get("online_today_all")."\";");

output_page($online_today_res);
exit;
?>