<?php
session_start();
require_once "DBConnect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Fetch logged-in user details
$stmtUser = $conn->prepare("SELECT Name, Gender, Age, Phone, Instituition FROM user WHERE ID = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$userData = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

// Fetch reference data for institutions and places
$institutions = $conn->query("SELECT Name, Area, City FROM instituition");
$places       = $conn->query("SELECT Area, City FROM place");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile - GoWithMe</title>
  <script>
    function toggleCustomInstitution(selectObj) {
      const customDiv = document.getElementById('custom_institution_div');
      if (selectObj.value === '__OTHER__') {
        customDiv.style.display = 'block';
      } else {
        customDiv.style.display = 'none';
      }
    }
  </script>
</head>
<body>
  <h2>Edit Profile Details</h2>
  <a href="dashboard.php">Back to Dashboard</a>
  <br><br>

  <form action="edit_profile_action.php" method="POST">
    <label>Full Name:</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars($userData['Name']) ?>" required><br><br>

    <label>New Password :</label><br>
    <input type="password" name="new_password" ><br><br>

    <label>Gender:</label><br>
    <select name="gender" required>
      <option value="Male" <?= $userData['Gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
      <option value="Female" <?= $userData['Gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
      <option value="Prefer not to say" <?= $userData['Gender'] === 'Prefer not to say' ? 'selected' : '' ?>>Prefer not to say</option>
    </select><br><br>

    <label>Age:</label><br>
    <input type="number" name="age" value="<?= htmlspecialchars($userData['Age']) ?>" min="16" required><br><br>

    <label>Phone Number:</label><br>
    <input type="text" name="phone" value="<?= htmlspecialchars($userData['Phone']) ?>" required><br><br>

    <label>Institution:</label><br>
    <select name="institution" onchange="toggleCustomInstitution(this)">
      <option value="">None / Not Applicable</option>
      <?php while($inst = $institutions->fetch_assoc()): ?>
        <option value="<?= htmlspecialchars($inst['Name']) ?>" <?= $userData['Instituition'] === $inst['Name'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($inst['Name'] . ' (' . $inst['Area'] . ', ' . $inst['City'] . ')') ?>
        </option>
      <?php endwhile; ?>
      <option value="__OTHER__">Write your own...</option>
    </select><br><br>

    <!-- Custom Institution Form Fields -->
    <div id="custom_institution_div" style="display: none;">
      <label>Custom Institution Name:</label><br>
      <input type="text" name="custom_inst_name" placeholder="e.g. Independent University"><br><br>
      
      <label>Institution Location (Area & City):</label><br>
      <select name="custom_inst_place">
        <option value="">Select Location</option>
        <?php 
        $places->data_seek(0);
        while($p = $places->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($p['Area'].'|'.$p['City']) ?>">
            <?= htmlspecialchars($p['Area'].', '.$p['City']) ?>
          </option>
        <?php endwhile; ?>
      </select><br><br>
    </div>

    <button type="submit">Save Changes</button>
  </form>
</body>
</html>