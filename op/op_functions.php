<?php

function does_ficha_exist($uid) {
    global $db;
    $ficha = select_one_query_with_id('mybb_op_fichas', 'fid', $uid);
    $moderada = $ficha['aprobado_por']!= 'sin_aprobar';

    return $ficha != null && $moderada;
}

// Sirve para ficha y tienda
function select_one_query_with_id($table_name, $id_name, $id) {
    global $db;

    $obj = null;

    $query = $db->query("
        SELECT * FROM $table_name WHERE $id_name='$id'
    ");

    while ($q = $db->fetch_array($query)) {
        $obj = $q;
    }

    return $obj;
}

function get_obj_from_query($query) {
    global $db;
    
    $obj = null;
    while ($q = $db->fetch_array($query)) {
        $obj = $q;
    }
    return $obj;
}

function log_audit($uid, $username, $categoria, $log) {
    global $db;
    $db->query("
        INSERT INTO `mybb_op_audit_general`(`uid`, `username`, `user_uid`, `categoria`, `log`) VALUES ('$uid','$username','$uid','$categoria', '$log');
    ");
}


function log_audit_currency($uid, $username, $user_uid, $categoria, $currency, $amount) {
    global $db;

    $ficha = null;
    $query_ficha = $db->query(" SELECT * FROM mybb_op_fichas WHERE fid='$user_uid' ");
    while ($q = $db->fetch_array($query_ficha)) { $ficha = $q; }

    if ($ficha && $currency == 'nikas') {

        $old = $ficha['nika'];
        $diff = intval($amount) - intval($old);
        $new = $amount;
        $log = "Nikas: $old->$new ($diff)";
        $db->query(" UPDATE `mybb_op_fichas` SET nika='$amount' WHERE fid='$user_uid' ");
        $db->query(" INSERT INTO `mybb_op_audit_general`(`uid`, `username`, `user_uid`, `categoria`, `log`) VALUES ('$uid','$username','$user_uid','$categoria', '$log'); ");
    }

    if ($ficha && $currency == 'kuros') {

        $old = $ficha['kuro'];
        $diff = intval($amount) - intval($old);
        $new = $amount;
        $log = "Kuros: $old->$new ($diff)";
        $db->query(" UPDATE `mybb_op_fichas` SET kuro='$new' WHERE fid='$user_uid' ");
        $db->query(" INSERT INTO `mybb_op_audit_general`(`uid`, `username`, `user_uid`, `categoria`, `log`) VALUES ('$uid','$username','$user_uid','$categoria', '$log'); ");
    }

    if ($ficha && $currency == 'puntos_oficio') {
        $old = $ficha['puntos_oficio'];
        $diff = intval($amount) - intval($old);
        $new = $amount;
        $log = "Puntos de oficio: $old->$new ($diff)";
        $db->query(" UPDATE `mybb_op_fichas` SET puntos_oficio='$amount' WHERE fid='$user_uid' ");
        $db->query(" INSERT INTO `mybb_op_audit_general`(`uid`, `username`, `user_uid`, `categoria`, `log`) VALUES ('$uid','$username','$user_uid','$categoria', '$log'); ");
    }

    if ($ficha && $currency == 'berries') {
        $old = $ficha['berries'];
        $diff = intval($amount) - intval($old);
        $new = $amount;
        $log = "Berries: $old->$new ($diff)";
        $db->query(" UPDATE `mybb_op_fichas` SET berries='$amount' WHERE fid='$user_uid' ");
        $db->query(" INSERT INTO `mybb_op_audit_general`(`uid`, `username`, `user_uid`, `categoria`, `log`) VALUES ('$uid','$username','$user_uid','$categoria', '$log'); ");
    }

    if ($ficha && $currency == 'experiencia') {
        $user = null;
        $query_user = $db->query(" SELECT * FROM mybb_users WHERE uid='$user_uid' ");
        while ($q = $db->fetch_array($query_user)) { $user = $q; }
        
        $old = $user['newpoints'];
        $diff = intval($amount) - intval($old);
        $new = $amount;
        $log = "Berries: $old->$new ($diff)";
        $db->query(" UPDATE `mybb_users` SET newpoints='$amount' WHERE `uid`='$user_uid';");
        $db->query(" INSERT INTO `mybb_op_audit_general`(`uid`, `username`, `user_uid`, `categoria`, `log`) VALUES ('$uid','$username','$user_uid','$categoria', '$log'); ");
    }

}

function is_narra($uid) {
    global $db;

    $has_narra_role = false;

    // $query = $db->query(" SELECT * FROM `mybb_users` WHERE uid='$uid' AND (usergroup = '14' OR additionalgroups LIKE '%14%' OR usergroup = '6' OR additionalgroups LIKE '%6%' OR usergroup = '4' OR additionalgroups LIKE '%4%'); ");
    $query = $db->query(" SELECT * FROM `mybb_users` WHERE uid='$uid' AND (additionalgroups LIKE  '%15%'); ");
    while ($q = $db->fetch_array($query)) { $has_narra_role = true; }

    return (
        $has_narra_role
    );    
}

function is_staff($uid) {
    global $db;

    $has_staff_role = false;

    // $query = $db->query(" SELECT * FROM `mybb_users` WHERE uid='$uid' AND (usergroup = '14' OR additionalgroups LIKE '%14%' OR usergroup = '6' OR additionalgroups LIKE '%6%' OR usergroup = '4' OR additionalgroups LIKE '%4%'); ");
    $query = $db->query(" SELECT * FROM `mybb_users` WHERE uid='$uid' AND (usergroup = '14' OR additionalgroups LIKE '%14%' OR usergroup = '6' OR usergroup = '4' OR usergroup = '16'); ");
    while ($q = $db->fetch_array($query)) { $has_staff_role = true; }

    return (
        $uid == '5'   || //Oda
        $uid == '10'  || //Cadmus
        $uid == '154' || // Giorno
        // $uid == '172' || // Crucio
        $uid == '17'  || //God Usoop. Kuro
        $uid == '7'   || //Lance
        $uid == '16'  || //Kinemon. Lance
        // $uid == '89'  || // Umibozu
        // $uid == '92'  || //mod Gong. Umi
        // $uid == '235' || //Hardo. Multi Umi
        // $uid == '799' || //Motumba
        // $uid == '3'   || //Timsy
        $uid == '117' || //mod katacristo. Ubben
        $uid == '90'  || //Ubben        
        $uid == '118' || // mod oppengarpimer. Fuji
        $uid == '25'  || // Fuji
        $uid == '121' || // mod condoriano. Juuken
        $uid == '23'  || // Juuken
        $uid == '123' || // Gretta
        $uid == '157' || // Moderadora Lola. Gretta
        $uid == '258' || // Kaku. Sirius
        $uid == '213' || // Sirius
        $uid == '263' || // Jango.Lobo
        $uid == '252' || // Wenzaemon
        $uid == '262' || // Key
        $has_staff_role || 
        $uid == '4'  //Hitsu
    );    
}

function is_peti_mod($uid) {
    return ($uid == '1');
}

function is_mod($uid) {
    return (is_user($uid));
}

function is_user($uid) {
    // admin: 1
    // Terence Blackmore: 2
    // Timsy: 3
    // Mr2 Bon Clay: 4
    // Oda: 5
    // Testoman: 6
    // Juan y Medio: 7
    // Kurosame 9

    return ($uid == '1' || $uid == '3' || $uid == '6' || $uid == '7' || $uid == '9');
}
