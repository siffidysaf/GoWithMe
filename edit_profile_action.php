<?php
session_start();
require_once "DBConnect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userId      = (int)$_SESSION['user_id'];
    $name        = trim($_POST['name']);
    $newPassword = trim($_POST['new_password']);
    $gender      = $_POST['gender'];
    $age         = (int)$_POST['age'];
    $phone       = trim($_POST['phone']);
    $instVal     = $_POST['institution'] ?? '';

    $finalInstName = NULL;

    if (!empty($instVal)) {
        if ($instVal === '__OTHER__') {
            $customName  = trim($_POST['custom_inst_name']);
            $customPlace = $_POST['custom_inst_place'] ?? '';

            if (!empty($customName) && !empty($customPlace)) {
                list($instArea, $instCity) = explode('|', $customPlace);
                $finalInstName = $customName;

                // Check if institution already exists
                $checkInst = $conn->prepare("SELECT Name FROM instituition WHERE Name = ?");
                $checkInst->bind_param("s", $finalInstName);
                $checkInst->execute();
                $resInst = $checkInst->get_result();
                $checkInst->close();

                // Create new institution if missing
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

    // Password
    if (!empty($newPassword)) {
        $stmtUpdate = $conn->prepare("UPDATE user SET Name = ?, Password = ?, Gender = ?, Age = ?, Phone = ?, Instituition = ? WHERE ID = ?");
        $stmtUpdate->bind_param("sssissi", $name, $newPassword, $gender, $age, $phone, $finalInstName, $userId);
    } else {
        $stmtUpdate = $conn->prepare("UPDATE user SET Name = ?, Gender = ?, Age = ?, Phone = ?, Instituition = ? WHERE ID = ?");
        $stmtUpdate->bind_param("ssissi", $name, $gender, $age, $phone, $finalInstName, $userId);
    }

    if ($stmtUpdate->execute()) {
        $stmtUpdate->close();
        $conn->close();
        echo "<script>
                alert('Profile details updated successfully!');
                window.location.href = 'dashboard.php';
              </script>";
        exit();
    } else {
        $error = $stmtUpdate->error;
        $stmtUpdate->close();
        $conn->close();
        echo "Error updating profile: " . htmlspecialchars($error) . "<br><br><a href='edit_profile.php'>Try Again</a>";
    }
} else {
    header("Location: edit_profile.php");
    exit();
}
?>