# HOTSPORT Gym — System Workflow

This document describes the end-to-end workflows of the HOTSPORT Gym Management System, reflecting the current state of the system including the **Trainer module**, **M-Pesa payments**, and **certificate issuance**.

> Diagrams for each workflow are in [DIAGRAMS.md](./DIAGRAMS.md) and as editable draw.io files in [`diagrams/`](./diagrams/).

---

## 1. System Overview

The system has **three authenticated portals** and one external payment service:

| Portal | URL | Session variable | Primary role |
|--------|-----|------------------|--------------|
| **Member** | `/login.php` | `$_SESSION['uid']` | Browse/enroll in classes, book classes, track progress |
| **Admin** | `/admin/login.php` | `$_SESSION['adminid']` | Manage members, packages, bookings, announcements, trainers |
| **Trainer** | `/trainer/login.php` | `$_SESSION['trainerid']` | Create class series, mark attendance, issue certificates |
| **M-Pesa** | external (Safaricom Daraja) | — | Process enrollment payments via STK Push |

All portals connect to a single MySQL database (`gym_codecampbd`) using **PDO prepared statements**.

> **Note:** The **Classes** feature has been **removed** (training sessions / class series cover it). The manual **booking** flow has been replaced by **M-Pesa payments**. Packages are paid via M-Pesa ("Pay & Subscribe") into `tblsubscriptions`; trainer-session enrollments are paid via M-Pesa into `tblclass_enrollment`. All money is recorded in the unified `tblpayments` ledger.

---

## 2. Authentication Workflow

Each portal has its own login and session guard.

### Member
1. Member submits email + password at `/login.php`.
2. System verifies against `tbluser` (password stored as `md5`).
3. On success, sets `$_SESSION['uid']`, `$_SESSION['email']`, `$_SESSION['name']`.
4. Protected pages check `if (strlen($_SESSION['uid'])==0) header('location:login.php');`.

### Admin
1. Admin submits email + password at `/admin/login.php`.
2. System verifies against `tbladmin`.
3. On success, sets `$_SESSION['adminid']`.
4. Protected pages check `if (strlen($_SESSION['adminid'])==0) header('location:logout.php');`.

### Trainer
1. Trainer submits email + password at `/trainer/login.php`.
2. System verifies against `tbltrainers` (also checks `status` — inactive trainers are blocked).
3. On success, sets `$_SESSION['trainerid']`, `$_SESSION['email']`, `$_SESSION['name']`.
4. Protected pages check `if (strlen($_SESSION['trainerid'])==0) header('location:login.php');`.

**Data isolation:** Every trainer-scoped query filters by `WHERE trainer_id = :trainerid`, so a trainer can never see another trainer's series, sessions, enrollments, attendance, or certificates.

---

## 3. Trainer Account Management (Admin)

1. Admin logs in → **Trainers → Manage Trainers** (`/admin/manage-trainers.php`).
2. Admin creates a trainer account: name, email, mobile, password, specialization, bio, status.
3. The account is stored in `tbltrainers`.
4. Admin can edit or delete trainer accounts.

---

## 4. Class Series & Session Management (Trainer)

### 4.1 Create a Class Series
1. Trainer logs in → **Class Series → Add Series** (`/trainer/add-series.php`).
2. Trainer enters: title, description, **total sessions** (e.g. 10), **frequency** (daily/weekly/bi-weekly/monthly), **start date**, **capacity**, **price**, status.
3. On submit, the system:
   - Inserts the series into `tblclass_series`.
   - **Auto-generates** one row per session in `tblclass_sessions` (`session_number`, `session_date`), spaced by the frequency using PHP `DateTime`/`DateInterval`.
4. The trainer is redirected to the session list.

### 4.2 Manage Series
- **Manage Series** (`/trainer/manage-series.php`) lists only the trainer's own series with **Edit / Delete / Sessions** actions.
- **Edit Series** (`/trainer/edit-series.php`) allows editing title, description, capacity, price, status (total sessions, frequency, and start date are read-only after creation).

### 4.3 Edit Session Dates
- **Manage Sessions** (`/trainer/manage-sessions.php?series=ID`) lets the trainer edit any individual session's date/time.
- Changes persist to `tblclass_sessions`. This allows rescheduling a single session without affecting the others.

---

## 5. Enrollment & Payment Workflow (Member + M-Pesa)

This is the core payment flow. See [M-Pesa Flow](./DIAGRAMS.md#3-m-pesa-stk-push-payment-flow).

1. Member logs in → **Class Series** (`/class-series.php`) → browses active series (shows trainer, total sessions, frequency, start date, enrolled/capacity, price).
2. Member clicks **View & Enroll** → `/class-series-details.php?series=ID`.
3. Member clicks **"Pay & Enroll"** and enters a **phone number** (free text — any valid M-Pesa number, not bound to the profile).
4. `mpesa/initiate.php`:
   - **Normalizes** the phone: `07XXXXXXXX` → `2547XXXXXXXX`.
   - Re-checks duplicate enrollment and capacity.
   - Creates the enrollment in `tblclass_enrollment` with `payment_status = 'pending'` (reserves a slot).
   - Inserts a `PENDING` row in `tblclass_enrollment_payments`.
   - Calls the Daraja **STK Push** endpoint.
5. The member's phone receives the prompt and they enter their M-Pesa PIN.
6. Safaricom POSTs the result to `mpesa/callback.php`:
   - **Success** (`ResultCode 0`): payment → `SUCCESS`, enrollment → `paid`, stores the M-Pesa receipt.
   - **Failure** (`ResultCode 1032` cancelled, `1037` timeout): payment → `FAILED`/`TIMEOUT`, enrollment → `failed`.
7. The member sees **"Payment Successful — You're Enrolled"** (with amount + receipt) or **"Payment Failed"** on the enrollment page.
8. **Fallback:** The **"Check Payment Status"** button calls `mpesa/status.php`, which uses the **Daraja STK Query endpoint** if the callback hasn't arrived yet.

### Payment states
| `payment_status` | Meaning |
|------------------|---------|
| `pending` | STK push sent, awaiting confirmation |
| `paid` | Payment confirmed, member fully enrolled |
| `failed` | Payment not completed (cancelled/timeout) |

---

## 6. Attendance Workflow (Trainer)

1. Trainer logs in → **Attendance → Mark Attendance** (`/trainer/attendance.php`).
2. Trainer selects a **class series** (only their own), then a **session**.
3. The system lists all **enrolled members** for that session.
4. Trainer marks each member **Attended / Late / Absent** and saves.
5. The system upserts into `tblclass_attendance` (`ON DUPLICATE KEY UPDATE`), so re-saving updates rather than duplicates.
6. **Attendance Report** (`/trainer/attendance-report.php`) shows a per-member breakdown (attended/late/absent/total/%) plus payment status.

> **Note:** This class-session attendance is **separate** from the existing gym check-in/out attendance (`tblattendance`), which is unchanged.

---

## 7. Certificate Workflow (Trainer + Public)

1. Trainer logs in → **Certificates → Issue Certificates** (`/trainer/certificates.php`).
2. Trainer selects a class series.
3. The system lists each enrolled member with their **attendance %** (computed on demand from `tblclass_attendance` — no stored counters).
4. A member is **eligible** at **≥80%** of total sessions (attended + late count toward it).
5. Trainer clicks **Issue Certificate**:
   - Generates a **unique code** (`HSP-` + random hex).
   - Sets completion date to today, `is_active = 1`.
   - Duplicate issuance is blocked.
6. **Manage Certificates** (`/trainer/manage-certificates.php`) lists all issued certificates with **Revoke** (`is_active = 0`) and **Activate**.
7. **Public verification** (`/certificate-verify.php`, no login): anyone enters a code and sees **VALID CERTIFICATE** (with member, series, trainer, completion date), **REVOKED CERTIFICATE**, or **NOT FOUND**.

---

## 8. Payment Workflows

- **Package subscription** — `index.php` "Pay & Subscribe" opens an M-Pesa modal; `mpesa/initiate.php` creates a pending `tblsubscriptions` + `tblpayments` row and triggers STK Push; `mpesa/callback.php` marks the payment SUCCESS and activates the subscription (with `end_date` parsed from `PackageDuratiobn`).
- **Trainer session enrollment** — `class-series-details.php` "Enroll" pays via M-Pesa; `mpesa/initiate.php` creates a pending `tblclass_enrollment` + `tblpayments` row; the callback marks the enrollment paid.
- **Admin payment report** — `admin/payment-report.php` lists all payments (packages + trainer sessions) with filters and a total.
- **Trainer payment report** — `trainer/payment-report.php` lists payments for the logged-in trainer's own sessions only.
- **Member payment history** — `payment-history.php` lists the member's payments; `payment-receipt.php` shows a printable receipt.

## 9. Existing (Unchanged) Workflows

The following existing flows are **untouched** and continue to work:

- **Gym check-in/out** — `attendance.php` / `attendance-history.php` use `tblattendance`.
- **Admin management** — members, packages, announcements, posts, progress reports.
- **Member progress** — goal setting, progress dashboard, workout logging.

---

## 10. Data Isolation & Integrity

- **Trainer isolation:** all trainer queries filter by `trainer_id`.
- **Foreign keys with `ON DELETE CASCADE`:** deleting a series removes its sessions, enrollments, attendance, and payments.
- **No denormalized counters:** capacity and attendance % are computed on demand via `COUNT(*)`.
- **Unique constraints:** prevent duplicate enrollments (`series_id, user_id`), duplicate session numbers, and duplicate certificate codes.

---

## 10. Quick Test Path

1. Admin creates a trainer → trainer logs in.
2. Trainer creates a 10-session weekly class series.
3. Member browses series → **Pay & Enroll** with an M-Pesa number.
4. Member approves the STK push → enrollment becomes `paid`.
5. Trainer marks attendance on 8 sessions → member reaches 80%.
6. Trainer issues a certificate → verify it on the public page.
