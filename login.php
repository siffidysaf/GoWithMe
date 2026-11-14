<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - GoWithMe</title>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <style>
    :root {
      --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
      --card-bg: rgba(30, 41, 59, 0.75);
      --card-border: rgba(255, 255, 255, 0.12);
      --primary: #6366f1;
      --primary-hover: #4f46e5;
      --accent: #ec4899;
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
      background: var(--bg-gradient);
      min-height: 100vh;
      color: var(--text-main);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
      overflow-x: hidden;
    }

    /* Background glow circles */
    .glow-circle-1 {
      position: absolute;
      width: 350px;
      height: 350px;
      background: rgba(99, 102, 241, 0.25);
      filter: blur(90px);
      border-radius: 50%;
      top: 10%;
      left: 15%;
      z-index: 0;
    }

    .glow-circle-2 {
      position: absolute;
      width: 300px;
      height: 300px;
      background: rgba(236, 72, 153, 0.2);
      filter: blur(90px);
      border-radius: 50%;
      bottom: 10%;
      right: 15%;
      z-index: 0;
    }

    /* Login Card */
    .login-card {
      width: 100%;
      max-width: 420px;
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid var(--card-border);
      border-radius: 24px;
      padding: 40px 32px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
      position: relative;
      z-index: 10;
    }

    .brand-header {
      text-align: center;
      margin-bottom: 28px;
    }

    .brand-logo {
      width: 56px;
      height: 56px;
      background: linear-gradient(135deg, #6366f1, #ec4899);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 1.8rem;
      color: #fff;
      margin: 0 auto 16px auto;
      box-shadow: 0 0 25px rgba(99, 102, 241, 0.5);
    }

    .brand-title {
      font-size: 1.75rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      color: #fff;
    }

    .brand-subtitle {
      font-size: 0.875rem;
      color: var(--text-muted);
      margin-top: 6px;
    }

    /* Form Controls */
    .form-group {
      margin-bottom: 20px;
      position: relative;
    }

    .form-label {
      display: block;
      font-size: 0.825rem;
      font-weight: 600;
      color: #cbd5e1;
      margin-bottom: 8px;
    }

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-icon {
      position: absolute;
      left: 14px;
      color: var(--text-muted);
      font-size: 1.1rem;
      pointer-events: none;
    }

    .form-input {
      width: 100%;
      background: rgba(15, 23, 42, 0.6);
      border: 1px solid var(--card-border);
      border-radius: 12px;
      padding: 12px 14px 12px 42px;
      color: #fff;
      font-size: 0.95rem;
      outline: none;
      transition: all 0.2s ease;
    }

    .form-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 12px rgba(99, 102, 241, 0.3);
      background: rgba(15, 23, 42, 0.85);
    }

    .toggle-pass {
      position: absolute;
      right: 14px;
      background: none;
      border: none;
      color: var(--text-muted);
      cursor: pointer;
      font-size: 1.1rem;
      padding: 2px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.2s;
    }

    .toggle-pass:hover {
      color: #fff;
    }

    /* Submit Button */
    .btn-submit {
      width: 100%;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      border: none;
      color: #fff;
      padding: 14px;
      border-radius: 12px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
      transition: all 0.2s ease;
      margin-top: 8px;
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(99, 102, 241, 0.6);
    }

    .btn-submit:active {
      transform: translateY(0);
    }

    /* Footer Links */
    .card-footer {
      margin-top: 24px;
      text-align: center;
      font-size: 0.875rem;
      color: var(--text-muted);
      border-top: 1px solid var(--card-border);
      padding-top: 20px;
    }

    .card-footer a {
      color: #a5b4fc;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s;
    }

    .card-footer a:hover {
      color: #fff;
      text-decoration: underline;
    }

  </style>
</head>
<body>

  <!-- Decorative Glows -->
  <div class="glow-circle-1"></div>
  <div class="glow-circle-2"></div>

  <!-- Main Login Card -->
  <div class="login-card">
    
    <!-- Brand Logo & Title -->
    <div class="brand-header">
      <div class="brand-logo">G</div>
      <h1 class="brand-title">GoWithMe</h1>
      <p class="brand-subtitle">Find travel companions & share your journey safely</p>
    </div>

    <!-- Login Form -->
    <form action="login_action.php" method="POST">
      
      <!-- Phone Number Field -->
      <div class="form-group">
        <label class="form-label" for="phoneInput">Phone Number</label>
        <div class="input-wrapper">
          <span class="input-icon">📱</span>
          <input type="text" id="phoneInput" name="phone" class="form-input" placeholder="e.g. 123456789" required autocomplete="username">
        </div>
      </div>

      <!-- Password Field -->
      <div class="form-group">
        <label class="form-label" for="passInput">Password</label>
        <div class="input-wrapper">
          <span class="input-icon">🔒</span>
          <input type="password" id="passInput" name="password" class="form-input" placeholder="Enter your password" required autocomplete="current-password">
          <button type="button" class="toggle-pass" onclick="togglePasswordVisibility()" title="Toggle Password">
            👁️
          </button>
        </div>
      </div>

      <!-- Submit Button -->
      <button type="submit" class="btn-submit">
        Log In ➔
      </button>

    </form>

    <!-- Footer Links -->
    <div class="card-footer">
      Don't have an account? <a href="register.php">Register now</a>
    </div>

  </div>

  <script>
    function togglePasswordVisibility() {
      const passInput = document.getElementById('passInput');
      if (passInput.type === 'password') {
        passInput.type = 'text';
      } else {
        passInput.type = 'password';
      }
    }
  </script>

</body>
</html>