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

---

## Additional Testing Support

The generated sample products also help simulate realistic marketplace activity and improve overall system testing.

Sample data supports:

- buyer and seller workflow testing
- notification system testing
- product search functionality testing
- category browsing testing
- wishlist testing
- profile dashboard testing
- order workflow testing
- product availability handling
- recommendation behavior testing
- UI layout testing
- chat and communication workflow testing
- offer and negotiation feature testing

---

## Recommendation System Testing

Sample products provide sufficient product variety for experimenting with recommendation logic, including:

- viewed product tracking
- cart activity
- purchase behavior
- category similarity
- product interactions
- content-based recommendation concepts
- cosine similarity experimentation

---

## Usage

Run the script after setting up the database:

```bash
php scripts/seed_sample_products.php
```

This quickly populates the marketplace with products and creates a more realistic environment for development and testing.

---

## Notes

- the script is for development only
- it should not be used directly on a production server
- seeded products may belong to the currently logged-in user, depending on script logic
- generated products are intended to support testing and experimentation
- sample data improves feature validation before real deployment

---

## Important

This utility exists only for development and testing purposes and is separated from the main application workflow to keep the production system clean and maintainable.