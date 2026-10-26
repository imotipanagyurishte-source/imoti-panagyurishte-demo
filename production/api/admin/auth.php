<?php
require __DIR__.'/bootstrap.php';
$action=$_GET['action']??'';
if($action==='csrf' && ($_SERVER['REQUEST_METHOD']??'')==='GET'){
 respond(200,['csrf'=>csrfToken(),'authenticated'=>!empty($_SESSION['admin_id'])]);
}
if($action==='me' && ($_SERVER['REQUEST_METHOD']??'')==='GET'){
 respond(200,['user'=>loggedIn()]);
}
requirePost();
checkOrigin($cfg);
verifyCsrf();
if($action==='logout'){
 $_SESSION=[];session_regenerate_id(true);
 respond(200,['ok'=>true]);
}
if($action!=='login')respond(404,['error'=>'Unknown action']);
if((int)($_SERVER['CONTENT_LENGTH']??0)>4096)respond(413,['error'=>'Payload too large']);
$data=json_decode(file_get_contents('php://input',false,null,0,4097),true);
if(!is_array($data))respond(400,['error'=>'Invalid JSON']);
$email=strtolower(trim((string)($data['email']??'')));
$password=$data['password']??'';
if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190||!is_string($password)||strlen($password)>1024)respond(422,['error'=>'Invalid credentials']);
$key=hash_hmac('sha256',($_SERVER['REMOTE_ADDR']??'').'|'.$email,$cfg['rate_secret']);
try{
 $db->beginTransaction();
 $q=$db->prepare('SELECT COUNT(*) FROM admin_login_attempts WHERE key_hash=? AND attempted_at>NOW()-INTERVAL 15 MINUTE');
 $q->execute([$key]);
 $attempts=(int)$q->fetchColumn();
 if($attempts>=5){$db->rollBack();respond(429,['error'=>'Too many attempts. Try later.']);}
 $q=$db->prepare('INSERT INTO admin_login_attempts(key_hash) VALUES (?)');$q->execute([$key]);
 $db->commit();
 $q=$db->prepare('SELECT id,password_hash,role FROM admin_users WHERE email=? AND is_active=1 LIMIT 1');
 $q->execute([$email]);$user=$q->fetch(PDO::FETCH_ASSOC);
 if(!$user||!password_verify($password,$user['password_hash']))respond(401,['error'=>'Invalid credentials']);
 session_regenerate_id(true);
 $_SESSION['admin_id']=(int)$user['id'];
 $_SESSION['csrf']=bin2hex(random_bytes(32));
 $q=$db->prepare('UPDATE admin_users SET last_login_at=NOW() WHERE id=?');$q->execute([$user['id']]);
 respond(200,['ok'=>true,'csrf'=>$_SESSION['csrf'],'role'=>$user['role']]);
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();error_log('Admin auth failure: '.get_class($e));respond(503,['error'=>'Service unavailable']);}