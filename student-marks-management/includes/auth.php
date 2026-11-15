<?php
require_once __DIR__ . '/functions.php';

function require_login(): void {
    if (empty($_SESSION['teacher_id'])) {
        flash('error', 'Please log in to continue.');
        redirect('/auth/login.php');
    }
}
