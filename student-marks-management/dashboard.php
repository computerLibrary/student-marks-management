<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
$tid = current_teacher_id();
$counts = [];
foreach (['classes' => 'classes', 'students' => 'students', 'subjects' => 'subjects', 'marks' => 'marks'] as $label => $table) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE teacher_id = ?");
    $stmt->execute([$tid]);
    $counts[$label] = (int)$stmt->fetchColumn();
}
$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">YOUR WORKSPACE</span><h1>Welcome, <?= e($_SESSION['teacher_name'] ?? 'Teacher') ?></h1><p class="muted">Here is an overview of your academic records.</p></div></div>
<div class="stats-grid">
    <div class="stat-card"><span>Total classes</span><strong><?= $counts['classes'] ?></strong><a href="classes/index.php">Manage classes →</a></div>
    <div class="stat-card"><span>Total students</span><strong><?= $counts['students'] ?></strong><a href="students/index.php">Manage students →</a></div>
    <div class="stat-card"><span>Total subjects</span><strong><?= $counts['subjects'] ?></strong><a href="subjects/index.php">Manage subjects →</a></div>
    <div class="stat-card"><span>Mark entries</span><strong><?= $counts['marks'] ?></strong><a href="marks/index.php">View marks →</a></div>
</div>
<h2 class="section-title">Quick actions</h2>
<div class="quick-grid">
    <a class="quick-card" href="classes/add.php"><b>＋ Add a class</b><span>Create a class group</span></a>
    <a class="quick-card" href="students/add.php"><b>＋ Register student</b><span>Add a student to a class</span></a>
    <a class="quick-card" href="subjects/add.php"><b>＋ Add a subject</b><span>Set a subject and maximum mark</span></a>
    <a class="quick-card" href="marks/record.php"><b>＋ Record marks</b><span>Enter assessment results</span></a>
    <a class="quick-card" href="reports/index.php"><b>↓ Download reports</b><span>Export marks for your classes</span></a>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
