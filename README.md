# TradeSphere-Ecommerce-Platform [Final Year Project - B.Sc.CSIT]

TradeSphere is a multi-vendor e-commerce marketplace built with PHP, MySQL, HTML, CSS, and JavaScript. Users can buy and sell products through a single account. It includes product listings, cart and checkout features, dashboards, an admin panel, a recommendation system using cosine similarity, and secure buyer–seller chat with RSA key exchange.

---

# TradeSphere

TradeSphere is a multi-vendor e-commerce web application built using PHP, MySQL, HTML, CSS, and JavaScript. It allows users to register, log in, list products for sale, browse items, add products to cart, and manage their own listings.

The platform aims to provide a complete marketplace experience while exploring modern concepts such as secure communication, intelligent recommendations, negotiation workflows, and notification systems.

---

## Main Features

### User Features

- User authentication
- Email verification workflow
- Single account supports buying and selling
- User profile dashboard
- Wishlist management
- Profile dropdown interface
- Floating cart interface

---

### Marketplace Features

- Buy and sell products
- Product cart system
- Seller listing management
- Product availability handling
- Sold product handling in cart
- Product search and filtering
- Product categories
- Product browsing system
- Product image uploads
- Purchase history
- Received orders section
- Completed sales tracking

---

### Communication Features

TradeSphere includes buyer–seller communication features:

- Product-specific chat rooms
- Floating message interface
- Read/unread message indicators
- Message notification system
- Quick message suggestions
- Product discussion before purchase
- Product discussion after purchase
- Pinned product cards inside chat

---

### Offer and Negotiation System

The platform supports negotiation workflows:

- Buyer can make offers
- Seller can accept offers
- Seller can reject offers
- Accepted offers become pinned
- Negotiated prices can be directly added to cart
- Checkout supports negotiated pricing

---

### Notification Features

- Notification bell system
- Unread notification counters
- Read/unread notification handling
- Message notifications
- Delivery notifications
- Order updates
- Floating message indicators

---

### Order Tracking Features

Order workflow:

```text
Processing
    ↓
Out For Delivery
    ↓
Delivered
```

Both buyers and sellers receive updates throughout the process.

---

### Security Features

- RSA/signature-related experimental security utilities
- Secure communication experimentation
- RSA key generation experimentation
- Digital signature workflows
- Hash generation experimentation
- Non-repudiation concepts
- Secure buyer–seller communication research

---

### Recommendation System

TradeSphere includes recommendation-ready architecture using:

- viewed products
- cart history
- product interactions
- purchase behavior
- category matching
- product similarity
- cosine similarity concepts

---

## Technology Stack

### Frontend

- HTML
- CSS
- JavaScript

### Backend

- PHP

### Database

- MySQL
- phpMyAdmin

### Development Environment

- XAMPP
- Visual Studio Code

### Payment Integration

- eSewa API
- Khalti (planned)

---

## Project Structure

```text
TradeSphere/
│
├── config/          → database connection
├── includes/        → reusable PHP components
├── css/             → styles
├── js/              → scripts
├── uploads/         → product images
├── scripts/         → development/setup utility scripts
├── docs/            → documentation and project notes
│
├── index.php
├── profile.php
├── product_details.php
├── chat.php
├── messages.php
└── payment_success.php
```

---



## Setup

Please check:

```text
docs/INSTALLATION_GUIDE.md
```

---

## Development Utilities

Development utility files are stored inside:

```text
scripts/
```

Includes:

- `seed_sample_products.php`
- `generate_keys.php`
- `make_hash.php`

These scripts support:

- sample product generation
- recommendation testing
- RSA experimentation
- hashing workflows

These files are primarily for development and testing purposes.

---

## Future Improvements

Planned enhancements:

- Khalti payment integration
- WebSocket-based real-time chat
- Azure deployment
- GitHub Actions CI/CD
- AI-powered recommendation system
- Cloud image storage
- mobile responsiveness improvements

---

## Portfolio Highlights

This project demonstrates:

- Full-stack development
- Marketplace workflow implementation
- Database design
- Recommendation systems
- Notification architecture
- Security experimentation
- Payment integration
- UI/UX design concepts
- GitHub project management

---

## Author

Alina Gelal  
University of Cincinnati  
Biochemistry Major

---

## License

Academic and educational use.


