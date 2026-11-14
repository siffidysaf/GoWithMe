<?php
session_start();
require_once "DBConnect.php";

if (!isset($_SESSION['user_id']) || !isset($_POST['favored_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$favored_id = (int)$_POST['favored_id'];

// Prevent users from favoriting themselves
if ($user_id === $favored_id) {
    $referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
    header("Location: $referer");
    exit();
}

// Check if the favorite relationship already exists
$checkStmt = $conn->prepare("SELECT * FROM favorites WHERE User_ID = ? AND Favored_ID = ?");
$checkStmt->bind_param("ii", $user_id, $favored_id);
$checkStmt->execute();
$result = $checkStmt->get_result();
$checkStmt->close();

if ($result->num_rows > 0) {
    // Remove from favorites
    $stmt = $conn->prepare("DELETE FROM favorites WHERE User_ID = ? AND Favored_ID = ?");
} else {
    // Add to favorites
    $stmt = $conn->prepare("INSERT INTO favorites (User_ID, Favored_ID) VALUES (?, ?)");
}

$stmt->bind_param("ii", $user_id, $favored_id);
$stmt->execute();
$stmt->close();
$conn->close();

$referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
header("Location: $referer");
exit();
?>