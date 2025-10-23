<?php
/**
 * Controller Helper Functions
 * 
 * Utility functions for controllers (not part of the Model layer)
 * These handle HTTP responses, redirects, and other controller-specific tasks
 */

/**
 * Redirect to a new location
 * 
 * @param string $location URL to redirect to
 */
function redirect_to($location) {
    header("Location: " . $location);
    exit();
}

/**
 * Redirect with a message (for flash messages)
 * 
 * @param string $location URL to redirect to
 * @param string $message Message to display
 * @param string $type Message type (success, error, warning, info)
 */
function redirect_with_message($location, $message, $type) {
    $encodedMessage = urlencode($message);
    $redirectUrl = $location . "?message=" . $encodedMessage . "&type=" . $type;
    header("Location: " . $redirectUrl);
    exit();
}
?>
