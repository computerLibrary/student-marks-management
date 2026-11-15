<?php
require_once __DIR__ . '/../config/database.php'; require_once __DIR__ . '/../includes/auth.php'; require_login();
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$name=trim($_POST['class_name']??'');$year=trim($_POST['academic_year']??'');
 if($name===''||$year==='')$error='Enter a class name and academic year.';
 else{$q=$pdo->prepare('INSERT INTO classes(teacher_id,class_name,academic_year) VALUES(?,?,?)');try{$q->execute([current_teacher_id(),$name,$year]);flash('success','Class added successfully.');redirect('index.php');}catch(PDOException $e){$error='This class already exists for that academic year.';}}
}
$pageTitle='Add class';require __DIR__ . '/../includes/header.php';?>
<div class="narrow"><div class="page-heading"><div><span class="eyebrow">ACADEMIC SETUP</span><h1>Add class</h1></div></div><section class="form-card">
<?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif;?>
<form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<label>Class name<input name="class_name" placeholder="e.g. S4 A" required></label>
<label>Academic year<input name="academic_year" placeholder="e.g. 2026" required></label>
<button class="btn primary" type="submit">Save class</button> <a class="btn secondary" href="index.php">Cancel</a></form></section></div>
<?php require __DIR__ . '/../includes/footer.php';?>
