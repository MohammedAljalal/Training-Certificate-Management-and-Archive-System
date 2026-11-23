# Training Certificate Management and Archive System (TCMAS)

> **CIT1303 — Advanced Web Technology**  
> University of Tabuk · Faculty of Computers & Information Technology

---

## 📋 Project Overview

A full-featured web application for managing training course certificates. Built with **PHP 8.2**, **MariaDB**, **HTML/CSS**, and **JavaScript**.

### Key Features
- 🔐 Role-based authentication (Admin + Participant)
- 📚 Full CRUD for training courses and participants
- 🎓 Real PDF certificate generation (Dompdf)
- 🔍 Public certificate verification portal
- 📊 Admin dashboard with Chart.js analytics

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.2 (strict types, PDO, bcrypt) |
| Database | MariaDB 10.4 / MySQL |
| Frontend | HTML5, CSS3 (1800+ lines custom), JavaScript |
| PDF | Dompdf v3.1.6 |
| Charts | Chart.js 4.4.1 |
| Icons | Font Awesome 6 Free |

---

## 🚀 Setup & Installation

### Requirements
- PHP 8.1+
- MySQL / MariaDB
- Composer

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/MohammedAljalal/Training-Certificate-Management-and-Archive-System.git
cd Training-Certificate-Management-and-Archive-System

# 2. Install PHP dependencies (Dompdf)
composer install

# 3. Create database
mysql -u root -p < database/schema.sql

# 4. Seed demo data (optional)
php database/seed_dummy_data.php

# 5. Start dev server
php -S localhost:8000 -t public
```

### Database Configuration

Edit `config/db.php` and set your database credentials:

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'tcmas');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
```

---

## 🔑 Demo Credentials

| Role | Email | Password |
|------|-------|----------|
| **Admin** | `admin@tcmas.local` | `Admin@1234` |
| **Participant** | `m.otaibi@example.com` | `Password@123` |

---

## 📁 Project Structure

```
├── config/
│   └── db.php                  # Database connection (PDO)
├── database/
│   ├── schema.sql              # Database schema + FK relationships
│   └── seed_dummy_data.php     # Demo data seeder
├── public/                     # Web root (point server here)
│   ├── login.php
│   ├── logout.php
│   ├── verify.php              # Public certificate verification
│   ├── admin/                  # Admin-only pages (CRUD)
│   │   ├── dashboard.php
│   │   ├── courses/
│   │   ├── participants/
│   │   ├── enrollments/
│   │   └── certificates/
│   ├── participant/            # Participant portal
│   └── assets/                 # CSS, JS
├── src/
│   ├── helpers.php             # Session, CSRF, auth guards
│   ├── models/                 # Data access layer (PDO)
│   └── views/layout/           # Shared header/footer/nav
├── storage/
│   └── certificates/           # Generated PDF files
└── vendor/                     # Composer packages (Dompdf)
```

---

## 🔒 Security Features

- ✅ CSRF protection on all POST forms (`hash_equals`)
- ✅ SQL injection prevention (PDO prepared statements, `ATTR_EMULATE_PREPARES=false`)
- ✅ XSS protection (`htmlspecialchars` on all output via `h()`)
- ✅ Passwords hashed with bcrypt (`password_hash` / `password_verify`)
- ✅ Session security: `httponly`, `samesite=Strict`, `session_regenerate_id`
- ✅ Role-based access guards (`require_admin()`, `require_participant()`)

---

## 📸 Pages

| Page | URL | Access |
|------|-----|--------|
| Login | `/login.php` | Public |
| Admin Dashboard | `/admin/dashboard.php` | Admin only |
| Courses Management | `/admin/courses/` | Admin only |
| Participants | `/admin/participants/` | Admin only |
| Certificate Issuance | `/admin/certificates/issue.php` | Admin only |
| Participant Portal | `/participant/dashboard.php` | Participant |
| Verification Portal | `/verify.php` | Public |

---

## 📊 Database Schema

```
users ──── enrollments ──── courses
               │
           certificates
```

4 tables with proper Foreign Key relationships.

---

*Developed for CIT1303 — University of Tabuk, 2026*
