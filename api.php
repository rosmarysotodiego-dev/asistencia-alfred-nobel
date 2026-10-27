<?php
declare(strict_types=1);
require __DIR__.'/private/bootstrap.php';
try {
 check_origin();$db=connect_database();$route=$_GET['route']??'data';$method=$_SERVER['REQUEST_METHOD'];ensure(in_array($method,['GET','POST'],true),'Método no permitido.',405);
 $b=[];if($method==='POST'){ensure((int)($_SERVER['CONTENT_LENGTH']??0)<20000,'Solicitud demasiado grande.',413);$b=json_decode(file_get_contents('php://input'),true);ensure(is_array($b),'Solicitud JSON inválida.');}
 if($route==='auth') {
  ensure($method==='POST','Método no permitido.',405);
  if(($b['action']??'')==='logout'){$token=session_token();if($token!=='')query($db,'DELETE FROM sessions WHERE token_hash=?',[hash('sha256',$token)]);set_token_cookie('',-3600);respond(['ok'=>true]);}
  $login=strtolower(value($b,'email',120));$password=$b['password']??'';ensure(is_string($password)&&strlen($password)>0&&strlen($password)<=200,'Completa la contraseña.');$attempt=first($db,'SELECT * FROM login_attempts WHERE email=?',[$login]);ensure(!$attempt||(int)$attempt['expires_at']<millis()||(int)$attempt['attempts']<10,'Demasiados intentos. Intenta nuevamente en 15 minutos.',429);
  $user=first($db,'SELECT * FROM users WHERE (username=? OR email=?) AND active=1',[$login,$login]);
  if(!$user||!password_verify($password,$user['password_hash'])){query($db,'INSERT INTO login_attempts(email,attempts,expires_at) VALUES(?,1,?) ON DUPLICATE KEY UPDATE attempts=IF(expires_at<?,1,attempts+1),expires_at=IF(expires_at<?,VALUES(expires_at),expires_at)',[$login,millis()+900000,millis(),millis()]);throw new AppError('Usuario o contraseña incorrectos.',401);}
  if(($b['action']??'')==='change_password'){
   $next=new_password($b);ensure(!password_verify($next,$user['password_hash']),'La contraseña nueva debe ser diferente de la actual.');
   $db->beginTransaction();$locked=first($db,'SELECT * FROM users WHERE id=? FOR UPDATE',[$user['id']]);ensure($locked&&(int)$locked['active']===1&&password_verify($password,$locked['password_hash']),'La contraseña actual cambió. Intenta nuevamente.',403);
   query($db,'UPDATE users SET password_hash=? WHERE id=?',[password_hash($next,PASSWORD_DEFAULT),$user['id']]);query($db,'DELETE FROM sessions WHERE user_id=?',[$user['id']]);query($db,'DELETE FROM login_attempts WHERE email=?',[$login]);audit($db,$user,'password.change',$user['id']);$db->commit();set_token_cookie('',-3600);respond(['ok'=>true]);
  }
  $token=bin2hex(random_bytes(32));$db->beginTransaction();query($db,'DELETE FROM login_attempts WHERE email=?',[$login]);query($db,'DELETE FROM sessions WHERE expires_at<?',[millis()]);query($db,'INSERT INTO sessions(token_hash,user_id,expires_at) VALUES(?,?,?)',[hash('sha256',$token),$user['id'],millis()+28800000]);audit($db,$user,'login',$user['id']);$db->commit();set_token_cookie($token,28800);respond(['ok'=>true,'sessionToken'=>$token]);
 }
 ensure($route==='data','Ruta no encontrada.',404);$user=current_user($db);ensure($user,'Tu sesión venció. Ingresa nuevamente con tu usuario y contraseña.',401);
 $permissions=['admin'=>['read','students','teachers','attendance','delete','users','audit'],'gestor'=>['read','students','teachers','attendance','delete'],'docente'=>['read','own_classes'],'consulta'=>['read'],'estudiante'=>['read','self_attendance']];$rights=$permissions[$user['role']]??[];
 $can=fn(string $p)=>in_array($p,$rights,true);$permit=function(string $p)use($can){ensure($can($p),'No tienes permiso para esta operación.',403);};$scope=fn(string $program)=>$user['role']!=='docente'||$program===$user['program'];
 if($method==='GET'){
  if($user['role']==='estudiante'){
   $s=first($db,'SELECT id,name,dni,program,semester FROM students WHERE id=?',[$user['student_id']]);ensure($s,'Tu cuenta no tiene un estudiante vinculado.',403);
   $days=['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
   $classes=query($db,"SELECT t.id,t.name,t.subject,t.day,DATE_FORMAT(t.start_time,'%H:%i') AS start,DATE_FORMAT(t.end_time,'%H:%i') AS end,a.status,a.created_at FROM teachers t JOIN class_enrollments e ON e.teacher_id=t.id AND e.student_id=? LEFT JOIN class_sessions c ON c.teacher_id=t.id AND c.class_date=? LEFT JOIN attendance a ON a.session_id=c.id AND a.student_id=? WHERE t.day=? ORDER BY t.start_time",[$s['id'],date('Y-m-d'),$s['id'],$days[(int)date('w')]])->fetchAll();
   $availableClasses=query($db,"SELECT t.id,t.name,t.subject,t.day,DATE_FORMAT(t.start_time,'%H:%i') AS start,DATE_FORMAT(t.end_time,'%H:%i') AS end FROM teachers t JOIN users u ON u.id=t.teacher_user_id AND u.role='docente' AND u.active=1 WHERE t.program=? AND t.semester=? AND NOT EXISTS(SELECT 1 FROM class_enrollments e WHERE e.teacher_id=t.id AND e.student_id=?) ORDER BY t.subject,t.day,t.start_time",[$s['program'],$s['semester'],$s['id']])->fetchAll();
   $enrolledClasses=query($db,"SELECT t.id,t.name,t.subject,t.day,DATE_FORMAT(t.start_time,'%H:%i') AS start,DATE_FORMAT(t.end_time,'%H:%i') AS end FROM teachers t JOIN class_enrollments e ON e.teacher_id=t.id WHERE e.student_id=? ORDER BY t.subject,t.day,t.start_time",[$s['id']])->fetchAll();
   $marks=query($db,'SELECT a.*,s.name,s.program,s.semester,c.subject,c.class_date FROM attendance a JOIN students s ON s.id=a.student_id LEFT JOIN class_sessions c ON c.id=a.session_id WHERE a.student_id=? ORDER BY a.created_at DESC',[$s['id']])->fetchAll();
   respond(['me'=>['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email'],'role'=>$user['role'],'program'=>$s['program'],'permissions'=>$rights],'students'=>[$s],'teachers'=>[],'attendance'=>$marks,'users'=>[],'audit'=>[],'classes'=>$classes,'availableClasses'=>$availableClasses,'enrolledClasses'=>$enrolledClasses,'serverTime'=>date('c')]);
  }
  $permit('read');
  $teacherOnly=$user['role']==='docente';
  $students=query($db,'SELECT s.id,s.name,s.dni,s.program,s.semester,s.verification_hash IS NOT NULL AS hasCode FROM students s'.($teacherOnly?' WHERE EXISTS(SELECT 1 FROM class_enrollments e JOIN teachers t ON t.id=e.teacher_id WHERE e.student_id=s.id AND t.teacher_user_id=?)':'').' ORDER BY s.name',$teacherOnly?[$user['id']]:[])->fetchAll();
  $teachers=query($db,"SELECT id,teacher_user_id,name,email,program,semester,subject,day,DATE_FORMAT(start_time,'%H:%i') AS `start`,DATE_FORMAT(end_time,'%H:%i') AS `end` FROM teachers".($teacherOnly?' WHERE teacher_user_id=?':'').' ORDER BY name',$teacherOnly?[$user['id']]:[])->fetchAll();
  $marks=query($db,'SELECT a.*,s.name,s.program,s.semester,c.subject,c.class_date,c.teacher_id FROM attendance a JOIN students s ON s.id=a.student_id LEFT JOIN class_sessions c ON c.id=a.session_id'.($teacherOnly?' WHERE c.teacher_user_id=?':'').' ORDER BY a.created_at DESC',$teacherOnly?[$user['id']]:[])->fetchAll();
  $enrollments=query($db,'SELECT e.teacher_id,e.student_id FROM class_enrollments e JOIN teachers t ON t.id=e.teacher_id'.($teacherOnly?' WHERE t.teacher_user_id=?':''),$teacherOnly?[$user['id']]:[])->fetchAll();
  $teacherAccounts=$user['role']==='docente'?[['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email']]]:($can('teachers')?query($db,"SELECT id,name,email FROM users WHERE role='docente' AND active=1 ORDER BY name")->fetchAll():[]);

  foreach($students as &$s){$a=null;foreach($marks as $mark){if($mark['student_id']===$s['id']){$a=$mark;break;}}$s['status']=$a['status']??'Pendiente';$s['method']=$a['method']??'—';$s['time']=$a?date('H:i',strtotime($a['created_at'])):'—';$s['attendanceId']=$a['id']??null;}unset($s);
  $users=$can('users')?query($db,'SELECT id,email,username,name,role,program,student_id,active,1 AS linked FROM users ORDER BY name')->fetchAll():[];foreach($users as &$u){$u['active']=(int)$u['active'];}unset($u);
  respond(['me'=>['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email'],'role'=>$user['role'],'program'=>$user['program'],'permissions'=>$rights],'students'=>$students,'teachers'=>$teachers,'enrollments'=>$enrollments,'teacherAccounts'=>$teacherAccounts,'attendance'=>$marks,'users'=>$users,'audit'=>$can('audit')?query($db,'SELECT * FROM audit ORDER BY created_at DESC LIMIT 100')->fetchAll():[]]);
 }
 $action=value($b,'action',40);$target='';$credentials=null;$reauth=false;$programs=['APSTI','ENFERMERÍA','FISIOTERAPIA'];$semesters=['I','II','III','IV','V','VI'];$db->beginTransaction();
 if($action==='attendance.self'){
  $permit('self_attendance');$student=first($db,'SELECT * FROM students WHERE id=?',[$user['student_id']]);ensure($student,'Cuenta sin estudiante vinculado.',403);
  $teacher=value($b,'teacherId',36);$t=first($db,'SELECT * FROM teachers WHERE id=? FOR UPDATE',[$teacher]);ensure($t,'Clase no encontrada.',404);
  $days=['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
  ensure(first($db,'SELECT student_id FROM class_enrollments WHERE teacher_id=? AND student_id=?',[$teacher,$student['id']]),'No estás matriculado en esta clase.',403);
  ensure($t['day']===$days[(int)date('w')],'La clase no corresponde al día de hoy.',403);
  $time=date('H:i:s');ensure($time>=$t['start_time']&&$time<=$t['end_time'],'Solo puedes registrar durante el horario de la clase.',403);
  $class=class_session($db,$t);
  ensure(!first($db,'SELECT id FROM attendance WHERE student_id=? AND session_id=?',[$student['id'],$class['id']]),'Ya registraste tu asistencia en esta clase.',409);
  $target=uid();query($db,'INSERT INTO attendance(id,student_id,session_id,status,method,created_at,actor) VALUES(?,?,?,?,?,?,?)',[$target,$student['id'],$class['id'],'Presente','Manual',now_iso(),$user['email']]);
 }elseif($action==='student.create'){
  $permit('students');$name=value($b,'name',100);$program=value($b,'program',32);$semester=value($b,'semester',3);$dni=empty($b['dni'])?null:value($b,'dni',8);
  ensure(in_array($program,$programs,true)&&in_array($semester,$semesters,true),'Programa o semestre inválido.');ensure(!$dni||preg_match('/^\d{8}$/',$dni),'DNI inválido.');ensure(!$dni||!first($db,'SELECT id FROM students WHERE dni=?',[$dni]),'El DNI ya está registrado.',409);
  $auto=$b['enrollBySemester']??false;ensure(is_bool($auto),'La opción de matrícula debe ser verdadera o falsa.');$code=(string)random_int(100000,999999);$verificationHash=password_hash($code,PASSWORD_DEFAULT);
  $target=next_student_code($db);
  query($db,'INSERT INTO students(id,name,dni,program,semester,verification_hash) VALUES(?,?,?,?,?,?)',[$target,$name,$dni,$program,$semester,$verificationHash]);
  $enrolled=0;
  if($auto){$classes=query($db,'SELECT id FROM teachers WHERE program=? AND semester=? ORDER BY id FOR UPDATE',[$program,$semester])->fetchAll();foreach($classes as $t){query($db,'INSERT INTO class_enrollments(teacher_id,student_id,created_at) VALUES(?,?,?)',[$t['id'],$target,now_iso()]);$enrolled++;}}
  $credentials=['studentCode'=>$code,'studentId'=>$target,'enrolledClasses'=>$enrolled];

 }elseif($action==='student.code'){
  $permit('students');$target=value($b,'id',30);ensure(first($db,'SELECT id FROM students WHERE id=?',[$target]),'Estudiante no encontrado.',404);$code=(string)random_int(100000,999999);query($db,'UPDATE students SET verification_hash=? WHERE id=?',[password_hash($code,PASSWORD_DEFAULT),$target]);$credentials=['studentCode'=>$code,'studentId'=>$target];
 }elseif($action==='student.update'){
  $permit('students');$target=value($b,'id',30);$old=first($db,'SELECT * FROM students WHERE id=? FOR UPDATE',[$target]);ensure($old,'Estudiante no encontrado.',404);
  $name=value($b,'name',100);$dni=empty($b['dni'])?null:value($b,'dni',8);$program=value($b,'program',32);$semester=value($b,'semester',3);
  ensure(in_array($program,$programs,true)&&in_array($semester,$semesters,true),'Programa o semestre inválido.');ensure(!$dni||preg_match('/^\d{8}$/',$dni),'DNI inválido.');ensure(!$dni||!first($db,'SELECT id FROM students WHERE dni=? AND id<>?',[$dni,$target]),'El DNI pertenece a otro estudiante.',409);
  $moved=$old['program']!==$program||$old['semester']!==$semester;
  if($moved){$incompatible=first($db,'SELECT e.teacher_id FROM class_enrollments e JOIN teachers t ON t.id=e.teacher_id WHERE e.student_id=? AND (t.program<>? OR t.semester<>?) LIMIT 1',[$target,$program,$semester]);ensure(!$incompatible||($b['removeIncompatible']??false)===true,'Marca la opción para retirar las matrículas incompatibles con la nueva carrera o semestre.');if($incompatible)query($db,'DELETE e FROM class_enrollments e JOIN teachers t ON t.id=e.teacher_id WHERE e.student_id=? AND (t.program<>? OR t.semester<>?)',[$target,$program,$semester]);}
  query($db,'UPDATE students SET name=?,dni=?,program=?,semester=? WHERE id=?',[$name,$dni,$program,$semester,$target]);query($db,'UPDATE users SET name=?,program=? WHERE student_id=?',[$name,$program,$target]);
  if($moved)query($db,'DELETE s FROM sessions s JOIN users u ON u.id=s.user_id WHERE u.student_id=?',[$target]);
 }elseif($action==='student.delete'){
  $permit('students');$permit('delete');$target=value($b,'id',30);ensure(first($db,'SELECT id FROM students WHERE id=?',[$target]),'Estudiante no encontrado.',404);query($db,'DELETE FROM students WHERE id=?',[$target]);
 }elseif($action==='teacher.save'){
  if($user['role']==='docente'){$permit('own_classes');}else{$permit('teachers');}$target=empty($b['id'])?uid():value($b,'id',36);$name=value($b,'name',100);$email=empty($b['email'])?'':strtolower(value($b,'email',120));$program=value($b,'program',32);$semester=value($b,'semester',3);$subject=value($b,'subject',120);$day=value($b,'day',16);$start=substr(value($b,'start',8),0,5);$end=substr(value($b,'end',8),0,5);
  ensure(in_array($program,$programs,true)&&in_array($semester,$semesters,true)&&in_array($day,['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'],true),'Datos de clase inválidos.');ensure(!$email||filter_var($email,FILTER_VALIDATE_EMAIL),'Correo inválido.');ensure(preg_match('/^([01]\d|2[0-3]):[0-5]\d$/',$start)&&preg_match('/^([01]\d|2[0-3]):[0-5]\d$/',$end)&&$end>$start,'Horario inválido.');ensure(!first($db,'SELECT id FROM teachers WHERE LOWER(name)=LOWER(?) AND day=? AND start_time<? AND end_time>? AND id<>? FOR UPDATE',[$name,$day,$end,$start,$target]),'El docente ya tiene una clase en ese horario.',409);
  if($user['role']==='docente'){ensure($program===$user['program'],'Solo puedes crear clases de tu carrera asignada.',403);$owned=first($db,'SELECT teacher_user_id FROM teachers WHERE id=? FOR UPDATE',[$target]);ensure(!$owned||$owned['teacher_user_id']===$user['id'],'No puedes modificar la clase de otro docente.',403);}
  $teacherUserId=$user['role']==='docente'?$user['id']:(empty($b['teacherUserId'])?null:value($b,'teacherUserId',36));
  if($teacherUserId){$account=first($db,"SELECT * FROM users WHERE id=? AND role='docente' AND active=1",[$teacherUserId]);ensure($account,'Selecciona una cuenta docente activa.');$name=$account['name'];$email=$account['email'];}
  $existingClass=first($db,'SELECT * FROM teachers WHERE id=? FOR UPDATE',[$target]);
  ensure(!first($db,'SELECT e.student_id FROM class_enrollments e JOIN students s ON s.id=e.student_id WHERE e.teacher_id=? AND (s.program<>? OR s.semester<>?) LIMIT 1',[$target,$program,$semester]),'Retira primero las matrículas incompatibles con el nuevo programa o semestre.');
  if($teacherUserId)ensure(!first($db,'SELECT id FROM teachers WHERE teacher_user_id=? AND day=? AND start_time<? AND end_time>? AND id<>? FOR UPDATE',[$teacherUserId,$day,$end,$start,$target]),'La cuenta docente ya tiene una clase en ese horario.',409);
  if($existingClass){query($db,'UPDATE teachers SET name=?,email=?,program=?,semester=?,subject=?,day=?,start_time=?,end_time=?,teacher_user_id=? WHERE id=?',[$name,$email,$program,$semester,$subject,$day,$start,$end,$teacherUserId,$target]);}else{query($db,'INSERT INTO teachers(id,name,email,program,semester,subject,day,start_time,end_time,teacher_user_id) VALUES(?,?,?,?,?,?,?,?,?,?)',[$target,$name,$email,$program,$semester,$subject,$day,$start,$end,$teacherUserId]);}
  if($teacherUserId)query($db,'UPDATE class_sessions SET teacher_user_id=? WHERE teacher_id=? AND teacher_user_id IS NULL',[$teacherUserId,$target]);
 }elseif($action==='enrollment.self'){
  $permit('self_attendance');$target=value($b,'teacherId',36);$student=first($db,'SELECT * FROM students WHERE id=?',[$user['student_id']]);ensure($student,'Cuenta sin estudiante vinculado.',403);
  $t=first($db,'SELECT t.* FROM teachers t WHERE id=? FOR UPDATE',[$target]);ensure($t,'Clase no encontrada.',404);
  ensure($t['program']===$student['program']&&$t['semester']===$student['semester'],'Solo puedes inscribirte en clases de tu carrera y semestre.',403);
  ensure($t['teacher_user_id']&&first($db,"SELECT id FROM users WHERE id=? AND role='docente' AND active=1",[$t['teacher_user_id']]),'La clase todavía no tiene una cuenta docente activa.',403);
  ensure(!first($db,'SELECT student_id FROM class_enrollments WHERE teacher_id=? AND student_id=?',[$target,$student['id']]),'Ya estás inscrito en esta clase.',409);
  query($db,'INSERT INTO class_enrollments(teacher_id,student_id,created_at) VALUES(?,?,?)',[$target,$student['id'],now_iso()]);
 }elseif($action==='enrollment.save'){
  $permit('teachers');$target=value($b,'teacherId',36);$t=first($db,'SELECT * FROM teachers WHERE id=? FOR UPDATE',[$target]);ensure($t,'Clase no encontrada.',404);
  $ids=$b['studentIds']??null;ensure(is_array($ids)&&count($ids)<=1000,'Lista de estudiantes inválida.');$valid=[];
  foreach($ids as $id){ensure(is_string($id)&&strlen($id)<=30,'Código de estudiante inválido.');$s=first($db,'SELECT * FROM students WHERE id=?',[$id]);ensure($s&&$s['program']===$t['program']&&$s['semester']===$t['semester'],'Todos los estudiantes deben pertenecer al programa y semestre de la clase.');$valid[$id]=true;}
  query($db,'DELETE FROM class_enrollments WHERE teacher_id=?',[$target]);foreach(array_keys($valid) as $id)query($db,'INSERT INTO class_enrollments(teacher_id,student_id,created_at) VALUES(?,?,?)',[$target,$id,now_iso()]);

 }elseif($action==='teacher.delete'){
  $permit('teachers');$permit('delete');$target=value($b,'id',36);ensure(first($db,'SELECT id FROM teachers WHERE id=?',[$target]),'Asignación no encontrada.',404);query($db,'DELETE FROM teachers WHERE id=?',[$target]);
 }elseif($action==='attendance.create'){
  $permit('attendance');$student=value($b,'studentId',30);$s=first($db,'SELECT * FROM students WHERE id=?',[$student]);ensure($s,'Estudiante no encontrado.',404);
  $classId=value($b,'teacherId',36);$t=first($db,'SELECT * FROM teachers WHERE id=? FOR UPDATE',[$classId]);ensure($t,'Clase no encontrada.',404);ensure(first($db,'SELECT student_id FROM class_enrollments WHERE teacher_id=? AND student_id=?',[$classId,$student]),'El estudiante no está matriculado en esa clase.');
  $days=['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];ensure($t['day']===$days[(int)date('w')],'Solo se registra la sesión del día de hoy.');
  $code=value($b,'code',6);ensure(preg_match('/^\d{6}$/',$code)&&password_verify($code,$s['verification_hash']),'Código de asistencia incorrecto.',403);$status=value($b,'status',16);ensure(in_array($status,['Presente','Tardanza','Falta'],true),'Estado inválido.');ensure(($b['method']??'')==='Manual','Los lectores físicos aún no están integrados.');
  $c=class_session($db,$t);ensure(!first($db,'SELECT id FROM attendance WHERE student_id=? AND session_id=?',[$student,$c['id']]),'Ya existe asistencia para este estudiante y clase.',409);$target=uid();query($db,'INSERT INTO attendance(id,student_id,session_id,status,method,created_at,actor) VALUES(?,?,?,?,?,?,?)',[$target,$student,$c['id'],$status,'Manual',now_iso(),$user['email']]);

 }elseif($action==='attendance.delete'){
  $permit('attendance');$permit('delete');$target=value($b,'id',36);ensure(first($db,'SELECT id FROM attendance WHERE id=?',[$target]),'Marcación no encontrada.',404);query($db,'DELETE FROM attendance WHERE id=?',[$target]);
 }elseif($action==='user.save'){
  $permit('users');$target=empty($b['id'])?uid():value($b,'id',36);$name=value($b,'name',100);$email=strtolower(value($b,'email',120));$role=value($b,'role',12);ensure(filter_var($email,FILTER_VALIDATE_EMAIL)&&array_key_exists($role,$permissions),'Correo o rol inválido.');$program=$role==='docente'?value($b,'program',32):null;ensure(!$program||in_array($program,$programs,true),'Programa inválido.');$studentId=$role==='estudiante'?value($b,'studentId',30):null;if($studentId){$linked=first($db,'SELECT * FROM students WHERE id=?',[$studentId]);ensure($linked,'Selecciona un estudiante del padrón.');ensure(!first($db,'SELECT id FROM users WHERE student_id=? AND id<>?',[$studentId,$target]),'El estudiante ya tiene una cuenta.',409);$program=$linked['program'];}$active=($b['active']??true)===false?0:1;$existing=first($db,'SELECT * FROM users WHERE id=?',[$target]);ensure(!first($db,'SELECT id FROM users WHERE email=? AND id<>?',[$email,$target]),'Este correo ya está registrado.',409);if($existing){ensure($existing['email']===$email,'El correo de una cuenta creada no se puede cambiar.');ensure($existing['id']!=='primary-admin'||($role==='admin'&&$active===1),'El administrador principal debe permanecer activo.');}
  ensure(!first($db,'SELECT id FROM users WHERE username=? AND id<>?',[$email,$target]),'El correo coincide con otro identificador de acceso.',409);
  $hash=$existing['password_hash']??password_hash(new_password($b),PASSWORD_DEFAULT);
  if($existing){query($db,'UPDATE users SET username=?,name=?,role=?,program=?,active=?,student_id=? WHERE id=?',[$email,$name,$role,$program,$active,$studentId,$target]);}else{query($db,'INSERT INTO users(id,username,email,name,role,program,active,password_hash,student_id) VALUES(?,?,?,?,?,?,?,?,?)',[$target,$email,$email,$name,$role,$program,$active,$hash,$studentId]);}
  if($existing&&($existing['role']!==$role||!$active))query($db,'UPDATE teachers SET teacher_user_id=NULL WHERE teacher_user_id=?',[$target]);
  if(!$active||($existing&&($existing['role']!==$role||$existing['student_id']!==$studentId||$existing['program']!==$program))){query($db,'DELETE FROM sessions WHERE user_id=?',[$target]);$reauth=$target===$user['id'];}
 }elseif($action==='user.password.reset'){
  $permit('users');$target=value($b,'id',36);$next=new_password($b);$resetUser=first($db,'SELECT id,email,username FROM users WHERE id=? FOR UPDATE',[$target]);ensure($resetUser,'Usuario no encontrado.',404);query($db,'DELETE FROM login_attempts WHERE email IN (?,?)',[$resetUser['email'],$resetUser['username']]);
  query($db,'UPDATE users SET password_hash=? WHERE id=?',[password_hash($next,PASSWORD_DEFAULT),$target]);query($db,'DELETE FROM sessions WHERE user_id=?',[$target]);$reauth=$target===$user['id'];

 }else{throw new AppError('Operación no reconocida.');}
 audit($db,$user,$action,$target);$db->commit();if($reauth)set_token_cookie('',-3600);respond(['ok'=>true,'credentials'=>$credentials,'reauth'=>$reauth]);
}catch(Throwable $e){if(isset($db)&&$db->inTransaction())$db->rollBack();if($e instanceof AppError)respond(['error'=>$e->getMessage()],$e->status);error_log('SCAE operation: '.$e->getMessage());if($e instanceof PDOException&&$e->getCode()==='23000')respond(['error'=>'Este registro ya existe o está relacionado con otros datos.'],409);respond(['error'=>'No fue posible guardar los datos. Revisa el registro de errores de Apache y la configuración de MySQL.'],500);}
