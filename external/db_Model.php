<?php
define("DB_SERVER", "localhost");
define("DB_USER", "myroot");
define("DB_PASS", "julius");
define("DB_NAME", "mystore");


	$connection = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
	if (mysqli_connect_errno()) {
    die("Database connection failed: " .
        mysqli_connect_error() .
        "(" . mysqli_connect_errno() . ")"
    );
	}       

function redirect_to($new_location) {
    header("Location: ".$new_location);
    exit();
}
//version 1
function save ($insertQuery){
    global $connection;
    $sql = mysqli_query($connection, $insertQuery);
    mysqli_close($connection);
}



// other functionality
function display_by_id($id){
	
}
//
function display_by_table($table_map){
	
}
?>




