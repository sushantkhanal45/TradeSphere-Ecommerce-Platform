
---

## `docs/cicd_guide.md`

```md
# CI/CD Guide

TradeSphere uses CI/CD through GitHub Actions and Azure App Service.

## What is CI/CD?

CI/CD means Continuous Integration and Continuous Deployment.

In this project, whenever new code is pushed to GitHub, Azure automatically deploys the latest version of the website.

## CI/CD Flow

```text
Developer updates code
↓
git add .
↓
git commit
↓
git push
↓
GitHub Actions starts automatically
↓
Azure deploys the updated website
↓
Live website is updated






---

## `docs/environment_variables.md`

```md
# Environment Variables Guide

TradeSphere uses environment variables to store sensitive configuration values securely.

## Why Environment Variables Are Used

Sensitive data should not be pushed to GitHub.

Examples of sensitive data include:

- database password
- database username
- SMTP email password
- payment gateway secrets
- API keys

Instead of writing these values directly in code, they are stored in Azure App Service Environment Variables.

## Database Environment Variables

The following environment variables are used for Azure MySQL connection:

```text
DB_HOST=tradesphere-db.mysql.database.azure.com
DB_USER=adminuser
DB_PASSWORD=your_azure_mysql_password
DB_NAME=tradesphere
DB_PORT=3306





---

## `docs/hosting_notes.md`

```md
# Hosting Notes

TradeSphere was originally developed locally using PHP, MySQL, HTML, CSS, and JavaScript.

## Local Development Setup

During local development, the project was run using:

- XAMPP Apache
- MySQL database
- phpMyAdmin / MySQL Workbench
- VS Code

## Cloud Hosting Setup

For cloud hosting, the project uses:

- Azure App Service for PHP hosting
- Azure Database for MySQL Flexible Server
- GitHub for version control
- GitHub Actions for CI/CD

## Database Migration

The local database was exported as an SQL file and imported into Azure MySQL.

This allowed existing tables and sample data to be moved into the cloud database.

## Important Tables

Some important database tables include:

- users
- products
- cart
- orders
- notifications
- chat_messages
- chat_rooms
- product_offers
- product_ratings
- wishlist

## Live Data

After deployment, new user registrations, products, chats, offers, carts, and orders are stored in the Azure MySQL database.

This means data is stored in the cloud and can be accessed later through MySQL Workbench or Azure database tools.

## Deployment Note

The website should not use localhost database settings after deployment. Azure App Service connects to Azure MySQL using environment variables.
