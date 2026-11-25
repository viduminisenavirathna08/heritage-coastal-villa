# Heritage Coastal Villa - Luxury Resort Booking Web Application

An end-to-end web-based resort booking and experiential hospitality management system built for **Heritage Coastal Villa** (Mirissa, Southern Province, Sri Lanka). The application provides guests with an intuitive booking flow with real-time price calculations, interactive add-on experiences, dual currency display, and policy compliance, while giving administrators a portal to manage suites, curated experiences, and reservation records.

---

## 📖 Table of Contents
1. [Project Overview](#-project-overview)
2. [Implemented Features](#-implemented-features)
3. [Software Requirements](#-software-requirements)
4. [Installation & Configuration Instructions](#-installation--configuration-instructions)
5. [Sample Login Credentials](#-sample-login-credentials)
6. [Database Schema & Architecture](#-database-schema--architecture)
7. [Security & Verification Practices](#-security--verification-practices)

---

## 🏨 Project Overview

Heritage Coastal Villa is a boutique wellness sanctuary combining traditional Sri Lankan Ceylon aesthetics with modern coastal luxury. The web application serves both prospective travelers and hotel staff:
- **Guest-Facing Interface:** Showcases suites and private plunge pool villas, curated coastal experiences (marine tours, wellness therapies, dining), and an interactive booking calculator.
- **Admin Management Portal:** Dedicated dashboard for villa management to track bookings, manage room rates and inventory, and configure paid experiences as well as complimentary amenities.

---

## ✨ Implemented Features

### 1. Guest Experience & Public Facing
- **Responsive Tropical Luxury UI:** Handcrafted CSS design styled with Cinzel serif typography, warm gold, deep navy, and olive palette, optimized across mobile, tablet, and desktop screens.
- **Suite & Villa Showcase:** Detailed listings highlighting front beach views, private infinity swimming pools, and fully-equipped kitchenettes with interactive modal details.
- **Categorized Curated Experiences:**
  - 🎁 **Complimentary & Free Villa Facilities:** Managed dynamically via admin portal.
  - 🏨 **In-House Luxuries & Villa Dining:** Floating Ceylon breakfast, private beachfront candlelight BBQ, sunset beach picnics.
  - 🌿 **Ayurvedic Wellness & Herbal Spa:** Traditional herbal steam baths, full-body massages, royal wellness sanctuary treatments.
  - 🌊 **Marine Excursions & Ocean Safaris:** Mirissa dolphin/whale watching, PADI scuba diving reefs, turtle sanctuary tours.
  - 🏺 **Cultural Heritage & Plantation Tours:** Village clay-pot cooking masterclasses, artisan cinnamon estate walks.
  - 🚗 **Airport Transfers & Transport:** Luxury private highway airport pickup and drop-off to Bandaranaike International Airport (BIA).
- **Interactive Packing Guide Modal:** Complete tropical island packing checklist with direct dismiss button.
- **Live Reservation Calculator (`book.php`):**
  - Instant dynamic price computation without reloading the page.
  - Automatic tiered discounts for longer stays: **5% off for 2 nights**, **10% off for 3+ nights**.
  - Server-calculated **8% Government VAT** applied on net subtotal.
  - **Dual Currency Switcher:** Toggle view cleanly between **USD ($)** and **Sri Lankan Rupees (LKR / Rs.)** with exchange rate computation (`1 USD = Rs. 320`).
- **Villa Policies & House Rules Gate:**
  - Full disclosure of check-in (2:00 PM) / check-out (11:00 AM) times, quiet hours (10:00 PM - 7:00 AM), no-smoking indoors, and safety rules.
  - Mandatory agreement checkbox required prior to confirming any reservation.
  - Dedicated custom requests box for dietary requirements and special occasion setups.
- **Guest Account Dashboard (`my_bookings.php`):** View past and pending reservations, itemized cost breakdowns, dates, and booking statuses.

### 2. Administration & Back-Office
- **Admin Dashboard (`admin/dashboard.php`):** High-level operational metrics (total bookings, revenue, room occupancy, pending confirmations).
- **Room Management (`admin/rooms.php`):** Create, edit, and update suite rates, capacity, descriptions, images, and availability statuses.
- **Experience & Services CRUD (`admin/services.php`):** Full control over all paid add-ons as well as `$0.00` Included Privilleges (with automated image resolution from `assets/images`).

---

## 💻 Software Requirements

- **Operating System:** Windows 10/11, macOS, or Linux
- **Web Server:** Apache HTTP Server (e.g. via XAMPP, WampServer, or LAMP stack)
- **PHP:** Version 7.4, 8.0, 8.1, or 8.2 (with `pdo_mysql`, `mbstring`, `session` extensions enabled)
- **Database Server:** MySQL 5.7+ or MariaDB 10.4+
- **Web Browser:** Any modern browser (Google Chrome, Mozilla Firefox, Microsoft Edge, Safari)

---

## 🚀 Installation & Configuration Instructions

### Step 1: Clone or Copy the Repository
Place the project directory into your web server's document root:
- **XAMPP on Windows:** `C:\xampp\htdocs\Web Technology Project`
- **WampServer on Windows:** `C:\wamp64\www\Web Technology Project`
- **Linux Apache:** `/var/www/html/Web Technology Project`

### Step 2: Start Apache and MySQL
1. Open the **XAMPP Control Panel**.
2. Click **Start** for both **Apache** and **MySQL**.
3. Verify both services turn green without port conflicts.

### Step 3: Database Setup
1. Open your browser and navigate to **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Create a new database named:
   ```sql
   heritage_villa_db
   ```
3. Click on the newly created `heritage_villa_db` database, select the **Import** tab, and choose:
   ```text
   C:\xampp\htdocs\Web Technology Project\database.sql
   ```
4. Click **Go / Import**. All tables (`users`, `rooms`, `services`, `bookings`, `booking_services`) and initial seed data will be created.

### Step 4: Verify Database Connection Configuration
Open `config/db.php` in a text editor and confirm connection parameters:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'heritage_villa_db');
define('DB_USER', 'root');
define('DB_PASS', '');           // Default password in XAMPP is empty
define('DB_CHARSET', 'utf8mb4');
define('USD_TO_LKR', 320);        // 1 USD = 320 LKR
define('VAT_RATE', 0.08);         // 8% VAT
```

### Step 5: Launch the Application
Open your web browser and navigate to:
```text
http://localhost/Web%20Technology%20Project/
```

---

## 🔑 Sample Login Credentials

The database comes pre-seeded with standard test accounts:

| User Role | Email Address | Password | Permissions |
| :--- | :--- | :--- | :--- |
| **Administrator (General Manager)** | `admin@heritagevilla.lk` | `admin123` | Full access to Admin Dashboard, Suite Management, Experiences & Bookings |
| **Registered Guest** | `guest@example.com` | `admin123` | Make reservations, view personal booking history & itemized billing |

*Note: New visitors can also freely create their own guest accounts via the **Register** page (`register.php`).*

---

## 🗄️ Database Schema & Architecture

The application relies on a relational MySQL structure with foreign key constraints:
- `users`: User profiles with role flags (`admin`, `guest`) and `password_hash()` encrypted passwords.
- `rooms`: Villa accommodations, room types, nightly base rates (USD), capacity, amenities, and photo references.
- `services`: Add-on experiences and free facilities categorized into `free_facility`, `in_house`, `wellness`, `marine`, `cultural`, and `travel`.
- `bookings`: Reservations linking a user and room across check-in/out dates, storing subtotal, discounts, VAT, and final grand totals.
- `booking_services`: Junction table recording selected add-on experiences per reservation with historical price capture.

---

## 🛡️ Security & Verification Practices

- **Prepared SQL Statements:** All queries use PHP Data Objects (PDO) with parameterized binding to prevent SQL Injection attacks.
- **CSRF Token Protection:** Form submissions (booking, registration, admin CRUD) are verified against per-session cryptographic CSRF tokens.
- **Double-Booking Prevention:** Transaction isolation with row-level locking (`SELECT ... FOR UPDATE`) prevents overlapping room reservations.
- **XSS Sanitization:** User-submitted content is sanitized using `htmlspecialchars()` prior to rendering in HTML views.
- **Bcrypt Hashing:** Passwords are encrypted using standard PHP `PASSWORD_BCRYPT`.

---

2026 **Heritage Coastal Villa**. All Rights Reserved. Mirissa, Sri Lanka.
