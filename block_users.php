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

$message      = "";
$message_type = ""; // 'success' or 'error'

// Handle POST actions for Blocking and Unblocking
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'block') {
        $blocked_id = isset($_POST['blocked_id']) ? (int)$_POST['blocked_id'] : 0;
        $reason     = isset($_POST['reason']) ? trim($_POST['reason']) : '';

        if ($blocked_id <= 0) {
            $message      = "Please select a valid user to block.";
            $message_type = "error";
        } elseif ($blocked_id === $current_user_id) {
            $message      = "You cannot block yourself.";
            $message_type = "error";
        } elseif (empty($reason)) {
            $message      = "Please provide or select a reason for blocking.";
            $message_type = "error";
        } else {
            // Truncate reason to 50 chars as specified in schema
            if (strlen($reason) > 50) {
                $reason = substr($reason, 0, 50);
            }

            // Insert or update block record
            $stmt = $conn->prepare("INSERT INTO blocks (BlockedBy_ID, Blocked_ID, Date, Reason) VALUES (?, ?, CURDATE(), ?) ON DUPLICATE KEY UPDATE Date = CURDATE(), Reason = VALUES(Reason)");
            $stmt->bind_param("iis", $current_user_id, $blocked_id, $reason);

            if ($stmt->execute()) {
                $message      = "User successfully blocked/flagged.";
                $message_type = "success";
            } else {
                $message      = "Failed to block user: " . $conn->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    } elseif ($action === 'unblock') {
        $blocked_id = isset($_POST['blocked_id']) ? (int)$_POST['blocked_id'] : 0;

        if ($blocked_id > 0) {
            $stmt = $conn->prepare("DELETE FROM blocks WHERE BlockedBy_ID = ? AND Blocked_ID = ?");
            $stmt->bind_param("ii", $current_user_id, $blocked_id);

            if ($stmt->execute()) {
                $message      = "User successfully unblocked.";
                $message_type = "success";
            } else {
                $message      = "Failed to unblock user: " . $conn->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}

// Preselected blocked user ID from query string if any
$preselect_id = isset($_GET['blocked_id']) ? (int)$_GET['blocked_id'] : 0;

// 1. Fetch List of All Users (except current user) for Block Form Dropdown
$all_users = [];
$stmtU = $conn->prepare("SELECT ID, Name, Instituition FROM user WHERE ID != ? ORDER BY Name ASC");
$stmtU->bind_param("i", $current_user_id);
$stmtU->execute();
$resU = $stmtU->get_result();
while ($row = $resU->fetch_assoc()) {
    $all_users[] = $row;
}
$stmtU->close();

// 2. Fetch Currently Blocked Users by Current User
$blocked_users = [];
$stmtB = $conn->prepare("
    SELECT b.Blocked_ID, b.Date, b.Reason, u.Name, u.Instituition, u.Phone 
    FROM blocks b 
    JOIN user u ON b.Blocked_ID = u.ID 
    WHERE b.BlockedBy_ID = ? 
    ORDER BY b.Date DESC
");
$stmtB->bind_param("i", $current_user_id);
$stmtB->execute();
$resB = $stmtB->get_result();
while ($row = $resB->fetch_assoc()) {
    $blocked_users[] = $row;
}
$stmtB->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Block Abusive Users - GoWithMe</title>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <style>
    :root {
      --bg-color: #0f172a;
      --card-bg: #1e293b;
      --card-border: #334155;
      --primary: #6366f1;
      --danger: #ef4444;
      --danger-hover: #dc2626;
      --success: #10b981;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    body {
      background: var(--bg-color);
      min-height: 100vh;
      color: var(--text-main);
      display: flex;
      flex-direction: column;
    }

    /* Navbar */
    .navbar {
      height: 64px;
      background: #0f172a;
      border-bottom: 1px solid var(--card-border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 24px;
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
      background: var(--primary);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 1.1rem;
      color: #fff;
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

    .btn-nav {
      color: var(--text-muted);
      text-decoration: none;
      font-size: 0.875rem;
      font-weight: 500;
      padding: 6px 12px;
      border-radius: 8px;
    }

    .btn-nav:hover {
      color: #fff;
      background: rgba(255, 255, 255, 0.08);
    }

    /* Container */
    .container {
      max-width: 1000px;
      width: 100%;
      margin: 30px auto;
      padding: 0 20px;
      flex: 1;
    }

    .page-title {
      font-size: 1.6rem;
      font-weight: 700;
      margin-bottom: 6px;
    }

    .page-subtitle {
      color: var(--text-muted);
      font-size: 0.9rem;
      margin-bottom: 24px;
    }

    /* Alert Banner */
    .alert {
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 0.9rem;
      font-weight: 500;
    }

    .alert.success {
      background: rgba(16, 185, 129, 0.15);
      border: 1px solid rgba(16, 185, 129, 0.3);
      color: #34d399;
    }

    .alert.error {
      background: rgba(239, 68, 68, 0.15);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #f87171;
    }

    /* Cards */
    .panel-card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 24px;
    }

    .panel-title {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 16px;
      border-bottom: 1px solid var(--card-border);
      padding-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Form controls */
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-bottom: 16px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .form-group.full-width {
      grid-column: span 2;
    }

    label {
      font-size: 0.85rem;
      font-weight: 600;
      color: #cbd5e1;
    }

    select, input[type="text"] {
      background: #0f172a;
      border: 1px solid var(--card-border);
      border-radius: 8px;
      padding: 10px 12px;
      color: #fff;
      font-size: 0.9rem;
      outline: none;
    }

    select:focus, input[type="text"]:focus {
      border-color: var(--primary);
    }

    .btn-danger {
      background: var(--danger);
      color: #fff;
      border: none;
      padding: 10px 18px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .btn-danger:hover {
      background: var(--danger-hover);
    }

    .btn-secondary {
      background: rgba(255, 255, 255, 0.1);
      color: #fff;
      border: 1px solid var(--card-border);
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 500;
      cursor: pointer;
    }

    .btn-secondary:hover {
      background: rgba(255, 255, 255, 0.2);
    }

    /* Table */
    .table-container {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.9rem;
    }

    th, td {
      padding: 12px 14px;
      border-bottom: 1px solid var(--card-border);
    }

    th {
      background: rgba(15, 23, 42, 0.6);
      color: var(--text-muted);
      font-weight: 600;
      font-size: 0.8rem;
      text-transform: uppercase;
    }

    tr:last-child td {
      border-bottom: none;
    }

    .reason-tag {
      background: rgba(239, 68, 68, 0.1);
      color: #f87171;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.8rem;
      border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .empty-msg {
      text-align: center;
      padding: 24px;
      color: var(--text-muted);
      font-size: 0.9rem;
    }

    @media (max-width: 640px) {
      .form-grid {
        grid-template-columns: 1fr;
      }
      .form-group.full-width {
        grid-column: span 1;
      }
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <header class="navbar">
    <a href="dashboard.php" class="brand">
      <div class="brand-logo">G</div>
      <span>GoWithMe</span>
    </a>
    <div class="nav-actions">
      <div class="user-badge">
        👤 <?= htmlspecialchars($current_user_name) ?>
      </div>
      <a href="dashboard.php" class="btn-nav">Dashboard</a>
      <a href="request_history.php" class="btn-nav">History</a>
      <a href="logout.php" class="btn-nav">Logout</a>
    </div>
  </header>

  <!-- Container -->
  <main class="container">

    <h1 class="page-title">🚫 Flag & Block Abusive Users</h1>
    <p class="page-subtitle">Block abusive or inappropriate users to prevent them from matching with you in future ride requests.</p>

    <?php if (!empty($message)): ?>
      <div class="alert <?= $message_type ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <!-- Block User Form Panel -->
    <div class="panel-card">
      <div class="panel-title">
        <span>🚩 Flag User</span>
      </div>

      <form action="block_users.php" method="POST">
        <input type="hidden" name="action" value="block">

        <div class="form-grid">
          <div class="form-group">
            <label for="blocked_id">Select User to Block:</label>
            <select name="blocked_id" id="blocked_id" required>
              <option value="">-- Select User --</option>
              <?php foreach ($all_users as $u): ?>
                <?php 
                  $selected = ($u['ID'] == $preselect_id) ? 'selected' : '';
                ?>
                <option value="<?= (int)$u['ID'] ?>" <?= $selected ?>>
                  <?= htmlspecialchars($u['Name']) ?> (<?= htmlspecialchars($u['Instituition'] ?? 'Member') ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="reason_preset">Reason Category:</label>
            <select id="reason_preset" onchange="handleReasonPreset(this)">
              <option value="">Select a common reason...</option>
              <option value="Abusive Language">Abusive Language</option>
              <option value="Harassment / Misbehavior">Harassment / Misbehavior</option>
              <option value="No-Show / Flaked on Ride">No-Show / Flaked on Ride</option>
              <option value="Unsafe Behavior">Unsafe Behavior</option>
              <option value="Fake Account / Spam">Fake Account / Spam</option>
              <option value="Other">Other (Custom)</option>
            </select>
          </div>

          <div class="form-group full-width">
            <label for="reason">Specify Reason (Max 50 characters):</label>
            <input type="text" id="reason" name="reason" maxlength="50" placeholder="e.g. Abusive behavior during ride" required>
          </div>
        </div>

        <button type="submit" class="btn-danger">
          🚫 Block User
        </button>
      </form>
    </div>

    <!-- Currently Blocked Users List -->
    <div class="panel-card">
      <div class="panel-title">
        <span>🔒 Currently Blocked Users (<?= count($blocked_users) ?>)</span>
      </div>

      <div class="table-container">
        <?php if (empty($blocked_users)): ?>
          <div class="empty-msg">You have not blocked any users yet.</div>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>Blocked User</th>
                <th>Institution</th>
                <th>Date Blocked</th>
                <th>Reason</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($blocked_users as $bu): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($bu['Name']) ?></strong></td>
                  <td><?= htmlspecialchars($bu['Instituition'] ?? 'Member') ?></td>
                  <td><?= htmlspecialchars($bu['Date']) ?></td>
                  <td><span class="reason-tag"><?= htmlspecialchars($bu['Reason']) ?></span></td>
                  <td>
                    <form action="block_users.php" method="POST" style="display:inline;">
                      <input type="hidden" name="action" value="unblock">
                      <input type="hidden" name="blocked_id" value="<?= (int)$bu['Blocked_ID'] ?>">
                      <button type="submit" class="btn-secondary" onclick="return confirm('Are you sure you want to unblock <?= htmlspecialchars(addslashes($bu['Name'])) ?>?')">
                        Unblock
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

  </main>

  <script>
    function handleReasonPreset(selectEl) {
      const val = selectEl.value;
      const reasonInput = document.getElementById('reason');
      if (val && val !== 'Other') {
        reasonInput.value = val.substring(0, 50);
      } else if (val === 'Other') {
        reasonInput.value = '';
        reasonInput.focus();
      }
    }
  </script>
</body>
</html>
