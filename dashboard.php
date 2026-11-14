<?php
session_start();
require_once "DBConnect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// 1. Fetch user info 
$stmtUser = $conn->prepare("
    SELECT u.ID, u.Name, u.Gender, u.Age, u.Phone, u.Instituition, inst.Area AS Inst_Area, inst.City AS Inst_City 
    FROM user u
    LEFT JOIN instituition inst ON u.Instituition = inst.Name
    WHERE u.ID = ?
");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$userInfo = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

// 2. Fetch active ride requests
$stmtMyReqs = $conn->prepare("
    SELECT r.ID AS Request_ID, r.Date, r.Time, r.Status, r.`Solo or Group` AS Travel_Type,
           m.Area AS Pickup_Area, m.City AS Pickup_City,
           d.Area AS Dest_Area, d.City AS Dest_City,
           g.Type AS Transport_Type
    FROM requests r
    JOIN posts po ON r.ID = po.Req_ID
    JOIN meetup m ON r.ID = m.R_ID
    JOIN destination d ON r.ID = d.R_ID
    JOIN go_by g ON r.ID = g.R_ID
    WHERE po.User_ID = ?
    ORDER BY r.Date DESC, r.Time DESC
");
$stmtMyReqs->bind_param("i", $user_id);
$stmtMyReqs->execute();
$myRequestsRes = $stmtMyReqs->get_result();
$myRequests = [];
if ($myRequestsRes) {
    while ($row = $myRequestsRes->fetch_assoc()) {
        $myRequests[] = $row;
    }
}
$stmtMyReqs->close();

// 3. Fetch favorited users
$stmtFav = $conn->prepare("
    SELECT u.ID, u.Name, u.Phone, u.Gender, u.Age, u.Instituition 
    FROM favorites f
    JOIN user u ON f.Favored_ID = u.ID
    WHERE f.User_ID = ?
");
$stmtFav->bind_param("i", $user_id);
$stmtFav->execute();
$myFavoritesRes = $stmtFav->get_result();
$myFavorites = [];
if ($myFavoritesRes) {
    while ($row = $myFavoritesRes->fetch_assoc()) {
        $myFavorites[] = $row;
    }
}
$stmtFav->close();

$userName = $userInfo['Name'] ?? 'User';
$userInitial = strtoupper(substr($userName, 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - GoWithMe</title>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <style>
    :root {
      --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
      --card-bg: rgba(30, 41, 59, 0.75);
      --card-border: rgba(255, 255, 255, 0.1);
      --primary: #6366f1;
      --primary-hover: #4f46e5;
      --accent: #ec4899;
      --danger: #ef4444;
      --danger-hover: #dc2626;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --badge-searching-bg: rgba(245, 158, 11, 0.15);
      --badge-searching-color: #fbbf24;
      --badge-searching-border: rgba(245, 158, 11, 0.3);
      --badge-found-bg: rgba(16, 185, 129, 0.15);
      --badge-found-color: #34d399;
      --badge-found-border: rgba(16, 185, 129, 0.3);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    body {
      background: var(--bg-gradient);
      min-height: 100vh;
      color: var(--text-main);
      display: flex;
      flex-direction: column;
    }

    /* Top Navbar */
    .navbar {
      height: 64px;
      background: rgba(15, 23, 42, 0.85);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--card-border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 24px;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 700;
      font-size: 1.25rem;
      color: #fff;
      text-decoration: none;
    }

    .brand-logo {
      width: 36px;
      height: 36px;
      background: linear-gradient(135deg, #6366f1, #ec4899);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 1.1rem;
      box-shadow: 0 0 15px rgba(99, 102, 241, 0.4);
    }

    .nav-actions {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .user-badge {
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, 0.05);
      padding: 6px 14px;
      border-radius: 20px;
      border: 1px solid var(--card-border);
      font-size: 0.875rem;
    }

    .user-avatar-mini {
      width: 26px;
      height: 26px;
      background: #475569;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 0.75rem;
      color: #fff;
    }

    .btn-nav {
      color: var(--text-muted);
      text-decoration: none;
      font-size: 0.875rem;
      font-weight: 500;
      padding: 6px 12px;
      border-radius: 8px;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .btn-nav:hover, .btn-nav.active {
      color: #fff;
      background: rgba(255, 255, 255, 0.08);
    }

    .btn-nav-primary {
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      color: #fff !important;
      font-weight: 600;
      box-shadow: 0 2px 10px rgba(99, 102, 241, 0.3);
    }

    .btn-nav-primary:hover {
      box-shadow: 0 4px 15px rgba(99, 102, 241, 0.5);
    }

    /* Container */
    .container {
      max-width: 1200px;
      width: 100%;
      margin: 24px auto;
      padding: 0 20px;
      flex: 1;
    }

    /* Welcome Header */
    .hero-banner {
      background: var(--card-bg);
      backdrop-filter: blur(12px);
      border: 1px solid var(--card-border);
      border-radius: 20px;
      padding: 28px;
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 20px;
    }

    .hero-text h1 {
      font-size: 1.8rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      margin-bottom: 6px;
    }

    .hero-text p {
      color: var(--text-muted);
      font-size: 0.95rem;
    }

    .hero-actions {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .quick-btn {
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid var(--card-border);
      color: #fff;
      text-decoration: none;
      padding: 10px 16px;
      border-radius: 12px;
      font-size: 0.875rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s;
    }

    .quick-btn:hover {
      background: rgba(255, 255, 255, 0.12);
      border-color: rgba(255, 255, 255, 0.2);
      transform: translateY(-2px);
    }

    .quick-btn.chat-btn {
      background: rgba(99, 102, 241, 0.15);
      border-color: rgba(99, 102, 241, 0.3);
      color: #a5b4fc;
    }

    .quick-btn.chat-btn:hover {
      background: var(--primary);
      color: #fff;
      box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
    }

    .quick-btn.block-btn {
      background: rgba(239, 68, 68, 0.15);
      border-color: rgba(239, 68, 68, 0.3);
      color: #f87171;
    }

    .quick-btn.block-btn:hover {
      background: var(--danger);
      color: #fff;
      box-shadow: 0 4px 14px rgba(239, 68, 68, 0.4);
    }

    /* Grid Layout */
    .dashboard-grid {
      display: grid;
      grid-template-columns: 340px 1fr;
      gap: 24px;
      margin-bottom: 24px;
    }

    /* Profile Card */
    .panel-card {
      background: var(--card-bg);
      backdrop-filter: blur(12px);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      padding: 24px;
      margin-bottom: 24px;
    }

    .panel-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 1px solid var(--card-border);
    }

    .panel-title {
      font-size: 1.1rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .badge-count {
      background: rgba(99, 102, 241, 0.2);
      color: #a5b4fc;
      border: 1px solid rgba(99, 102, 241, 0.3);
      font-size: 0.75rem;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 12px;
    }

    .profile-avatar-large {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      background: linear-gradient(135deg, #6366f1, #ec4899);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 1.8rem;
      color: #fff;
      margin: 0 auto 16px auto;
      box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
    }

    .profile-name-title {
      text-align: center;
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: 4px;
    }

    .profile-id-sub {
      text-align: center;
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-bottom: 20px;
    }

    .info-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 20px;
    }

    .info-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 12px;
      background: rgba(15, 23, 42, 0.5);
      border-radius: 10px;
      font-size: 0.875rem;
    }

    .info-label {
      color: var(--text-muted);
      font-weight: 500;
    }

    .info-val {
      font-weight: 600;
      color: #fff;
    }

    .btn-edit-profile {
      width: 100%;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid var(--card-border);
      color: #fff;
      text-decoration: none;
      padding: 10px;
      border-radius: 10px;
      font-size: 0.875rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.2s;
    }

    .btn-edit-profile:hover {
      background: rgba(255, 255, 255, 0.15);
      border-color: rgba(255, 255, 255, 0.25);
    }

    /* Modern Table Container */
    .table-responsive {
      overflow-x: auto;
    }

    .modern-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.9rem;
    }

    .modern-table th, .modern-table td {
      padding: 14px 16px;
      border-bottom: 1px solid var(--card-border);
    }

    .modern-table th {
      background: rgba(15, 23, 42, 0.6);
      color: var(--text-muted);
      font-weight: 600;
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .modern-table tr:hover td {
      background: rgba(255, 255, 255, 0.02);
    }

    .status-badge {
      font-size: 0.78rem;
      font-weight: 600;
      padding: 4px 10px;
      border-radius: 20px;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .status-badge.Searching {
      background: var(--badge-searching-bg);
      color: var(--badge-searching-color);
      border: 1px solid var(--badge-searching-border);
    }

    .status-badge.Found {
      background: var(--badge-found-bg);
      color: var(--badge-found-color);
      border: 1px solid var(--badge-found-border);
    }

    .tag-pill {
      font-size: 0.75rem;
      background: rgba(99, 102, 241, 0.15);
      color: #a5b4fc;
      padding: 3px 8px;
      border-radius: 6px;
      border: 1px solid rgba(99, 102, 241, 0.25);
    }

    /* Action Buttons in Tables */
    .btn-action-group {
      display: flex;
      align-items: center;
      gap: 6px;
      flex-wrap: wrap;
    }

    .btn-sm {
      padding: 6px 12px;
      border-radius: 8px;
      font-size: 0.8rem;
      font-weight: 600;
      text-decoration: none;
      cursor: pointer;
      border: none;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: all 0.2s;
    }

    .btn-sm-danger {
      background: rgba(239, 68, 68, 0.15);
      color: #f87171;
      border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .btn-sm-danger:hover {
      background: var(--danger);
      color: #fff;
    }

    .btn-sm-chat {
      background: rgba(99, 102, 241, 0.15);
      color: #a5b4fc;
      border: 1px solid rgba(99, 102, 241, 0.3);
    }

    .btn-sm-chat:hover {
      background: var(--primary);
      color: #fff;
    }

    .btn-sm-block {
      background: rgba(245, 158, 11, 0.15);
      color: #fbbf24;
      border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .btn-sm-block:hover {
      background: #d97706;
      color: #fff;
    }

    .btn-sm-secondary {
      background: rgba(255, 255, 255, 0.08);
      color: #cbd5e1;
      border: 1px solid var(--card-border);
    }

    .btn-sm-secondary:hover {
      background: rgba(255, 255, 255, 0.18);
      color: #fff;
    }

    .empty-state {
      padding: 32px 16px;
      text-align: center;
      color: var(--text-muted);
      font-size: 0.9rem;
    }

    .empty-state a {
      color: #a5b4fc;
      text-decoration: underline;
    }

    @media (max-width: 900px) {
      .dashboard-grid {
        grid-template-columns: 1fr;
      }
      .navbar {
        height: auto;
        padding: 12px 16px;
        flex-direction: column;
        gap: 12px;
      }
      .nav-actions {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>
<body>

  <!-- Top Navbar -->
  <header class="navbar">
    <a href="dashboard.php" class="brand">
      <div class="brand-logo">G</div>
      <span>GoWithMe</span>
    </a>
    
    <div class="nav-actions">
      <div class="user-badge">
        <div class="user-avatar-mini">
          <?= htmlspecialchars($userInitial) ?>
        </div>
        <span><?= htmlspecialchars($userName) ?></span>
      </div>
      <a href="create_request.php" class="btn-nav btn-nav-primary">➕ Create Request</a>
      <a href="search.php" class="btn-nav">🔍 Search</a>
      <a href="chat.php" class="btn-nav">💬 Chat</a>
      <a href="block_users.php" class="btn-nav">🚫 Block Users</a>
      <a href="request_history.php" class="btn-nav">📜 History</a>
      <a href="logout.php" class="btn-nav">Logout</a>
    </div>
  </header>

  <!-- Main Container -->
  <main class="container">

    <!-- Hero Banner & Quick Actions -->
    <section class="hero-banner">
      <div class="hero-text">
        <h1>Welcome back, <?= htmlspecialchars($userName) ?>! 👋</h1>
        <p>Manage your ride requests, connect with travel buddies, or message companions safely.</p>
      </div>
      <div class="hero-actions">
        <a href="chat.php" class="quick-btn chat-btn">
          <span>💬 Start Chat</span>
        </a>
        <a href="block_users.php" class="quick-btn block-btn">
          <span>🚫 Block User</span>
        </a>
        <a href="create_request.php" class="quick-btn">
          <span>➕ Create Request</span>
        </a>
        <a href="search.php" class="quick-btn">
          <span>🔍 Find Rides</span>
        </a>
      </div>
    </section>

    <!-- Dashboard Main Grid -->
    <div class="dashboard-grid">

      <!-- Left Column: User Profile Panel -->
      <aside>
        <div class="panel-card">
          <div class="panel-header">
            <span class="panel-title">👤 User Profile</span>
            <span class="tag-pill">ID: #<?= (int)$userInfo['ID'] ?></span>
          </div>

          <div class="profile-avatar-large">
            <?= htmlspecialchars($userInitial) ?>
          </div>
          <div class="profile-name-title"><?= htmlspecialchars($userInfo['Name']) ?></div>
          <div class="profile-id-sub"><?= htmlspecialchars($userInfo['Gender'] ?? 'N/A') ?> • <?= htmlspecialchars($userInfo['Age'] ?? 'N/A') ?> yrs</div>

          <div class="info-list">
            <div class="info-item">
              <span class="info-label">Phone</span>
              <span class="info-val"><?= htmlspecialchars($userInfo['Phone'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
              <span class="info-label">Institution</span>
              <span class="info-val" style="text-align: right; max-width: 180px;">
                <?php 
                  if (!empty($userInfo['Instituition'])) {
                      $area = $userInfo['Inst_Area'] ?? 'N/A';
                      $city = $userInfo['Inst_City'] ?? 'N/A';
                      echo htmlspecialchars($userInfo['Instituition'] . ' (' . $area . ', ' . $city . ')');
                  } else {
                      echo 'N/A';
                  }
                ?>
              </span>
            </div>
            <div class="info-item">
              <span class="info-label">Active Posts</span>
              <span class="info-val"><?= count($myRequests) ?></span>
            </div>
            <div class="info-item">
              <span class="info-label">Saved Favorites</span>
              <span class="info-val"><?= count($myFavorites) ?></span>
            </div>
          </div>

          <a href="edit_profile.php" class="btn-edit-profile">
            <span>⚙️ Edit Profile Information</span>
          </a>
        </div>
      </aside>

      <!-- Right Column: Main Content -->
      <div>

        <!-- Posted Travel Requests Panel -->
        <section class="panel-card">
          <div class="panel-header">
            <span class="panel-title">🚘 Your Posted Travel Requests</span>
            <span class="badge-count"><?= count($myRequests) ?> Posted</span>
          </div>

          <div class="table-responsive">
            <table class="modern-table">
              <thead>
                <tr>
                  <th>Req ID</th>
                  <th>Type</th>
                  <th>Transport</th>
                  <th>Route (Pickup ➔ Destination)</th>
                  <th>Date & Time</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($myRequests)): ?>
                  <?php foreach ($myRequests as $req): ?>
                    <tr>
                      <td><strong>#<?= (int)$req['Request_ID'] ?></strong></td>
                      <td><span class="tag-pill"><?= htmlspecialchars($req['Travel_Type']) ?></span></td>
                      <td><?= htmlspecialchars($req['Transport_Type']) ?></td>
                      <td>
                        <strong><?= htmlspecialchars($req['Pickup_Area'] . ', ' . $req['Pickup_City']) ?></strong>
                        <span style="color: var(--primary); margin: 0 4px;">➔</span>
                        <strong><?= htmlspecialchars($req['Dest_Area'] . ', ' . $req['Dest_City']) ?></strong>
                      </td>
                      <td><?= htmlspecialchars($req['Date'] . ' at ' . $req['Time']) ?></td>
                      <td>
                        <span class="status-badge <?= htmlspecialchars($req['Status']) ?>">
                          <?= htmlspecialchars($req['Status']) ?>
                        </span>
                      </td>
                      <td>
                        <form action="delete_request.php" method="POST" style="margin:0;">
                          <input type="hidden" name="req_id" value="<?= (int)$req['Request_ID'] ?>">
                          <button type="submit" class="btn-sm btn-sm-danger" onclick="return confirm('Are you sure you want to delete this travel request?');">
                            🗑️ Delete
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7">
                      <div class="empty-state">
                        You haven't posted any travel requests yet.<br>
                        <a href="create_request.php">Click here to create your first travel request</a>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Favorite Travel Buddies Panel -->
        <section class="panel-card">
          <div class="panel-header">
            <span class="panel-title">⭐ My Favorite Travel Buddies</span>
            <span class="badge-count"><?= count($myFavorites) ?> Saved</span>
          </div>

          <div class="table-responsive">
            <table class="modern-table">
              <thead>
                <tr>
                  <th>Buddy Name</th>
                  <th>Gender</th>
                  <th>Age</th>
                  <th>Institution</th>
                  <th>Phone</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($myFavorites)): ?>
                  <?php foreach ($myFavorites as $f): ?>
                    <tr>
                      <td><strong><?= htmlspecialchars($f['Name']) ?></strong></td>
                      <td><?= htmlspecialchars($f['Gender']) ?></td>
                      <td><?= htmlspecialchars($f['Age']) ?></td>
                      <td><?= htmlspecialchars($f['Instituition'] ?? 'N/A') ?></td>
                      <td><?= htmlspecialchars($f['Phone']) ?></td>
                      <td>
                        <div class="btn-action-group">
                          <!-- Direct Chat Button -->
                          <a href="chat.php?receiver_id=<?= (int)$f['ID'] ?>" class="btn-sm btn-sm-chat" title="Chat with <?= htmlspecialchars($f['Name']) ?>">
                            💬 Chat
                          </a>
                          
                          <!-- Direct Block Button -->
                          <a href="block_users.php?blocked_id=<?= (int)$f['ID'] ?>" class="btn-sm btn-sm-block" title="Block <?= htmlspecialchars($f['Name']) ?>">
                            🚫 Block
                          </a>

                          <!-- Remove Favorite Form -->
                          <form action="toggle_favorite.php" method="POST" style="margin:0;">
                            <input type="hidden" name="favored_id" value="<?= (int)$f['ID'] ?>">
                            <button type="submit" class="btn-sm btn-sm-secondary" title="Remove from favorites">
                              Remove
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6">
                      <div class="empty-state">
                        You haven't added any favorite travel buddies yet.<br>
                        <a href="search.php">Search travel requests and favorite users to save them here!</a>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

      </div>
    </div>

  </main>

</body>
</html>