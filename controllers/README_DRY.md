# Controllers - DRY Implementation Guide

This directory contains the controller classes that implement the business logic for the reservation system, following DRY (Don't Repeat Yourself) principles and utilizing the generic database functions from `db_model.php`.

## Available Controllers

### ReservationManagementController.php
- Handles all reservation-related operations
- Implements DRY status management with centralized status definitions
- Features: CRUD operations, status updates, bulk operations, statistics
- Uses generic `fetch()`, `update()`, `delete()` functions where possible

### AccountManagementController.php
- Manages customer account operations
- Handles customer CRUD operations and profile management

### OrderController.php
- Manages order processing and management
- Handles order lifecycle and status tracking

### MenuManagementController.php ⭐ **NEW**
- Comprehensive menu item management following DRY principles
- Features:
  - **CRUD Operations**: Add, edit, delete menu items using generic `save()`, `update()`, `delete()` functions
  - **Image Handling**: Automatic image upload, validation, and cleanup
  - **Search & Filtering**: Search by name/price, filter by best seller status
  - **Statistics**: Menu statistics (total items, best sellers, price analytics)
  - **Bulk Operations**: Support for bulk delete operations
  - **Validation**: Input validation, duplicate checking, file type validation
  - **Error Handling**: Comprehensive error handling with user-friendly messages

## DRY Principles Implementation

### Generic Database Functions Used
- `fetch($table, $conditions, $orderBy)` - Data retrieval
- `save($table, $data)` - Insert operations
- `update($table, $data, $conditions)` - Update operations
- `delete($table, $id, $idColumn)` - Delete operations

### Benefits
1. **Consistency**: All controllers follow the same patterns
2. **Maintainability**: Changes to database logic only need to be made in one place
3. **Reusability**: Common operations are abstracted into generic functions
4. **Testing**: Easier to test and debug isolated controller logic

## Integration with Search Filter Component

The `MenuManagementController` is integrated with the reusable `search_filter.php` component:
- **Search functionality**: Real-time search by menu item name or price
- **Filter options**: Filter by best seller status (All/Best Sellers/Regular Items)
- **PDF Export**: Integrated PDF export functionality with statistics
- **Responsive design**: Mobile-friendly filter interface

## Usage Pattern

```php
// Include the controller
require_once '../../controllers/MenuManagementController.php';

// Initialize and handle requests
$menuController = new MenuManagementController();
$menuController->handleRequest();

// Get data for display
$menuItems = $menuController->getAllMenuItems($search, 'all', $bestSeller);
$stats = $menuController->getMenuStatistics();
```

## File Organization

Each controller follows this structure:
- **Class definition** with private properties for configuration
- **handleRequest()** method for processing POST requests
- **Private methods** for specific operations (add, edit, delete)
- **Public methods** for data retrieval and statistics
- **Helper methods** for validation and file handling
- **DRY utility methods** for common operations

This approach ensures consistent, maintainable, and scalable code throughout the application.

## MenuManagementController Features

### Search & Filter Integration
```php
// The controller seamlessly integrates with search_filter.php
$menuItems = $menuController->getAllMenuItems($search, 'all', $bestSeller);

// Statistics for dashboard display
$stats = $menuController->getMenuStatistics();
// Returns: total_items, best_sellers, average_price, min_price, max_price
```

### Image Upload Management
- Automatic image validation (type, size)
- Smart filename generation with menu ID
- Automatic cleanup of old images on update/delete
- Support for multiple image formats (JPG, PNG)

### Error Handling
- Comprehensive validation for all inputs
- Duplicate name checking
- File upload validation
- User-friendly error messages
- Automatic redirects with status messages

This implementation demonstrates how to properly structure controllers using DRY principles while maintaining clean separation of concerns and reusable components.