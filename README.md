# HOTSPORT Gym Management System

A PHP/MySQL gym management system running on Apache/XAMPP. It provides separate portals for **Admins**, **Trainers**, and **Members**, with class series management, M-Pesa payment integration, attendance tracking, and certificate issuance.

---

## Table of Contents

- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Portals & Login](#portals--login)
- [Features](#features)
- [M-Pesa Integration](#m-pesa-integration)
- [Project Structure](#project-structure)
- [Database Tables](#database-tables)
- [Testing](#testing)
- [Security Notes](#security-notes)
- [Documentation](#documentation)

---

## Tech Stack

- **Backend:** PHP 8.x (PDO with prepared statements)
- **Database:** MySQL / MariaDB (database: `gym_codecampbd`)
- **Server:** Apache (XAMPP)
- **Frontend:** Bootstrap 3, Font Awesome 4.7, jQuery, DataTables
- **Admin/Trainer theme:** Vali Admin
- **Member theme:** Ahana Yoga template
- **Payments:** Safaricom M-Pesa Daraja API (STK Push)

---

## Requirements

- XAMPP (Apache + MySQL + PHP) or equivalent LAMP stack
- PHP with `pdo_mysql` and `curl` extensions enabled
- For M-Pesa testing: a Daraja developer account and (for local callback testing) an ngrok tunnel

---

## Installation

1. **Copy the project** into your web root:
   ```bash
   cp -r /path/to/Hotsport /opt/lampp/htdocs/Hotsport
   ```

2. **Start Apache and MySQL** in XAMPP.

3. **Create the database** and import the schema:
   - Open phpMyAdmin (`http://localhost/phpmyadmin`)
   - Create a database named `gym_codecampbd`
   - Import `SQL File/gym_codecampbd.sql`

4. **Run the schema updaters** (idempotent — safe to run multiple times):
   - `http://localhost/Hotsport/update_schema.php` (base tables)
   - `http://localhost/Hotsport/trainer/update_schema.php` (trainer module tables)
   - `http://localhost/Hotsport/mpesa/update_schema.php` (M-Pesa payment tables)

5. **Configure the database connection** in each config file if your credentials differ:
   - `include/config.php`
   - `admin/include/config.php`
   - `trainer/include/config.php`

6. **Configure M-Pesa** (see [M-Pesa Integration](#m-pesa-integration)).

7. **Access the app** at `http://localhost/Hotsport/`.

---

## Database Setup

The database name is `gym_codecampbd`. The schema is split across:

| File | Purpose |
|------|---------|
| `SQL File/gym_codecampbd.sql` | Base tables (users, classes, bookings, packages, etc.) |
| `SQL File/trainer_schema.sql` | Trainer module tables (reference) |
| `trainer/update_schema.php` | Creates trainer tables idempotently |
| `mpesa/update_schema.php` | Creates M-Pesa payment tables idempotently |

---

## Portals & Login

| Portal | URL | Session var | Role |
|--------|-----|-------------|------|
| **Member** | `http://localhost/Hotsport/login.php` | `$_SESSION['uid']` | Browse/enroll in classes, book classes, track progress |
| **Admin** | `http://localhost/Hotsport/admin/login.php` | `$_SESSION['adminid']` | Manage members, packages, bookings, announcements, trainers |
| **Trainer** | `http://localhost/Hotsport/trainer/login.php` | `$_SESSION['trainerid']` | Create class series, mark attendance, issue certificates |

> **Note:** The **Classes** feature has been **removed** (training sessions / class series cover it). The manual **booking** flow has been replaced by **M-Pesa payments**. Packages are paid via M-Pesa ("Pay & Subscribe") into `tblsubscriptions`; trainer-session enrollments are paid via M-Pesa into `tblclass_enrollment`. All money is recorded in the unified `tblpayments` ledger.

---

## Features

### Member Portal
- Subscribe to packages via M-Pesa ("Pay & Subscribe") (`index.php`)
- Browse class series and enroll via M-Pesa (`class-series.php`, `class-series-details.php`)
- View enrollments and payment status (`my-enrollments.php`)
- Payment history with printable receipts (`payment-history.php`, `payment-receipt.php`)
- Attendance history (gym check-in/out)
- Goal setting, progress dashboard, workout logging
- Public certificate verification (`certificate-verify.php`)

### Admin Portal
- Manage members, packages, bookings, announcements, posts, categories
- View booking history and payment reports
- Manage trainer accounts (`manage-trainers.php`)
- Progress reports and member progress views
- Gym check-in/out attendance (`attendance.php`)

### Trainer Portal
- Create class series with auto-generated sessions (`add-series.php`)
- Manage series and edit individual session dates (`manage-series.php`, `manage-sessions.php`)
- Mark per-session attendance (attended/late/absent) (`attendance.php`)
- Attendance reports (`attendance-report.php`)
- Issue certificates at 80% attendance threshold (`certificates.php`)
- Manage/revoke certificates (`manage-certificates.php`)

### Class Series & Sessions
- A trainer creates a **class series** with a defined number of total sessions, frequency (daily/weekly/bi-weekly/monthly), start date, capacity, price, and description.
- The system **auto-generates** individual session records (`session_number`, `session_date`) based on the start date and frequency.
- Trainers can edit individual session dates afterward.

### Enrollment & Payments
- Members enroll in a class series via **"Pay & Enroll"**.
- The member enters any valid M-Pesa phone number (free text, not bound to profile).
- The system normalizes `07XXXXXXXX` → `2547XXXXXXXX` before sending the STK Push.
- Enrollment is created as **pending**; the M-Pesa callback flips it to **paid** or **failed**.
- A **"Check Payment Status"** button uses the Daraja STK Query endpoint as a fallback.

### Certificates
- A member becomes eligible for a certificate at **80% attendance** of total sessions (attended + late).
- The trainer issues a certificate with a **unique code** (`HSP-` + random hex), completion date, and an `is_active` flag.
- Certificates can be revoked/re-activated.
- Public verification page: `certificate-verify.php`.

---

## M-Pesa Integration

M-Pesa payments use the **Safaricom Daraja API** (Lipa Na M-Pesa Online / STK Push).

### Configuration
Edit `mpesa/config.php` (this file is **git-ignored** — never commit it):

```php
define('MPESA_ENV', 'sandbox');            // 'sandbox' or 'production'
define('MPESA_CONSUMER_KEY', 'YOUR_KEY');
define('MPESA_CONSUMER_SECRET', 'YOUR_SECRET');
define('MPESA_PASSKEY', 'YOUR_PASSKEY');
define('MPESA_SHORTCODE', '174379');       // sandbox default
define('MPESA_CALLBACK_URL', 'https://YOUR_NGROK.ngrok.io/mpesa/callback.php');
```

### How it works
1. Member clicks **"Pay & Enroll"** on a class series and enters a phone number.
2. `mpesa/initiate.php` creates a pending enrollment, records a PENDING payment, and triggers the STK Push.
3. The member approves the payment on their phone.
4. Safaricom POSTs the result to `mpesa/callback.php`.
5. `callback.php` matches the `CheckoutRequestID` to the payment row and flips it to `SUCCESS`/`FAILED` and the enrollment to `paid`/`failed`.
6. The member can use **"Check Payment Status"** (`mpesa/status.php`) which queries Daraja as a fallback.

### Sandbox vs Production
- **Sandbox:** Use the sandbox test number (`254708374149`). Payments are simulated and refunded after ~12 hours. No real money moves.
- **Production:** Requires live Daraja credentials, a real paybill shortcode, and a registered callback URL. Real money is deducted.

### Callback URL (local testing)
For local testing, use **ngrok** to expose your localhost:
```bash
ngrok http 80
```
Then set `MPESA_CALLBACK_URL` to your ngrok URL + `/mpesa/callback.php`.

---

## Project Structure

```
Hotsport/
├── index.php                 # Member home + Pay & Subscribe (M-Pesa)
├── login.php / logout.php    # Member auth
├── registration.php          # Member registration
├── class-series.php          # Browse class series
├── class-series-details.php  # Series details + Pay & Enroll
├── my-enrollments.php        # Member enrollments + payment status
├── certificate-verify.php    # Public certificate verification
├── payment-history.php       # Member payment history
├── payment-receipt.php       # Printable payment receipt
├── attendance-history.php    # Gym check-in/out history
├── goal-setting.php          # Goal setting
├── progress-dashboard.php    # Progress dashboard
├── workout-logging.php       # Workout logging
├── announcements.php         # Announcements
├── update_schema.php         # Base schema updater
│
├── admin/                    # Admin portal (Vali theme)
│   ├── login.php / logout.php
│   ├── index.php             # Admin dashboard
│   ├── manage-trainers.php   # Manage trainer accounts
│   ├── payment-report.php    # Payment report (packages + sessions)
│   ├── add-package.php       # Add package
│   ├── manage-announcements.php
│   ├── attendance.php        # Gym check-in/out
│   ├── progress-reports.php  # Progress reports
│   └── include/              # config, header, sidebar, footer
│
├── trainer/                  # Trainer portal (Vali theme)
│   ├── login.php / logout.php
│   ├── index.php             # Trainer dashboard
│   ├── add-series.php        # Create class series
│   ├── manage-series.php     # Manage series
│   ├── edit-series.php       # Edit series
│   ├── manage-sessions.php   # Edit session dates
│   ├── attendance.php        # Mark per-session attendance
│   ├── attendance-report.php # Attendance report
│   ├── payment-report.php    # Payment report (own sessions)
│   ├── certificates.php      # Issue certificates
│   ├── manage-certificates.php # Manage/revoke certificates
│   ├── profile.php           # Trainer profile
│   ├── change-password.php   # Change password
│   ├── update_schema.php     # Trainer schema updater
│   └── include/              # config, header, sidebar, footer
│
├── mpesa/                    # M-Pesa Daraja integration
│   ├── config.php            # Credentials (GIT-IGNORED)
│   ├── Mpesa.php             # Daraja API client
│   ├── initiate.php          # Start STK push (package / class_series)
│   ├── callback.php          # Payment callback endpoint
│   ├── status.php            # Check payment status (STK Query fallback)
│   └── update_schema.php     # M-Pesa schema updater
│
├── include/                  # Shared includes
│   ├── config.php            # DB connection
│   ├── header.php            # Member header
│   ├── footer.php            # Member footer
│   └── sidebar.php           # Standalone template (Ahana Yoga)
│
├── css/ js/ img/ icon-fonts/ # Frontend assets
├── SQL File/                 # SQL schema files
└── .gitignore                # Ignores mpesa/config.php, logs, vendor
```

---

## Database Tables

### Base tables (existing, untouched)
- `tbladmin` — admin accounts
- `tbluser` — member accounts
- `tblattendance` — gym check-in/out attendance
- `tblpackage` / `tbladdpackage` — membership packages
- `tblmpesa_transactions` — legacy M-Pesa stub (unused)
- `tblannouncements` / `tblannouncement_reads` — announcements
- `tblgoals` / `tblmeasurements` / `tblworkouts` / `tblworkout_exercises` — progress tracking
- `tblcategory` / `tblpost` — posts/categories

### Payment tables (new)
- `tblpayments` — unified M-Pesa payment ledger (packages + trainer sessions)
- `tblsubscriptions` — package subscriptions (replaces `tblbooking`)

### Trainer module tables (new)
- `tbltrainers` — trainer accounts
- `tblclass_series` — class series (trainer_id FK, total_sessions, frequency, start_date, capacity, price)
- `tblclass_sessions` — individual sessions (series_id FK, session_number, session_date)
- `tblclass_enrollment` — member enrollments (series_id + user_id FK, payment_status)
- `tblclass_attendance` — per-session attendance (session_id + enrollment_id FK, status)
- `tblcertificates` — issued certificates (unique code, is_active)

### M-Pesa tables (new)
- `tblclass_enrollment_payments` — enrollment payments (enrollment_id FK, checkout_request_id, status)

All new tables use **foreign keys with `ON DELETE CASCADE`**. Attendance counts and capacity are **computed on demand** (no denormalized counters).

---

## Testing

### Smoke test the core flows
1. **Trainer login** → `trainer@hotsport.com` / `trainer123` (or create via admin)
2. **Create a class series** → auto-generates sessions
3. **Member enrolls** → "Pay & Enroll" with M-Pesa
4. **Trainer marks attendance** → per-session
5. **Issue certificate** at 80% attendance
6. **Verify certificate** on the public page

### M-Pesa testing
- **Sandbox:** use test number `254708374149`, set `MPESA_ENV='sandbox'`.
- **Callback:** use ngrok to expose localhost, or POST a sample callback JSON to `mpesa/callback.php` to test the handler.

### Regression
- Existing admin flows (packages, announcements, check-in/out) and member flows (`class-series.php`, `payment-history.php`) must remain working.
- `tblattendance` is untouched.

---

## Security Notes

- **`mpesa/config.php` is git-ignored** — it contains real Daraja credentials and must never be committed.
- Passwords use `md5()` (legacy convention in this codebase).
- All database queries use **PDO prepared statements** with named parameters.
- Every trainer query filters by `trainer_id` to enforce **data isolation** (a trainer never sees another trainer's data).
- M-Pesa callback logs are written to `mpesa/logs/` (git-ignored).

---

## License

This is a private project. No license is specified.

---

## Documentation

For detailed system workflows and diagrams, see the [`docs/`](./docs/) folder:

- **[System Workflow](./docs/SYSTEM_WORKFLOW.md)** — end-to-end narrative of all workflows (auth, class series, enrollment, M-Pesa payments, attendance, certificates).
- **[Diagrams](./docs/DIAGRAMS.md)** — index of the draw.io diagrams.
- **Diagrams (draw.io):**
  - [System Architecture](./docs/diagrams/architecture.drawio)
  - [Trainer Workflow](./docs/diagrams/trainer-workflow.drawio)
  - [M-Pesa STK Push Flow](./docs/diagrams/mpesa-flow.drawio)
  - [Database ER Diagram](./docs/diagrams/er-diagram.drawio)
