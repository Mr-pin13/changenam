<?php
require_once __DIR__ . '/../koneksidb.php';

$errors = [];
$first_name = '';
$last_name = '';
$username = '';
$email = '';
$agreeTerms = false;

function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $agreeTerms = isset($_POST['agreeTerms']);

    if ($first_name === '' || $last_name === '' || $username === '' || $email === '' || $password === '' || $confirm_password === '') {
        $errors[] = 'Semua field harus diisi.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Password dan konfirmasi password tidak cocok.';
    }

    if (!$agreeTerms) {
        $errors[] = 'Anda harus menyetujui semua syarat.';
    }

    if (empty($errors)) {
        $name = $first_name . ' ' . $last_name;

        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = 'Username atau email sudah terdaftar.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';

            $insert = $conn->prepare('INSERT INTO users (first_name, last_name, username, email, password, role) VALUES (?, ?, ?, ?, ?, ?)');
            $insert->bind_param('ssssss', $first_name, $last_name, $username, $email, $hashedPassword, $role);

            if ($insert->execute()) {
                header('Location: index.html?registered=1');
                exit;
            } else {
                $errors[] = 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.';
            }

            $insert->close();
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up</title>
  <link rel="stylesheet" href="register.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
  <div class="page-title">Register</div>
  
  <div class="main-container">
    <div class="left-section">
      <div class="illustration">
        <svg viewBox="0 0 400 600" xmlns="http://www.w3.org/2000/svg">
          <circle cx="120" cy="120" r="35" fill="#1E3A8A"/>
          
          <rect x="95" y="165" width="50" height="70" rx="10" fill="#E0E7FF"/>
          <rect x="75" y="165" width="20" height="90" fill="#1E3A8A"/>
          <rect x="145" y="175" width="20" height="80" fill="#1E3A8A"/>
          
          <rect x="85" y="245" width="30" height="80" fill="#1E3A8A"/>
          <rect x="125" y="245" width="30" height="80" fill="#1E3A8A"/>
          
          <rect x="80" y="330" width="20" height="50" fill="#93C5FD"/>
          <rect x="130" y="330" width="20" height="50" fill="#93C5FD"/>
          
          <rect x="200" y="100" width="120" height="150" rx="8" fill="#3B82F6"/>
          <line x1="215" y1="125" x2="305" y2="125" stroke="#93C5FD" stroke-width="3"/>
          <line x1="215" y1="145" x2="305" y2="145" stroke="#93C5FD" stroke-width="3"/>
          <line x1="215" y1="165" x2="305" y2="165" stroke="#93C5FD" stroke-width="3"/>
          <line x1="215" y1="185" x2="280" y2="185" stroke="#93C5FD" stroke-width="3"/>
          <line x1="215" y1="205" x2="290" y2="205" stroke="#93C5FD" stroke-width="3"/>
          <line x1="215" y1="225" x2="260" y2="225" stroke="#93C5FD" stroke-width="3"/>
          
          <rect x="240" y="280" width="100" height="120" rx="8" fill="#06B6D4"/>
          <line x1="255" y1="305" x2="325" y2="305" stroke="#FFFFFF" stroke-width="2"/>
          <line x1="255" y1="325" x2="325" y2="325" stroke="#FFFFFF" stroke-width="2"/>
          <line x1="255" y1="345" x2="325" y2="345" stroke="#FFFFFF" stroke-width="2"/>
          <line x1="255" y1="365" x2="295" y2="365" stroke="#FFFFFF" stroke-width="2"/>
          
          <rect x="170" y="350" width="110" height="130" rx="8" fill="#3B82F6"/>
          <circle cx="225" cy="380" r="8" fill="#93C5FD"/>
          <line x1="185" y1="410" x2="265" y2="410" stroke="#93C5FD" stroke-width="3"/>
          <line x1="185" y1="430" x2="265" y2="430" stroke="#93C5FD" stroke-width="3"/>
          <line x1="185" y1="450" x2="240" y2="450" stroke="#93C5FD" stroke-width="3"/>
        </svg>
      </div>
    </div>

    <div class="right-section">
      <div class="form-box">
        <h1>Sign Up</h1>
        <?php if (!empty($errors)): ?>
          <div style="color:#B91C1C; margin-bottom:16px; font-size:14px;">
            <?php echo implode('<br>', array_map('e', $errors)); ?>
          </div>
        <?php endif; ?>
        
        <form method="post" action="register.php">
          <div class="input-group">
            <i class="fas fa-pen"></i>
            <input type="text" name="first_name" placeholder="Enter First Name" value="<?php echo e($first_name); ?>" required>
          </div>
          
          <div class="input-group">
            <i class="fas fa-pen"></i>
            <input type="text" name="last_name" placeholder="Enter Last Name" value="<?php echo e($last_name); ?>" required>
          </div>
          
          <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="username" placeholder="Enter Username" value="<?php echo e($username); ?>" required>
          </div>
          
          <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" placeholder="Enter Email" value="<?php echo e($email); ?>" required>
          </div>
          
          <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" placeholder="Enter Password" required>
          </div>
          
          <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
          </div>
          
          <div class="remember-me">
            <input type="checkbox" id="agreeTerms" name="agreeTerms" <?php echo $agreeTerms ? 'checked' : ''; ?>>
            <label for="agreeTerms">I agree to all terms</label>
          </div>
          
          <button type="submit" class="btn-register">Register</button>
        </form>
        
        <div class="signup-link">
          Already have an account? <a href="login/login.php">Sign In</a>
        </div>
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
