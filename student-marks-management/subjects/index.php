<?php
require_once __DIR__ . '/../config/database.php';require_once __DIR__ . '/../includes/auth.php';require_login();
$q=$pdo->prepare('SELECT * FROM subjects WHERE teacher_id=? ORDER BY subject_name');$q->execute([current_teacher_id()]);$rows=$q->fetchAll();
$pageTitle='Subjects';require __DIR__ . '/../includes/header.php';?>
<div class="page-heading"><div><span class="eyebrow">ACADEMIC SETUP</span><h1>Subjects</h1><p class="muted">Set up subjects and their default maximum marks.</p></div><a class="btn primary" href="add.php">＋ Add subject</a></div>
<div class="table-card"><table><thead><tr><th>Subject</th><th>Subject code</th><th>Default maximum mark</th></tr></thead><tbody><?php if(!$rows):?><tr><td colspan="3" class="empty">No subjects added yet.</td></tr><?php endif;?><?php foreach($rows as $r):?><tr><td><strong><?=e($r['subject_name'])?></strong></td><td><?=e($r['subject_code']?:'—')?></td><td><?=e($r['default_max_mark'])?></td></tr><?php endforeach;?></tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php';?>
