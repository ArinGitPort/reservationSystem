<?php
function redirect_to($url) {
    header("Location: $url");
    exit();
}

// Redirect to home page
redirect_to("pages/home.php");
?>