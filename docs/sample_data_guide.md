# Sample Data Guide

To test recommendation systems and product browsing features, sample products were generated using a helper script.

## Script Used
`scripts/seed_sample_products.php`

## Purpose
This script inserts sample products into the database so the project has enough data for:
- product listing display
- cart testing
- recommendation testing
- seller product management testing

## Notes
- the script is for development only
- it should not be used directly on a production server
- seeded products may belong to the currently logged-in user, depending on script logic