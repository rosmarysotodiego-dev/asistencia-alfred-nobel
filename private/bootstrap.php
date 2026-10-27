<?php
declare(strict_types=1);
date_default_timezone_set('America/Lima');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
class AppError extends RuntimeException {public int $status; public function __construct(string $message,int $status=400){parent::__construct($message);$this->status=$status;}}
function respond(array $data,int $status=200): void {http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function ensure($condition,string $message,int $status=400): void {if(!$condition)throw new AppError($message,$status);}
function value($data,string $key,int $max=120): string {ensure(isset($data[$key])&&is_string($data[$key])&&trim($data[$key])!==''&&preg_match('/^.{1,'.$max.'}$/us',$data[$key])===1,'Campos incompletos o inválidos.');return trim($data[$key]);}
function uid(): string {$hex=bin2hex(random_bytes(16));return substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4).'-'.substr($hex,16,4).'-'.substr($hex,20);}
function now_iso(): string {return gmdate('Y-m-d\TH:i:s.000\Z');}
function millis(): int {return (int)floor(microtime(true)*1000);}
function query(PDO $db,string $sql,array $args=[]): PDOStatement {$st=$db->prepare($sql);$st->execute($args);return $st;}
function first(PDO $db,string $sql,array $args=[]): ?array {$v=query($db,$sql,$args)->fetch();return $v===false?null:$v;}
function session_token(): string {$authorization=$_SERVER['HTTP_AUTHORIZATION']??'';if(strncmp($authorization,'Bearer ',7)===0)return substr($authorization,7);return $_COOKIE['nobel_session']??'';}
function set_token_cookie(string $token,int $duration): void {setcookie('nobel_session',$token,['expires'=>time()+$duration,'path'=>'/','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Strict']);}
function current_user(PDO $db): ?array {$token=session_token();if(!preg_match('/^[a-f0-9]{64}$/',$token))return null;return first($db,'SELECT u.* FROM sessions s JOIN users u ON u.id=s.user_id WHERE s.token_hash=? AND s.expires_at>? AND u.active=1',[hash('sha256',$token),millis()]);}
function audit(PDO $db,array $user,string $action,string $target): void {query($db,'INSERT INTO audit(id,actor,action,target,created_at) VALUES(?,?,?,?,?)',[uid(),$user['email'],$action,$target,now_iso()]);}
function check_origin(): void {if($_SERVER['REQUEST_METHOD']!=='POST')return;$origin=$_SERVER['HTTP_ORIGIN']??'';$expected=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off'?'https':'http').'://'.($_SERVER['HTTP_HOST']??'');ensure($origin!==''&&rtrim($origin,'/')===$expected,'Origen de solicitud no permitido.',403);ensure(strpos($_SERVER['CONTENT_TYPE']??'','application/json')!==false,'Formato no permitido.',415);}
function connect_database(): PDO {ensure(is_file(__DIR__.'/config.php')&&is_file(__DIR__.'/installed.lock'),'Ejecuta install.php para configurar la base de datos.',503);$c=require __DIR__.'/config.php';try{return new PDO('mysql:host='.$c['host'].';port='.$c['port'].';dbname='.$c['database'].';charset=utf8mb4',$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}catch(PDOException $e){error_log('SCAE connection: '.$e->getMessage());throw new AppError('No se pudo conectar a MySQL. Comprueba que MySQL esté iniciado en XAMPP y revisa private/config.php.',503);}}

function new_password(array $data): string {
 $password=$data['newPassword']??null;$confirmation=$data['confirmPassword']??null;
 ensure(is_string($password)&&preg_match_all('/./us',$password)>=8&&strlen($password)<=72,'La contraseña debe tener al menos 8 caracteres y no superar 72 bytes.');
 ensure(is_string($confirmation)&&hash_equals($password,$confirmation),'Las contraseñas nuevas no coinciden.');
 return $password;
}
function class_session(PDO $db,array $t): array {
 $c=first($db,'SELECT * FROM class_sessions WHERE teacher_id=? AND class_date=?',[$t['id'],date('Y-m-d')]);
 if($c)return $c;
 $id=uid();query($db,'INSERT INTO class_sessions(id,teacher_id,teacher_user_id,class_date,subject,teacher_name,program,semester,start_time,end_time) VALUES(?,?,?,?,?,?,?,?,?,?)',[$id,$t['id'],$t['teacher_user_id'],date('Y-m-d'),$t['subject'],$t['name'],$t['program'],$t['semester'],$t['start_time'],$t['end_time']]);
 return ['id'=>$id];
}

function next_student_code(PDO $db): string {
 ensure($db->inTransaction(),'El código debe generarse dentro de una transacción.',500);
 query($db,"INSERT IGNORE INTO student_sequences(name,last_value) VALUES('students',0)");
 $row=first($db,"SELECT last_value FROM student_sequences WHERE name='students' FOR UPDATE");
 $next=(int)$row['last_value'];
 do {
  ensure($next<PHP_INT_MAX-1,'Se alcanzó el límite del contador de estudiantes.',409);
  $next++;$code=str_pad((string)$next,3,'0',STR_PAD_LEFT);
 }while(first($db,'SELECT id FROM students WHERE id=?',[$code]));
 query($db,"UPDATE student_sequences SET last_value=? WHERE name='students'",[$next]);
 return $code;
}
