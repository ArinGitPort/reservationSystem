<?php
define(DB_SERVER, "localhost");
define(DB_NAME, "ellenfoodhouse");
define(DB_USER, "root");
define(DB_PASS, "1234");

$connection = mysqli_connect(DB_SERVER, DB_NAME, DB_USER,DB_PASS);
	if (mysqli_connect_errno()) {
    die("Database connection failed: " .
        mysqli_connect_error() .
        "(" . mysqli_connect_errno() . ")"
    );
}
?>