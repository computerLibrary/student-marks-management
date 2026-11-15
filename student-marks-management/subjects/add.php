<?php
require_once __DIR__ . '/../config/database.php';require_once __DIR__ . '/../includes/auth.php';require_login();$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$name=trim($_POST['subject_name']??'');$code=trim($_POST['subject_code']??'');$max=$_POST['default_max_mark']??'';
 if(!$name||!is_numeric($max)||(float)$max<=0)$error='Enter a subject name and a maximum mark greater than zero.';
 else{try{$q=$pdo->prepare('INSERT INTO subjects(teacher_id,subject_name,subject_code,default_max_mark) VALUES(?,?,?,?)');$q->execute([current_teacher_id(),$name,$code?:null,$max]);flash('success','Subject added successfully.');redirect('index.php');}catch(PDOException $e){$error='This subject or subject code already exists in your account.';}}
}
$pageTitle='Add subject';require __DIR__ . '/../includes/header.php';?>
<div class="narrow"><div class="page-heading"><div><span class="eyebrow">ACADEMIC SETUP</span><h1>Add subject</h1></div></div><section class="form-card"><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif;?>
<form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<label>Subject name<input name="subject_name" required placeholder="e.g. Mathematics"></label>
<label>Subject code (optional)<input name="subject_code" placeholder="e.g. MAT"></label>
<label>Default maximum mark<input type="number" name="default_max_mark" min="0.01" step="0.01" required value="100"></label>
<p class="help">You can change the maximum mark for each assessment when recording marks.</p>
<button class="btn primary">Save subject</button> <a class="btn secondary" href="index.php">Cancel</a></form></section></div>
<?php require __DIR__ . '/../includes/footer.php';?>
