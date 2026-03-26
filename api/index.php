<?php
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'api/index.php');
chdir(dirname(__DIR__));
require_once './global.php';

if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}
if (!isset($_SERVER['HTTP_AUTHORIZATION']) && function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $_SERVER['HTTP_AUTHORIZATION'] = $headers['Authorization'];
    }
}

header('Content-Type: application/json; charset=utf-8');

function json($data, int $code=200){ http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }

function body(){ $raw=file_get_contents('php://input')?:''; $d=json_decode($raw,true); return is_array($d)?$d:[]; }

const JWT_SECRET = 'test'; // MUÉVELO a inc/config.php en producción

function b64url($s){ return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }

function jwt_sign(array $payload, int $ttl=604800){ // 7 días
  $h = b64url(json_encode(['alg'=>'HS256','typ'=>'JWT']));
  $now = TIME_NOW;
  $p = b64url(json_encode($payload + ['iat'=>$now,'nbf'=>$now,'exp'=>$now+$ttl]));
  $s = b64url(hash_hmac('sha256', "$h.$p", JWT_SECRET, true));
  return "$h.$p.$s";
}

function jwt_payload($jwt){ $a=explode('.',$jwt); if(count($a)!==3) return null;
  [$h,$p,$s]=$a; $sig=b64url(hash_hmac('sha256', "$h.$p", JWT_SECRET, true));
  if(!hash_equals($sig,$s)) return null;
  $pl=json_decode(base64_decode(strtr($p,'-_','+/')),true);
  $now=TIME_NOW; if(($pl['nbf']??0)>$now || ($pl['exp']??0)<$now) return null;
  return $pl;
}

// Si viene el token de la app, hacer login automático
if(isset($_GET['app_token'])){
  $token = $_GET['app_token'];
  $payload = jwt_payload($token);
  
  if($payload && isset($payload['uid'])){
    $uid = (int)$payload['uid'];
    $user = get_user($uid);
    
    if($user){
      // Crear sesión de MyBB
      require_once MYBB_ROOT.'inc/functions_user.php';
      my_setcookie('mybbuser', $user['uid'].'_'.$user['loginkey'], null, true, 'lax');
      $mybb->user = $user;
    }
  }
}

function bearer_uid(){
  $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  if ($hdr === '' && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $hdr = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
  }
  if ($hdr === '' && function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $hdr = $headers['Authorization'] ?? '';
  }
  
  if(!preg_match('/Bearer\s+([A-Za-z0-9\-\._~\+\/]+=*)/', $hdr, $m)) return null;
  $pl = jwt_payload($m[1]); 
  return $pl['uid'] ?? null;
}

// CORS dev (ajusta dominios permitidos)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if($origin){
  header("Access-Control-Allow-Origin: {$origin}");
  header('Vary: Origin');
  header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
}

if(($_SERVER['REQUEST_METHOD'] ?? '')==='OPTIONS'){ http_response_code(204); exit; }

$route = $_GET['route'] ?? '';

define('API_LOADED', true);

try {
  // Cargar módulos de la API
  require_once __DIR__ . '/auth.php';
  require_once __DIR__ . '/ficha.php';
  require_once __DIR__ . '/threads.php';
  require_once __DIR__ . '/thread.php';
  require_once __DIR__ . '/new_reply.php';

  json(['error'=>'Ruta no encontrada'],404);
} catch(Throwable $e){
  error_log('=== API EXCEPTION ===');
  error_log('Route: ' . $route);
  error_log('Error: ' . $e->getMessage());
  error_log('File: ' . $e->getFile() . ':' . $e->getLine());
  error_log('Trace: ' . $e->getTraceAsString());
  json(['error'=>'Excepción','message'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()],500);
}
