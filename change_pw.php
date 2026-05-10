<?php
session_start();
include 'koneksidb.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$name = 'Guest';
$email = '';
$profile_pic = 'assets/default_pp.png';

// Get user data
$query = "SELECT CONCAT(first_name, ' ', last_name) AS name, email, password FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $name = $user['name'];
    $email = $user['email'];
    $hashed_password = $user['password'];
}
$stmt->close();

// Get profile pic from biodata
$query_bio = "SELECT profile_pic FROM user_biodata WHERE user_id = ?";
$stmt_bio = $conn->prepare($query_bio);
$stmt_bio->bind_param("i", $user_id);
$stmt_bio->execute();
$result_bio = $stmt_bio->get_result();
if ($result_bio->num_rows > 0) {
    $bio = $result_bio->fetch_assoc();
    $profile_pic = $bio['profile_pic'] ?: 'assets/default_pp.png';
}
$stmt_bio->close();

// Handle password change
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate current password
    if (empty($current_password)) {
        $errors[] = 'Current password is required.';
    } elseif (!password_verify($current_password, $hashed_password)) {
        $errors[] = 'Current password is incorrect.';
    }

    // Validate new password
    if (empty($new_password)) {
        $errors[] = 'New password is required.';
    } elseif (strlen($new_password) < 8) {
        $errors[] = 'New password must be at least 8 characters long.';
    } elseif ($new_password === $current_password) {
        $errors[] = 'New password must be different from current password.';
    }

    // Validate confirm password
    if (empty($confirm_password)) {
        $errors[] = 'Confirm password is required.';
    } elseif ($new_password !== $confirm_password) {
        $errors[] = 'New password and confirm password do not match.';
    }

    // If no errors, update password
    if (empty($errors)) {
        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_hashed, $user_id);
        if ($update_stmt->execute()) {
            $success = 'Password updated successfully.';
            // Optionally, log out user or keep session
        } else {
            $errors[] = 'Failed to update password. Please try again.';
        }
        $update_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <!-- User Profile Section -->
        <div class="user-profile">
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile" class="profile-pic">
            <h3><?php echo htmlspecialchars($name); ?></h3>
            <p><?php echo htmlspecialchars($email); ?></p>
        </div>

        <!-- Navigation Menu -->
        <nav class="sidebar-menu">
            <ul>
                <li><a href="index.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="#"><i class="fas fa-exclamation-circle"></i> Vital Task</a></li>
                <li><a href="#"><i class="fas fa-tasks"></i> My Task</a></li>
                <li><a href="#"><i class="fas fa-list"></i> Task Categories</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="#"><i class="fas fa-question-circle"></i> Help</a></li>
            </ul>
        </nav>

        <!-- Logout -->
        <div class="logout">
            <button onclick="showLogoutModal()" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="topbar">
            <div class="logo">
                <span class="logo-primary">Yuk</span><span class="logo-secondary">Kerjain!</span>
            </div>

            <div class="search-box">
                <input type="text" placeholder="Search your task here...">
                <button><i class="fas fa-search"></i></button>
            </div>
            <div class="topbar-actions">
                <button class="icon-btn"><i class="fas fa-bell"></i></button>
                <button class="icon-btn"><i class="fas fa-envelope"></i></button>
                <div class="topbar-date">Tuesday 20/06/2026</div>
            </div>
        </div>

        <!-- Content -->
        <div class="content account-content">
            <div class="page-header">
                <div>
                    <h2>Change Password</h2>
                </div>
            </div>

            <div class="account-card">
                <div class="account-user">
                    <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile" class="account-avatar">
                    <div class="account-user-text">
                        <h3><?php echo htmlspecialchars($name); ?></h3>
                        <p><?php echo htmlspecialchars($email); ?></p>
                    </div>
                </div>

                <!-- Display errors or success -->
                <?php if (!empty($errors)): ?>
                    <div class="error-messages">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form method="POST" class="password-form">
                    <div class="form-group">
                        <label>Current Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="current_password" name="current_password" placeholder="" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="new_password" name="new_password" placeholder="" required minlength="8">
                            <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="btn-primary">Update Password</button>
                        <a href="index.php" class="btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<!-- Logout Modal -->
<div id="logoutModal" class="modal">
    <div class="modal-overlay" onclick="hideLogoutModal()"></div>
    <div class="modal-content">
        <h1>Apakah Anda Yakin?</h1>
        <p>Anda akan keluar dari akun saat ini dan kembali ke tampilan profil Guest.</p>
        <form method="post" action="index.php" class="logout-buttons">
            <button type="submit" name="confirm" value="yes" class="btn-yes">Yes</button>
            <button type="button" onclick="hideLogoutModal()" class="btn-no">No</button>
        </form>
    </div>
</div>

<script>
function showLogoutModal() {
    const modal = document.getElementById('logoutModal');
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('show'), 10);
}

function hideLogoutModal() {
    const modal = document.getElementById('logoutModal');
    modal.classList.remove('show');
    setTimeout(() => modal.style.display = 'none', 300);
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<style>
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1000;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .modal.show {
        opacity: 1;
    }
    .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
    }
    .modal-content {
        position: relative;
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        text-align: center;
        max-width: 400px;
        width: 90%;
        transform: scale(0.8);
        transition: transform 0.3s ease;
    }
    .modal.show .modal-content {
        transform: scale(1);
    }
    .modal-content h1 {
        margin-bottom: 10px;
        color: #333;
    }
    .modal-content p {
        margin-bottom: 20px;
        color: #666;
    }
    .logout-buttons {
        display: flex;
        justify-content: center;
        gap: 10px;
    }
    .btn-yes, .btn-no {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        transition: background 0.2s ease;
    }
    .btn-yes {
        background: #dc3545;
        color: white;
    }
    .btn-yes:hover {
        background: #c82333;
    }
    .btn-no {
        background: #6c757d;
        color: white;
    }
    .btn-no:hover {
        background: #5a6268;
    }
    .logout-btn {
        background: none;
        border: none;
        color: #dc3545;
        cursor: pointer;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: color 0.2s ease;
    }
    .logout-btn:hover {
        color: #c82333;
    }
    .error-messages {
        background: #f8d7da;
        color: #721c24;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .error-messages ul {
        margin: 0;
        padding-left: 20px;
    }
    .success-message {
        background: #d4edda;
        color: #155724;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .password-form {
        max-width: 500px;
        margin: 0 auto;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
    }
    .password-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    .password-wrapper input {
        width: 100%;
        padding: 12px 40px 12px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
        transition: border-color 0.3s ease;
    }
    .password-wrapper input:focus {
        outline: none;
        border-color: #ff6b6b;
        box-shadow: 0 0 5px rgba(255, 107, 107, 0.3);
    }
    .toggle-password {
        position: absolute;
        right: 10px;
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        font-size: 18px;
        padding: 5px;
        transition: color 0.3s ease;
    }
    .toggle-password:hover {
        color: #ff6b6b;
    }
    .form-buttons {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 30px;
    }
    .btn-primary {
        background: #ff6b6b;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    .btn-primary:hover {
        background: #ff5252;
    }
    .btn-secondary {
        background: #6c757d;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        text-decoration: none;
        display: inline-block;
        text-align: center;
        transition: background 0.3s ease;
    }
    .account-card {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-top: 20px;
    }
    .account-user {
        display: flex;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }
    .account-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        margin-right: 20px;
        object-fit: cover;
    }
    .account-user-text h3 {
        margin-bottom: 5px;
        color: #333;
    }
    .account-user-text p {
        color: #666;
        font-size: 14px;
    }
</style>
</style>

</body>
</html>