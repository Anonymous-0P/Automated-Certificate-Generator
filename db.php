<?php
$mysqli = new mysqli("localhost", "root", "", "certificates_db");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
?>
