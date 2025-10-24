# UIU Nexus – Smart Alumni & Career Connection Platform

**UIU Nexus** is an integrated digital platform designed to connect **students, alumni, and recruiters** of **United International University (UIU)** within a single ecosystem. It serves as a **career development and community engagement hub**, promoting mentorship, collaboration, and professional growth.

Built with **PHP, MySQL, and minimal JavaScript**, the platform emphasizes **role-based access control (RBAC)**, **CRUD operations**, and a **reward-driven interaction model** (coins, badges, and reputation).

---

Quick start instructions for running this PHP project locally (XAMPP / Windows).

## Installation

1. Install XAMPP (Apache + MySQL) and Composer on Windows.
2. Place the project in XAMPP's htdocs (example path used here):
   - C:\xampp\htdocs\uiu-nexus
3. Install PHP dependencies (if any):
   ```powershell
   cd C:\xampp\htdocs\uiu-nexus
   composer install
   ```
4. Create environment config:
   - Copy any environment example to a working config (if present):
   ```powershell
   copy .env.example .env
   ```
   - Or edit app/Config/Database.php (or the project-specific config) to set DB credentials.

## Database setup

1. Start XAMPP Apache and MySQL.
2. Import the canonical SQL dump:
   ```powershell
   cd C:\xampp\htdocs\uiu-nexus
   mysql -u root -p < nexus.sql
   ```
   (Replace `root` and `-p` usage with your DB user/password as required.)
3. Verify database credentials in your .env or config file.

## Usage

- Using XAMPP:
  - Start Apache and MySQL from the XAMPP Control Panel.
  - Open the app in a browser:
    - http://localhost/uiu-nexus/ or http://127.0.0.1/uiu-nexus/
- Or use PHP built-in server (if supported by project):
  ```powershell
  cd C:\xampp\htdocs\uiu-nexus\public
  php -S 127.0.0.1:8080
  ```
  Then visit http://127.0.0.1:8080/

## Login

- The project may include sample users in `nexus.sql`. Check that file for sample usernames/passwords.
- If no sample admin exists, create one:
  - Use the app's registration flow (if present), or
  - Insert an admin user directly into the database (use a safe hashed password) or run any included seed script.
- After importing DB, update credentials as needed and rotate passwords for any sample accounts.

## Users / Admin

- New users can typically be registered through the web UI (registration page), or added directly in the users table in the database.
- Admin-level users should be created manually via DB or through any admin creation script included in the project.
- If the project provides an administrative panel, its URL and credentials are either documented in `nexus.sql` or created during setup.

## Git / Deployment notes

- A recommended `.gitignore` is present to exclude environment files, uploads, vendor/node_modules, and dumps:
  - Do not commit `.env`, private keys, uploads, or real DB dumps with sensitive data.
- Before pushing to GitHub, confirm `nexus.sql` is the single canonical dump you want tracked. Remove any sensitive or large files from history (use git-filter-repo or BFG if needed).

## Troubleshooting

- If pages show database errors: check DB credentials in .env or config and that MySQL is running.
- If assets or JS/CSS are missing: confirm `public/` is the web root (or adjust Apache DocumentRoot).

For more details about the system and features, inspect the code under `app/Views/`, `app/Controllers/`, and the `nexus.sql` dump for sample data and account info.

## 👥 Team Members

| Name | GitHub Profile |
|------|----------------|
| Chowdhury Naqib Bin Ershad | [CrankyBuddy](https://github.com/Crankybuddy) |
| Hirock Jerom Rozario | [hrozario2330363](https://github.com/hrozario2330363) |
| Md. Rahadul Islam | [mislam2330364](https://github.com/mislam2330364) |
| Abdul Gofran Emon | [GofranEmonKhan](https://github.com/GofranEmonKhan) |

---

## Table of Contents

* [Features](#features)
* [Installation](#installation)
* [Usage](#usage)
* [Technologies](#technologies)
* [Contributing](#contributing)
* [License](#license)

---

## Features

### 1. User Management & Profiles

* Role-based registration: Student, Alumni, Recruiter, Admin.
* Editable profiles with skills, achievements, and privacy controls.
* Admin verification of accounts.

### 2. People Directory

* Search and filter users by role, skill, or badge.
* Quick actions for communication or mentorship requests.
* Admin can gift coins to users.

### 3. Forum & Reputation

* Q&A forum with **upvotes/downvotes**.
* Reputation tracking and automatic badge assignments.
* Coin-based rewards for active participation.

### 4. Mentorship System

* Alumni-hosted mentorship listings (paid or free).
* Request workflows with escrow handling.
* Automatic coin and badge rewards for mentors and mentees.

### 5. Wallet & Transactions

* Coin and reputation ledger.
* Transaction history for transparency.
* Admin grants and adjustments.

### 6. Campus Life & Opportunities

* Event announcements for students.
* Job and internship listings.

### 7. Notifications & Messaging

* Real-time notifications for important updates.
* Private messaging between users.

### 8. Admin Controls

* Full moderation capabilities.
* Audit logging and suspension management.
* Coin and reward management.
* Policy enforcement.

---

## Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/yourusername/uiu-nexus.git
   ```
2. Navigate to the project directory:

   ```bash
   cd uiu-nexus
   ```
3. Set up a local server (XAMPP, WAMP, or LAMP).
4. Import the `database.sql` file into MySQL.
5. Configure `config.php` with your database credentials.
6. Access the application in your browser:

   ```
   http://localhost/uiu-nexus/public
   ```

---

## Usage

* **Students & Alumni:** Sign up, update profiles, participate in forums, and engage in mentorship.
* **Recruiters:** Post opportunities, connect with verified talent, and view profiles.
* **Admins:** Manage users, monitor transactions, enforce policies, and moderate content.

---

## Technologies

* **Backend:** PHP
* **Database:** MySQL
* **Frontend:** HTML, CSS, Minimal JavaScript
* **Other:** RBAC, Coin & Badge Reward System

---


## License

This project is licensed under the MIT License.
