# OrderController API Documentation

## Overview
The `OrderController` provides a complete CRUD API for managing orders in the Ellen Food House reservation system.

## Controller Location
`controllers/OrderController.php`

## How It's Used

### Customer Orders (Public API)
**Endpoint:** `POST /api/process_order.php`
- Calls: `OrderController::handle()`
- Creates new customer orders from the menu

### Admin Operations (Admin Page Handlers)
**Page:** `/pages/admin/order_management.php`
- Calls controller methods directly based on action parameter
- No separate API files needed for admin operations

---

## Controller Methods

### 1. Process New Order (Create)
**Method:** `OrderController::handle()`  
**Called By:** `api/process_order.php`

**Request Body:**
```json
{
  "customer_name": "John Doe",
  "customer_phone": "123-456-7890",
  "customer_email": "john@example.com",
  "order_type": "dine-in",
  "delivery_address": "",
  "special_instructions": "No onions please",
  "payment_method": "cash",
  "cart_items": [
    {
      "menu_id": 1,
      "quantity": 2,
      "price": 150.00
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Order placed successfully!",
  "order_id": 123,
  "order_number": "EFH-000123"
}
```

---

### 2. Get All Orders (Read)
**Method:** `OrderController::getAllOrders($limit, $status)`  
**Called By:** Admin page directly

**Usage:**
```php
require_once 'controllers/OrderController.php';
OrderController::getAllOrders(10, 'pending');
```

**Returns:** JSON with array of orders

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "order_id": 1,
      "customer_name": "John Doe",
      "order_status": "pending",
      "total_amount": "480.00",
      ...
    }
  ],
  "count": 10
}
```

---

### 3. Get Order by ID (Read)
**Method:** `OrderController::getOrderById($orderId)`  
**Called By:** `order_management.php?action=get_order&id={id}`

**Example:**
```php
OrderController::getOrderById(123);
```

**Response:**
```json
{
  "success": true,
  "data": {
    "order": {
      "order_id": 123,
      "customer_name": "John Doe",
      "order_status": "pending",
      "total_amount": "480.00",
      ...
    },
    "items": [
      {
        "item_id": 1,
        "menu_id": 1,
        "menu_name": "Adobo Rice Bowl",
        "quantity": 2,
        "price": "150.00",
        "subtotal": "300.00"
      }
    ]
  }
}
```

---

### 4. Update Order Status (Update)
**Method:** `OrderController::updateOrderStatus($orderId, $status)`  
**Called By:** `order_management.php` (POST with action=update_status)

**Usage:**
```php
OrderController::updateOrderStatus(123, 'confirmed');
```

**Valid Status Values:**
- `pending`
- `confirmed`
- `preparing`
- `ready`
- `delivered`
- `cancelled`

**Response:**
```json
{
  "success": true,
  "message": "Order status updated successfully"
}
```

---

### 5. Delete Order (Delete)
**Method:** `OrderController::deleteOrder($orderId)`  
**Called By:** `order_management.php` (POST with action=delete_order)

**Usage:**
```php
OrderController::deleteOrder(123);
```

**Response:**
```json
{
  "success": true,
  "message": "Order deleted successfully"
}
```

**Note:** Deleting an order will automatically delete all associated order items due to CASCADE foreign key constraint.

---

## Testing

### Customer Order (Public API)
```
POST http://localhost/reservationSystem-feature-cart/api/process_order.php
```

### Admin Operations (Through Admin Page)
```
GET  http://localhost/reservationSystem-feature-cart/pages/admin/order_management.php
GET  http://localhost/reservationSystem-feature-cart/pages/admin/order_management.php?action=get_order&id=1
POST http://localhost/reservationSystem-feature-cart/pages/admin/order_management.php (with form data)
```

---

## Error Responses

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "error": "Detailed error message (in development)"
}
```

**HTTP Status Codes:**
- `200` - Success
- `400` - Bad Request (validation error)
- `404` - Not Found
- `405` - Method Not Allowed
- `500` - Internal Server Error

---

## Usage in JavaScript

## Usage in JavaScript

### Customer placing order (Public)
```javascript
fetch('../api/process_order.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(orderData)
})
```

### Admin operations (Through admin page)
```javascript
// View order details
fetch('order_management.php?action=get_order&id=5')

// Update status
const formData = new FormData();
formData.append('action', 'update_status');
formData.append('order_id', 5);
formData.append('status', 'confirmed');
fetch('order_management.php', { method: 'POST', body: formData })

// Delete order
const formData = new FormData();
formData.append('action', 'delete_order');
formData.append('order_id', 5);
fetch('order_management.php', { method: 'POST', body: formData })
```

---

## Controller Methods (Direct PHP Usage)

If you need to call controller methods directly (e.g., from other admin pages):

```php
<?php
require_once '../../controllers/OrderController.php';

// Get all orders
OrderController::getAllOrders(10, 'pending');

// Get specific order
OrderController::getOrderById(123);

// Update order status
OrderController::updateOrderStatus(123, 'confirmed');

// Delete order
OrderController::deleteOrder(123);
?>
```

---

## Architecture

```
┌─────────────────────────────────────────────────┐
│                 CUSTOMER FLOW                   │
│  Menu Page → api/process_order.php →           │
│  OrderController::handle() → db_model.php      │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│                  ADMIN FLOW                     │
│  Admin Page → order_management.php →           │
│  OrderController methods → db_model.php        │
└─────────────────────────────────────────────────┘
```

**Benefits:**
- ✅ No redundant API files
- ✅ Controller methods are reusable
- ✅ Clean separation of public/admin operations
- ✅ Easier to maintain and extend

---

## Database Dependencies

The controller relies on these functions in `config/db_model.php`:
- `processOrder($orderData)` - Create new order
- `getAllOrders($limit, $status, $orderBy)` - Fetch orders
- `getOrderById($orderId)` - Fetch single order
- `getOrderItemsById($orderId)` - Fetch order items
- `updateOrderStatus($orderId, $status)` - Update status
- `delete($table, $idValue, $idColumn)` - Delete record

---

## Security Notes

1. **Always validate input** - The controller validates all inputs before processing
2. **Use prepared statements** - Ensure `db_model.php` uses parameterized queries
3. **Session management** - The controller attaches session user ID when available
4. **CORS headers** - Currently allows all origins (`*`) - restrict in production
5. **Authentication** - Add authentication checks for admin-only operations (update, delete)

---

## Future Enhancements

- Add authentication/authorization middleware
- Implement pagination for large result sets
- Add search and advanced filtering
- Add order history tracking
- Generate PDF receipts
- Send email notifications
