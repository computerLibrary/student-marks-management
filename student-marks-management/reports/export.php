<?php
require_once __DIR__ . '/../config/database.php';require_once __DIR__ . '/../includes/auth.php';require_login();
$tid=current_teacher_id();$class=(int)($_GET['class_id']??0);$year=trim($_GET['academic_year']??'');$term=trim($_GET['term']??'');
$sql='SELECT s.admission_no,s.full_name,c.class_name,u.subject_name,m.assessment_name,m.term,m.academic_year,m.assessment_date,m.score,m.max_score FROM marks m JOIN students s ON s.id=m.student_id JOIN classes c ON c.id=m.class_id JOIN subjects u ON u.id=m.subject_id WHERE m.teacher_id=?';
$params=[$tid];if($class>0){$sql.=' AND m.class_id=?';$params[]=$class;}if($year!==''&&ctype_digit($year)){$sql.=' AND m.academic_year=?';$params[]=(int)$year;}if($term!==''){$sql.=' AND m.term=?';$params[]=$term;}$sql.=' ORDER BY c.class_name,s.full_name,u.subject_name,m.academic_year,m.term';
$q=$pdo->prepare($sql);$q->execute($params);$rows=$q->fetchAll();
$filename='student_marks_'.date('Ymd_His').'.csv';
header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="'.$filename.'"');
$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");
fputcsv($out,['Admission Number','Student Name','Class','Subject','Assessment','Term','Academic Year','Assessment Date','Score','Maximum Mark','Percentage']);
foreach($rows as $r){$pct=$r['max_score']>0?round(($r['score']/$r['max_score'])*100,2):0;fputcsv($out,[$r['admission_no'],$r['full_name'],$r['class_name'],$r['subject_name'],$r['assessment_name'],$r['term'],$r['academic_year'],$r['assessment_date'],$r['score'],$r['max_score'],$pct]);}
fclose($out);exit;
