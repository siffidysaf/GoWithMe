<?php
session_start();
require_once "DBConnect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch reference data for dropdowns
$institutions = $conn->query("SELECT Name, Area, City FROM instituition");
$transports   = $conn->query("SELECT Type FROM transport_mode");
$places       = $conn->query("SELECT Area, City FROM place");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Travel Request - GoWithMe</title>
  <script>
    function toggleCustomInstitution(selectObj) {
      const customDiv = document.getElementById('custom_institution_div');
      if (selectObj.value === '__OTHER__') {
        customDiv.style.display = 'block';
      } else {
        customDiv.style.display = 'none';
      }
    }

    function toggleGroupSize(selectObj) {
      const groupDiv = document.getElementById('group_size_div');
      const groupInput = document.getElementById('group_size_input');
      if (selectObj.value === 'Group') {
        groupDiv.style.display = 'block';
        groupInput.setAttribute('required', 'required');
      } else {
        groupDiv.style.display = 'none';
        groupInput.removeAttribute('required');
      }
    }
  </script>
</head>
<body>
  <h2>Create Travel Request</h2>
  <a href="dashboard.php">Back to Dashboard</a>
  <br><br>

  <form action="create_request_action.php" method="POST">
    <label>Travel Type:</label><br>
    <select name="solo_group" onchange="toggleGroupSize(this)" required>
      <option value="Solo">Solo</option>
      <option value="Group">Group</option>
    </select><br><br>

    <!-- Group Size (Revealed only when Group is selected) -->
    <div id="group_size_div" style="display: none;">
      <label>Number of People:</label><br>
      <input type="number" id="group_size_input" name="group_size" min="1" ><br><br>
    </div>

    <label>Travel Date:</label><br>
    <input type="date" name="date" required><br><br>

    <label>Travel Time:</label><br>
    <input type="time" name="time" required><br><br>

    <label>Min Preference Age:</label><br>
    <input type="number" name="min_age" min="16" required><br><br>

    <label>Max Preference Age:</label><br>
    <input type="number" name="max_age" min="16" required><br><br>

    <label>Transport Mode:</label><br>
    <select name="transport" required>
      <option value="">Select Transport Mode</option>
      <?php while($t = $transports->fetch_assoc()): ?>
        <option value="<?= htmlspecialchars($t['Type']) ?>"><?= htmlspecialchars($t['Type']) ?></option>
      <?php endwhile; ?>
    </select><br><br>

    <label>Pickup Location:</label><br>
    <select name="pickup" required>
      <option value="">Select Pickup Location</option>
      <?php while($p = $places->fetch_assoc()): ?>
        <option value="<?= htmlspecialchars($p['Area'].'|'.$p['City']) ?>"><?= htmlspecialchars($p['Area'].', '.$p['City']) ?></option>
      <?php endwhile; ?>
    </select><br><br>

    <label>Destination Location:</label><br>
    <select name="destination" required>
      <option value="">Select Destination Location</option>
      <?php 
      $places->data_seek(0);
      while($p = $places->fetch_assoc()): ?>
        <option value="<?= htmlspecialchars($p['Area'].'|'.$p['City']) ?>"><?= htmlspecialchars($p['Area'].', '.$p['City']) ?></option>
      <?php endwhile; ?>
    </select><br><br>

    <label>Preferred Institution (Optional):</label><br>
    <select name="preferred_institution" onchange="toggleCustomInstitution(this)">
      <option value="">Any Institution</option>
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
      <input type="text" name="custom_inst_name"><br><br>
      
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

    <button type="submit">Submit Request</button>
  </form>
</body>
</html>