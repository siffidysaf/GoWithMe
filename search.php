<?php
session_start();
require_once "DBConnect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

// Fetch data for search
$institutions = $conn->query("SELECT Name, Area, City FROM instituition");
$transports   = $conn->query("SELECT Type FROM transport_mode");
$places       = $conn->query("SELECT Area, City FROM place");

// Get the search filters
$gender      = $_GET['gender'] ?? '';
$min_age     = $_GET['min_age'] ?? '';
$max_age     = $_GET['max_age'] ?? '';
$transport   = $_GET['transport'] ?? '';
$institution = $_GET['institution'] ?? '';
$pickup      = $_GET['pickup'] ?? '';
$destination = $_GET['destination'] ?? '';
$solo_group  = $_GET['solo_group'] ?? '';

// Select search datas
$sql = "SELECT DISTINCT
            r.ID AS Request_ID,
            r.Date,
            r.Time,
            r.min_age,
            r.max_age,
            r.`Solo or Group` AS Travel_Type,
            r.group_size,
            (SELECT COUNT(*) FROM accepts acc WHERE acc.Req_ID = r.ID) AS accepted_count,
            u.ID AS Poster_ID,
            u.Name AS Poster_Name,
            u.Gender AS Poster_Gender,
            u.Age AS Poster_Age,
            u.Phone AS Poster_Phone,
            u.Instituition AS Poster_Inst_Name,
            p.I_Name AS Preferred_Inst_Name,
            pref_inst.Area AS Preferred_Inst_Area,
            pref_inst.City AS Preferred_Inst_City,
            user_inst.Area AS Poster_Inst_Area,
            user_inst.City AS Poster_Inst_City,
            m.Area AS Pickup_Area,
            m.City AS Pickup_City,
            d.Area AS Dest_Area,
            d.City AS Dest_City,
            g.Type AS Transport_Type
        FROM requests r
        JOIN posts po ON r.ID = po.Req_ID
        JOIN user u ON po.User_ID = u.ID
        LEFT JOIN instituition user_inst ON u.Instituition = user_inst.Name
        LEFT JOIN prefers p ON r.ID = p.R_ID
        LEFT JOIN instituition pref_inst ON p.I_Name = pref_inst.Name
        JOIN meetup m ON r.ID = m.R_ID
        JOIN destination d ON r.ID = d.R_ID
        JOIN go_by g ON r.ID = g.R_ID
        WHERE r.Status = 'Searching'";

$params = [];
$types  = "";

if (!empty($gender)) {
    $sql .= " AND u.Gender = ?";
    $params[] = $gender;
    $types .= "s";
}

if (!empty($min_age)) {
    $sql .= " AND r.min_age >= ?";
    $params[] = (int)$min_age;
    $types .= "i";
}

if (!empty($max_age)) {
    $sql .= " AND r.max_age <= ?";
    $params[] = (int)$max_age;
    $types .= "i";
}

if (!empty($solo_group)) {
    $sql .= " AND r.`Solo or Group` = ?";
    $params[] = $solo_group;
    $types .= "s";
}

if (!empty($transport)) {
    $sql .= " AND g.Type = ?";
    $params[] = $transport;
    $types .= "s";
}

if (!empty($institution)) {
    $sql .= " AND (p.I_Name = ? OR u.Instituition = ?)";
    $params[] = $institution;
    $params[] = $institution;
    $types .= "ss";
}

if (!empty($pickup)) {
    list($pickupArea, $pickupCity) = explode('|', $pickup);
    $sql .= " AND m.Area = ? AND m.City = ?";
    $params[] = $pickupArea;
    $params[] = $pickupCity;
    $types .= "ss";
}

if (!empty($destination)) {
    list($destArea, $destCity) = explode('|', $destination);
    $sql .= " AND d.Area = ? AND d.City = ?";
    $params[] = $destArea;
    $params[] = $destCity;
    $types .= "ss";
}

$sql .= " ORDER BY r.Date ASC, r.Time ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$results = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Search & Accept Travel Requests - GoWithMe</title>
</head>
<body>
  <h2>Search & Accept Travel Requests</h2>
  <a href="dashboard.php">Back to Dashboard</a>
  <br><br>

  <form method="GET" action="search.php">
    <table border="0" cellpadding="5">
      <tr>
        <td><label>Gender:</label></td>
        <td>
          <select name="gender">
            <option value="">All Genders</option>
            <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Prefer not to say" <?= $gender === 'Prefer not to say' ? 'selected' : '' ?>>Prefer not to say</option>
          </select>
        </td>

        <td><label>Transport Mode:</label></td>
        <td>
          <select name="transport">
            <option value="">All Transport Modes</option>
            <?php while($t = $transports->fetch_assoc()): ?>
              <option value="<?= htmlspecialchars($t['Type']) ?>" <?= $transport === $t['Type'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($t['Type']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </td>
      </tr>

      <tr>
        <td><label>Min Request Age:</label></td>
        <td><input type="number" name="min_age" value="<?= htmlspecialchars($min_age) ?>" min="10" ></td>

        <td><label>Max Request Age:</label></td>
        <td><input type="number" name="max_age" value="<?= htmlspecialchars($max_age) ?>" min="10" ></td>
      </tr>

      <tr>
        <td><label>Travel Type:</label></td>
        <td>
          <select name="solo_group">
            <option value="">All Types</option>
            <option value="Solo" <?= $solo_group === 'Solo' ? 'selected' : '' ?>>Solo</option>
            <option value="Group" <?= $solo_group === 'Group' ? 'selected' : '' ?>>Group</option>
          </select>
        </td>

        <td><label>Poster Institution:</label></td>
        <td>
          <select name="institution">
            <option value="">All Institutions</option>
            <?php while($inst = $institutions->fetch_assoc()): ?>
              <option value="<?= htmlspecialchars($inst['Name']) ?>" <?= $institution === $inst['Name'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($inst['Name'] . ' (' . $inst['Area'] . ', ' . $inst['City'] . ')') ?>
              </option>
            <?php endwhile; ?>
          </select>
        </td>
      </tr>

      <tr>
        <td><label>Pickup Location:</label></td>
        <td>
          <select name="pickup">
            <option value="">All Pickup Locations</option>
            <?php 
            $places->data_seek(0);
            while($p = $places->fetch_assoc()): 
              $val = $p['Area'].'|'.$p['City'];
            ?>
              <option value="<?= htmlspecialchars($val) ?>" <?= $pickup === $val ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['Area'].', '.$p['City']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </td>

        <td><label>Destination:</label></td>
        <td>
          <select name="destination">
            <option value="">All Destinations</option>
            <?php 
            $places->data_seek(0);
            while($p = $places->fetch_assoc()): 
              $val = $p['Area'].'|'.$p['City'];
            ?>
              <option value="<?= htmlspecialchars($val) ?>" <?= $destination === $val ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['Area'].', '.$p['City']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </td>
      </tr>

      <tr>
        <td colspan="4">
          <br>
          <button type="submit">Filter Requests</button>
          <a href="search.php"><button type="button">Reset Filters</button></a>
        </td>
      </tr>
    </table>
  </form>

  <hr>

  <h3>Available Requests (Status: Searching)</h3>
  <table border="1" cellpadding="8" cellspacing="0">
    <thead>
      <tr>
        <th>Req ID</th>
        <th>Poster Name</th>
        <th>Type</th>
        <th>Gender</th>
        <th>Age Range</th>
        <th>Institution</th>
        <th>Transport</th>
        <th>Pickup</th>
        <th>Destination</th>
        <th>Travel Time</th>
        <th>Action</th>
        <th>Favorite</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($results && $results->num_rows > 0): ?>
        <?php while($row = $results->fetch_assoc()): ?>
          <tr>
            <td><?= $row['Request_ID'] ?></td>
            <td><?= htmlspecialchars($row['Poster_Name']) ?></td>
            <td>
              <?= htmlspecialchars($row['Travel_Type']) ?>
              <?php if ($row['Travel_Type'] === 'Group'): ?>
                <br><small>(<?= $row['accepted_count'] ?>/<?= $row['group_size'] ?> joined)</small>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($row['Poster_Gender']) ?></td>
            <td><?= htmlspecialchars($row['min_age']) ?> - <?= htmlspecialchars($row['max_age']) ?> yrs</td>
            <td>
              <?php 
                if (!empty($row['Preferred_Inst_Name'])) {
                    $area = !empty($row['Preferred_Inst_Area']) ? $row['Preferred_Inst_Area'] : 'N/A';
                    $city = !empty($row['Preferred_Inst_City']) ? $row['Preferred_Inst_City'] : 'N/A';
                    echo htmlspecialchars($row['Preferred_Inst_Name'] . ' (' . $area . ', ' . $city . ')');
                } elseif (!empty($row['Poster_Inst_Name'])) {
                    $area = !empty($row['Poster_Inst_Area']) ? $row['Poster_Inst_Area'] : 'N/A';
                    $city = !empty($row['Poster_Inst_City']) ? $row['Poster_Inst_City'] : 'N/A';
                    echo htmlspecialchars($row['Poster_Inst_Name'] . ' (' . $area . ', ' . $city . ')');
                } else {
                    echo 'Any / N/A';
                }
              ?>
            </td>
            <td><?= htmlspecialchars($row['Transport_Type']) ?></td>
            <td><?= htmlspecialchars($row['Pickup_Area'].', '.$row['Pickup_City']) ?></td>
            <td><?= htmlspecialchars($row['Dest_Area'].', '.$row['Dest_City']) ?></td>
            <td><?= htmlspecialchars($row['Date'].' at '.$row['Time']) ?></td>
            <td>
              <?php if ((int)$row['Poster_ID'] === $current_user_id): ?>
                <em>(Your Post)</em>
              <?php else: ?>
                <form action="accept_request.php" method="POST" style="margin:0;">
                  <input type="hidden" name="req_id" value="<?= $row['Request_ID'] ?>">
                  <button type="submit" onclick="return confirm('Accept this travel request?');">
                    Accept Request
                  </button>
                </form>
              <?php endif; ?>
            </td>
            <td>
              <?php if ((int)$row['Poster_ID'] !== $current_user_id): ?>
                <form action="toggle_favorite.php" method="POST" style="margin:0;">
                  <input type="hidden" name="favored_id" value="<?= $row['Poster_ID'] ?>">
                  <button type="submit">Favorite User</button>
                </form>
              <?php else: ?>
                <em>N/A</em>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="12">No active searching requests match your criteria.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>