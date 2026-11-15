<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
if (!empty($_SESSION['teacher_id'])) redirect('/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT id, full_name, password_hash FROM teachers WHERE email = ?');
    $stmt->execute([$email]);
    $teacher = $stmt->fetch();
    if ($teacher && password_verify($password, $teacher['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['teacher_id'] = (int)$teacher['id'];
        $_SESSION['teacher_name'] = $teacher['full_name'];
        redirect('/dashboard.php');
    }
    $error = 'Email or password is incorrect.';
}
$pageTitle = 'Teacher login';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
<section class="form-card">
    <span class="eyebrow">WELCOME BACK</span><h1>Teacher login</h1>
    <p class="muted">Sign in to access your marks management dashboard.</p>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Email address<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
        <label>Password<input type="password" name="password" required></label>
        <button class="btn primary full" type="submit">Log in</button>
    </form>
    <p class="form-foot">New teacher? <a href="register.php">Create an account</a></p>
</section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
