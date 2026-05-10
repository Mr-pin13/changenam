<?php
session_start();
require_once __DIR__ . '/koneksidb.php';

$loginError = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $loginError = 'Username dan password harus diisi.';
    } else {
        $stmt = $conn->prepare('SELECT id, password FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                header('Location: index.php');
                exit;
            }
        }

        $loginError = 'Username atau password salah.';
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In</title>
  <link rel="stylesheet" href="login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
  <div class="page-title">Login</div>
  
  <div class="main-container">
    <div class="left-section">
      <div class="form-box">
        <h1>Sign In</h1>
        <?php if ($loginError): ?>
          <div style="color:#B91C1C; margin-bottom:16px; font-size:14px;">
            <?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>
        
        <form method="post" action="login.php">
          <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="username" placeholder="Enter Username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" required>
          </div>
          
          <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" placeholder="Enter Password" required>
          </div>
          
          <div class="remember-me">
            <input type="checkbox" id="rememberMe">
            <label for="rememberMe">Remember Me</label>
          </div>
          
          <button type="submit" class="btn-login">Login</button>
        </form>
        
        <div class="divider">Or, Login with</div>
        
        <div class="social-icons">
          <button class="icon-btn facebook" onclick="openSocial('facebook')" type="button">
            <i class="fab fa-facebook-f"></i>
          </button>
          <button class="icon-btn google" onclick="openSocial('google')" type="button">
            <i class="fab fa-google"></i>
          </button>
          <button class="icon-btn twitter" onclick="openSocial('twitter')" type="button">
            <i class="fab fa-x-twitter"></i>
          </button>
        </div>
        
        <div class="signup-link">
          Don't have an account? <a href="register.php">Create One</a>
        </div>
      </div>
    </div>
    
    <div class="right-section">
      <div class="illustration">
        <svg viewBox="0 0 400 500" xmlns="http://www.w3.org/2000/svg">
          <rect x="80" y="80" width="150" height="280" rx="20" fill="#4A90E2" stroke="#2E5CB8" stroke-width="3"/>
          <rect x="90" y="95" width="130" height="250" rx="15" fill="#F5F5F5"/>
          
          <circle cx="155" cy="150" r="30" fill="#4CAF50"/>
          <path d="M 140 150 L 150 160 L 170 140" stroke="white" stroke-width="4" fill="none"/>
          
          <rect x="95" y="200" width="120" height="30" rx="5" fill="#E0E0E0"/>
          <circle cx="155" cy="285" r="8" fill="#2E5CB8"/>
          
          <circle cx="280" cy="130" r="25" fill="#8B5CF6"/>
          <path d="M 260 155 Q 280 145 300 155" fill="#FF69B4"/>
          <path d="M 255 160 L 275 180" stroke="#1F2937" stroke-width="3"/>
          <path d="M 305 160 L 285 180" stroke="#1F2937" stroke-width="3"/>
          <polygon points="260,185 300,185 290,220 270,220" fill="#4B5563"/>
          
          <ellipse cx="270" cy="250" rx="80" ry="100" fill="#FF69B4" opacity="0.3"/>
        </svg>
      </div>
    </div>
  </div>

  <script>
    function openSocial(platform) {
      let url = '';
      
      if (platform === 'facebook') {
        url = 'https://www.facebook.com';
      } else if (platform === 'google') {
        url = 'https://www.google.com';
      } else if (platform === 'twitter') {
        url = 'https://www.twitter.com';
      }
      
      if (url) {
        window.open(url, '_blank');
      }
    }
  </script>
</body>
</html>
