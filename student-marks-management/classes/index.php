<?php
require_once __DIR__ . '/../config/database.php'; require_once __DIR__ . '/../includes/auth.php'; require_login();
$stmt=$pdo->prepare('SELECT c.*, (SELECT COUNT(*) FROM students s WHERE s.class_id=c.id) student_count FROM classes c WHERE c.teacher_id=? ORDER BY c.class_name');
$stmt->execute([current_teacher_id()]); $rows=$stmt->fetchAll();
$pageTitle='Classes'; require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">ACADEMIC SETUP</span><h1>Classes</h1><p class="muted">Create and manage your class groups.</p></div><a class="btn primary" href="add.php">＋ Add class</a></div>
<div class="table-card"><table><thead><tr><th>Class name</th><th>Academic year</th><th>Students</th><th>Created</th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="4" class="empty">No classes yet. Add your first class.</td></tr><?php endif; ?>
<?php foreach($rows as $r): ?><tr><td><strong><?=e($r['class_name'])?></strong></td><td><?=e($r['academic_year'])?></td><td><?= (int)$r['student_count'] ?></td><td><?=e($r['created_at'])?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
