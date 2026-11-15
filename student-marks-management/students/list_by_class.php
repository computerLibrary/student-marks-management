<?php
require_once __DIR__ . '/../config/database.php';require_once __DIR__ . '/../includes/auth.php';require_login();
header('Content-Type: application/json; charset=utf-8');
$class=(int)($_GET['class_id']??0);
$q=$pdo->prepare('SELECT id,admission_no,full_name FROM students WHERE class_id=? AND teacher_id=? ORDER BY full_name');$q->execute([$class,current_teacher_id()]);
echo json_encode($q->fetchAll(),JSON_HEX_TAG|JSON_HEX_AMP);
