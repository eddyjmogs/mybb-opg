<?php
// auth.php - Manejo de autenticación
if(!defined('API_LOADED')) exit;

// POST /auth/login
if($_SERVER['REQUEST_METHOD']==='POST' && $route==='auth/login'){
  $in=body(); 
  error_log('=== LOGIN START ===');
  error_log('Login body: ' . print_r($in, true));
  $username=trim($in['username']??''); 
  $password=(string)($in['password']??'');
  
  if($username===''||$password==='') {
    error_log('ERROR: Missing credentials');
    json(['error'=>'Faltan credenciales'],400);
  }
  
  // Login manual sin LoginDataHandler
  error_log('Attempting manual login for: ' . $username);
  
    $query = $db->simple_select('users', 'uid,username,password,salt,loginkey,usergroup,additionalgroups', "username='".$db->escape_string($username)."'");
  $user = $db->fetch_array($query);
  
  error_log('User from DB: ' . print_r($user, true));
  
  if(!$user) {
    error_log('ERROR: User not found');
    json(['error'=>'Credenciales inválidas'],401);
  }
  
  // Verificar password
  $hashed_pass = md5(md5($user['salt']).md5($password));
  error_log('Password check: ' . ($hashed_pass === $user['password'] ? 'MATCH' : 'NO MATCH'));
  
  if($hashed_pass !== $user['password']) {
    error_log('ERROR: Password mismatch');
    json(['error'=>'Credenciales inválidas'],401);
  }
  
  error_log('Login successful for uid: ' . $user['uid']);
  
  $token = jwt_sign(['uid'=>(int)$user['uid']]);
  error_log('Token generated: ' . substr($token, 0, 50) . '...');
  
    // Calcular grupos (primario + adicionales)
    $additional = array_filter(array_map('intval', explode(',', $user['additionalgroups'] ?? '')));
    $groups = array_values(array_filter(array_unique(array_merge([(int)$user['usergroup']], $additional))));

    $response = [
      'token' => $token,
      'user' => [
        'uid' => (int)$user['uid'],
        'username' => $user['username'],
        'usergroup' => (int)$user['usergroup'],
        'groups' => $groups
      ]
    ];
  error_log('Response: ' . print_r($response, true));
  error_log('=== LOGIN END ===');
  json($response);
}

// GET /me
if($_SERVER['REQUEST_METHOD']==='GET' && $route==='me'){
  $uid = bearer_uid(); if(!$uid) json(['error'=>'No autorizado'],401);
  $u = get_user($uid); if(!$u) json(['error'=>'No encontrado'],404);
  $additional = array_filter(array_map('intval', explode(',', $u['additionalgroups'] ?? '')));
  $groups = array_values(array_filter(array_unique(array_merge([(int)$u['usergroup']], $additional))));
  json(['user'=>[
    'uid'=>(int)$u['uid'],
    'username'=>$u['username'],
    'usergroup'=>(int)$u['usergroup'],
    'groups'=>$groups,
    'postnum'=>(int)$u['postnum']
  ]]);
}
