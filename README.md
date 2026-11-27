# Hostel Order Pooling System

A web-based hostel order pooling system that helps hostel students combine their online shopping orders to achieve free delivery thresholds. It allows students to join automatic or manual order groups and save delivery charges by pooling their purchases.

## Project Members

1. **Adarsh Gupta** — Roll No. 2026071105
2. **Kushagra Upadhyay** — Roll No. 2026071135
3. **Abhinav Verma** — Roll No. 2026071104
4. **Kaushal Kumar** — Roll No. 2026071133


## Project Supervisor

**Mr. Tejbahadur Sir**

## Features

- Student registration and login.
- User profile management.
- Automatic order pooling.
- Manual order pooling.
- Separate order groups for different shopping applications.
- Support for Blinkit and Zepto.
- Group creation and group history.
- Notifications when the required order threshold is reached.
- Group member details for communication.
- Admin dashboard for managing students and groups.
- Responsive interface for mobile and desktop devices.

## Tech Stack

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Local Development Environment:** XAMPP

## Prerequisites

Before running the project, make sure you have installed:

- XAMPP (including Apache and MySQL).
- PHP (included with XAMPP).
- MySQL / MariaDB (included with XAMPP).
- A modern web browser (Google Chrome, Firefox, etc.).

## Installation & Setup

### 1. Clone the Repository

Open the terminal and run:

```bash
git clone https://github.com/adarshgupta5753-ops/hostel-ordering.git
cd hostel-ordering
```

Alternatively, download the repository as a ZIP file from GitHub.

### 2. Set Up the Project

- Copy the project folder into the XAMPP `htdocs` directory.
- Start Apache and MySQL from the XAMPP Control Panel.
- Open phpMyAdmin in your browser: `http://localhost/phpmyadmin`
- Create a database named `hostel_ordering`.
- Import the provided `hostel_ordering.sql` file into the database.

### 3. Configure the Database

- Create a copy of `config.example.php` and name it `config.php`.
- Open `config.php`.
- Update the database host, database name, username and password according to your local MySQL configuration.
- Do not upload `config.php` containing actual credentials to a public GitHub repository.

### 4. Run the Application

Open your browser and visit:

```text
http://localhost/hostel-ordering/
```

Make sure Apache and MySQL are running.

## Usage

1. Register as a student using the registration page.
2. Log in using your registered mobile number and password.
3. Visit the Place Order section.
4. Choose Automatic or Manual order pooling.
5. Select the shopping application and enter the required order details.
6. Join or create a group with other students.
7. Receive notifications when the required threshold is reached.
8. View your group details and group history.

## Database

The project uses MySQL to store student information, orders, groups and notifications.

The database structure is available in the `hostel_ordering.sql` file.


## License

This project is licensed under the MIT License.
See the [LICENSE](LICENSE) file for details.
