# Components Folder

This folder contains reusable components and modal content files that are used across the application but are not standalone pages.

## Structure:
- `get_order_details.php` - Modal content for displaying detailed order information
- Future modal components and reusable UI elements should be placed here

## Usage:
Components in this folder are typically loaded via AJAX calls or included in other pages. They are not meant to be accessed directly as standalone pages.

## Path Convention:
When calling components from admin pages, use: `../../components/filename.php`
When calling components from root pages, use: `components/filename.php`