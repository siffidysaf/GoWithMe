<?php
session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Silence echo output from DBConnect if any
ob_start();
require_once "DBConnect.php";
ob_end_clean();

$current_user_id   = (int)$_SESSION['user_id'];
$current_user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';

// 1. Fetch Requests Posted by Logged-in User
$posted_sql = "
    SELECT 
        r.ID as Req_ID, 
        r.Date, 
        r.Time, 
        r.Status, 
        r.min_age,
        r.max_age, 
        r.group_size,
        r.`Solo or Group` as TravelType,
        m.Area as MeetupArea, 
        m.City as MeetupCity,
        d.Area as DestArea, 
        d.City as DestCity,
        g.Type as TransportType,
        pref.I_Name as PreferredInst,
        acc_u.ID as Counterpart_ID,
        acc_u.Name as Counterpart_Name,
        acc_u.Instituition as Counterpart_Inst,
        acc_u.Phone as Counterpart_Phone
    FROM posts p
    JOIN requests r ON p.Req_ID = r.ID
    LEFT JOIN meetup m ON r.ID = m.R_ID
    LEFT JOIN destination d ON r.ID = d.R_ID
    LEFT JOIN go_by g ON r.ID = g.R_ID
    LEFT JOIN prefers pref ON r.ID = pref.R_ID
    LEFT JOIN accepts a ON r.ID = a.Req_ID
    LEFT JOIN user acc_u ON a.User_ID = acc_u.ID
    WHERE p.User_ID = ?
    ORDER BY r.Date DESC, r.Time DESC
";

$posted_requests = [];
if ($stmtP = $conn->prepare($posted_sql)) {
    $stmtP->bind_param("i", $current_user_id);
    $stmtP->execute();
    $resP = $stmtP->get_result();
    while ($row = $resP->fetch_assoc()) {
        $reqId = $row['Req_ID'];
        if (!isset($posted_requests[$reqId])) {
            $posted_requests[$reqId] = [
                'Req_ID' => $row['Req_ID'],
                'Date' => $row['Date'],
                'Time' => $row['Time'],
                'Status' => $row['Status'],
                'min_age' => $row['min_age'],
                'max_age' => $row['max_age'],
                'group_size' => $row['group_size'],
                'TravelType' => $row['TravelType'],
                'MeetupArea' => $row['MeetupArea'],
                'MeetupCity' => $row['MeetupCity'],
                'DestArea' => $row['DestArea'],
                'DestCity' => $row['DestCity'],
                'TransportType' => $row['TransportType'],
                'PreferredInst' => $row['PreferredInst'],
                'type' => 'posted',
                'counterparts' => []
            ];
        }
        if (!empty($row['Counterpart_ID'])) {
            $alreadyAdded = false;
            foreach ($posted_requests[$reqId]['counterparts'] as $cp) {
                if ($cp['id'] == $row['Counterpart_ID']) {
                    $alreadyAdded = true;
                    break;
                }
            }
            if (!$alreadyAdded) {
                $posted_requests[$reqId]['counterparts'][] = [
                    'id' => $row['Counterpart_ID'],
                    'name' => $row['Counterpart_Name'],
                    'institution' => $row['Counterpart_Inst'],
                    'phone' => $row['Counterpart_Phone']
                ];
            }
        }
    }
    $stmtP->close();
}

// 2. Fetch Requests Accepted by Logged-in User
$accepted_sql = "
    SELECT 
        r.ID as Req_ID, 
        r.Date, 
        r.Time, 
        r.Status, 
        r.min_age,
        r.max_age, 
        r.group_size,
        r.`Solo or Group` as TravelType,
        m.Area as MeetupArea, 
        m.City as MeetupCity,
        d.Area as DestArea, 
        d.City as DestCity,
        g.Type as TransportType,
        pref.I_Name as PreferredInst,
        poster_u.ID as Counterpart_ID,
        poster_u.Name as Counterpart_Name,
        poster_u.Instituition as Counterpart_Inst,
        poster_u.Phone as Counterpart_Phone
    FROM accepts a
    JOIN requests r ON a.Req_ID = r.ID
    LEFT JOIN meetup m ON r.ID = m.R_ID
    LEFT JOIN destination d ON r.ID = d.R_ID
    LEFT JOIN go_by g ON r.ID = g.R_ID
    LEFT JOIN prefers pref ON r.ID = pref.R_ID
    LEFT JOIN posts p ON r.ID = p.Req_ID
    LEFT JOIN user poster_u ON p.User_ID = poster_u.ID
    WHERE a.User_ID = ?
    ORDER BY r.Date DESC, r.Time DESC
";

$accepted_requests = [];
if ($stmtA = $conn->prepare($accepted_sql)) {
    $stmtA->bind_param("i", $current_user_id);
    $stmtA->execute();
    $resA = $stmtA->get_result();
    while ($row = $resA->fetch_assoc()) {
        $reqId = $row['Req_ID'];
        if (!isset($accepted_requests[$reqId])) {
            $accepted_requests[$reqId] = [
                'Req_ID' => $row['Req_ID'],
                'Date' => $row['Date'],
                'Time' => $row['Time'],
                'Status' => $row['Status'],
                'min_age' => $row['min_age'],
                'max_age' => $row['max_age'],
                'group_size' => $row['group_size'],
                'TravelType' => $row['TravelType'],
                'MeetupArea' => $row['MeetupArea'],
                'MeetupCity' => $row['MeetupCity'],
                'DestArea' => $row['DestArea'],
                'DestCity' => $row['DestCity'],
                'TransportType' => $row['TransportType'],
                'PreferredInst' => $row['PreferredInst'],
                'type' => 'accepted',
                'counterparts' => []
            ];
        }
        if (!empty($row['Counterpart_ID'])) {
            $alreadyAdded = false;
            foreach ($accepted_requests[$reqId]['counterparts'] as $cp) {
                if ($cp['id'] == $row['Counterpart_ID']) {
                    $alreadyAdded = true;
                    break;
                }
            }
            if (!$alreadyAdded) {
                $accepted_requests[$reqId]['counterparts'][] = [
                    'id' => $row['Counterpart_ID'],
                    'name' => $row['Counterpart_Name'],
                    'institution' => $row['Counterpart_Inst'],
                    'phone' => $row['Counterpart_Phone']
                ];
            }
        }
    }
    $stmtA->close();
}

$conn->close();

// Combine all requests for display stats
$all_requests = array_merge(array_values($posted_requests), array_values($accepted_requests));

// Statistics calculation
$total_posted   = count($posted_requests);
$total_accepted = count($accepted_requests);
$total_history  = count($all_requests);

$searching_count = 0;
$found_count     = 0;

foreach ($all_requests as $req) {
    if ($req['Status'] === 'Searching') {
        $searching_count++;
    } else if ($req['Status'] === 'Found') {
        $found_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Request History - GoWithMe</title>
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

    /* Navbar */
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
      gap: 16px;
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
    }

    .btn-nav:hover {
      color: #fff;
      background: rgba(255, 255, 255, 0.08);
    }

    /* Container */
    .container {
      max-width: 1200px;
      width: 100%;
      margin: 24px auto;
      padding: 0 20px;
      flex: 1;
    }

    /* Header Section */
    .header-section {
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      flex-wrap: wrap;
      gap: 16px;
    }

    .header-title h1 {
      font-size: 1.8rem;
      font-weight: 700;
      letter-spacing: -0.02em;
    }

    .header-title p {
      color: var(--text-muted);
      font-size: 0.95rem;
      margin-top: 4px;
    }

    .action-btn-primary {
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      color: #fff;
      text-decoration: none;
      padding: 10px 18px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 0.9rem;
      box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
      transition: transform 0.2s, box-shadow 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .action-btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(99, 102, 241, 0.6);
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 16px;
      margin-bottom: 28px;
    }

    .stat-card {
      background: var(--card-bg);
      backdrop-filter: blur(12px);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      padding: 18px 20px;
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      flex-shrink: 0;
    }

    .stat-icon.purple { background: rgba(99, 102, 241, 0.15); color: #a5b4fc; }
    .stat-icon.pink { background: rgba(236, 72, 153, 0.15); color: #f472b6; }
    .stat-icon.amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
    .stat-icon.green { background: rgba(16, 185, 129, 0.15); color: #34d399; }

    .stat-value {
      font-size: 1.5rem;
      font-weight: 700;
      line-height: 1.2;
    }

    .stat-label {
      font-size: 0.825rem;
      color: var(--text-muted);
    }

    /* Controls Bar: Tabs & Search */
    .controls-bar {
      background: var(--card-bg);
      backdrop-filter: blur(12px);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      padding: 14px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 24px;
      flex-wrap: wrap;
    }

    .tabs-group {
      display: flex;
      gap: 8px;
      background: rgba(15, 23, 42, 0.6);
      padding: 4px;
      border-radius: 10px;
      border: 1px solid var(--card-border);
    }

    .tab-btn {
      background: transparent;
      border: none;
      color: var(--text-muted);
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 0.875rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .tab-btn.active {
      background: var(--primary);
      color: #fff;
      box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
    }

    .filters-group {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .search-input {
      background: rgba(15, 23, 42, 0.6);
      border: 1px solid var(--card-border);
      border-radius: 10px;
      padding: 8px 14px;
      color: #fff;
      font-size: 0.875rem;
      outline: none;
      width: 220px;
      transition: border-color 0.2s;
    }

    .search-input:focus {
      border-color: var(--primary);
    }

    .select-filter {
      background: rgba(15, 23, 42, 0.6);
      border: 1px solid var(--card-border);
      border-radius: 10px;
      padding: 8px 14px;
      color: #fff;
      font-size: 0.875rem;
      outline: none;
      cursor: pointer;
    }

    .select-filter option {
      background: #1e293b;
      color: #fff;
    }

    /* Requests Grid / Cards */
    .requests-list {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .request-card {
      background: var(--card-bg);
      backdrop-filter: blur(12px);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      padding: 20px;
      transition: transform 0.2s, border-color 0.2s;
      position: relative;
      overflow: hidden;
    }

    .request-card:hover {
      border-color: rgba(99, 102, 241, 0.4);
      transform: translateY(-2px);
    }

    .card-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 14px;
      border-bottom: 1px solid var(--card-border);
      margin-bottom: 16px;
    }

    .req-meta {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .req-id {
      font-weight: 700;
      font-size: 1rem;
      color: #a5b4fc;
    }

    .type-tag {
      font-size: 0.75rem;
      font-weight: 600;
      padding: 4px 10px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .type-tag.posted {
      background: rgba(99, 102, 241, 0.15);
      color: #a5b4fc;
      border: 1px solid rgba(99, 102, 241, 0.3);
    }

    .type-tag.accepted {
      background: rgba(236, 72, 153, 0.15);
      color: #f472b6;
      border: 1px solid rgba(236, 72, 153, 0.3);
    }

    .status-badge {
      font-size: 0.78rem;
      font-weight: 600;
      padding: 4px 12px;
      border-radius: 20px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
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

    .route-container {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
      background: rgba(15, 23, 42, 0.5);
      padding: 12px 16px;
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .route-point {
      flex: 1;
    }

    .route-label {
      font-size: 0.72rem;
      color: var(--text-muted);
      text-transform: uppercase;
      font-weight: 600;
    }

    .route-val {
      font-size: 0.95rem;
      font-weight: 600;
      color: #fff;
      margin-top: 2px;
    }

    .route-arrow {
      color: var(--primary);
      font-size: 1.2rem;
      font-weight: 700;
    }

    .details-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 12px;
      margin-bottom: 16px;
    }

    .detail-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.85rem;
      color: #cbd5e1;
    }

    .detail-icon {
      color: var(--primary);
      font-size: 1rem;
    }

    .counterpart-box {
      margin-top: 16px;
      padding-top: 14px;
      border-top: 1px solid var(--card-border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
    }

    .counterpart-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .cp-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, #3b82f6, #8b5cf6);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      color: #fff;
      font-size: 0.95rem;
    }

    .cp-details .cp-role {
      font-size: 0.72rem;
      color: var(--text-muted);
      text-transform: uppercase;
      font-weight: 600;
    }

    .cp-details .cp-name {
      font-size: 0.95rem;
      font-weight: 600;
      color: #fff;
    }

    .cp-details .cp-inst {
      font-size: 0.78rem;
      color: #94a3b8;
    }

    .btn-chat {
      background: rgba(99, 102, 241, 0.15);
      border: 1px solid rgba(99, 102, 241, 0.4);
      color: #a5b4fc;
      text-decoration: none;
      padding: 8px 14px;
      border-radius: 8px;
      font-size: 0.85rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }

    .btn-chat:hover {
      background: var(--primary);
      color: #fff;
      box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
    }

    .empty-state {
      background: var(--card-bg);
      backdrop-filter: blur(12px);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      padding: 48px 20px;
      text-align: center;
      color: var(--text-muted);
    }

    .empty-icon {
      font-size: 2.5rem;
      margin-bottom: 12px;
    }

    @media (max-width: 640px) {
      .route-container {
        flex-direction: column;
        align-items: flex-start;
      }
      .route-arrow {
        transform: rotate(90deg);
        align-self: center;
      }
      .controls-bar {
        flex-direction: column;
        align-items: stretch;
      }
      .search-input {
        width: 100%;
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
          <?= strtoupper(substr(htmlspecialchars($current_user_name), 0, 1)) ?>
        </div>
        <span><?= htmlspecialchars($current_user_name) ?></span>
      </div>
      <a href="dashboard.php" class="btn-nav">Dashboard</a>
      <a href="block_users.php" class="btn-nav">🚫 Blocked Users</a>
      <a href="chat.php" class="btn-nav">💬 Chat</a>
      <a href="logout.php" class="btn-nav">Logout</a>
    </div>
  </header>

  <!-- Main Container -->
  <main class="container">

    <!-- Header Section -->
    <div class="header-section">
      <div class="header-title">
        <h1>📜 Request History</h1>
        <p>Review all your past posted ride requests and accepted companion requests.</p>
      </div>
      <a href="create_request.php" class="action-btn-primary">
        <span>+ Create New Request</span>
      </a>
    </div>

    <!-- Stats Summary Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon purple">📊</div>
        <div>
          <div class="stat-value"><?= $total_history ?></div>
          <div class="stat-label">Total History Items</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon pink">📤</div>
        <div>
          <div class="stat-value"><?= $total_posted ?></div>
          <div class="stat-label">Posted by You</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green">🤝</div>
        <div>
          <div class="stat-value"><?= $total_accepted ?></div>
          <div class="stat-label">Accepted by You</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon amber">🔍</div>
        <div>
          <div class="stat-value"><?= $searching_count ?></div>
          <div class="stat-label">Active (Searching)</div>
        </div>
      </div>
    </div>

    <!-- Controls Bar: Tabs & Search/Filters -->
    <div class="controls-bar">
      <div class="tabs-group">
        <button class="tab-btn active" onclick="filterTab('all', this)">All History (<?= $total_history ?>)</button>
        <button class="tab-btn" onclick="filterTab('posted', this)">Posted by Me (<?= $total_posted ?>)</button>
        <button class="tab-btn" onclick="filterTab('accepted', this)">Accepted by Me (<?= $total_accepted ?>)</button>
      </div>

      <div class="filters-group">
        <input type="text" id="searchFilter" class="search-input" placeholder="Search area, transport, name..." oninput="applyFilters()">
        <select id="statusFilter" class="select-filter" onchange="applyFilters()">
          <option value="all">All Statuses</option>
          <option value="Searching">Searching</option>
          <option value="Found">Found</option>
        </select>
      </div>
    </div>

    <!-- Requests List -->
    <div class="requests-list" id="requestsList">
      <?php if (empty($all_requests)): ?>
        <div class="empty-state">
          <div class="empty-icon">📭</div>
          <h3>No Request History Found</h3>
          <p style="margin-top: 6px;">You haven't posted or accepted any travel requests yet.</p>
          <a href="create_request.php" class="action-btn-primary" style="margin-top: 16px;">Post a Request Now</a>
        </div>
      <?php else: ?>
        <?php foreach ($all_requests as $req): ?>
          <?php 
            $isPosted = ($req['type'] === 'posted');
            $typeClass = $isPosted ? 'posted' : 'accepted';
            $typeLabel = $isPosted ? 'Posted by You' : 'Accepted by You';
            $statusClass = htmlspecialchars($req['Status']);
            
            // Format location text
            $pickup = !empty($req['MeetupArea']) ? htmlspecialchars($req['MeetupArea'].', '.$req['MeetupCity']) : 'Not Specified';
            $dest   = !empty($req['DestArea']) ? htmlspecialchars($req['DestArea'].', '.$req['DestCity']) : 'Not Specified';
            
            // Search string for client-side filtering
            $searchData = strtolower(implode(' ', [
                $req['Req_ID'],
                $typeLabel,
                $req['Status'],
                $pickup,
                $dest,
                $req['TransportType'] ?? '',
                $req['PreferredInst'] ?? ''
            ]));
            
            // Collect counterpart search info
            foreach ($req['counterparts'] as $cp) {
                $searchData .= ' ' . strtolower($cp['name'] . ' ' . ($cp['institution'] ?? ''));
            }
          ?>

          <div class="request-card" 
               data-type="<?= $req['type'] ?>" 
               data-status="<?= htmlspecialchars($req['Status']) ?>"
               data-search="<?= htmlspecialchars($searchData) ?>">
            
            <!-- Card Top Header -->
            <div class="card-top">
              <div class="req-meta">
                <span class="req-id">#REQ-<?= htmlspecialchars($req['Req_ID']) ?></span>
                <span class="type-tag <?= $typeClass ?>"><?= $typeLabel ?></span>
              </div>
              <span class="status-badge <?= $statusClass ?>">
                <?= $req['Status'] === 'Found' ? '✅' : '🔍' ?> <?= htmlspecialchars($req['Status']) ?>
              </span>
            </div>

            <!-- Route Info -->
            <div class="route-container">
              <div class="route-point">
                <div class="route-label">📍 Pickup / Meetup</div>
                <div class="route-val"><?= $pickup ?></div>
              </div>
              <div class="route-arrow">➔</div>
              <div class="route-point">
                <div class="route-label">🏁 Destination</div>
                <div class="route-val"><?= $dest ?></div>
              </div>
            </div>

            <!-- Trip Details Grid -->
            <div class="details-grid">
              <div class="detail-item">
                <span class="detail-icon">📅</span>
                <span><strong>Date:</strong> <?= htmlspecialchars($req['Date']) ?></span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">⏰</span>
                <span><strong>Time:</strong> <?= htmlspecialchars($req['Time']) ?></span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">🚗</span>
                <span><strong>Transport:</strong> <?= htmlspecialchars($req['TransportType'] ?? 'N/A') ?></span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">👥</span>
                <span><strong>Travel Type:</strong> <?= htmlspecialchars($req['TravelType'] ?? 'Solo') ?><?= (!empty($req['group_size']) ? ' ('.$req['group_size'].' people)' : '') ?></span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">🎯</span>
                <span><strong>Age Range:</strong> <?= htmlspecialchars($req['min_age']) ?> - <?= htmlspecialchars($req['max_age']) ?> yrs</span>
              </div>
              <?php if (!empty($req['PreferredInst'])): ?>
                <div class="detail-item">
                  <span class="detail-icon">🏫</span>
                  <span><strong>Preferred Inst:</strong> <?= htmlspecialchars($req['PreferredInst']) ?></span>
                </div>
              <?php endif; ?>
            </div>

            <!-- Counterpart / Companion Info -->
            <?php if (!empty($req['counterparts'])): ?>
              <?php foreach ($req['counterparts'] as $cp): ?>
                <div class="counterpart-box">
                  <div class="counterpart-info">
                    <div class="cp-avatar"><?= strtoupper(substr(htmlspecialchars($cp['name']), 0, 1)) ?></div>
                    <div class="cp-details">
                      <div class="cp-role"><?= $isPosted ? 'Accepted By' : 'Request Posted By' ?></div>
                      <div class="cp-name"><?= htmlspecialchars($cp['name']) ?></div>
                      <div class="cp-inst"><?= htmlspecialchars($cp['institution'] ?? 'Member') ?> • 📞 <?= htmlspecialchars($cp['phone']) ?></div>
                    </div>
                  </div>
                  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <a href="chat.php?receiver_id=<?= (int)$cp['id'] ?>" class="btn-chat">
                      💬 Message User
                    </a>
                    <a href="block_users.php?blocked_id=<?= (int)$cp['id'] ?>" class="btn-chat" style="border-color: rgba(239, 68, 68, 0.4); color: #f87171; background: rgba(239, 68, 68, 0.1);">
                      🚫 Block User
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="counterpart-box">
                <div style="font-size: 0.825rem; color: var(--text-muted);">
                  <?= $isPosted ? 'No companion accepted this request yet.' : 'Poster details unavailable.' ?>
                </div>
              </div>
            <?php endif; ?>

          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- JS Empty Search Result Container -->
    <div id="noResultsMsg" class="empty-state" style="display: none; margin-top: 16px;">
      <div class="empty-icon">🔍</div>
      <h3>No Matching Requests Found</h3>
      <p style="margin-top: 6px;">Try adjusting your search terms or filter selection.</p>
    </div>

  </main>

  <script>
    let activeTab = 'all';

    function filterTab(type, btnElement) {
      activeTab = type;
      document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
      if (btnElement) {
        btnElement.classList.add('active');
      }
      applyFilters();
    }

    function applyFilters() {
      const searchVal = document.getElementById('searchFilter').value.toLowerCase().trim();
      const statusVal = document.getElementById('statusFilter').value;
      const cards = document.querySelectorAll('.request-card');
      let visibleCount = 0;

      cards.forEach(card => {
        const cardType = card.getAttribute('data-type');
        const cardStatus = card.getAttribute('data-status');
        const cardSearch = card.getAttribute('data-search');

        const matchesTab = (activeTab === 'all') || (cardType === activeTab);
        const matchesStatus = (statusVal === 'all') || (cardStatus === statusVal);
        const matchesSearch = !searchVal || cardSearch.includes(searchVal);

        if (matchesTab && matchesStatus && matchesSearch) {
          card.style.display = 'block';
          visibleCount++;
        } else {
          card.style.display = 'none';
        }
      });

      const noResultsMsg = document.getElementById('noResultsMsg');
      if (noResultsMsg) {
        noResultsMsg.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
      }
    }
  </script>
</body>
</html>
