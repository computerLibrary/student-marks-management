<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
if (!empty($_SESSION['teacher_id'])) redirect('/dashboard.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must contain at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $check = $pdo->prepare('SELECT id FROM teachers WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO teachers (full_name, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['teacher_id'] = (int)$pdo->lastInsertId();
            $_SESSION['teacher_name'] = $name;
            flash('success', 'Your teacher account has been created.');
            redirect('/dashboard.php');
        }
    }
}
$pageTitle = 'Create account';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
<section class="form-card">
    <span class="eyebrow">GET STARTED</span><h1>Create teacher account</h1>
    <p class="muted">Create an account to manage your academic records.</p>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Full name<input name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>"></label>
        <label>Email address<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
        <label>Password<input type="password" name="password" minlength="8" required></label>
        <label>Confirm password<input type="password" name="confirm_password" minlength="8" required></label>
        <button class="btn primary full" type="submit">Create account</button>
    </form>
    <p class="form-foot">Already registered? <a href="login.php">Log in</a></p>
</section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
