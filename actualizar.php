<?php
declare(strict_types=1);
if(!in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true)){http_response_code(403);exit('Ejecuta la actualización desde localhost en el servidor.');}
session_start();if(empty($_SESSION['upgrade_csrf']))$_SESSION['upgrade_csrf']=bin2hex(random_bytes(32));
$message='';
if($_SERVER['REQUEST_METHOD']==='POST')try{
 if(!hash_equals($_SESSION['upgrade_csrf'],$_POST['csrf']??''))throw new RuntimeException('Sesión vencida. Recarga la página.');
 if(!is_file(__DIR__.'/private/installed.lock'))throw new RuntimeException('Primero instala el sistema con install.php.');
 $c=require __DIR__.'/private/config.php';
 $db=new PDO('mysql:host='.$c['host'].';port='.$c['port'].';dbname='.$c['database'].';charset=utf8mb4',$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 require __DIR__.'/private/migrations.php';migrate_manual($db);$message='Actualización completada. Ya puedes configurar cuentas docentes, matrículas y contraseñas.';
}catch(Throwable $e){error_log('SCAE upgrade: '.$e->getMessage());$message='No se pudo completar. Revisa el registro de Apache, la conexión y los permisos ALTER/CREATE de MySQL. Puedes repetir esta actualización después de corregir el error.';}
?><!doctype html><html lang="es"><meta charset="utf-8"><title>Actualizar SCAE</title><link rel="stylesheet" href="assets/accessibility.css?v=20261023r2"><body style="font:17px sans-serif;max-width:720px;margin:50px auto;line-height:1.7"><h1>Actualizar base de datos</h1><p>Antes de continuar, exporta una copia de tu base desde phpMyAdmin y pausa el uso del sistema. La actualización agrega los vínculos de cuentas docentes, matrículas, sesiones de clase y el contador de códigos estudiantiles. Conserva los registros existentes.</p><p><?=htmlspecialchars($message,ENT_QUOTES,'UTF-8')?></p><form method="post"><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['upgrade_csrf'],ENT_QUOTES,'UTF-8')?>"><button>Actualizar base de datos</button></form><p><a href="index.php">Abrir sistema</a></p><script src="assets/accessibility.js?v=20261023" defer></script></body></html>
