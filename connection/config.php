<?php
$conn = mysqli_connect("localhost", "root", "", "aumerch");

if (!$conn)
    {
        die ("Connection Failed: " . mysqli_connect_error());
    }
?>