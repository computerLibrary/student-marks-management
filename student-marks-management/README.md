# Student Marks Management System (SMMS)

A PHP + MySQL starter application for teachers to manage classes, students, subjects, assessment marks and Excel-compatible CSV reports.

## Requirements
- XAMPP (Apache, PHP 8.1+ and MySQL/MariaDB)
- A modern web browser

## Installation on Windows with XAMPP
1. Extract the `student-marks-management` folder into `C:\xampp\htdocs\`.
2. Open the XAMPP Control Panel and start **Apache** and **MySQL**.
3. Open `http://localhost/phpmyadmin`.
4. Select **Import**, choose this project's `database.sql`, and import it.
   - The SQL creates the `student_marks_db` database and all required tables.
5. If your MySQL username or password is different from the XAMPP default, update `config/database.php`.
6. Open `http://localhost/student-marks-management/`.
7. Create a teacher account, then add classes, students and subjects before recording marks.

## Main workflow
1. Create a teacher account and log in.
2. Add classes and academic years.
3. Register students with unique admission numbers.
4. Add subjects and their default maximum marks.
5. Open **Record marks**, choose a class and subject, enter the assessment name, maximum mark, term and academic year.
6. Enter each student's score and save.
7. Open **Reports** to export all classes or filter by class, year and term.

## Excel export
The report downloads as UTF-8 CSV with a `.csv` extension. Excel opens this file directly. It includes each student's score, maximum mark and calculated percentage. This starter version uses CSV to avoid requiring Composer packages. A native `.xlsx` workbook can be added using PhpSpreadsheet.

## Important notes
- Teachers can only query records associated with their own teacher account.
- Passwords are stored using PHP `password_hash()`.
- Database operations use PDO prepared statements.
- Forms that change data use CSRF tokens.
- Marks are stored as a score out of the maximum mark entered for that assessment.
- A repeated student + subject + assessment + term + year entry updates the existing score rather than creating a duplicate.
- This is a functional starter/MVP. Before public deployment, configure HTTPS, secure database credentials, backups, server-side rate limiting, account recovery, and a school-approved privacy policy. Add administrator and school-wide roles if the system will be centrally managed.

## Troubleshooting
- **Database connection failed:** check that MySQL is running and the credentials in `config/database.php` are correct.
- **Page not found:** confirm the folder is named `student-marks-management` and is inside `C:\xampp\htdocs`.
- **CSS or links not loading:** the project currently uses `/student-marks-management/` as its base URL. If you rename the folder, update that base path in the PHP files and CSS/JS references.


## Deployment on Railway
This repository includes a Dockerfile for Railway's PHP/Apache runtime. Add a MySQL service,
then set these variables on the app service using Railway variable references:
- `MYSQLHOST=${{MySQL.MYSQLHOST}}`
- `MYSQLPORT=${{MySQL.MYSQLPORT}}`
- `MYSQLDATABASE=${{MySQL.MYSQLDATABASE}}`
- `MYSQLUSER=${{MySQL.MYSQLUSER}}`
- `MYSQLPASSWORD=${{MySQL.MYSQLPASSWORD}}`

Import `database.sql` into the MySQL service before using the app. Do not commit database
credentials or real student data to GitHub. Test with dummy records first.
