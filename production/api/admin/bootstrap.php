<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
function respond(int $code,array $payload): never {http_response_code($code);echo json_encode($payload,JSON_UNESCAPED_UNICODE);exit;}
if (PHP_VERSION_ID < 80100) respond(503,['error'=>'PHP 8.1+ required']);
$path=getenv('IMOTI_CONFIG_PATH') ?: '';
if (!$path || !is_file($path)) respond(503,['error'=>'Not configured']);
$cfg=require $path;
if (empty($cfg['dsn']) || empty($cfg['rate_secret'])) respond(503,['error'=>'Not configured']);
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') respond(403,['error'=>'HTTPS required']);
session_name('__Host-imoti_admin');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Strict']);
ini_set('session.use_strict_mode','1');
session_start();
try{$db=new PDO($cfg['dsn'],$cfg['user'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);}
catch(Throwable $e){error_log('DB connection failed');respond(503,['error'=>'Service unavailable']);}
function requirePost(): void {if(($_SERVER['REQUEST_METHOD']??'')!=='POST')respond(405,['error'=>'Method not allowed']);}
function checkOrigin(array $cfg): void {
 $origin=$_SERVER['HTTP_ORIGIN']??'';
 if($origin!==($cfg['allowed_origin']??''))respond(403,['error'=>'Origin denied']);
}
function csrfToken(): string {
 if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));
 return $_SESSION['csrf'];
}
function verifyCsrf(): void {
 $token=$_SERVER['HTTP_X_CSRF_TOKEN']??'';
 if(!is_string($token)||!hash_equals(csrfToken(),$token))respond(403,['error'=>'Invalid CSRF token']);
}
function loggedIn(): array {
 if(empty($_SESSION['admin_id']))respond(401,['error'=>'Login required']);
 global $db;
 $q=$db->prepare('SELECT id,email,role FROM admin_users WHERE id=? AND is_active=1');
 $q->execute([$_SESSION['admin_id']]);
 $user=$q->fetch(PDO::FETCH_ASSOC);
 if(!$user){$_SESSION=[];respond(401,['error'=>'Login required']);}
 return $user;
}