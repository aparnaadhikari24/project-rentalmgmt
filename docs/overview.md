# StayNest Project Overview

This document explains how the project works end-to-end, including setup, flows, data model, and diagrams (DFD, ER, and use cases).

## Getting Started
- Requirements: PHP 8+, MySQL 5.7+/8+, a web server (Apache via XAMPP/WAMP) with file uploads enabled.
- Database: Create a DB (e.g., `rental_app`) and import `backend/schema.sql`. If your DB is older, visit `backend/migrate.php` once to add new columns.
- Config: Update DB credentials in `backend/db.php` if needed.
- Run: Place this folder in web root (e.g., `htdocs/final project`) and visit `/index.php`.

## High-level Architecture
- Public pages are under project root for compatibility, with canonical copies under `pages/`.
- Controllers in `controllers/` forward to backend endpoints; backend under `backend/` contains core logic (favorites, profile update, inquiries, etc.).
- MySQL stores users, properties, favorites, and inquiries.
- Images are uploaded to `uploads/` and referenced by relative URLs.

### DFD Level 0 (Context)
```mermaid
flowchart TD
  User[(Guest/User)] -->|HTTP(S)| Web[StayNest PHP App]
  Admin[(Admin)] -->|HTTP(S)| Web
  Web -->|SQL (PDO)| DB[(MySQL)]
  Web -->|read/write| UP[(uploads/ images)]
```

### DFD Level 1 (Main Processes)
```mermaid
flowchart LR
  subgraph WebApp[StayNest PHP App]
    A[Auth & Sessions\nlogin.php / signup.php] --> S[(Session)]
    L[Listings & Search\nproperties.php] --> DB[(MySQL)]
    D[Property Detail\nproperty.php] --> DB
    F[Favorites API\nbackend/favorite.php] --> DB
    C[Contact/Inquiries API\nbackend/contact.php] --> DB
    T[Tenant Portal\ntenant.php] --> DB
    T --> UP[(uploads/)]
  AD[Admin Dashboard (Users Only)\ndashboard.php] --> DB
  end
  User --> L
  User --> D
  User --> F
  User --> C
  User --> A
  Admin --> AD
  
```

## Data Model (ER Diagram)
```mermaid
erDiagram
  USERS ||--o{ PROPERTIES : owns
  USERS ||--o{ FAVORITES : saves
  USERS ||--o{ INQUIRIES : sends
  PROPERTIES ||--o{ INQUIRIES : receives
  PROPERTIES ||--o{ FAVORITES : has

  USERS {
    int id PK
    varchar name
    varchar email UNIQUE
    varchar password
    enum role "user|admin"
  }
  PROPERTIES {
    int id PK
    varchar title
    text description
    decimal price
    varchar location
    varchar type
    varchar image_url
    int owner_id FK -> USERS.id
    enum status  "available|rented"
    datetime created_at
  }
  FAVORITES {
    int user_id FK -> USERS.id
    int property_id FK -> PROPERTIES.id
    PK (user_id, property_id)
  }
  INQUIRIES {
    int id PK
    int user_id FK -> USERS.id
    int property_id FK -> PROPERTIES.id
    text message
    datetime created_at
  }
```

## Use Case Diagram (Actors & Goals)
```mermaid
flowchart LR
  subgraph Actors
    G(Guest)
    U(User)
    O(Owner/Tenant)
    A(Admin)
  end
  subgraph UseCases
    UC1((Browse Listings))
    UC2((Search/Filter))
    UC3((View Property))
    UC4((Sign Up / Log In))
    UC5((Save to Favorites))
    UC6((Send Inquiry))
    UC7((Manage Profile))
    UC8((Create/Manage Listings))
    UC9((Upload Images))
    UC10((Toggle Status: Available/Rented))
    UC11((Admin CRUD & Review)))
  end

  G --> UC1
  G --> UC2
  G --> UC3
  G --> UC4

  U --> UC5
  U --> UC6
  U --> UC7

  O --> UC8
  O --> UC9
  O --> UC10

  A --> UC11
```

## How It Works
- Authentication: Users sign up/login; passwords are hashed. Sessions identify the current user and gate features.
- Listings & Search: `properties.php` queries MySQL with optional filters and pages results.
- Property Detail: `property.php` shows full info; favorites and inquiries are available unless status is `rented`.
- Favorites: Button triggers `backend/favorite.php` to add/remove a row in `favorites`.
- Inquiries: `backend/contact.php` stores messages in `inquiries`. When a property is `rented`, the UI disables inquiries and the backend rejects them.
- Tenant Portal: Logged-in users can create and manage their own properties, upload images to `uploads/`, and toggle status between `available` and `rented`.
- Admin Dashboard: Admins manage users (create/update/delete). Admins are redirected here on login/signup.

## Security & Notes
- Prepared statements everywhere (PDO) to avoid SQL injection.
- Passwords use `password_hash`/`password_verify`.
- Sessions required for write actions; admin endpoints check admin flag.
- TODO: Add CSRF tokens to forms; add rate-limiting for sensitive actions.

## Folder Structure (at a glance)
```
final project/
├── assets/
│   ├── css/style.css
│   └── js/script.js
├── backend/
│   ├── db.php
│   ├── schema.sql
│   ├── migrate.php
│   ├── contact.php
│   ├── favorite.php
│   ├── profile.php
│   └── tenant_inquiries.php
├── docs/
│   └── overview.md   # This document
├── includes/
│   ├── header.php
│   ├── navbar.php
│   ├── footer.php
│   └── profile_drawer.php
├── controllers/
│   ├── contact.php
│   ├── favorite.php
│   ├── profile.php
│   └── tenant_inquiries.php
├── pages/ (canonical page versions; root files exist for compatibility)
│   ├── index.php
│   ├── properties.php
│   ├── property.php
│   ├── tenant.php
│   ├── login.php
│   ├── signup.php
│   └── dashboard.php
├── uploads/
│   └── (image files)
├── index.php
├── properties.php
├── property.php
├── tenant.php
├── dashboard.php
├── admin/
│   └── admin_portal.php  # redirects to dashboard (legacy)
├── login.php
├── signup.php
├── README.md
└── index.html  # kept for compatibility; main entry is index.php
```

## Troubleshooting
- If property owner columns are missing, run `backend/migrate.php` once.
- If uploads fail, ensure `uploads/` exists and the web server can write to it.
- Check your DB credentials in `backend/db.php`.
