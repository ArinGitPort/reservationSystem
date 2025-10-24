# Ellen's Food House Reservation System - AI Coding Guide

## Architecture Overview

This is a classic **PHP MVC web application** for restaurant reservation management with dual authentication systems. The architecture follows strict separation of concerns:

- **Model Layer**: `models/db_model.php` - Pure data operations using MySQLi with prepared statements
- **Controller Layer**: `controllers/*.php` - Business logic and request handling  
- **View Layer**: `pages/*.php` - HTML presentation with embedded PHP
- **Static Assets**: `assets/css/`, `assets/js/`, `uploads/` - Frontend resources

## Critical Patterns & Conventions

### Database Operations
- **Always use `executeQuery()`** from `models/db_model.php` for parameterized queries
- **Never use `fetch()` with user input** - it's for simple conditions only
- Parameter binding format: `executeQuery($sql, [$param1, $param2], 'ss')`
- Database config is externalized in `config/config.php` (credentials, upload paths, pagination)

### Dual Authentication System
Two separate auth flows exist - never mix them:
- **Admin/Staff**: `AuthController` → sessions prefixed with `admin_*` → pages under `/admin/`
- **Customer**: `CustomerAuthController` → sessions prefixed with `customer_*` → pages under `/customer/`

Each controller has identical methods but different session namespaces and redirect paths.

### Controller Pattern
Controllers follow a consistent structure:
```php
class ExampleController {
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'action_name': return $this->methodName();
            }
        }
    }
}
```

### File Path Resolution
Controllers use dynamic model loading for flexible deployment:
```php
$configPath = file_exists('../models/db_model.php') ? '../models/db_model.php' : '../../models/db_model.php';
```

### Redirect Pattern
Use `ControllerHelper.php` functions:
- `redirect_to($location)` - Simple redirect
- `redirect_with_message($location, $message, $type)` - Flash message redirect

## Key Business Logic

### Reservation System
- **Table conflicts**: `UNIQUE KEY unique_table_datetime` prevents double-booking
- **Status flow**: pending → confirmed → seated → completed (or cancelled/no-show)
- **Party size validation**: 1-20 people enforced at database level

### Order Management
- **Order types**: dine-in, takeout, delivery (affects workflow)
- **Status progression**: pending → confirmed → preparing → ready → delivered
- **Customer association**: Orders link to `customers` table but store redundant contact info for data integrity

### File Upload Structure
- **Banners**: `uploads/banners/` - Event/promotional images
- **Menu items**: `uploads/menu/` - Food photos  
- **Customer profiles**: `uploads/profiles/` - User avatars
- Upload config in `config/config.php` defines size limits and allowed types

## Database Schema Highlights

### Core Tables
- `customers` - Customer accounts with auth fields (`password`, `verification_token`)
- `reservations` - Bookings with foreign key to customers
- `orders` + `order_items` - Order management with line items
- `menu` - Food items with pricing and bestseller flags
- `banners` - Event/promotional content with date ranges

### Authentication Tables  
- `admin_users` - Staff accounts with role-based access (admin/staff)
- `login_sessions` + `customer_sessions` - Session tracking for security

### Indexing Strategy
All tables have comprehensive indexes on frequently queried columns (dates, foreign keys, search fields).

## Development Workflows

### Adding New Admin Pages
1. Create controller in `controllers/` extending the handleRequest pattern
2. Add page in `pages/admin/` with `AuthController::requireAuth()` 
3. Include controller at top, instantiate and call `handleRequest()`
4. Add navigation item in `includes/sidebar.php`

### Frontend Assets
- **Bootstrap 5** + **Font Awesome 6** are CDN-loaded in all admin pages
- Custom CSS in `assets/css/` follows component-based naming
- JavaScript in `assets/js/` uses vanilla JS (no jQuery dependency)
- Mobile-first responsive design with sidebar toggle functionality

### Security Implementation
- All admin pages require `AuthController::requireAuth()` 
- Session tokens stored in database for tracking
- Password hashing uses PHP's `password_hash()` with bcrypt
- File uploads validate type and size limits from config

## Important Notes
- **Default admin**: username `admin`, password `admin123` (change in production)
- **Database name**: `ellenfoodhouse` (hardcoded in config)
- **XAMPP setup**: Configured for localhost MySQL with no password
- **Timezone**: America/New_York (configurable in `config/config.php`)

When extending this system, maintain the MVC separation, use the established auth patterns, and follow the database operation conventions for security and consistency.