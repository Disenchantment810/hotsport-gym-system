# HOTSPORT Gym — Diagrams

This document indexes the system diagrams. Each diagram is available as an **editable draw.io file** in [`diagrams/`](./diagrams/). Open them in [draw.io](https://app.diagrams.net) or the draw.io desktop app.

---

## Diagram Index

| # | Diagram | File | Description |
|---|---------|------|-------------|
| 1 | System Architecture | [`diagrams/architecture.drawio`](./diagrams/architecture.drawio) | The three portals, the database, and the M-Pesa external service |
| 2 | Trainer Workflow | [`diagrams/trainer-workflow.drawio`](./diagrams/trainer-workflow.drawio) | Class series → sessions → enrollment → attendance → certificate |
| 3 | M-Pesa STK Push Flow | [`diagrams/mpesa-flow.drawio`](./diagrams/mpesa-flow.drawio) | Pay & Enroll → STK push → callback → payment confirmation |
| 4 | Database ER Diagram | [`diagrams/er-diagram.drawio`](./diagrams/er-diagram.drawio) | New tables and their relationships |

---

## 1. System Architecture

**File:** [`diagrams/architecture.drawio`](./diagrams/architecture.drawio)

Shows the three authenticated portals (Member, Admin, Trainer), each with its own login and session variable, all connecting to the central MySQL database (`gym_codecampbd`) via PDO prepared statements. The database connects to the M-Pesa Daraja API for STK Push and callbacks.

```
Member Portal ─┐
Admin Portal ──┼──► MySQL Database (gym_codecampbd) ──► M-Pesa Daraja API
Trainer Portal ┘
```

---

## 2. Trainer Workflow

**File:** [`diagrams/trainer-workflow.drawio`](./diagrams/trainer-workflow.drawio)

The end-to-end trainer flow:

1. Trainer creates a **class series** (title, total sessions, frequency, start date, capacity, price).
2. System **auto-generates session records** based on the frequency.
3. Trainer can **edit individual session dates**.
4. Member **enrolls** via "Pay & Enroll" (M-Pesa).
5. Trainer **marks per-session attendance** (attended/late/absent).
6. Attendance % is **computed on demand**.
7. Member reaches **80% threshold** → eligible for certificate.
8. Trainer **issues certificate** (unique code, completion date, is_active).
9. **Public verification** (valid/revoked/not found).

---

## 3. M-Pesa STK Push Payment Flow

**File:** [`diagrams/mpesa-flow.drawio`](./diagrams/mpesa-flow.drawio)

The payment flow (generic for **packages** and **trainer sessions**):

1. Member clicks **"Pay & Subscribe"** (package) or **"Pay & Enroll"** (trainer session) and enters a phone number.
2. `mpesa/initiate.php` normalizes the phone (`07X` → `2547X`), creates a pending entitlement (`tblsubscriptions` for packages, `tblclass_enrollment` for sessions), records a PENDING row in `tblpayments`, and triggers the STK Push.
3. Daraja sends the push to the member's phone.
4. Member enters their M-Pesa PIN.
5. Safaricom sends the **callback** to `mpesa/callback.php`.
6. `callback.php` matches the `CheckoutRequestID` to the `tblpayments` row.
7. Payment → `SUCCESS`, entitlement → `paid` (subscription activated with `end_date`), stores the M-Pesa receipt.
8. Member sees **"Payment Successful"** + receipt.
9. **Fallback:** "Check Payment Status" (`mpesa/status.php`) uses the Daraja **STK Query** endpoint if the callback hasn't arrived.

---

## 4. Database ER Diagram

**File:** [`diagrams/er-diagram.drawio`](./diagrams/er-diagram.drawio)

The new tables and their relationships (all with `ON DELETE CASCADE`):

```
tbltrainers 1──N tblclass_series 1──N tblclass_sessions
                    │ 1                    │ 1
                    │ N                    │ N
                    ├──N tblclass_enrollment ──N tblclass_attendance
                    │
                    └──N tblcertificates
tbluser (existing) 1──N tblclass_enrollment
tbluser (existing) 1──N tblcertificates
tbluser (existing) 1──N tblpayments
tbluser (existing) 1──N tblsubscriptions
tbladdpackage (existing) 1──N tblsubscriptions
tbltrainers 1──N tblcertificates
```

### Tables

| Table | Key columns | Notes |
|-------|-------------|-------|
| `tbltrainers` | `id` PK, `email` UNIQUE | Trainer accounts |
| `tblclass_series` | `id` PK, `trainer_id` FK | Class series definition |
| `tblclass_sessions` | `id` PK, `series_id` FK, `session_number` | Auto-generated sessions |
| `tblclass_enrollment` | `id` PK, `series_id` FK, `user_id` FK, `payment_status` | Member enrollments |
| `tblclass_attendance` | `id` PK, `session_id` FK, `enrollment_id` FK, `status` | Per-session attendance |
| `tblcertificates` | `id` PK, `certificate_code` UNIQUE, `is_active` | Issued certificates |
| `tblpayments` | `id` PK, `user_id` FK, `payment_type` (`package`/`class_series`), `reference_id`, `checkout_request_id` UNIQUE, `status` | Unified M-Pesa payment ledger |
| `tblsubscriptions` | `id` PK, `package_id` FK, `user_id` FK, `status`, `payment_status` | Package subscriptions (replaces `tblbooking`) |

---

## Editing the Diagrams

1. Open [draw.io](https://app.diagrams.net).
2. **File → Open From → Device** → select the `.drawio` file in `docs/diagrams/`.
3. Edit and save. The changes are tracked in git.

> The `.drawio` files are plain XML, so they diff cleanly in git.
