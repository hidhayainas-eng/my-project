# MDM – Master Data Management System (PHP + MySQL)

A compact PHP application that implements authentication, role-based access (admin vs user),
and CRUD for Brands, Categories, and Items with pagination, search/filter, file uploads,
and CSV export.

## Features

- Register / Login / Logout (passwords hashed with `password_hash`)
- Role-Based Access Control: `is_admin` on users (1=admin can manage all records; 0=can only see own)
- CRUD:
  - Brands (code, name, status)
  - Categories (code, name, status)
  - Items (brand, category, code, name, status, attachment)
- Pagination (default 5 per page) for all lists
- Search & Filter on Items (code/name/status)
- Delete confirmation (JS modal)
- CSV export for Items
- Simple, dependency-free PHP (PDO). No framework required.

## Requirements

- PHP 8+
- MySQL 5.7+ / MariaDB 10.4+
- Apache (e.g., XAMPP) or any server that can serve PHP
- `uploads/` directory must be writable by the web server

## Quick Start

1. Create database and tables:
   - Import `schema.sql` into MySQL
   - Optionally import `seed.sql` to create an admin user (email: admin@mdm.local / password: admin123)
2. Configure database credentials in `config/db.php`
3. Place this folder under your web root (e.g., `htdocs/MDM`)
4. Visit `http://localhost/MDM/public/index.php`

## Default Admin (if using seed.sql)

- Email: superadmin@mdm.local
- Password: superadmin123

> Change or delete the seeded user in production.

## Notes

- File uploads are stored under `public/uploads/`
- To change items per page, edit `config/app.php` (`ITEMS_PER_PAGE`)
- CSV export available on the Items page (`Export CSV` button)
