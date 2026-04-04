# TradeSphere Setup Guide

## Requirements
- XAMPP
- PHP
- MySQL
- Web browser

## Steps to Run
1. Place the `TradeSphere` folder inside `htdocs`
2. Start Apache and MySQL from XAMPP
3. Create the database in phpMyAdmin
4. Import or create the required tables
5. Update `config/db.php` if your MySQL port is different
6. Open the project in browser using:
   `http://localhost/TradeSphere/`

## Optional Development Scripts
The `scripts/` folder contains helper files for development:
- `seed_sample_products.php` → inserts sample products
- `generate_keys.php` → generates RSA keys
- `make_hash.php` → used for hashing-related testing

These scripts are for development purposes only and should not be used in production without review.