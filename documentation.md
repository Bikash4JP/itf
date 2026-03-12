# ITF Project Documentation

## Overview

**IT Future Co., Ltd. (株式会社アイティーエフ)** — a Japanese staffing and foreign worker support company. This is a full-stack web application hosted at `https://it-future.jp`, providing a public-facing recruitment website and a private staff management system (CMS).

The site targets foreign nationals (technical interns, specified skilled workers) seeking employment in Japan, and the internal staff who manage them.

---

## Technology Stack

| Layer | Technology |
|---|---|
| Backend | PHP 7.4+ with PDO (MySQL) |
| Database | MySQL 5.7+ (`itf_db`) |
| Web Server | Apache ([.htaccess](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/.htaccess) for routing) |
| Frontend | Vanilla HTML, CSS, JavaScript (jQuery 3.7.1) |
| Localization | i18next with JSON translation files |
| Dependencies | Composer (PHP) · npm (Node.js) |
| PHP Libraries | PhpSpreadsheet (Excel), Dompdf (PDF) |
| Node Libraries | Express, googleapis, dotenv, i18next-http-backend |

---

## Directory Structure

```
itf/
├── index.html              # Homepage (top page)
├── about.html              # About the company
├── company_info.html       # Company info page
├── greeting.html           # President's greeting
├── inquiry.html            # Contact/inquiry form
├── news.html               # News list page
├── privacy.html            # Privacy policy
├── saiyou.php              # Recruitment/job listings (public)
├── dashboard.html          # Staff dashboard (entry point)
│
├── php/                    # All backend PHP & API scripts
├── css/                    # Page-specific and common CSS
├── js/                     # Page-specific JavaScript
├── locales/                # i18n translation files (9 languages)
├── images/                 # Site images and logos
├── fonts/                  # Web fonts
├── templates/              # Excel resume template (.xlsx)
├── uploads/                # User-uploaded files (photos, docs)
├── logs/                   # Server-side error/success logs
├── recruit/                # Static recruit info page
├── rireki/                 # Resume generation pages
├── p_info/                 # Personal info pages
├── 30thann/                # 30th anniversary content
└── links/                  # Link pages
```

---

## Public-Facing Pages

| File | Purpose |
|---|---|
| [index.html](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/index.html) | Homepage with hero section, intros, and news feed |
| [about.html](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/about.html) | Company background and mission |
| [company_info.html](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/company_info.html) | Official corporate details |
| [greeting.html](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/greeting.html) | President/director greeting |
| [news.html](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/news.html) | News and announcements list |
| [inquiry.html](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/inquiry.html) | Public contact form |
| [privacy.html](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/privacy.html) | Privacy policy |
| [saiyou.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/saiyou.php) | Job listings for foreign workers (multilingual) |
| [recruit/index.html](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/recruit/index.html) | Recruitment info static page |

---

## Staff Management System (CMS)

Accessible only after logging in via [php/login.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/login.php). Staff sessions are maintained with `$_SESSION['id']` and `$_SESSION['username']`.

### Staff Pages

| File | Purpose |
|---|---|
| [php/login.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/login.php) | Staff login with CSRF protection & account lockout |
| [php/staffdb.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/staffdb.php) | Staff home screen (post overview) |
| [php/dashboard.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/dashboard.php) | Worker search dashboard (Google Sheets integration) |
| [php/manage_jobs.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/manage_jobs.php) | Full job listing management UI |
| [php/manage_posts.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/manage_posts.php) | Staff post management |
| [php/addnews.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/addnews.php) | Add news articles |
| [php/addstaff.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/addstaff.php) | Add new staff members |
| [php/editstaff.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/editstaff.php) | Edit existing staff profiles |
| [php/edit_job.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/edit_job.php) | Edit individual job listings |
| [php/edit_post.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/edit_post.php) | Edit news/posts |
| [php/profile.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/profile.php) | Logged-in staff profile page |
| [php/rireki_list.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/rireki_list.php) | Resume (履歴書) list management |
| [php/invoice_request.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/invoice_request.php) | Invoice management |
| [php/logout.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/logout.php) | Staff logout |

### Job Admin (Restricted)

The [php/jobs_api.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/jobs_api.php) file is a REST-style API restricted to specific admin users (`osaka_ueda`, `bikash`, `kimura`). It supports:

- `list` — fetch all job posts
- [create](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/jobs_api.php#228-245) — create a new job draft
- `updateRow` — update job fields
- `delete` — delete a job post
- `files` — list attached files
- `uploadFile` — attach PDF/image files to a job
- `deleteFile` — remove an attached file

All mutating actions require a **CSRF token**.

---

## Public Job Application Flow

1. User browses job listings on [saiyou.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/saiyou.php)
2. User clicks a job to view details ([php/job_details.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/job_details.php))
3. User fills out an application form (on job details page or [php/resume.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/resume.php))
4. Form is submitted to [php/submit_application.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/submit_application.php):
   - Validates all required fields
   - Stores applicant data in the `applicant` table
   - Uploads documents (photo, passport, residence card, certificates)
   - Generates an Excel resume (`.xlsx`) using `PhpSpreadsheet`
   - Generates a PDF resume using `Dompdf`
   - Redirects to [php/application_success.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/application_success.php)

### Applicant Fields Collected

| Category | Fields |
|---|---|
| Personal | Full name, furigana, roman name, nationality, gender, religion, DOB, birthplace, marital status |
| Contact | Address, postal code, phone, email |
| Physical | Height, weight |
| Documents | Passport (number, expiry), residence card, residency status & expiry |
| Immigration | Migration history, recent entry/exit dates |
| Background | Education (JSON), Work experience (JSON), Certifications (JSON) |
| Application | Self introduction, motivation, job preference |
| Uploads | Photo, passport scan, residence card, skill certificates |

---

## User (Applicant) Account System

Separate from the staff login. Managed via [php/user_auth.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/user_auth.php).

**Tables auto-created on first use:**

| Table | Description |
|---|---|
| `app_users` | Applicant accounts (username, email, password hash) |
| `app_profiles` | Profile data stored as JSON per format (`kaigo`, etc.) |
| `app_resumes` | Token-based resume submissions linked to jobs |
| `app_applications` | Application records (user + job + resume token) |

**Key pages:**

| File | Purpose |
|---|---|
| [php/user_login.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/user_login.php) | Applicant login |
| [php/user_logout.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/user_logout.php) | Applicant logout |
| [php/user_dashboard.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/user_dashboard.php) | Applicant's personal dashboard |
| [php/user_profile.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/user_profile.php) | View/edit applicant profile |
| [php/user_applied_jobs.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/user_applied_jobs.php) | List of jobs applied for |
| [php/apply_with_profile.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/apply_with_profile.php) | Apply using saved profile data |

---

## Database Tables

| Table | Description |
|---|---|
| `staff` | Staff accounts (`username`, `password`, `failed_attempts`, `is_blocked`) |
| [posts](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/jobs_api.php#139-161) | All job postings and news articles (`post_type='job'` or `'news'`) |
| `job_files` | Files attached to job posts |
| `applicant` | Job applications with all personal/document data |
| `app_users` | Public applicant accounts |
| `app_profiles` | Saved applicant profile data (JSON) |
| `app_resumes` | Resume tokens per user per job |
| `app_applications` | Application tracking records |
| `activity_log` | Audit log of staff actions (managed by [activity_logger.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/activity_logger.php)) |

### [posts](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/jobs_api.php#139-161) Table — Key Job Fields

| Field | Description |
|---|---|
| `title` | Job title |
| `company_name` | Facility/employer name |
| `org_work_type` | Work type/industry |
| `job_location` | Prefecture/city |
| [status](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/jobs_api.php#53-64) | `募集中` / `急募` / `募集終` |
| `publish_state` | `draft` / `published` / `archived` |
| `salary`, `salary_basic`, `salary_takehome` | Salary breakdown |
| `japanese_level` | Required Japanese proficiency |
| `preferred_nationalities` | JSON array of accepted nationalities |
| `bonuses`, `visa_support`, `life_support`, etc. | TinyInt benefit flags |

---

## Multilingual Support (i18n)

The site uses **i18next** for client-side internationalization. Translation files are in `locales/` as JSON.

**Supported languages:**

| Code | Language |
|---|---|
| [en](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/user_auth.php#89-99) | English |
| `bn` | Bengali (বাংলা) |
| `hi` | Hindi (हिन्दी) |
| [id](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/user_auth.php#85-88) | Indonesian (Bahasa Indonesia) |
| `ko` | Korean (한국어) |
| `ne` | Nepali (नेपाली) |
| `tl` | Filipino (Tagalog) |
| [vi](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/jobs_api.php#296-313) | Vietnamese (Tiếng Việt) |
| `zh` | Chinese (中文) |

The [js/i18n.js](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/js/i18n.js) file initializes i18next and loads translations dynamically from `locales/{lang}.json`.

---

## Security Features

- **CSRF Tokens** — All mutating staff actions require a CSRF token verified server-side
- **Account Lockout** — Staff accounts are locked after 3 failed login attempts (`is_blocked = 1`)
- **Password Hashing** — Passwords stored using PHP `password_hash()` / verified with `password_verify()`
- **Session Cookies** — Secure, HttpOnly, SameSite=Lax cookies for staff sessions
- **Role-Based API Access** — [jobs_api.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/jobs_api.php) restricts to a hardcoded admin list
- **Input Sanitization** — All user inputs are sanitized with `htmlspecialchars` and `filter_var`
- **PDO Prepared Statements** — All DB queries use parameterized statements to prevent SQL injection
- **File Upload Validation** — Only JPG/PNG/WEBP/PDF allowed; max 10MB; filenames sanitized

---

## Activity Logging

All staff actions on jobs are recorded by [php/activity_logger.php](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/php/activity_logger.php) into an `activity_log` table with:

- `actor_type` (staff)
- `actor_staff_id`, `actor_username`
- `action` (create / update / delete / upload_file / delete_file)
- `entity_type` / `entity_id`
- `company_name`
- `message_ja` — human-readable Japanese description of the change

---

## Setup Instructions

> [!IMPORTANT]
> These steps are required for a fresh server deployment.

1. **Database** — Create a MySQL database named `itf_db` and run the schema SQL to create all tables.
2. **DB Credentials** — Set your MySQL username/password in `php/db_connect.php`.
3. **Composer** — Run `php composer.phar install` to install PHP dependencies (PhpSpreadsheet, Dompdf).
4. **npm** — Run `npm install` to install Node.js dependencies.
5. **Uploads Directory** — Create `uploads/` with write permissions (`chmod 777 uploads/`).
6. **Resume Directory** — Create `resumes/` directory for generated PDF/Excel files.
7. **Logs Directory** — Create `logs/` directory with write permissions.
8. **Templates** — Place `template.xlsx` in `templates/` for Excel resume generation.
9. **Images** — Place `logo.png` and `office.jpg` in `images/`.
10. **[.htaccess](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/.htaccess)** — Ensure Apache `mod_rewrite` is enabled for routing rules.

---

## Key JavaScript Files

| File | Purpose |
|---|---|
| [js/i18n.js](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/js/i18n.js) | Multilingual initialization (i18next) |
| [js/recruit.js](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/js/recruit.js) | Job listing display and filtering |
| [js/search.js](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/js/search.js) / [search_Main.js](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/js/search_Main.js) | Worker search UI |
| [js/staffdb.js](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/js/staffdb.js) | Staff dashboard interactions |
| [js/rireki.js](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/js/rireki.js) | Resume form logic |
| [js/news.js](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/js/news.js) | News page rendering |
| [js/form-validation.js](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/js/form-validation.js) | Client-side form validation |
| [js/login.js](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/js/login.js) | Login form and password toggle |
| [js/top.js](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/js/top.js) | Homepage animations/logic |
| [js/privacy.js](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/js/privacy.js) | Privacy policy interactions |
| [js/shortcut.js](file:///c:/Users/pc/OneDrive/Desktop/itf/itf/js/shortcut.js) | Keyboard shortcut utilities |

---

## Deployment Notes

- **Domain**: `https://it-future.jp`
- **Server Path**: `/home/it-future/www/itf/`
- **SFTP** configuration defined in `sftp.json`
- **robots.txt** is present to control search engine indexing
- **sitemap** can be generated via `sitemap_generator.php`
- **Audit script** available via `composer_audit.php`
