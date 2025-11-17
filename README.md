## 🎥 Project Demo  
[▶️Watch the demonstration video (Bangla)](https://youtu.be/tduk_e8v2RU)

---

## 👥 Team Members

| Name | GitHub Profile |
|------|----------------|
| Chowdhury Naqib Bin Ershad | [CrankyBuddy](https://github.com/Crankybuddy) |
| Hirock Jerom Rozario | [hrozario2330363](https://github.com/hrozario2330363) |
| Md. Rahadul Islam | [mislam2330364](https://github.com/mislam2330364) |
| Abdul Gofran Emon | [GofranEmonKhan](https://github.com/GofranEmonKhan) |

---

# UIU Nexus – Smart Alumni & Career Connection Platform

**UIU Nexus** is an integrated digital platform designed to connect **students, alumni, and recruiters** of **United International University (UIU)** within a single ecosystem. It serves as a **career development and community engagement hub**, promoting mentorship, collaboration, and professional growth.

Built with **PHP, MySQL, and minimal JavaScript**, the platform emphasizes **role-based access control (RBAC)**, **CRUD operations**, and a **reward-driven interaction model** (coins, badges, and reputation).

---

Quick start instructions for running this PHP project locally (XAMPP / Windows).

## Installation

1. Install XAMPP (Apache + MySQL) and Composer on Windows.
2. Place the project in XAMPP's htdocs (example path used here):
   - C:\xampp\htdocs\uiu-nexus
     
## Database setup

1. Start XAMPP Apache and MySQL.
2. Import the canonical SQL dump:
   ```
   C:\xampp\htdocs\uiu-nexus\nexus.sql
   ```

## Usage

- Using XAMPP:
  - Start Apache and MySQL from the XAMPP Control Panel.
  - Open the app in a browser:
    - http://localhost/uiu-nexus/

## Login

- The password for every user is "password"
- The users:
  ```
   admin1@uiu.ac.bd — admin
   admin2@uiu.ac.bd — admin
   alice.student@uiu.ac.bd — student
   chris.student@uiu.ac.bd — student
   nina.student@uiu.ac.bd — student
   jamal.student@uiu.ac.bd — student
   leena.student@uiu.ac.bd — student
   omar.student@uiu.ac.bd — student
   fatima.student@uiu.ac.bd — student
   bob.alumni@uiu.ac.bd — alumni
   diana.alumni@uiu.ac.bd — alumni
   karim.alumni@uiu.ac.bd — alumni
   sara.alumni@uiu.ac.bd — alumni
   rachel.recruiter@acme.example — recruiter
   victor.hr@globex.example — recruiter
   beta.hr@beta.example — recruiter
  ```

## Users / Admin

- New users can typically be registered through the web UI (registration page), or added directly in the users table in the database.
- Admin-level users should be created manually via DB or through any admin creation script included in the project.


## Troubleshooting

- If pages show database errors: check DB credentials in .env or config and that MySQL is running.
- If assets or JS/CSS are missing: confirm `public/` is the web root (or adjust Apache DocumentRoot).

For more details about the system and features, inspect the code under `app/Views/`, `app/Controllers/`, and the `nexus.sql` dump for sample data and account info.

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
