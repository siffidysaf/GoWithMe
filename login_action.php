<?php
session_start();
require_once "DBConnect.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $phone    = trim($_POST['phone']);
    $password = trim($_POST['password']);

    // Match strictly by Phone number
    $stmt = $conn->prepare("SELECT ID, Name, Password, Phone, Instituition FROM user WHERE Phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($password === $row['Password']) {
            // Save user details in the session
            $_SESSION['user_id']      = $row['ID'];
            $_SESSION['user_name']    = $row['Name'];
            $_SESSION['user_phone']   = $row['Phone'];
            $_SESSION['instituition'] = $row['Instituition'];

            header("Location: dashboard.php");
            exit();
        } else {
            echo "Incorrect password. <a href='login.php'>Try again</a>";
        }
    } else {
        echo "No account registered with this phone number. <a href='login.php'>Try again</a>";
    }

    $stmt->close();
    $conn->close();
}
?>