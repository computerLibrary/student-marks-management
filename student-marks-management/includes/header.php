<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'Student Marks Management';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | SMMS</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="/">SMMS <span>Student Marks Management</span></a>
    <nav>
        <?php if (!empty($_SESSION['teacher_id'])): ?>
            <a href="/dashboard.php">Dashboard</a>
            <a href="/classes/index.php">Classes</a>
            <a href="/students/index.php">Students</a>
            <a href="/subjects/index.php">Subjects</a>
            <a href="/marks/index.php">Marks</a>
            <a href="/reports/index.php">Reports</a>
            <a class="nav-button" href="/auth/logout.php">Logout</a>
        <?php else: ?>
            <a href="/auth/login.php">Login</a>
            <a class="nav-button" href="/auth/register.php">Create account</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
<?php show_flash(); ?>
