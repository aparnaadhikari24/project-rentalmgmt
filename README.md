# StayNest — Property Rental Web Application

Tech stack: PHP (PDO, MySQL), HTML, CSS, JS (vanilla).

## Features
- Home with search
- Listings with filters + pagination
- Property details with contact form
- Auth (login/signup via PHP sessions)
- Favorites (per user)
- Tenant portal to create/manage listings with image uploads
- Admin dashboard (manage users only; admins redirect to dashboard after login/signup)

## Structure
```
assets/
   css/style.css
   js/script.js
backend/          # DB helpers and core endpoints
   db.php
   schema.sql
   migrate.php
   contact.php
   favorite.php
   profile.php
   tenant_inquiries.php
controllers/      # Route wrappers (stable public endpoints)
   contact.php
   favorite.php
   profile.php
   tenant_inquiries.php
   logout.php
includes/
   header.php
   navbar.php
   footer.php
   profile_drawer.php
pages/            # Canonical page copies (root files remain for compatibility)
   index.php
   properties.php
   property.php
   tenant.php
   login.php
   signup.php
   dashboard.php
docs/
   overview.md
uploads/
index.php          # Main entry (session-aware)
properties.php
property.php
tenant.php
dashboard.php
login.php
signup.php
index.html         # Legacy landing (kept for compatibility)
.htaccess          # Optional Apache clean routes
```

Full documentation (how it works, flows, DFD, ER, use cases): see `docs/overview.md`.

## Database
1. Create DB and import schema:
    - Create a MySQL database named `rental_app`.
    - Import `backend/schema.sql`.
2. Update credentials in `database/db.php` (or `backend/db.php` shim) if needed.

Admin seed: email `admin@example.com`, password `admin123`. Change after first login.

## Running locally (XAMPP/WAMP)
- Put this folder under your web root (e.g. `htdocs/final project`).
- Visit `http://localhost/final%20project/index.php` (recommended). Clean routes via .htaccess are included but optional.

## Notes
- File uploads: Tenant portal supports uploading images to the `uploads/` folder. Ensure the web server can write to that folder.
- Security: All DB writes are behind session checks. Consider adding CSRF tokens and rate limiting.
