<?php
// CLI ONLY: IMOTI_CONFIG_PATH=/secure/path/config.php php create-admin.php email@example.com
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if($argc!==2||!filter_var($argv[1],FILTER_VALIDATE_EMAIL)){fwrite(STDERR,"Usage: php create-admin.php email@example.com\n");exit(1);}
$path=getenv('IMOTI_CONFIG_PATH') ?: '';
if(!$path||!is_file($path)){fwrite(STDERR,"Missing config\n");exit(1);}
$cfg=require $path;
echo "Enter password (input may be visible; use a private terminal): ";
$password=trim((string)fgets(STDIN));
if(strlen($password)<16){fwrite(STDERR,"Minimum 16 characters\n");exit(1);}
$db=new PDO($cfg['dsn'],$cfg['user'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$stmt=$db->prepare("INSERT INTO admin_users(email,password_hash,role) VALUES (?,?,'superadmin')");
$stmt->execute([strtolower($argv[1]),password_hash($password,PASSWORD_DEFAULT)]);
echo "Admin created. Clear terminal history if necessary.\n";