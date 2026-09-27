<?php
$conn = @mysqli_connect("localhost", "root", "", "au_merch");

// Older local copies of the project used the database name "aumerch".
if (!$conn) {
    $conn = @mysqli_connect("localhost", "root", "", "aumerch");
}

if (!$conn) {
    die("Database connection failed. Check that MySQL is running and the au_merch database exists.");
}
?>