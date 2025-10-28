<?php
/**
 * Home Controller
 * Handles business logic for the home page
 */

require_once __DIR__ . '/../models/db_model.php';

class HomeController {
    
    /**
     * Automatically deactivate expired event banners
     */
    public static function deactivateExpiredBanners() {
        $today = date('Y-m-d');
        update('banners', ['active' => 0], "event_end_date < '{$today}' AND active = 1");
    }
    
    /**
     * Get active banners for display
     * 
     * @return array Active banners
     */
    public static function getActiveBanners() {
        $today = date('Y-m-d');
        return fetch('banners', "active = 1 AND (event_end_date >= '{$today}' OR event_end_date IS NULL)", "event_start_date ASC, date_uploaded DESC");
    }
}
?>
