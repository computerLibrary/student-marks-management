<?php
require_once __DIR__ . '/../config/database.php';require_once __DIR__ . '/../includes/auth.php';require_login();
if($_SERVER['REQUEST_METHOD']!=='POST'){redirect('record.php');}verify_csrf();
$tid=current_teacher_id();$class=(int)($_POST['class_id']??0);$subject=(int)($_POST['subject_id']??0);$assessment=trim($_POST['assessment_name']??'');$max=$_POST['max_score']??'';$term=$_POST['term']??'';$year=(int)($_POST['academic_year']??0);$date=$_POST['assessment_date']??'';$scores=$_POST['scores']??[];
if(!$class||!$subject||!$assessment||!is_numeric($max)||(float)$max<=0||!in_array($term,['Term 1','Term 2','Term 3'],true)||$year<2000||$year>2100||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||!is_array($scores)){flash('error','Please provide valid assessment details.');redirect('record.php');}
$own=$pdo->prepare('SELECT id FROM classes WHERE id=? AND teacher_id=?');$own->execute([$class,$tid]);$subjectOwn=$pdo->prepare('SELECT id FROM subjects WHERE id=? AND teacher_id=?');$subjectOwn->execute([$subject,$tid]);
if(!$own->fetch()||!$subjectOwn->fetch()){http_response_code(403);exit('Invalid class or subject.');}
$studentQuery=$pdo->prepare('SELECT id FROM students WHERE class_id=? AND teacher_id=?');$studentQuery->execute([$class,$tid]);$valid=array_fill_keys(array_map('intval',$studentQuery->fetchAll(PDO::FETCH_COLUMN)),true);
$insert=$pdo->prepare('INSERT INTO marks(teacher_id,class_id,student_id,subject_id,assessment_name,term,academic_year,assessment_date,score,max_score) VALUES(?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE score=VALUES(score),max_score=VALUES(max_score),assessment_date=VALUES(assessment_date)');
$saved=0;$invalid=0;
try{$pdo->beginTransaction();foreach($scores as $studentId=>$score){$sid=(int)$studentId;if(!isset($valid[$sid])||$score===''||!is_numeric($score)){continue;}$score=(float)$score;if($score<0||$score>(float)$max){$invalid++;continue;}$insert->execute([$tid,$class,$sid,$subject,$assessment,$term,$year,$date,$score,$max]);$saved++;}$pdo->commit();}
catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
flash($saved?'success':'error',$saved." student mark(s) saved.".($invalid?" {$invalid} invalid score(s) skipped.":''));
redirect('index.php');
