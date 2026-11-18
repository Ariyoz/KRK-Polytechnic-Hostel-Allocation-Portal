# KRK Polytechnic — Hostel Allocation Portal

> **CSC 445 Group 2 Project** · Submission Deadline: 25 November 2026

A fully digital hostel room allocation system for KRK Polytechnic, Lagos.  
Built with **PHP · MySQL · HTML/CSS · JavaScript** — no external frameworks.

---

## Features

| Area | Capability |
|------|-----------|
| Students | Register, email activation, school-fee eligibility check, room application, hostel fee payment, allocation letter download |
| DSA Admin | Grant / revoke room allocations, view all applications, manage students |
| Registrar | Verify student school-fee payment status |
| ICT Staff | Upload / manage hostel room records |
| System | Email notifications (PHPMailer), role-based access control, CSRF protection, responsive UI |

---

## Project Structure

```
hostel_portal/
├── index.php                   ← Public landing page
├── .htaccess
├── .gitignore
│
├── admin/                      ← Admin portal (DSA / Registrar / ICT)
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── sidebar.php
│   ├── applications.php
│   ├── students.php
│   ├── student_detail.php
│   ├── rooms.php
│   ├── upload_rooms.php
│   ├── fees.php
│   ├── reports.php
│   ├── email_logs.php
│   ├── settings.php
│   └── auth_guard.php
│
├── student/                    ← Student portal
│   ├── register.php
│   ├── login.php
│   ├── logout.php
│   ├── activate.php
│   ├── dashboard.php
│   ├── application.php
│   ├── payment.php
│   ├── profile.php
│   ├── allocation_letter.php
│   ├── forgot_password.php
│   ├── reset_password.php
│   ├── resend_activation.php
│   └── sidebar.php
│
├── ajax/
│   ├── get_departments.php     ← Dynamic department loader
│   └── room_count.php
│
├── config/
│   ├── db.php                  ← Database connection & settings helpers
│   ├── mailer.php              ← PHPMailer SMTP setup
│   ├── session.php
│   └── phpmailer/              ← PHPMailer library (bundled)
│
├── includes/
│   ├── header.php              ← Global HTML head + navbar
│   └── footer.php             ← Global footer + JS
│
├── assets/
│   ├── css/app.css             ← Shared styles (vars, layout, components)
│   ├── css/public.css          ← Landing page
│   ├── css/auth.css            ← Login / register / password pages
│   ├── css/student.css         ← Student portal pages
│   ├── css/admin.css           ← Admin portal shell + shared admin pages
│   ├── css/admin-extra.css     ← Rooms / fees / reports / settings pages
│   ├── fonts/                  ← Self-hosted Inter & Space Grotesk (woff2)
│   ├── js/main.js
│   └── images/
│
└── database/
    └── hostel_db.sql           ← Full schema + seed data
```

---

## Setup (XAMPP / WAMP)

### Requirements
- PHP **7.4+**
- MySQL **5.7+** / MariaDB **10.3+**

### Steps

```bash
# 1. Place the folder inside htdocs
cp -r hostel_portal/ /xampp/htdocs/hostel_portal/

# 2. Import the database
# Open phpMyAdmin → create database → import database/hostel_db.sql

# 3. Edit SMTP credentials (optional — only needed for email)
# Open config/mailer.php and set MAIL_USERNAME and MAIL_PASS

# 4. Visit in browser
http://localhost/hostel_portal/
```

> `config/db.php` defaults to `localhost / root / (no password) / krk_hostel_db` — standard XAMPP settings.  
> Change `DB_USER` and `DB_PASS` if your MySQL uses different credentials.

---

## Default Admin Logins

| Role | Email | Password |
|------|-------|----------|
| DSA | `dsa@krkpoly.edu.ng` | `Admin@2025` |
| Registrar | `registrar@krkpoly.edu.ng` | `Admin@2025` |
| ICT Staff | `ict@krkpoly.edu.ng` | `Admin@2025` |

> **Change these passwords immediately after first login in a production environment.**

---

## Email (SMTP) Setup

1. Open `config/mailer.php`
2. Set `MAIL_USERNAME` to your Gmail address
3. For `MAIL_PASS` — enable 2FA on your Google account then generate an **App Password** at  
   <https://myaccount.google.com/apppasswords>
4. If email is not configured, the system still functions — email logs are stored in the database

---

## Admin Roles & Permissions

| Permission | DSA | Registrar | ICT |
|------------|:---:|:---------:|:---:|
| View dashboard | ✅ | ✅ | ✅ |
| Grant / Revoke rooms | ✅ | ❌ | ❌ |
| Fee verification | ✅ | ✅ | ❌ |
| Upload rooms | ✅ | ❌ | ✅ |
| View reports | ✅ | ✅ | ✅ |
| System settings | ✅ | ❌ | ❌ |

---

## Team — CSC 445 Group 2

| Name | Role |
|------|------|
| Member 1 | Project Lead / Backend Developer |
| Member 2 | Frontend Developer (HTML/CSS) |
| Member 3 | Database Designer |
| Member 4 | JavaScript Developer |
| Member 5 | Testing & Documentation |

---

## Academic Session: 2025/2026

&copy; 2026 KRK Polytechnic, Lagos
