<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "GoWithMe";

$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Select primary database 'GoWithMe', or fallback to 'projectfile' if 'GoWithMe' does not exist
if (!@$conn->select_db($dbname)) {
    if (!@$conn->select_db("projectfile")) {
        die("Database selection failed: Neither '$dbname' nor 'projectfile' database was found. Error: " . $conn->error);
    }
}

?>