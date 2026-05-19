# TradeSphere Installation Guide

This guide explains how to set up and run TradeSphere locally using XAMPP.

---

# Requirements

Required software:

- XAMPP
- PHP
- MySQL
- Web browser

Recommended tools:

- Visual Studio Code
- Git
- GitHub Desktop
- Composer
- phpMyAdmin

---

# Project Setup Steps

## Step 1: Clone or Download Project

Clone repository:

```bash
git clone https://github.com/yourusername/TradeSphere-Ecommerce-Platform.git
```

Or download ZIP and extract.

---

## Step 2: Move Project Folder

Place the project folder inside:

```text
C:\xampp\htdocs\
```

Example:

```text
C:\xampp\htdocs\TradeSphere
```

---

## Step 3: Start XAMPP Services

Open XAMPP Control Panel.

Start:

- Apache
- MySQL

Make sure both services run without errors.

---

## Step 4: Create Database

Open:

```text
http://localhost/phpmyadmin
```

Create database:

```text
TradeSphere
```

---

## Step 5: Import Database

Import the SQL file if available.

If creating manually, important tables include:

- users
- products
- categories
- cart
- orders
- wishlist
- notifications
- chat_rooms
- chat_messages
- product_offers
- product_views
- admin

Additional tables may exist depending on project version.

---

## Step 6: Configure Database Connection

Open:

```php
config/db.php
```

Default configuration:

```php
$conn = new mysqli(
    "localhost",
    "root",
    "",
    "TradeSphere"
);
```

If MySQL uses another port:

```php
$conn = new mysqli(
    "localhost:3307",
    "root",
    "",
    "TradeSphere"
);
```

---

## Step 7: Open Project

Open browser:

```text
http://localhost/TradeSphere/
```

TradeSphere should now load.

---

# Email Verification Setup

TradeSphere uses PHPMailer for:

- email verification
- OTP workflows
- account communication

Install:

```bash
composer require phpmailer/phpmailer
```

Configure SMTP:

```php
$mail->Host='smtp.gmail.com';
$mail->Username='your_email';
$mail->Password='your_app_password';
```

Use app passwords instead of personal passwords.

---

# Payment Setup

Current payment support:

- eSewa

Planned:

- Khalti

For local testing:

```text
http://localhost/TradeSphere/payment_success.php
```

For deployment:

```text
https://yourdomain.com/payment_success.php
```

Update callback URLs accordingly.

---

# Chat System Setup

TradeSphere includes buyer–seller communication.

Required database tables:

- chat_rooms
- chat_messages
- product_offers
- notifications

Features:

- product-specific chat
- message notifications
- unread indicators
- negotiation workflows
- accepted offers

---

# Optional Development Scripts

The `scripts/` folder contains helper files for development:

- `seed_sample_products.php`
- `generate_keys.php`
- `make_hash.php`

Purpose:

- inserts sample products
- generates RSA keys
- supports hashing experiments

---

# Development Utilities

Helper utilities support:

- recommendation testing
- marketplace simulation
- RSA experimentation
- sample product generation
- security experimentation

These scripts are intended only for development.

---

# Hosting Notes

When deploying:

Replace:

```text
localhost
```

with:

```text
https://yourdomain.com
```

Update:

- database credentials
- SMTP credentials
- payment callback URLs
- uploaded image paths

---

# Future Deployment Plans

Planned deployment improvements:

- Azure App Service
- Azure MySQL
- GitHub Actions CI/CD
- automated deployment pipelines
- cloud hosting support

---

# Notes

Development utility files such as sample generators and RSA experimentation scripts are separated from the main application flow to keep the system organized and maintainable.
