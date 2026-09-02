# Watch Collection

A PHP + MySQL e-commerce college project for browsing and ordering watches.

## Requirements

- PHP 7.4+ (XAMPP or WAMP)
- MySQL 5.7+ / MariaDB
- Apache (bundled with XAMPP/WAMP)

## Setup Instructions

### 1. Place Project Files

**XAMPP:** copy the `watch-collection` folder into `C:\xampp\htdocs\`
**WAMP:** copy the `watch-collection` folder into `C:\wamp64\www\`

### 2. Start Services

Start **Apache** and **MySQL** from the XAMPP/WAMP control panel.

### 3. Create Database

Open **phpMyAdmin** (`http://localhost/phpmyadmin`) and import:

```
database/watch_collection.sql
```

This creates the `watch_collection` database with all required tables (no dummy data).

### 4. Configure Database Connection

Edit `config/database.php` if your MySQL credentials differ from defaults:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'watch_collection');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP/WAMP default is usually empty
```

### 5. Access the Website

```
http://localhost/watch-collaction/
```

Admin panel:

```
http://localhost/watch-collaction/admin/
```

## Notes

- This project is built for academic/college demonstration purposes.
- Database ships empty — no sample/demo records.