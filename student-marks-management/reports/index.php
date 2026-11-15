<?php
require_once __DIR__ . '/../config/database.php';require_once __DIR__ . '/../includes/auth.php';require_login();$tid=current_teacher_id();
$q=$pdo->prepare('SELECT id,class_name FROM classes WHERE teacher_id=? ORDER BY class_name');$q->execute([$tid]);$classes=$q->fetchAll();
$pageTitle='Reports';require __DIR__ . '/../includes/header.php';?>
<div class="page-heading"><div><span class="eyebrow">ACADEMIC REPORTING</span><h1>Download reports</h1><p class="muted">Export your recorded marks to a CSV file that opens in Microsoft Excel, LibreOffice and Google Sheets.</p></div></div>
<section class="form-card report-card"><h2>Export marks</h2><form method="get" action="export.php">
<label>Class<select name="class_id"><option value="0">All my classes</option><?php foreach($classes as $c):?><option value="<?=$c['id']?>"><?=e($c['class_name'])?></option><?php endforeach;?></select></label>
<label>Academic year<input type="number" name="academic_year" min="2000" max="2100" placeholder="All years"></label>
<label>Term<select name="term"><option value="">All terms</option><option>Term 1</option><option>Term 2</option><option>Term 3</option></select></label>
<button class="btn primary" type="submit">↓ Download Excel-compatible CSV</button>
</form><p class="help">The export includes student admission number, student name, class, subject, assessment, term, year, score, maximum mark and percentage.</p></section>
<?php require __DIR__ . '/../includes/footer.php';?>
