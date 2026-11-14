<?php
session_start();
require_once "DBConnect.php";

$institutions = $conn->query("SELECT Name, Area, City FROM instituition");
$places       = $conn->query("SELECT Area, City FROM place");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - GoWithMe</title>
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
  <h2>Create a New Account</h2>

  <form action="register_action.php" method="POST">
    <label>Full Name:</label><br>
    <input type="text" name="name" required><br><br>

    <label>Phone Number:</label><br>
    <input type="text" name="phone" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <label>Gender:</label><br>
    <select name="gender" required>
      <option value="">Select Gender</option>
      <option value="Male">Male</option>
      <option value="Female">Female</option>
      <option value="Prefer not to say">Prefer not to say</option>
    </select><br><br>

    <label>Age:</label><br>
    <input type="number" name="age" min="16" required><br><br>

    <label>Institution (Optional):</label><br>
    <select name="institution" onchange="toggleCustomInstitution(this)">
      <option value="">Select Later</option>
      <?php while($inst = $institutions->fetch_assoc()): ?>
        <option value="<?= htmlspecialchars($inst['Name']) ?>">
          <?= htmlspecialchars($inst['Name'] . ' (' . $inst['Area'] . ', ' . $inst['City'] . ')') ?>
        </option>
      <?php endwhile; ?>
      <option value="__OTHER__">Write your own...</option>
    </select><br><br>

    <!-- Custom Institution Input Fields -->
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

    <button type="submit">Register Account</button>
  </form>

  <br><hr><br>

  <p>Already have an account?</p>
  <a href="login.php"><button type="button">Back to Login</button></a>
</body>
</html>