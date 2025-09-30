# API Folder

This folder contains API endpoints and server-side processing scripts that handle AJAX requests and data processing.

## Structure:
- `process_order.php` - Handles order processing and payment transactions
- Future API endpoints should be placed here

## Purpose:
Files in this folder are:
- **API Endpoints:** Handle AJAX requests from the frontend
- **Data Processing:** Process forms, handle transactions, manage data
- **JSON Responses:** Return JSON data to the frontend
- **Not User-Facing:** These are not pages users navigate to directly

## Usage:
API endpoints are called via JavaScript fetch requests:
```javascript
fetch('../api/process_order.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
})
```

## Path Convention:
- From pages: `../api/filename.php`
- From admin pages: `../../api/filename.php`
- From components: `../api/filename.php`

## Best Practices:
- Always validate input data
- Use proper HTTP status codes
- Return consistent JSON responses
- Include error handling
- Validate user permissions where needed