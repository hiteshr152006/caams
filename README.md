# College Admission & Academic Management System (CAAMS)

CAAMS is a PHP and MySQL mini project for managing college applications and the academic information of admitted students. It uses a responsive dark interface built with the shared `style.css`.

## Features

- Online admission applications with photo and marksheet uploads.
- Admin review, approval/rejection, and course/semester/section allocation.
- Student login and profile with a digital ID card and scannable QR code.
- Student attendance and internal marks views; admin entry screens.
- Weekly timetable and study-material management.
- Admin student filtering and spreadsheet-compatible export.
- Session security, CSRF checks on state-changing forms, prepared database queries, and password hashing.

## Requirements

- XAMPP with Apache, MySQL, and PHP 8.0 or newer.
- A modern browser.
- Internet access when loading the QR image (generated using `api.qrserver.com`).

## Setup with XAMPP

1. Copy the project folder into `C:\xampp\htdocs\college_system`.
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`, choose **Import**, select the project's `database.sql`, and run the import. The SQL creates the `college_system` database and an initial administrator account.
4. Copy `.env.example` to `.env` if your MySQL connection settings differ from the XAMPP defaults. Set `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, and `DB_NAME` as needed.
5. Open `http://localhost/college_system/`.
6. Allow PHP/Apache to write files under `uploads/` (including `uploads/materials/`). Admission applications store their submitted documents there.

### QR code on a phone

The QR URL uses `APP_BASE_URL` from `.env` when set. For scanning across devices, set it to the address this server can reach, including the project path, for example:

```dotenv
APP_BASE_URL=http://192.168.1.25/college_system
```

Use the computer's actual LAN IP in place of `192.168.1.25`, and ensure the phone and computer can reach each other. When `APP_BASE_URL` is blank, CAAMS builds the URL from the current validated host and installation path; a URL based on `localhost` will only work on that same computer.

## Default login credentials

- **Admin:** username `admin`, password `admin123`.
- **Student:** after an admin approves an application, the assigned student ID is shown and the initial password is `student123`.

These are demo credentials. The current mini project does not include a password-change screen; do not use the defaults with real student data. To set a different admin password, generate a PHP password hash with `php -r "echo password_hash('your-new-password', PASSWORD_DEFAULT), PHP_EOL;"` and update that account's `admin.password` value in phpMyAdmin. Student password changes likewise require an administrator to set a new hash in `students.password`.

## Folder structure

```text
college_system/
├── admin_*.php             # Admin login and management pages
├── student_*.php           # Student login, dashboard, and academic pages
├── admission.php           # Public admission form
├── config.php              # Session, CSRF, environment, and DB connection helpers
├── database.sql            # Database schema and initial admin account
├── index.php               # Public landing page
├── logout.php              # Session logout
├── migrate_passwords.php   # One-time legacy plaintext-password migration
├── style.css               # Shared dark-theme stylesheet
├── uploads/                # Admission documents and study materials
└── .env.example            # Example local database/application settings
```

The section allotment is stored in `students.section`; CAAMS does not require a separate `sections` table. `student_materials.php` is a compatibility alias for `student_material.php`.
