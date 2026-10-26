<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function reply(int $code, string $message): never {
 http_response_code($code); echo json_encode(['message'=>$message], JSON_UNESCAPED_UNICODE); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Allow: POST'); reply(405,'Методът не е разрешен.'); }
if (strtolower((string)($_SERVER['CONTENT_TYPE'] ?? '')) !== 'application/json') {
 $ct = strtolower(trim(explode(';',(string)($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
 if ($ct !== 'application/json') reply(415,'Очаква се JSON.');
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 12000) reply(413,'Заявката е твърде голяма.');
$configPath = getenv('IMOTI_CONFIG_PATH') ?: '';
if (!$configPath || !is_file($configPath)) reply(503,'Услугата още не е конфигурирана.');
$config = require $configPath;
if (!$config['dsn'] || !$config['rate_secret']) reply(503,'Услугата още не е конфигурирана.');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== $config['allowed_origin']) reply(403,'Непозволен произход на заявката.');
$body = json_decode(file_get_contents('php://input', false, null, 0, 12001), true);
if (!is_array($body)) reply(400,'Невалидни данни.');
if (!empty($body['website'])) reply(200,'Заявката е получена.');
function field(array $b,string $key,int $max,bool $required=false): string {
 $v=$b[$key] ?? '';
 if (!is_string($v)) reply(422,'Невалидно поле.');
 $v=trim($v);
 if (($required && $v==='') || mb_strlen($v)>$max) reply(422,'Проверете попълнените полета.');
 return $v;
}
$name=field($body,'name',120,true); $phone=field($body,'phone',30,true);
$town=field($body,'town',120,true); $kind=field($body,'kind',40,true);
$purpose=field($body,'purpose',10,true); $visit=field($body,'visit',150);
$notes=field($body,'notes',2000);
if (!preg_match('/^\+?[0-9 ()-]{8,25}$/D',$phone)) reply(422,'Невалиден телефон.');
if (!in_array($kind,['Апартамент','Къща','Парцел/земя','Търговски имот','Друг'],true)) reply(422,'Невалиден вид имот.');
if (!in_array($purpose,['sale','rent'],true)) reply(422,'Невалиден вид сделка.');
if (($body['privacy_consent'] ?? false) !== true) reply(422,'Необходимо е потвърждение за обработката на данни.');
$area=$body['area'] ?? null;
if ($area==='' || $area===null) $area=null;
elseif (!is_numeric($area) || (float)$area<=0 || (float)$area>100000000) reply(422,'Невалидна площ.');
else $area=(float)$area;
try {
 $db=new PDO($config['dsn'],$config['user'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
 $ip=(string)($_SERVER['REMOTE_ADDR'] ?? '');
 $hash=hash_hmac('sha256',$ip,$config['rate_secret']);
 $db->beginTransaction();
 $stmt=$db->prepare('INSERT INTO request_rate_limits (key_hash,window_start,request_count) VALUES (?,NOW(),1) ON DUPLICATE KEY UPDATE request_count=IF(window_start < NOW()-INTERVAL 1 HOUR,1,request_count+1), window_start=IF(window_start < NOW()-INTERVAL 1 HOUR,NOW(),window_start)');
 $stmt->execute([$hash]);
 $stmt=$db->prepare('SELECT request_count FROM request_rate_limits WHERE key_hash=? FOR UPDATE');
 $stmt->execute([$hash]); $count=(int)$stmt->fetchColumn();
 if ($count>5) { $db->rollBack(); reply(429,'Твърде много заявки. Опитайте по-късно.'); }
 $id=sprintf('%s-%s-%s-%s-%s',bin2hex(random_bytes(4)),bin2hex(random_bytes(2)),bin2hex(random_bytes(2)),bin2hex(random_bytes(2)),bin2hex(random_bytes(6)));
 $stmt=$db->prepare('INSERT INTO managed_property_requests (public_id,owner_name,phone,town,property_type,purpose,area_sqm,preferred_visit,notes,privacy_notice_version) VALUES (?,?,?,?,?,?,?,?,?,?)');
 $stmt->execute([$id,$name,$phone,$town,$kind,$purpose,$area,$visit,$notes,'draft-v1']);
 $db->commit();
 reply(201,'Заявката е приета. Ще се свържем с Вас.');
} catch (Throwable $e) {
 error_log('Managed request API error: '.get_class($e));
 reply(500,'Възникна временна грешка. Опитайте отново.');
}