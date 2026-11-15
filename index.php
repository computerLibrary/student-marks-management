<?php
declare(strict_types=1);

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params(['httponly'=>true, 'secure'=>$secure, 'samesite'=>'Lax']);
session_start();
date_default_timezone_set('Africa/Kigali');

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): never { header('Location: '.$url); exit; }
function csrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419); exit('Session expired. Please go back and try again.');
    }
}
function flash(string $msg, string $type='success'): void { $_SESSION['flash']=['msg'=>$msg,'type'=>$type]; }
function require_login(): void { if (empty($_SESSION['teacher_id'])) redirect('?page=login'); }
function db(): PDO {
    static $pdo=null;
    if ($pdo instanceof PDO) return $pdo;
    $host=getenv('MYSQLHOST') ?: '127.0.0.1';
    $port=getenv('MYSQLPORT') ?: '3306';
    $name=getenv('MYSQLDATABASE') ?: 'student_marks_db';
    $user=getenv('MYSQLUSER') ?: 'root';
    $pass=getenv('MYSQLPASSWORD') ?: '';
    $pdo=new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",$user,$pass,[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false
    ]);
    $sql = [
      "CREATE TABLE IF NOT EXISTS teachers (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(150) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
      "CREATE TABLE IF NOT EXISTS school_classes (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, teacher_id BIGINT UNSIGNED NOT NULL, class_name VARCHAR(100) NOT NULL, academic_year VARCHAR(20) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_class (teacher_id,class_name,academic_year), FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE)",
      "CREATE TABLE IF NOT EXISTS subjects (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, teacher_id BIGINT UNSIGNED NOT NULL, subject_name VARCHAR(120) NOT NULL, subject_code VARCHAR(30) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_subject (teacher_id,subject_name), FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE)",
      "CREATE TABLE IF NOT EXISTS students (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, teacher_id BIGINT UNSIGNED NOT NULL, class_id BIGINT UNSIGNED NOT NULL, student_code VARCHAR(50) NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE, FOREIGN KEY (class_id) REFERENCES school_classes(id) ON DELETE CASCADE, UNIQUE KEY uq_student_code (teacher_id,student_code))",
      "CREATE TABLE IF NOT EXISTS assessments (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, teacher_id BIGINT UNSIGNED NOT NULL, class_id BIGINT UNSIGNED NOT NULL, subject_id BIGINT UNSIGNED NOT NULL, assessment_name VARCHAR(120) NOT NULL, assessment_type VARCHAR(80) NOT NULL, academic_year VARCHAR(20) NOT NULL, term VARCHAR(40) NULL, max_marks DECIMAL(8,2) NOT NULL, assessment_date DATE NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE, FOREIGN KEY (class_id) REFERENCES school_classes(id) ON DELETE CASCADE, FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE)",
      "CREATE TABLE IF NOT EXISTS marks (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, assessment_id BIGINT UNSIGNED NOT NULL, student_id BIGINT UNSIGNED NOT NULL, score DECIMAL(8,2) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uq_mark (assessment_id,student_id), FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE, FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE)",
      "CREATE TABLE IF NOT EXISTS grading_scales (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, teacher_id BIGINT UNSIGNED NOT NULL, grade_label VARCHAR(10) NOT NULL, min_percentage DECIMAL(5,2) NOT NULL, max_percentage DECIMAL(5,2) NOT NULL, description VARCHAR(100) NULL, UNIQUE KEY uq_grade (teacher_id,grade_label), FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE)"
    ];
    foreach ($sql as $q) $pdo->exec($q);
    return $pdo;
}
function rows(string $sql, array $args=[]): array { $s=db()->prepare($sql); $s->execute($args); return $s->fetchAll(); }
function run(string $sql, array $args=[]): void { $s=db()->prepare($sql); $s->execute($args); }
function grade_for(float $pct, int $teacher): string {
    $g=rows('SELECT grade_label FROM grading_scales WHERE teacher_id=? AND ? BETWEEN min_percentage AND max_percentage ORDER BY min_percentage DESC LIMIT 1',[$teacher,$pct]);
    return $g[0]['grade_label'] ?? ($pct>=80?'A':($pct>=70?'B':($pct>=60?'C':($pct>=50?'D':'F'))));
}

$page=$_GET['page'] ?? 'home';
$teacher=(int)($_SESSION['teacher_id'] ?? 0);
try {
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        check_csrf();
        $action=$_POST['action'] ?? '';
        if ($action==='register') {
            $name=trim($_POST['full_name'] ?? ''); $email=strtolower(trim($_POST['email'] ?? '')); $pw=$_POST['password'] ?? '';
            if ($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($pw)<8) throw new RuntimeException('Enter your name, a valid email and a password of at least 8 characters.');
            run('INSERT INTO teachers(full_name,email,password_hash) VALUES(?,?,?)',[$name,$email,password_hash($pw,PASSWORD_DEFAULT)]);
            session_regenerate_id(true); $_SESSION['teacher_id']=(int)db()->lastInsertId(); $_SESSION['teacher_name']=$name; flash('Your account has been created. Welcome!'); redirect('?page=dashboard');
        }
        if ($action==='login') {
            $u=rows('SELECT * FROM teachers WHERE email=?',[strtolower(trim($_POST['email'] ?? ''))]);
            if (!$u || !password_verify($_POST['password'] ?? '',$u[0]['password_hash'])) throw new RuntimeException('Incorrect email or password.');
            session_regenerate_id(true); $_SESSION['teacher_id']=(int)$u[0]['id']; $_SESSION['teacher_name']=$u[0]['full_name']; flash('Welcome back!'); redirect('?page=dashboard');
        }
        require_login(); $teacher=(int)$_SESSION['teacher_id'];
        if ($action==='logout') { session_unset(); session_destroy(); redirect('?page=home'); }
        if ($action==='add_class') {
            $n=trim($_POST['class_name'] ?? ''); $y=trim($_POST['academic_year'] ?? date('Y'));
            if ($n==='') throw new RuntimeException('Enter a class name.');
            run('INSERT INTO school_classes(teacher_id,class_name,academic_year) VALUES(?,?,?)',[$teacher,$n,$y]); flash('Class added.');
        }
        if ($action==='add_subject') {
            $n=trim($_POST['subject_name'] ?? '');
            if ($n==='') throw new RuntimeException('Enter a subject name.');
            run('INSERT INTO subjects(teacher_id,subject_name,subject_code) VALUES(?,?,?)',[$teacher,$n,trim($_POST['subject_code'] ?? '')]); flash('Subject added.');
        }
        if ($action==='add_student') {
            $cid=(int)($_POST['class_id'] ?? 0); $cl=rows('SELECT id FROM school_classes WHERE id=? AND teacher_id=?',[$cid,$teacher]);
            if (!$cl || trim($_POST['first_name'] ?? '')==='' || trim($_POST['last_name'] ?? '')==='') throw new RuntimeException('Select your class and enter the student name.');
            run('INSERT INTO students(teacher_id,class_id,student_code,first_name,last_name) VALUES(?,?,?,?,?)',[$teacher,$cid,trim($_POST['student_code'] ?? '') ?: null,trim($_POST['first_name']),trim($_POST['last_name'])]); flash('Student registered.');
        }
        if ($action==='add_assessment') {
            $cid=(int)$_POST['class_id']; $sid=(int)$_POST['subject_id']; $max=(float)$_POST['max_marks'];
            if (!rows('SELECT id FROM school_classes WHERE id=? AND teacher_id=?',[$cid,$teacher]) || !rows('SELECT id FROM subjects WHERE id=? AND teacher_id=?',[$sid,$teacher]) || $max<=0) throw new RuntimeException('Check the class, subject and maximum marks.');
            run('INSERT INTO assessments(teacher_id,class_id,subject_id,assessment_name,assessment_type,academic_year,term,max_marks,assessment_date) VALUES(?,?,?,?,?,?,?,?,?)',[$teacher,$cid,$sid,trim($_POST['assessment_name']),trim($_POST['assessment_type']),trim($_POST['academic_year']),trim($_POST['term'] ?? ''),$max,$_POST['assessment_date'] ?: null]); flash('Assessment created. You can now record marks.');
        }
        if ($action==='save_marks') {
            $aid=(int)$_POST['assessment_id'];
            $a=rows('SELECT * FROM assessments WHERE id=? AND teacher_id=?',[$aid,$teacher]);
            if (!$a) throw new RuntimeException('Assessment not found.');
            $students=rows('SELECT id FROM students WHERE class_id=? AND teacher_id=?',[$a[0]['class_id'],$teacher]);
            foreach ($students as $st) {
                $sid=(int)$st['id']; $raw=$_POST['score'][$sid] ?? '';
                if ($raw==='') continue;
                if (!is_numeric($raw) || (float)$raw<0 || (float)$raw>(float)$a[0]['max_marks']) throw new RuntimeException('Every mark must be between zero and the assessment maximum.');
                run('INSERT INTO marks(assessment_id,student_id,score) VALUES(?,?,?) ON DUPLICATE KEY UPDATE score=VALUES(score)',[$aid,$sid,(float)$raw]);
            }
            flash('Marks saved successfully.');
        }
        if ($action==='save_grade') {
            $label=trim($_POST['grade_label']); $min=(float)$_POST['min_percentage']; $max=(float)$_POST['max_percentage'];
            if ($label==='' || $min<0 || $max>100 || $min>$max) throw new RuntimeException('Enter a valid grade and percentage range.');
            run('INSERT INTO grading_scales(teacher_id,grade_label,min_percentage,max_percentage,description) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE min_percentage=VALUES(min_percentage),max_percentage=VALUES(max_percentage),description=VALUES(description)',[$teacher,$label,$min,$max,trim($_POST['description'] ?? '')]); flash('Grading scale saved.');
        }
        redirect('?page='.($_POST['return_page'] ?? 'dashboard'));
    }

    if ($page==='export') {
        require_login();
        $data=rows('SELECT c.class_name,c.academic_year,st.student_code,st.first_name,st.last_name,s.subject_name,a.assessment_name,a.assessment_type,a.max_marks,m.score,ROUND((m.score/a.max_marks)*100,2) AS percentage FROM marks m JOIN assessments a ON a.id=m.assessment_id JOIN students st ON st.id=m.student_id JOIN school_classes c ON c.id=a.class_id JOIN subjects s ON s.id=a.subject_id WHERE a.teacher_id=? ORDER BY c.class_name,st.last_name,s.subject_name,a.assessment_name',[$teacher]);
        if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            $book=new \PhpOffice\PhpSpreadsheet\Spreadsheet(); $sheet=$book->getActiveSheet(); $sheet->fromArray(['Class','Academic Year','Student ID','First Name','Last Name','Subject','Assessment','Type','Maximum Marks','Score','Percentage'],null,'A1');
            $r=2; foreach($data as $d){$sheet->fromArray(array_values($d),null,'A'.$r++);}
            $writer=new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book); header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename="student-marks.xlsx"'); $writer->save('php://output'); exit;
        }
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="student-marks.csv"'); $out=fopen('php://output','w'); fputcsv($out,['Class','Academic Year','Student ID','First Name','Last Name','Subject','Assessment','Type','Maximum Marks','Score','Percentage']); foreach($data as $d) fputcsv($out,array_values($d)); fclose($out); exit;
    }
} catch (Throwable $ex) {
    $error=$ex instanceof PDOException && $ex->getCode()==='23000' ? 'That record already exists. Check for duplicate class, subject or student ID.' : $ex->getMessage();
    if ($ex instanceof PDOException && !isset($_SESSION['teacher_id'])) $error='The database is not ready yet. Please try again shortly.';
}
$flash=$_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$logged=isset($_SESSION['teacher_id']); $teacher=(int)($_SESSION['teacher_id'] ?? 0);
$classes=$logged?rows('SELECT * FROM school_classes WHERE teacher_id=? ORDER BY class_name',[$teacher]):[];
$subjects=$logged?rows('SELECT * FROM subjects WHERE teacher_id=? ORDER BY subject_name',[$teacher]):[];
$assessments=$logged?rows('SELECT a.*,c.class_name,s.subject_name FROM assessments a JOIN school_classes c ON c.id=a.class_id JOIN subjects s ON s.id=a.subject_id WHERE a.teacher_id=? ORDER BY a.created_at DESC',[$teacher]):[];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Marks Manager</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>
body{background:#f4f7fb;color:#172033}.navbar{box-shadow:0 2px 12px #12213b12}.hero{padding:70px 0}.card{border:0;border-radius:16px;box-shadow:0 6px 24px #20304a0c}.brand{font-weight:800;letter-spacing:.2px}.form-control,.form-select{padding:.7rem;border-radius:10px}.btn{border-radius:10px}.table thead th{background:#f1f5fa;color:#526176;font-size:.85rem}.metric{font-size:1.8rem;font-weight:800}.nav-pills .nav-link{border-radius:10px}
</style></head><body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary"><div class="container"><a class="navbar-brand brand" href="?">Marks Manager</a><div class="ms-auto d-flex gap-2"><?php if($logged): ?><span class="navbar-text text-white d-none d-md-inline">Hello, <?=e($_SESSION['teacher_name'] ?? 'Teacher')?></span><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="logout"><button class="btn btn-outline-light btn-sm">Logout</button></form><?php else: ?><a class="btn btn-outline-light btn-sm" href="?page=login">Login</a><a class="btn btn-light text-primary btn-sm" href="?page=register">Create account</a><?php endif; ?></div></div></nav>
<main class="container py-4">
<?php if(!empty($error)): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?>
<?php if($flash): ?><div class="alert alert-<?=e($flash['type'])?>"><?=e($flash['msg'])?></div><?php endif; ?>
<?php if(!$logged && $page==='home'): ?>
<section class="hero row align-items-center"><div class="col-lg-7"><span class="badge text-bg-primary mb-3">Teacher marks platform</span><h1 class="display-4 fw-bold">Student results, organised in one place.</h1><p class="lead text-secondary my-4">Manage your classes, subjects and assessments. Record marks using any maximum score and export your results to Excel.</p><a href="?page=register" class="btn btn-primary btn-lg me-2">Create teacher account</a><a href="?page=login" class="btn btn-outline-primary btn-lg">Teacher login</a></div><div class="col-lg-5 mt-4"><div class="card p-4"><h5>What you can do</h5><p class="mb-2">✓ Manage classes and students</p><p class="mb-2">✓ Organise subjects and assessments</p><p class="mb-2">✓ Enter marks out of 20, 50, 80, 100 or custom values</p><p class="mb-0">✓ Download all recorded marks for Excel</p></div></div></section>
<?php elseif(!$logged && in_array($page,['login','register'],true)): ?>
<div class="row justify-content-center py-5"><div class="col-md-6 col-lg-5"><div class="card p-4 p-md-5"><h2 class="fw-bold mb-3"><?=$page==='login'?'Teacher login':'Create teacher account'?></h2><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="<?=$page==='login'?'login':'register'?>"><?php if($page==='register'): ?><label class="form-label">Full name</label><input class="form-control mb-3" name="full_name" required><?php endif; ?><label class="form-label">Email address</label><input class="form-control mb-3" type="email" name="email" required><label class="form-label">Password</label><input class="form-control mb-3" type="password" name="password" minlength="8" required><button class="btn btn-primary w-100"><?=$page==='login'?'Login':'Create account'?></button></form><p class="text-secondary mt-3 mb-0 small"><?php if($page==='login'): ?>New teacher? <a href="?page=register">Create an account</a><?php else: ?>Already registered? <a href="?page=login">Login</a><?php endif; ?></p></div></div></div>
<?php elseif($logged): ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4"><div><h2 class="fw-bold mb-1">Teacher dashboard</h2><div class="text-secondary">Manage your academic records</div></div><a class="btn btn-success" href="?page=export">Download Excel report</a></div>
<div class="row g-3 mb-4"><div class="col-6 col-lg-3"><div class="card p-3"><div class="text-secondary">Classes</div><div class="metric"><?=count($classes)?></div></div></div><div class="col-6 col-lg-3"><div class="card p-3"><div class="text-secondary">Subjects</div><div class="metric"><?=count($subjects)?></div></div></div><div class="col-6 col-lg-3"><div class="card p-3"><div class="text-secondary">Assessments</div><div class="metric"><?=count($assessments)?></div></div></div><div class="col-6 col-lg-3"><div class="card p-3"><div class="text-secondary">Students</div><div class="metric"><?=count(rows('SELECT id FROM students WHERE teacher_id=?',[$teacher]))?></div></div></div></div>
<div class="row g-4">
<div class="col-lg-6"><div class="card p-4 mb-4"><h5>Add a class</h5><form method="post" class="row g-2"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="add_class"><div class="col-md-6"><input class="form-control" name="class_name" placeholder="e.g. Senior 4" required></div><div class="col-md-3"><input class="form-control" name="academic_year" placeholder="2026" value="<?=date('Y')?>" required></div><div class="col-md-3"><button class="btn btn-primary w-100">Add</button></div></form><hr><h6>Your classes</h6><?php if(!$classes): ?><p class="text-secondary mb-0">No classes yet.</p><?php else: ?><ul class="mb-0"><?php foreach($classes as $c): ?><li><?=e($c['class_name'])?> <span class="text-secondary">(<?=e($c['academic_year'])?>)</span></li><?php endforeach; ?></ul><?php endif; ?></div>
<div class="card p-4"><h5>Add a subject</h5><form method="post" class="row g-2"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="add_subject"><div class="col-md-7"><input class="form-control" name="subject_name" placeholder="e.g. Mathematics" required></div><div class="col-md-3"><input class="form-control" name="subject_code" placeholder="Code"></div><div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div></form><hr><h6>Your subjects</h6><?php if(!$subjects): ?><p class="text-secondary mb-0">No subjects yet.</p><?php else: ?><ul class="mb-0"><?php foreach($subjects as $s): ?><li><?=e($s['subject_name'])?></li><?php endforeach; ?></ul><?php endif; ?></div></div>
<div class="col-lg-6"><div class="card p-4 mb-4"><h5>Register a student</h5><form method="post" class="row g-2"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="add_student"><div class="col-md-6"><input class="form-control" name="first_name" placeholder="First name" required></div><div class="col-md-6"><input class="form-control" name="last_name" placeholder="Last name" required></div><div class="col-md-5"><input class="form-control" name="student_code" placeholder="Student ID (optional)"></div><div class="col-md-5"><select class="form-select" name="class_id" required><option value="">Choose class</option><?php foreach($classes as $c): ?><option value="<?=$c['id']?>"><?=e($c['class_name'])?> — <?=e($c['academic_year'])?></option><?php endforeach; ?></select></div><div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div></form><p class="small text-secondary mt-2 mb-0">Add classes first. Student IDs must be unique within your account.</p></div>
<div class="card p-4"><h5>Create an assessment</h5><form method="post" class="row g-2"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="add_assessment"><div class="col-md-6"><label class="form-label small">Assessment name</label><input class="form-control" name="assessment_name" placeholder="Midterm test" required></div><div class="col-md-6"><label class="form-label small">Type</label><input class="form-control" name="assessment_type" placeholder="Test / Exam / Assignment" required></div><div class="col-md-6"><label class="form-label small">Class</label><select class="form-select" name="class_id" required><option value="">Choose class</option><?php foreach($classes as $c): ?><option value="<?=$c['id']?>"><?=e($c['class_name'])?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label small">Subject</label><select class="form-select" name="subject_id" required><option value="">Choose subject</option><?php foreach($subjects as $s): ?><option value="<?=$s['id']?>"><?=e($s['subject_name'])?></option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label small">Maximum marks</label><input class="form-control" type="number" min=".01" step=".01" name="max_marks" placeholder="e.g. 50" required></div><div class="col-md-4"><label class="form-label small">Academic year</label><input class="form-control" name="academic_year" value="<?=date('Y')?>" required></div><div class="col-md-4"><label class="form-label small">Term</label><input class="form-control" name="term" placeholder="Term 1"></div><div class="col-md-8"><label class="form-label small">Date (optional)</label><input class="form-control" type="date" name="assessment_date"></div><div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary w-100">Create assessment</button></div></form></div></div></div>
<div class="card p-4 mt-4"><div class="d-flex justify-content-between flex-wrap gap-2"><h5>Record marks</h5><span class="text-secondary small">Scores cannot exceed each assessment's maximum</span></div><?php if(!$assessments): ?><p class="text-secondary">Create a class, subject and assessment first.</p><?php else: ?><form method="get" class="row g-2 mb-3"><input type="hidden" name="page" value="marks"><div class="col-md-8"><select class="form-select" name="assessment_id" required><option value="">Select assessment</option><?php foreach($assessments as $a): ?><option value="<?=$a['id']?>" <?=((int)($_GET['assessment_id']??0)===(int)$a['id'])?'selected':''?>><?=e($a['class_name'])?> — <?=e($a['subject_name'])?> — <?=e($a['assessment_name'])?> (out of <?=e($a['max_marks'])?>)</option><?php endforeach; ?></select></div><div class="col-md-4"><button class="btn btn-outline-primary w-100">Load students</button></div></form><?php endif; ?>
<?php if($page==='marks' && !empty($_GET['assessment_id'])): $aid=(int)$_GET['assessment_id']; $sel=rows('SELECT * FROM assessments WHERE id=? AND teacher_id=?',[$aid,$teacher]); if($sel): $stus=rows('SELECT st.* FROM students st WHERE st.class_id=? AND st.teacher_id=? ORDER BY st.last_name,st.first_name',[$sel[0]['class_id'],$teacher]); $saved=rows('SELECT student_id,score FROM marks WHERE assessment_id=?',[$aid]); $sm=[]; foreach($saved as $m)$sm[$m['student_id']]=$m['score']; ?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_marks"><input type="hidden" name="assessment_id" value="<?=$aid?>"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Student ID</th><th>Student name</th><th>Score / <?=e($sel[0]['max_marks'])?></th></tr></thead><tbody><?php foreach($stus as $st): ?><tr><td><?=e($st['student_code'])?></td><td><?=e($st['first_name'].' '.$st['last_name'])?></td><td><input class="form-control" style="max-width:160px" type="number" min="0" max="<?=e($sel[0]['max_marks'])?>" step=".01" name="score[<?=$st['id']?>]" value="<?=e($sm[$st['id']]??'')?>"></td></tr><?php endforeach; ?></tbody></table></div><?php if(!$stus): ?><p class="text-secondary">No students are registered in this class.</p><?php else: ?><button class="btn btn-primary">Save marks</button><?php endif; ?></form><?php endif; endif; ?></div>
<div class="card p-4 mt-4"><h5>Grading scale (optional)</h5><p class="small text-secondary">Set percentage bands to match your school's grading policy. Without custom bands, the system uses default A/B/C/D/F bands.</p><form method="post" class="row g-2"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_grade"><div class="col"><input class="form-control" name="grade_label" placeholder="Grade e.g. A" required></div><div class="col"><input class="form-control" type="number" step=".01" min="0" max="100" name="min_percentage" placeholder="Min %" required></div><div class="col"><input class="form-control" type="number" step=".01" min="0" max="100" name="max_percentage" placeholder="Max %" required></div><div class="col"><input class="form-control" name="description" placeholder="Description"></div><div class="col"><button class="btn btn-outline-primary w-100">Save grade</button></div></form></div>
<?php endif; ?>
<footer class="text-center text-secondary small py-5">Student Marks Management System · Teacher records are private to each account.</footer>
</main></body></html>