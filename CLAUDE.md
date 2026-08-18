# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Gym Management System built with PHP and MySQL. The system allows users to register, book classes, manage their profiles, and administrators to manage bookings, packages, posts, and generate reports.

## Directory Structure

- `admin/` - Admin panel files (PHP scripts for managing the gym)
- `include/` - Common PHP files (configuration, header, footer, sidebar)
- `css/` - Stylesheets
- `js/` - JavaScript files
- `img/` - Images used in the system
- `screen/` - Screenshots of the system (as referenced in README)
- `SQL File/` - Contains the database dump (`gym_codecampbd.sql`)
- Root directory - User-facing pages (index.php, about.php, booking-details.php, etc.)

## Database Setup

1. The database connection is defined in `include/config.php`:
   - Host: `localhost`
   - Username: `root`
   - Password: `(empty)`
   - Database: `gym_codecampbd`

2. To set up the database:
   - Import the SQL file located at `SQL File/gym_codecampbd.sql` into MySQL.
   - The expected database name is `gym_codecampbd` (as per config) but note the README mentions `ccbd_medipos` - this appears to be a discrepancy; the config uses `gym_codecampbd`.

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

## Admin Credentials (as per README)
- Admin Panel URL: `http://localhost/[your_project_folder]/admin`
- Email: `admin@gmail.com`
- Password: Refer to the README for the video link to obtain the password.