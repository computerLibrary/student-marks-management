<?php
require_once __DIR__ . '/includes/functions.php';
if (!empty($_SESSION['teacher_id'])) {
    redirect('/dashboard.php');
}
$pageTitle = 'Welcome';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="hero-copy">
        <span class="eyebrow">SCHOOL ACADEMIC RECORDS</span>
        <h1>Manage student marks with confidence.</h1>
        <p>Organise classes, register students, set up subjects and record assessment marks in one simple workspace. Export class results to Excel-compatible files whenever you need them.</p>
        <div class="actions">
            <a class="btn primary" href="/auth/register.php">Create teacher account</a>
            <a class="btn secondary" href="/auth/login.php">Teacher login</a>
        </div>
    </div>
    <div class="hero-panel">
        <div class="panel-label">ACADEMIC OVERVIEW</div>
        <div class="mock-stat"><span>Classes</span><strong>Organised</strong></div>
        <div class="mock-stat"><span>Marks</span><strong>Easy to record</strong></div>
        <div class="mock-stat"><span>Reports</span><strong>Excel export</strong></div>
        <div class="panel-note">A simple digital gradebook for teachers.</div>
    </div>
</section>
<section class="feature-grid">
    <article class="feature"><div class="feature-icon">01</div><h3>Manage classes</h3><p>Create and organise the classes you teach.</p></article>
    <article class="feature"><div class="feature-icon">02</div><h3>Register students</h3><p>Keep student names and admission numbers organised.</p></article>
    <article class="feature"><div class="feature-icon">03</div><h3>Record marks</h3><p>Set different maximum marks for each subject and assessment.</p></article>
    <article class="feature"><div class="feature-icon">04</div><h3>Export reports</h3><p>Download recorded marks in a format that opens in Excel.</p></article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
