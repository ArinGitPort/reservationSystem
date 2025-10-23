<?php
/**
 * Menu Controller (Public)
 * Handles business logic for the public menu page
 */

require_once __DIR__ . '/../config/db_model.php';

class MenuController {
    
    /**
     * Get all menu items for public display
     * 
     * @return array Menu items sorted by best sellers first, then by name
     */
    public static function getMenuItems() {
        return fetch('menu', '', 'is_best_seller DESC, name ASC');
    }
    
    /**
     * Get menu item by ID
     * 
     * @param int $menuId Menu item ID
     * @return array|null Menu item data or null if not found
     */
    public static function getMenuItemById($menuId) {
        $result = fetch('menu', "menu_id = $menuId");
        return $result ? $result[0] : null;
    }
    
    /**
     * Get best seller items only
     * 
     * @return array Best seller menu items
     */
    public static function getBestSellers() {
        return fetch('menu', 'is_best_seller = 1', 'name ASC');
    }
}
?>
