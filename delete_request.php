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

    $stmtCheck = $conn->prepare("SELECT Req_ID FROM posts WHERE User_ID = ? AND Req_ID = ?");
    $stmtCheck->bind_param("ii", $userId, $reqId);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();
    $stmtCheck->close();

    if ($result->num_rows > 0) {
        $conn->begin_transaction();

        try {
            $stmt1 = $conn->prepare("DELETE FROM posts WHERE Req_ID = ?");
            $stmt1->bind_param("i", $reqId);
            $stmt1->execute();
            $stmt1->close();

            $stmt2 = $conn->prepare("DELETE FROM meetup WHERE R_ID = ?");
            $stmt2->bind_param("i", $reqId);
            $stmt2->execute();
            $stmt2->close();

            $stmt3 = $conn->prepare("DELETE FROM destination WHERE R_ID = ?");
            $stmt3->bind_param("i", $reqId);
            $stmt3->execute();
            $stmt3->close();

            $stmt4 = $conn->prepare("DELETE FROM go_by WHERE R_ID = ?");
            $stmt4->bind_param("i", $reqId);
            $stmt4->execute();
            $stmt4->close();

            $stmt5 = $conn->prepare("DELETE FROM prefers WHERE R_ID = ?");
            $stmt5->bind_param("i", $reqId);
            $stmt5->execute();
            $stmt5->close();

            $stmt6 = $conn->prepare("DELETE FROM accepts WHERE Req_ID = ?");
            $stmt6->bind_param("i", $reqId);
            $stmt6->execute();
            $stmt6->close();

            $stmtReq = $conn->prepare("DELETE FROM requests WHERE ID = ?");
            $stmtReq->bind_param("i", $reqId);
            $stmtReq->execute();
            $stmtReq->close();

            $conn->commit();

            echo "<script>
                    alert('Travel request deleted successfully.');
                    window.location.href = 'dashboard.php';
                  </script>";
        } catch (Exception $e) {
            $conn->rollback();
            echo "Failed to delete request: " . $e->getMessage() . "<br><br><a href='dashboard.php'>Back to Dashboard</a>";
        }
    } else {
        echo "Unauthorized action or request not found.<br><br><a href='dashboard.php'>Back to Dashboard</a>";
    }

    $conn->close();
} else {
    header("Location: dashboard.php");
    exit();
}
?>