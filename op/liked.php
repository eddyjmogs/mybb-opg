<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'liked.php');
require_once "./../global.php";
require "./../inc/config.php";
require_once "./functions/op_functions.php";

global $templates, $mybb;

$user_username = $mybb->user['username'];
$user_uid = $mybb->user['uid'];

$post_pid = $mybb->get_input('pid');
$input_tid = $mybb->get_input('tid');

$is_liked = false;

if ($user_uid != '0') {

    $query_like = $db->query(" SELECT * FROM `mybb_op_likes` WHERE pid='$post_pid' AND liked_by_uid=$user_uid; ");

    while ($like = $db->fetch_array($query_like)) { 
    
        $is_liked = true;
    
    }
    
    if ($is_liked) {
        $db->query(" DELETE FROM `mybb_op_likes` WHERE pid='$post_pid' AND liked_by_uid=$user_uid; ");
        // echo("<script>alert('Le has quitado el Me gusta al post');</script>");
    } else {  
        $query_post = $db->query(" SELECT * FROM `mybb_posts` WHERE pid='$post_pid' ");
        while ($p = $db->fetch_array($query_post)) { 
            $post_tid = $p['tid'];
            $post_fid = $p['fid'];
            $post_uid = $p['uid'];
            $post_username = $p['username'];
            $subject = $p['subject'];
            $db->query(" INSERT INTO mybb_op_likes(pid, tid, fid, uid, username, subject, liked_by_uid, liked_by_username) VALUES ('$post_pid','$post_tid','$post_fid','$post_uid','$post_username','$subject','$user_uid','$user_username'); ");
            // echo(" INSERT INTO mybb_op_likes(pid, tid, fid, uid, username, subject, liked_by_uid, liked_by_username) VALUES ('$post_pid','$post_tid','$post_fid','$post_uid','$post_username','$subject','$user_uid','$user_username'); ");
        }
        // echo("<script>alert('Le has dado Me gusta al post');</script>");
       
    }
    
    echo("<script>window.location.replace('https://onepiecegaiden.com/showthread.php?tid=$input_tid&pid=$post_pid#pid$post_pid');</script>");
}
