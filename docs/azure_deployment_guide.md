# Azure Deployment Guide

TradeSphere is deployed using Azure App Service for the PHP web application and Azure Database for MySQL Flexible Server for the cloud database.

## Hosting Architecture

TradeSphere uses the following deployment structure:

- Frontend and backend PHP files are hosted on Azure App Service.
- MySQL database is hosted on Azure Database for MySQL Flexible Server.
- GitHub is used as the source code repository.
- GitHub Actions is used for automatic deployment to Azure.

## Azure Services Used

- Azure App Service
- Azure Database for MySQL Flexible Server
- GitHub Actions
- Azure Environment Variables

## Database Hosting

The local database was exported from the development environment and imported into Azure MySQL.

Azure database details:

```text
Database Server: tradesphere-db.mysql.database.azure.com
Database Name: tradesphere
Port: 3306