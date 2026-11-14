<?php
session_start();
require_once "DBConnect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userId        = (int)$_SESSION['user_id'];
    $soloGroup     = $_POST['solo_group'];
    $groupSize     = ($soloGroup === 'Group') ? (int)$_POST['group_size'] : NULL;
    $date          = $_POST['date'];
    $time          = $_POST['time'];
    $minAge        = (int)$_POST['min_age'];
    $maxAge        = (int)$_POST['max_age'];
    $transport     = $_POST['transport'];
    $prefInstVal   = $_POST['preferred_institution'] ?? '';

    list($pickupArea, $pickupCity) = explode('|', $_POST['pickup']);
    list($destArea, $destCity)     = explode('|', $_POST['destination']);

    $finalPrefInstName = NULL;

    // Handle Custom Institution
    if (!empty($prefInstVal)) {
        if ($prefInstVal === '__OTHER__') {
            $customName  = trim($_POST['custom_inst_name']);
            $customPlace = $_POST['custom_inst_place'] ?? '';

            if (!empty($customName) && !empty($customPlace)) {
                list($instArea, $instCity) = explode('|', $customPlace);
                $finalPrefInstName = $customName;

                // Check if institution already exists
                $checkInst = $conn->prepare("SELECT Name FROM instituition WHERE Name = ?");
                $checkInst->bind_param("s", $finalPrefInstName);
                $checkInst->execute();
                $resInst = $checkInst->get_result();
                $checkInst->close();

                if ($resInst->num_rows === 0) {
                    $stmtNewInst = $conn->prepare("INSERT INTO instituition (Name, Area, City) VALUES (?, ?, ?)");
                    $stmtNewInst->bind_param("sss", $finalPrefInstName, $instArea, $instCity);
                    $stmtNewInst->execute();
                    $stmtNewInst->close();
                }
            }
        } else {
            $finalPrefInstName = $prefInstVal;
        }
    }

    $conn->begin_transaction();

    try {
        // 1. Insert into requests table
        $stmt = $conn->prepare("INSERT INTO requests (Date, Time, min_age, max_age, `Solo or Group`, group_size, Status) VALUES (?, ?, ?, ?, ?, ?, 'Searching')");
        $stmt->bind_param("ssiisi", $date, $time, $minAge, $maxAge, $soloGroup, $groupSize);
        $stmt->execute();
        $reqId = $conn->insert_id;
        $stmt->close();

        // 2. Insert into posts table 
        $stmtPost = $conn->prepare("INSERT INTO posts (User_ID, Req_ID) VALUES (?, ?)");
        $stmtPost->bind_param("ii", $userId, $reqId);
        $stmtPost->execute();
        $stmtPost->close();

        // 3. Insert meetup into meetup table
        $stmtPickup = $conn->prepare("INSERT INTO meetup (R_ID, Area, City) VALUES (?, ?, ?)");
        $stmtPickup->bind_param("iss", $reqId, $pickupArea, $pickupCity);
        $stmtPickup->execute();
        $stmtPickup->close();

        // 4. Insert Destination into destination table
        $stmtDest = $conn->prepare("INSERT INTO destination (R_ID, Area, City) VALUES (?, ?, ?)");
        $stmtDest->bind_param("iss", $reqId, $destArea, $destCity);
        $stmtDest->execute();
        $stmtDest->close();

        // 5. Insert Transport into go_by table
        $stmtGoBy = $conn->prepare("INSERT INTO go_by (R_ID, Type) VALUES (?, ?)");
        $stmtGoBy->bind_param("is", $reqId, $transport);
        $stmtGoBy->execute();
        $stmtGoBy->close();

        // 6. Insert Preferred Target Institution into prefers table
        if (!empty($finalPrefInstName)) {
            $stmtPref = $conn->prepare("INSERT INTO prefers (R_ID, I_Name) VALUES (?, ?)");
            $stmtPref->bind_param("is", $reqId, $finalPrefInstName);
            $stmtPref->execute();
            $stmtPref->close();
        }

        $conn->commit();
        echo "<script>alert('Travel Request Created Successfully!'); window.location.href='dashboard.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error creating request: " . $e->getMessage();
    }
}
?>