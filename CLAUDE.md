# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Gym Management System built with PHP and MySQL. The system allows users to register, book packages, manage their profiles, and administrators to manage bookings, packages, posts, generate reports, track attendance, and manage class scheduling.

## Directory Structure

- `admin/` - Admin panel files (PHP scripts for managing the gym)
- `include/` - Common PHP files (configuration, header, footer, sidebar)
- `css/` - Stylesheets
- `js/` - JavaScript files
- `img/` - Images used in the system
- `screen/` - Screenshots of the system (as referenced in README)
- `SQL File/` - Contains the database dump (`gym_codecampbd.sql`)
- Root directory - User-facing pages (index.php, about.php, payment-history.php, etc.)

## Database Setup

1. The database connection is defined in `include/config.php`:
   - Host: `localhost`
   - Username: `root`
   - Password: `(empty)`
   - Database: `gym_codecampbd`

2. To set up the database:
   - Import the SQL file located at `SQL File/gym_codecampbd.sql` into MySQL.
   - The expected database name is `gym_codecampbd` (as per config) but note the README mentions `ccbd_medipos` - this appears to be a discrepancy; the config uses `gym_codecampbd`.
   - The SQL file now includes the `tblattendance` table for attendance tracking (added via the attendance feature implementation).

## Implemented Features

### Attendance Tracking System
- **Admin Check-In/Check-Out**: Administrators can check members in and out of the facility
- **Attendance Reports**: Admin can view and filter attendance records by date range and member
- **User Attendance History**: Members can view their personal attendance history with check-in/out times and duration calculations
- **Database**: Added `tblattendance` table with foreign key relationship to `tbluser`
- **Integration**: 
  - Admin sidebar now includes "Attendance" menu with Check In/Out and Attendance Report options
  - User header includes "Attendance History" link for logged-in members

### Class Scheduling & Booking System
- **Trainer Class Series**: Trainers create class series (training sessions) with sessions, capacity, and pricing.
- **User Enrollment via M-Pesa**: Logged-in members enroll in class series and pay via M-Pesa STK Push.
- **Database**: Added `tblclass_series`, `tblclass_sessions`, `tblclass_enrollment`, `tblclass_attendance`, `tblcertificates` tables.
- **Integration**: 
  - Trainer sidebar includes "Class Series" menu with Add Series, Manage Series, and Payments options.
  - User header includes "Class Series" link for logged-in members.
  - Uses existing FullCalendar assets for calendar view.
  - Follows same patterns as package booking (PDO prepared statements, session authentication, Bootstrap styling, alert feedback).

## Running the Project

As per the README:
1. Place the project in your web server's root directory (e.g., `xampp/htdocs`, `wamp/www`, or `var/www/html`).
2. Ensure MySQL is running and create the database `gym_codecampbd`.
3. Import the SQL dump from `SQL File/gym_codecampbd.sql`.
4. Access the project via `http://localhost/[your_project_folder]`.
5. Admin panel is accessible at `http://localhost/[your_project_folder]/admin`.
   - Default admin login: Email `admin@gmail.com`, password (refer to README for instructions - a video link is provided).

## Important Notes

- The project uses PDO for database interactions (see `include/config.php`).
- Error handling for database connection is via try-catch in `config.php`.
- Output buffering is started with `ob_start()` in `config.php`.
- There is no build system, linting, or testing framework configured in this project.
- All PHP files are server-dependent and require a PHP environment (e.g., XAMPP) to run.
- The attendance feature was implemented following existing codebase patterns for consistency.
- The class scheduling and booking feature follows the same patterns.

## Admin Credentials (as per README)
- Admin Panel URL: `http://localhost/[your_project_folder]/admin`
- Email: `admin@gmail.com`
- Password: Refer to the README for the video link to obtain the password.

## Suggested Future Features

The following features could enhance the Gym Management System and are recommended for future implementation:

1. **Progress Tracking & Measurements**
   - Enable members to track fitness progress (weight, body measurements, workout logs, goals)
   - Extend user profile with progress tracking dashboard

2. **Automated Payment & Membership Renewal**
   - Automatic recurring billing for membership packages with email/SMS reminders
   - Extend payment system with cron job for processing renewals

3. **Equipment Maintenance Tracking**
   - Track gym equipment maintenance schedules, repairs, and availability status
   - Admin module similar to package management

4. **Personal Trainer Management**
   - Assign trainers to members, track trainer schedules and client assignments
   - Extend user management with trainer-specific profiles

5. **Member Communication Portal**
   - Internal messaging system between admins/trainers and members
   - Similar to existing notification patterns

6. **Inventory Management (Shop)**
   - Track retail sales of supplements, merchandise, etc.
   - Similar to package management but for physical products

7. **Advanced Analytics Dashboard**
   - Enhanced reporting with charts/trends (attendance vs. bookings, revenue forecasts, member retention)
   - Extend admin dashboard with chart.js (already available in the system)

These suggested features follow the existing codebase patterns:
- Use PDO prepared statements for database security
- Match Bootstrap 3.x styling in admin interfaces
- Use session-based authentication like the current system
- Follow the MVC-ish pattern (separate PHP files for logic/display)