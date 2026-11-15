<?php
require_once __DIR__ . '/../config/database.php';require_once __DIR__ . '/../includes/auth.php';require_login();$tid=current_teacher_id();
$c=$pdo->prepare('SELECT id,class_name FROM classes WHERE teacher_id=? ORDER BY class_name');$c->execute([$tid]);$classes=$c->fetchAll();
$s=$pdo->prepare('SELECT id,subject_name,default_max_mark FROM subjects WHERE teacher_id=? ORDER BY subject_name');$s->execute([$tid]);$subjects=$s->fetchAll();
$pageTitle='Record marks';require __DIR__ . '/../includes/header.php';?>
<div class="page-heading"><div><span class="eyebrow">ASSESSMENT RECORDS</span><h1>Record student marks</h1><p class="muted">Choose a class, subject and assessment, then enter each student's score.</p></div></div>
<?php if(!$classes||!$subjects):?><div class="alert error">Before recording marks, add at least one <a href="../classes/add.php">class</a> and one <a href="../subjects/add.php">subject</a>.</div><?php else:?>
<section class="form-card wide-card">
<form method="post" action="save.php" id="marks-form">
<input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<div class="form-grid">
<label>Class<select name="class_id" id="class_id" required><option value="">Select class</option><?php foreach($classes as $v):?><option value="<?=$v['id']?>"><?=e($v['class_name'])?></option><?php endforeach;?></select></label>
<label>Subject<select name="subject_id" id="subject_id" required><option value="">Select subject</option><?php foreach($subjects as $v):?><option value="<?=$v['id']?>" data-max="<?=e($v['default_max_mark'])?>"><?=e($v['subject_name'])?></option><?php endforeach;?></select></label>
<label>Assessment name<input name="assessment_name" required placeholder="e.g. Mid-term test"></label>
<label>Maximum mark<input type="number" name="max_score" id="max_score" min="0.01" step="0.01" value="" required></label>
<label>Term<select name="term" required><option value="">Select term</option><option>Term 1</option><option>Term 2</option><option>Term 3</option></select></label>
<label>Academic year<input type="number" name="academic_year" min="2000" max="2100" value="<?=date('Y')?>" required></label>
<label>Assessment date<input type="date" name="assessment_date" value="<?=date('Y-m-d')?>" required></label>
</div>
<div id="students-area" class="students-area"><p class="muted">Select a class to load its students.</p></div>
<button class="btn primary" type="submit">Save marks</button>
</form></section>
<script>
window.SMMS_SUBJECTS = <?=json_encode(array_map(fn($x)=>['id'=>(int)$x['id'],'max'=>(float)$x['default_max_mark']],$subjects),JSON_HEX_TAG|JSON_HEX_AMP)?>;
</script>
<?php endif;?>
<?php require __DIR__ . '/../includes/footer.php';?>
