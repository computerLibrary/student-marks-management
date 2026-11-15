<?php
require_once __DIR__ . '/../config/database.php';require_once __DIR__ . '/../includes/auth.php';require_login();
$q=$pdo->prepare('SELECT id,class_name FROM classes WHERE teacher_id=? ORDER BY class_name');$q->execute([current_teacher_id()]);$classes=$q->fetchAll();$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$ad=trim($_POST['admission_no']??'');$name=trim($_POST['full_name']??'');$class=(int)($_POST['class_id']??0);$gender=$_POST['gender']??'';
 if(!$ad||!$name||!$class)$error='Complete all required fields.';
 elseif(!in_array($gender,['Male','Female','Other',''],true))$error='Invalid gender selection.';
 else{$own=$pdo->prepare('SELECT id FROM classes WHERE id=? AND teacher_id=?');$own->execute([$class,current_teacher_id()]);
 if(!$own->fetch())$error='Select one of your classes.';
 else{try{$ins=$pdo->prepare('INSERT INTO students(teacher_id,class_id,admission_no,full_name,gender) VALUES(?,?,?,?,?)');$ins->execute([current_teacher_id(),$class,$ad,$name,$gender?:null]);flash('success','Student registered successfully.');redirect('index.php');}catch(PDOException $e){$error='That admission number is already in use in your records.';}}}
}
$pageTitle='Register student';require __DIR__ . '/../includes/header.php';?>
<div class="narrow"><div class="page-heading"><div><span class="eyebrow">ACADEMIC RECORDS</span><h1>Register student</h1></div></div><section class="form-card">
<?php if(!$classes):?><div class="alert error">Create a class before registering students. <a href="../classes/add.php">Add a class</a>.</div><?php endif;?>
<?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif;?>
<form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<label>Admission number<input name="admission_no" required placeholder="e.g. STU001"></label>
<label>Full name<input name="full_name" required placeholder="Student full name"></label>
<label>Class<select name="class_id" required><option value="">Select class</option><?php foreach($classes as $c):?><option value="<?=$c['id']?>"><?=e($c['class_name'])?></option><?php endforeach;?></select></label>
<label>Gender<select name="gender"><option value="">Not specified</option><option>Male</option><option>Female</option><option>Other</option></select></label>
<button class="btn primary" type="submit" <?=!$classes?'disabled':''?>>Save student</button> <a class="btn secondary" href="index.php">Cancel</a></form></section></div>
<?php require __DIR__ . '/../includes/footer.php';?>
