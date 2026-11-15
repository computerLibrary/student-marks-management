<?php
require_once __DIR__ . '/../config/database.php'; require_once __DIR__ . '/../includes/auth.php'; require_login();
$q=$pdo->prepare('SELECT s.*,c.class_name FROM students s JOIN classes c ON c.id=s.class_id WHERE s.teacher_id=? ORDER BY c.class_name,s.full_name');$q->execute([current_teacher_id()]);$rows=$q->fetchAll();
$pageTitle='Students';require __DIR__ . '/../includes/header.php';?>
<div class="page-heading"><div><span class="eyebrow">ACADEMIC RECORDS</span><h1>Students</h1><p class="muted">Register and view students in your classes.</p></div><a class="btn primary" href="add.php">＋ Register student</a></div>
<div class="table-card"><table><thead><tr><th>Admission number</th><th>Student name</th><th>Class</th><th>Gender</th></tr></thead><tbody>
<?php if(!$rows):?><tr><td colspan="4" class="empty">No students registered yet.</td></tr><?php endif;?>
<?php foreach($rows as $r):?><tr><td><?=e($r['admission_no'])?></td><td><strong><?=e($r['full_name'])?></strong></td><td><?=e($r['class_name'])?></td><td><?=e($r['gender']?:'—')?></td></tr><?php endforeach;?>
</tbody></table></div><?php require __DIR__ . '/../includes/footer.php';?>
