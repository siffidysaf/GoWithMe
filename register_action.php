<?php
session_start();
require_once "DBConnect.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = trim($_POST['name']);
    $phone    = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $gender   = $_POST['gender'];
    $age      = (int)$_POST['age'];
    $instVal  = $_POST['institution'] ?? '';

    $finalInstName = NULL;

    // Handle Institution Selection
    if (!empty($instVal)) {
        if ($instVal === '__OTHER__') {
            $customName  = trim($_POST['custom_inst_name']);
            $customPlace = $_POST['custom_inst_place'] ?? '';

            if (!empty($customName) && !empty($customPlace)) {
                list($instArea, $instCity) = explode('|', $customPlace);
                $finalInstName = $customName;

                // Check if institution already exists in database
                $checkInst = $conn->prepare("SELECT Name FROM instituition WHERE Name = ?");
                $checkInst->bind_param("s", $finalInstName);
                $checkInst->execute();
                $resInst = $checkInst->get_result();
                $checkInst->close();

                // Create new institution if it doesn't exist
                if ($resInst->num_rows === 0) {
                    $stmtNewInst = $conn->prepare("INSERT INTO instituition (Name, Area, City) VALUES (?, ?, ?)");
                    $stmtNewInst->bind_param("sss", $finalInstName, $instArea, $instCity);
                    $stmtNewInst->execute();
                    $stmtNewInst->close();
                }
            }
        } else {
            $finalInstName = $instVal;
        }
    }

    // Check if phone number is already registered
    $checkPhone = $conn->prepare("SELECT ID FROM user WHERE Phone = ?");
    $checkPhone->bind_param("s", $phone);
    $checkPhone->execute();
    $resPhone = $checkPhone->get_result();
    $checkPhone->close();

    if ($resPhone->num_rows > 0) {
        echo "<script>
                alert('This phone number is already registered. Please log in.');
                window.location.href = 'login.php';
              </script>";
        exit();
    }

    // Insert new user into database
    $stmtUser = $conn->prepare("INSERT INTO user (Name, Gender, Age, Phone, Password, Instituition) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtUser->bind_param("ssisss", $name, $gender, $age, $phone, $password, $finalInstName);

    if ($stmtUser->execute()) {
        $stmtUser->close();
        $conn->close();
        echo "<script>
                alert('Registration successful! Please login.');
                window.location.href = 'login.php';
              </script>";
        exit();
    } else {
        $error = $stmtUser->error;
        $stmtUser->close();
        $conn->close();
        echo "Error creating account: " . htmlspecialchars($error) . "<br><br><a href='register.php'>Try Again</a>";
    }
} else {
    header("Location: register.php");
    exit();
}
?>