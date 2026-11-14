<?php
session_start();
require_once "DBConnect.php";

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id   = (int)$_SESSION['user_id'];
$current_user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$preselect_id      = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>In-App Chat - GoWithMe</title>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
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
      --chat-sent: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
      --chat-received: rgba(51, 65, 85, 0.85);
      --online-green: #10b981;
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

    /* Layout Container */
    .app-container {
      flex: 1;
      display: flex;
      max-width: 1400px;
      width: 100%;
      margin: 16px auto;
      padding: 0 16px;
      gap: 16px;
      height: calc(100vh - 96px);
    }

    /* Sidebar (Contacts Pane) */
    .sidebar {
      width: 340px;
      background: var(--card-bg);
      backdrop-filter: blur(16px);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .sidebar-header {
      padding: 16px;
      border-bottom: 1px solid var(--card-border);
    }

    .sidebar-title {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .privacy-tag {
      font-size: 0.7rem;
      background: rgba(16, 185, 129, 0.15);
      color: #34d399;
      padding: 3px 8px;
      border-radius: 12px;
      border: 1px solid rgba(16, 185, 129, 0.3);
      font-weight: 500;
    }

    .search-box {
      width: 100%;
      position: relative;
    }

    .search-input {
      width: 100%;
      background: rgba(15, 23, 42, 0.6);
      border: 1px solid var(--card-border);
      border-radius: 10px;
      padding: 10px 14px;
      color: #fff;
      font-size: 0.875rem;
      outline: none;
      transition: border-color 0.2s;
    }

    .search-input:focus {
      border-color: var(--primary);
    }

    .contacts-list {
      flex: 1;
      overflow-y: auto;
      padding: 8px;
    }

    .contact-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.2s;
      margin-bottom: 4px;
      position: relative;
    }

    .contact-item:hover {
      background: rgba(255, 255, 255, 0.05);
    }

    .contact-item.active {
      background: rgba(99, 102, 241, 0.2);
      border: 1px solid rgba(99, 102, 241, 0.4);
    }

    .avatar {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: linear-gradient(135deg, #3b82f6, #8b5cf6);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 1rem;
      color: #fff;
      flex-shrink: 0;
      position: relative;
    }

    .contact-info {
      flex: 1;
      min-width: 0;
    }

    .contact-name {
      font-weight: 600;
      font-size: 0.95rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .contact-inst {
      font-size: 0.75rem;
      color: var(--text-muted);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .contact-lastmsg {
      font-size: 0.8rem;
      color: #cbd5e1;
      margin-top: 2px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* Main Chat View */
    .chat-view {
      flex: 1;
      background: var(--card-bg);
      backdrop-filter: blur(16px);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .chat-header {
      padding: 16px 24px;
      border-bottom: 1px solid var(--card-border);
      background: rgba(15, 23, 42, 0.4);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .chat-user-details {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .chat-user-name {
      font-size: 1.1rem;
      font-weight: 700;
    }

    .chat-user-inst {
      font-size: 0.8rem;
      color: var(--text-muted);
    }

    .shield-badge {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.75rem;
      background: rgba(99, 102, 241, 0.15);
      border: 1px solid rgba(99, 102, 241, 0.3);
      color: #a5b4fc;
      padding: 4px 10px;
      border-radius: 20px;
    }

    /* Empty state */
    .empty-chat {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 40px;
      color: var(--text-muted);
    }

    .empty-chat-icon {
      width: 64px;
      height: 64px;
      background: rgba(99, 102, 241, 0.1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 16px;
      font-size: 1.8rem;
    }

    /* Message List */
    .messages-container {
      flex: 1;
      overflow-y: auto;
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .message-row {
      display: flex;
      flex-direction: column;
      max-width: 65%;
    }

    .message-row.sent {
      align-self: flex-end;
      align-items: flex-end;
    }

    .message-row.received {
      align-self: flex-start;
      align-items: flex-start;
    }

    .bubble {
      padding: 12px 16px;
      border-radius: 18px;
      font-size: 0.95rem;
      line-height: 1.45;
      word-wrap: break-word;
      position: relative;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }

    .sent .bubble {
      background: var(--chat-sent);
      color: #ffffff;
      border-bottom-right-radius: 4px;
    }

    .received .bubble {
      background: var(--chat-received);
      color: #f1f5f9;
      border: 1px solid var(--card-border);
      border-bottom-left-radius: 4px;
    }

    .msg-sender-label {
      font-size: 0.72rem;
      color: var(--text-muted);
      margin-bottom: 3px;
      padding: 0 4px;
    }

    /* Message Input Box */
    .chat-input-area {
      padding: 16px 20px;
      border-top: 1px solid var(--card-border);
      background: rgba(15, 23, 42, 0.4);
    }

    .input-form {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .msg-input {
      flex: 1;
      background: rgba(15, 23, 42, 0.7);
      border: 1px solid var(--card-border);
      border-radius: 24px;
      padding: 12px 20px;
      color: #fff;
      font-size: 0.95rem;
      outline: none;
      transition: all 0.2s;
    }

    .msg-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 12px rgba(99, 102, 241, 0.3);
    }

    .send-btn {
      width: 46px;
      height: 46px;
      border-radius: 50%;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      border: none;
      color: #fff;
      font-size: 1.1rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .send-btn:hover {
      transform: scale(1.05);
      box-shadow: 0 0 15px rgba(99, 102, 241, 0.5);
    }

    .send-btn:active {
      transform: scale(0.95);
    }

    /* Scrollbar Styling */
    ::-webkit-scrollbar {
      width: 6px;
    }
    ::-webkit-scrollbar-track {
      background: transparent;
    }
    ::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.15);
      border-radius: 3px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: rgba(255, 255, 255, 0.3);
    }

    @media (max-width: 768px) {
      .app-container {
        flex-direction: column;
        height: auto;
      }
      .sidebar {
        width: 100%;
        height: 250px;
      }
      .chat-view {
        height: 500px;
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
      <a href="logout.php" class="btn-nav">Logout</a>
    </div>
  </header>

  <!-- Main App Container -->
  <main class="app-container">

    <!-- Left Sidebar / Contacts List -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-title">
          <span>Direct Messages</span>
          <span class="privacy-tag">🔒 No Phone Needed</span>
        </div>
        <div class="search-box">
          <input type="text" id="searchInput" class="search-input" placeholder="Search user or institution..." oninput="handleSearch()">
        </div>
      </div>
      <div class="contacts-list" id="contactsList">
        <div style="text-align:center; padding: 20px; color: var(--text-muted); font-size: 0.85rem;">
          Loading registered users...
        </div>
      </div>
    </aside>

    <!-- Right Pane / Chat View -->
    <section class="chat-view">
      
      <!-- Chat Header -->
      <div class="chat-header" id="chatHeader" style="display: none;">
        <div class="chat-user-details">
          <div class="avatar" id="activeAvatar">?</div>
          <div>
            <div class="chat-user-name" id="activeUserName">Select User</div>
            <div class="chat-user-inst" id="activeUserInst">-</div>
          </div>
        </div>
        <div class="shield-badge">
          <span>🛡️ Private In-App Text</span>
        </div>
      </div>

      <!-- Empty State View -->
      <div class="empty-chat" id="emptyChatState">
        <div class="empty-chat-icon">💬</div>
        <h3 style="color: #fff; margin-bottom: 8px;">Private Conversations</h3>
        <p style="font-size: 0.9rem;">Select a buddy from the contact list to start messaging safely inside GoWithMe.</p>
      </div>

      <!-- Active Message Thread -->
      <div class="messages-container" id="messagesContainer" style="display: none;">
        <!-- Dynamic Messages Rendered Here -->
      </div>

      <!-- Chat Input Form -->
      <div class="chat-input-area" id="chatInputArea" style="display: none;">
        <form class="input-form" onsubmit="handleSend(event)">
          <input type="text" id="messageInput" class="msg-input" placeholder="Type a message..." autocomplete="off" required>
          <button type="submit" class="send-btn" title="Send message">
            ➤
          </button>
        </form>
      </div>

    </section>

  </main>

  <script>
    let activeReceiverId = <?= $preselect_id > 0 ? $preselect_id : 0 ?>;
    let activeReceiverName = '';
    let activeReceiverInst = '';
    let pollInterval = null;

    document.addEventListener('DOMContentLoaded', () => {
      loadContacts();
      // Auto-poll contacts list & messages every 2.5s
      pollInterval = setInterval(() => {
        loadContacts(true); // silent refresh
        if (activeReceiverId > 0) {
          loadMessages(activeReceiverId, true); // silent refresh
        }
      }, 2500);
    });

    // Fetch contacts list from chat_action.php
    function loadContacts(isSilent = false) {
      const searchVal = document.getElementById('searchInput').value.trim();
      fetch(`chat_action.php?action=fetch_contacts&search=${encodeURIComponent(searchVal)}`)
        .then(res => res.json())
        .then(data => {
          if (data.status === 'success') {
            renderContacts(data.contacts);
            if (activeReceiverId > 0 && !isSilent) {
              const activeContact = data.contacts.find(c => c.id === activeReceiverId);
              if (activeContact) {
                selectContact(activeContact.id, activeContact.name, activeContact.institution);
              }
            }
          }
        })
        .catch(err => console.error('Error fetching contacts:', err));
    }

    // Render contacts to sidebar
    function renderContacts(contacts) {
      const listEl = document.getElementById('contactsList');
      if (contacts.length === 0) {
        listEl.innerHTML = `<div style="text-align:center; padding: 20px; color: var(--text-muted); font-size: 0.85rem;">No contacts found.</div>`;
        return;
      }

      let html = '';
      contacts.forEach(c => {
        const initial = c.name.charAt(0).toUpperCase();
        const isActive = (c.id === activeReceiverId) ? 'active' : '';
        const lastMsgText = c.last_message ? (c.is_mine ? 'You: ' : '') + escapeHtml(c.last_message) : 'No messages yet';

        html += `
          <div class="contact-item ${isActive}" onclick="selectContact(${c.id}, '${escapeHtml(c.name)}', '${escapeHtml(c.institution)}')">
            <div class="avatar">${initial}</div>
            <div class="contact-info">
              <div class="contact-name">${escapeHtml(c.name)}</div>
              <div class="contact-inst">${escapeHtml(c.institution || 'Member')}</div>
              <div class="contact-lastmsg">${lastMsgText}</div>
            </div>
          </div>
        `;
      });

      listEl.innerHTML = html;
    }

    // Filter contacts upon typing in search box
    function handleSearch() {
      loadContacts(true);
    }

    // Select a contact and open chat thread
    function selectContact(id, name, institution) {
      activeReceiverId = id;
      activeReceiverName = name;
      activeReceiverInst = institution;

      // Update UI elements
      document.getElementById('emptyChatState').style.display = 'none';
      document.getElementById('chatHeader').style.display = 'flex';
      document.getElementById('messagesContainer').style.display = 'flex';
      document.getElementById('chatInputArea').style.display = 'block';

      document.getElementById('activeUserName').innerText = name;
      document.getElementById('activeUserInst').innerText = institution || 'Member';
      document.getElementById('activeAvatar').innerText = name.charAt(0).toUpperCase();

      // Highlight contact item
      loadContacts(true);

      // Load conversation history
      loadMessages(id, false);
    }

    // Load messages from chat_action.php
    function loadMessages(receiverId, isSilent = false) {
      fetch(`chat_action.php?action=fetch_messages&receiver_id=${receiverId}`)
        .then(res => res.json())
        .then(data => {
          if (data.status === 'success') {
            renderMessages(data.messages, isSilent);
          }
        })
        .catch(err => console.error('Error fetching messages:', err));
    }

    // Render message bubbles in thread container
    function renderMessages(messages, isSilent) {
      const container = document.getElementById('messagesContainer');
      const shouldScroll = !isSilent || (container.scrollHeight - container.scrollTop <= container.clientHeight + 100);

      if (messages.length === 0) {
        container.innerHTML = `
          <div style="text-align: center; color: var(--text-muted); margin: auto; font-size: 0.9rem;">
            No message history with <strong>${escapeHtml(activeReceiverName)}</strong> yet.<br>
            Say hello and coordinate your ride!
          </div>
        `;
        return;
      }

      let html = '';
      messages.forEach(m => {
        const typeClass = m.is_mine ? 'sent' : 'received';
        const senderLabel = m.is_mine ? 'You' : escapeHtml(activeReceiverName);

        html += `
          <div class="message-row ${typeClass}">
            <div class="msg-sender-label">${senderLabel}</div>
            <div class="bubble">
              ${escapeHtml(m.message)}
            </div>
          </div>
        `;
      });

      container.innerHTML = html;

      if (shouldScroll) {
        container.scrollTop = container.scrollHeight;
      }
    }

    // Handle message submit
    function handleSend(event) {
      event.preventDefault();
      const inputEl = document.getElementById('messageInput');
      const text = inputEl.value.trim();

      if (!text || activeReceiverId <= 0) return;

      const formData = new FormData();
      formData.append('action', 'send_message');
      formData.append('receiver_id', activeReceiverId);
      formData.append('message', text);

      inputEl.value = '';

      fetch('chat_action.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          loadMessages(activeReceiverId, false);
          loadContacts(true);
        } else {
          alert(data.message || 'Could not send message.');
        }
      })
      .catch(err => console.error('Error sending message:', err));
    }

    // Utility helper to prevent XSS
    function escapeHtml(str) {
      if (!str) return '';
      return str.replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
    }
  </script>
</body>
</html>
