<?php
session_start();
require_once "DBConnect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Fetch favorited users
$stmt = $conn->prepare("
    SELECT u.ID, u.Name, u.Phone, u.Gender, u.Age, u.Instituition 
    FROM favorites f
    JOIN user u ON f.Favored_ID = u.ID
    WHERE f.User_ID = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$favorites = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Favorites - GoWithMe</title>
</head>
<body>
  <h2>My Favorite Travel Buddies</h2>
  <a href="dashboard.php">Back to Dashboard</a>
  <br><br>

  <table border="1" cellpadding="8" cellspacing="0">
    <thead>
      <tr>
        <th>Name</th>
        <th>Gender</th>
        <th>Age</th>
        <th>Institution</th>
        <th>Phone</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($favorites && $favorites->num_rows > 0): ?>
        <?php while($f = $favorites->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($f['Name']) ?></td>
            <td><?= htmlspecialchars($f['Gender']) ?></td>
            <td><?= htmlspecialchars($f['Age']) ?></td>
            <td><?= htmlspecialchars($f['Instituition'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($f['Phone']) ?></td>
            <td>
              <form action="toggle_favorite.php" method="POST" style="margin:0;">
                <input type="hidden" name="favored_id" value="<?= $f['ID'] ?>">
                <button type="submit">Remove</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="6">You haven't added any favorites yet.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>