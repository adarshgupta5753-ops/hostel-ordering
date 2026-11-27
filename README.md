
# Hostel Order Pooling System

A web-based hostel order pooling system that helps students combine online shopping orders to achieve free delivery thresholds.

## About the Project

Students living in hostels often place online shopping orders individually. As a result, they may fail to meet the minimum order value required for free delivery.

The Hostel Order Pooling System provides a common platform where hostel students can combine their purchase amounts with other students using the same shopping application and reach the required delivery threshold.

## Features

- Student registration and login
- User profile management
- Mobile-friendly and responsive interface
- Automatic order pooling
- Manual order pooling
- App-specific order grouping
- Group creation and group history
- Notifications when the required threshold is reached
- Group member details for communication
- Admin dashboard for managing students and groups

## Technologies Used

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- XAMPP (Local Development Environment)

## Supported Shopping Applications

- Blinkit
- Zepto

## Database

The project uses MySQL as its database.

The database structure is provided in:

`hostel_ordering.sql`

## Installation (Local Setup)

1. Install XAMPP.
2. Copy the project folder into `C:\xampp\htdocs\`.
3. Start Apache and MySQL from the XAMPP Control Panel.
4. Open phpMyAdmin.
5. Create a database named `hostel_ordering`.
6. Import the `hostel_ordering.sql` file.
7. Configure database credentials in `config.php` using `config.example.php` as a reference.
8. Open the project in your browser:

   `http://localhost/hostel-ordering/`

## Project Purpose

The main objective of this project is to help hostel students save delivery charges by combining their online shopping orders through a simple and convenient platform.

## Author

Developed as an academic project.
