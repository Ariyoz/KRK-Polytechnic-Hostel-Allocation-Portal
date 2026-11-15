# KRK Polytechnic — Hostel Allocation Portal
**CSC 445 Group 2 Project**
Submission Deadline: 25th November 2026

---

## Project Overview
A web-based hostel allocation portal for KRK Polytechnic, Lagos.
Built with **HTML/CSS, PHP, MySQL, and JavaScript**.

---

## Features
- Student registration with faculty/department selection
- Email account activation
- School fees eligibility check before hostel application
- Room selection from available rooms (max 200 bed spaces)
- DSA/Registrar dashboard: Grant or Revoke room allocations
- Email notifications on grant/revoke
- ICT staff room upload panel
- Hostel fee payment confirmation
- Admin email logs

---

## Roles
| Role       | Access                                      |
|------------|---------------------------------------------|
| DSA        | Full admin: applications, fees, rooms       |
| Registrar  | Applications, Fee verification              |
| ICT Staff  | Upload rooms                                |
| Student    | Register, Apply, Pay hostel fee             |

---

## Project Structure
```
/
├── index.php                  → Homepage
├── .htaccess
├── config/
│   ├── db.php                 → Database connection
│   ├── mailer.php             → Email utility
│   └── session.php            → Session helpers
├── includes/
│   ├── header.php
│   └── footer.php
├── student/
│   ├── register.php           → Student registration
│   ├── login.php
│   ├── activate.php           → Email activation
│   ├── dashboard.php
│   ├── application.php        → Apply for hostel
│   ├── payment.php            → Pay hostel fee
│   ├── profile.php
│   └── logout.php
├── admin/
│   ├── login.php
│   ├── dashboard.php
│   ├── sidebar.php
│   ├── applications.php       → Grant/Revoke rooms
│   ├── students.php
│   ├── rooms.php
│   ├── upload_rooms.php       → ICT room upload
│   ├── fees.php               → Fee verification
│   ├── email_logs.php
│   └── logout.php
├── ajax/
│   └── get_departments.php    → Dynamic dept loading
├── assets/
│   ├── css/style.css
│   └── js/main.js
└── database/
    └── hostel_db.sql          → Database schema & seed data
```

---

## Setup Instructions

### Requirements
- XAMPP / WAMP / LAMP (PHP 7.4+ and MySQL 5.7+)

### Steps
1. Copy the entire project folder into `htdocs/` (XAMPP) or `www/` (WAMP).
2. Open **phpMyAdmin** and import `database/hostel_db.sql`.
3. Edit `config/db.php` with your MySQL username/password if needed.
4. Visit `http://localhost/CSC 445 G2/` in your browser.

---

## Default Admin Logins (change after setup)
| Role       | Email                        | Password   |
|------------|------------------------------|------------|
| DSA        | dsa@krkpoly.edu.ng           | password   |
| Registrar  | registrar@krkpoly.edu.ng     | password   |
| ICT        | ict@krkpoly.edu.ng           | password   |

> **Note:** Default passwords use the hash for `password`.
> To generate a correct hash run: `echo password_hash('YourPassword', PASSWORD_DEFAULT);`

---

## Email Configuration
Edit `config/mailer.php` to set your SMTP credentials.
If PHPMailer is not available, the system falls back to PHP `mail()`.

---

## Team Members & Roles
| Name | Role |
|------|------|
| Member 1 | Project Lead / Backend Developer |
| Member 2 | Frontend Developer (HTML/CSS) |
| Member 3 | Database Designer |
| Member 4 | JavaScript Developer |
| Member 5 | Testing & Documentation |

---

## Academic Session: 2025/2026
