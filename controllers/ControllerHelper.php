<?php
/**
 * Controller Helper Functions
 * 
 * Utility functions for controllers (not part of the Model layer)
 * These handle HTTP responses, redirects, and other controller-specific tasks
 */

/**
 * DRY: Universal redirect function that handles both simple and message redirects
 * 
 * @param string $location URL to redirect to
 * @param string $message Optional message to display (empty for simple redirect)
 * @param string $type Optional message type (success, error, warning, info)
 */
function redirect_to($location, $message = '', $type = '') {
    if (!empty($message) && !empty($type)) {
        $encodedMessage = urlencode($message);
        $location .= "?message=" . $encodedMessage . "&type=" . $type;
    }
    header("Location: " . $location);
    exit();
}

/**
 * DRY: Convenience wrapper for redirect with message (maintains backward compatibility)
 * 
 * @param string $location URL to redirect to
 * @param string $message Message to display
 * @param string $type Message type (success, error, warning, info)
 */
function redirect_with_message($location, $message, $type) {
    return redirect_to($location, $message, $type);
}

/**
 * DRY: Generic success redirect for controllers
 * 
 * @param string $message Success message to display
 * @param string $location Optional custom location (defaults to current page)
 */
function redirect_with_success($message, $location = null) {
    $location = $location ?? $_SERVER['PHP_SELF'];
    return redirect_to($location, $message, "success");
}

/**
 * DRY: Generic error redirect for controllers
 * 
 * @param string $message Error message to display
 * @param string $location Optional custom location (defaults to current page)
 */
function redirect_with_error($message, $location = null) {
    $location = $location ?? $_SERVER['PHP_SELF'];
    return redirect_to($location, $message, "error");
}
?>
