# TradeSphere Setup Guide

## Requirements

- XAMPP
- PHP
- MySQL
- Web browser

Additional recommended tools:

- Visual Studio Code
- Git
- GitHub Desktop
- Composer
- phpMyAdmin

---

## Steps to Run

### Step 1

Place the `TradeSphere` folder inside `htdocs`

Example:

```text
C:\xampp\htdocs\TradeSphere
```

---

### Step 2

Start Apache and MySQL from XAMPP.

Open XAMPP Control Panel and start:

- Apache
- MySQL

Verify both services are running properly.

---

### Step 3

Create the database in phpMyAdmin

Open:

```text
http://localhost/phpmyadmin
```

Create database:

```text
TradeSphere
```

---

### Step 4

Import or create the required tables

Import the SQL database file if available.

If importing manually:

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

and other required project tables.

---

### Step 5

Update `config/db.php` if your MySQL port is different.

Example:

```php
$conn = new mysqli(
    "localhost",
    "root",
    "",
    "TradeSphere"
);
```

If MySQL uses a different port:

```php
$conn = new mysqli(
    "localhost:3307",
    "root",
    "",
    "TradeSphere"
);
```

---

### Step 6

Open the project in browser using:

```text
http://localhost/TradeSphere/
```

---

## Email Verification Setup

TradeSphere uses PHPMailer for email verification and related workflows.

Install PHPMailer:

```bash
composer require phpmailer/phpmailer
```

Configure SMTP credentials inside project configuration files.

Example:

```php
$mail->Host='smtp.gmail.com';
$mail->Username='your_email';
$mail->Password='your_app_password';
```

Use app passwords instead of regular passwords whenever possible.

---

## Payment Setup

TradeSphere currently supports:

- eSewa integration

Planned:

- Khalti integration

Update callback URLs before deployment.

Local:

```text
http://localhost/TradeSphere/payment_success.php
```

Production:

```text
https://yourdomain.com/payment_success.php
```

---

## Chat System Setup

TradeSphere includes a buyer–seller communication system.

Required tables:

- chat_rooms
- chat_messages
- product_offers
- notifications

Features include:

- product-specific chats
- message notifications
- unread indicators
- offer negotiation
- accepted offer workflows

---

## Optional Development Scripts

The `scripts/` folder contains helper files for development:

- `seed_sample_products.php` → inserts sample products
- `generate_keys.php` → generates RSA keys
- `make_hash.php` → used for hashing-related testing

These scripts are for development purposes only and should not be used in production without review.

---

## Development Utilities

Helper scripts support:

- sample product generation
- recommendation testing
- security experimentation
- RSA key generation
- hashing experimentation

---

## Deployment Notes

For hosting:

Replace:

```text
localhost
```

with:

```text
https://yourdomain.com
```

Update:

- payment callback URLs
- SMTP credentials
- database credentials
- uploaded image paths

---

## Future Deployment Plans

Planned deployment improvements:

- Azure App Service
- Azure MySQL
- GitHub Actions CI/CD
- cloud hosting
- automated deployment pipelines

---