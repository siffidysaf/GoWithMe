<?php
session_start();
require_once "DBConnect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['req_id'])) {
    $userId = (int)$_SESSION['user_id'];
    $reqId  = (int)$_POST['req_id'];

    // Check request state
    $stmtReq = $conn->prepare("SELECT `Solo or Group`, group_size, Status FROM requests WHERE ID = ?");
    $stmtReq->bind_param("i", $reqId);
    $stmtReq->execute();
    $request = $stmtReq->get_result()->fetch_assoc();
    $stmtReq->close();

    if (!$request || $request['Status'] !== 'Searching') {
        echo "<script>alert('This request is no longer available.'); window.location.href='search.php';</script>";
        exit();
    }

    // Check if user already accepted
    $stmtCheckAcc = $conn->prepare("SELECT * FROM accepts WHERE User_ID = ? AND Req_ID = ?");
    $stmtCheckAcc->bind_param("ii", $userId, $reqId);
    $stmtCheckAcc->execute();
    if ($stmtCheckAcc->get_result()->num_rows > 0) {
        echo "<script>alert('You have already accepted this request.'); window.location.href='search.php';</script>";
        exit();
    }
    $stmtCheckAcc->close();

    $conn->begin_transaction();

    try {
        // Set as Acceptance
        $stmtInsertAcc = $conn->prepare("INSERT INTO accepts (User_ID, Req_ID) VALUES (?, ?)");
        $stmtInsertAcc->bind_param("ii", $userId, $reqId);
        $stmtInsertAcc->execute();
        $stmtInsertAcc->close();

        // Status update
        if ($request['Solo or Group'] === 'Solo') {
            $stmtUpdate = $conn->prepare("UPDATE requests SET Status = 'Found' WHERE ID = ?");
            $stmtUpdate->bind_param("i", $reqId);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        } else {
            // Count total acceptances for group post
            $stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM accepts WHERE Req_ID = ?");
            $stmtCount->bind_param("i", $reqId);
            $stmtCount->execute();
            $acceptedCount = $stmtCount->get_result()->fetch_assoc()['total'];
            $stmtCount->close();

            if ($acceptedCount >= (int)$request['group_size']) {
                $stmtUpdate = $conn->prepare("UPDATE requests SET Status = 'Found' WHERE ID = ?");
                $stmtUpdate->bind_param("i", $reqId);
                $stmtUpdate->execute();
                $stmtUpdate->close();
            }
        }

        $conn->commit();
        echo "<script>alert('Request accepted successfully!'); window.location.href='request_history.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Failed to accept request: " . $e->getMessage();
    }
}
?>