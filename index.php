<?php
session_start();
include 'koneksidb.php';

// Default values for guest
$name = 'Guest';
$email = '';
$profile_pic = 'assets/default_pp.png';
$logged_in = false;

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $logged_in = true;
    $user_id = $_SESSION['user_id'];
    
    // Query to get user data from database
    $query = "SELECT CONCAT(first_name, ' ', last_name) AS name, email FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $name = $user['name'];
        $email = $user['email'];
        // Assuming profile pic is stored in database or use default
        // $profile_pic = $user['profile_pic'] ?: 'assets/default_pp.png';
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <!-- User Profile Section -->
        <div class="user-profile">
            <img src="<?php echo $profile_pic; ?>" alt="profile" class="profile-pic">
            <h3><?php echo htmlspecialchars($name); ?></h3>
            <p><?php echo htmlspecialchars($email); ?></p>
        </div>

        <!-- Navigation Menu -->
        <nav class="sidebar-menu">
            <ul>
                <li><a href="#"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="#"><i class="fas fa-exclamation-circle"></i> Vital Task</a></li>
                <li><a href="#"><i class="fas fa-tasks"></i> My Task</a></li>
                <li><a href="#"><i class="fas fa-list"></i> Task Categories</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="#"><i class="fas fa-question-circle"></i> Help</a></li>
            </ul>
        </nav>

        <!-- Logout -->
        <div class="logout">
            <?php if ($logged_in): ?>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php endif; ?>
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
        <div class="content profile-content">
            <div class="page-header">
                <div>
                    <h2>Account Information</h2>
                </div>
            </div>
            <!-- User Info Card -->
            <div class="profile-card">
                <img src="<?php echo $profile_pic; ?>" alt="profile" class="profile-large-pic">
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($name); ?></h3>
                    <div class="profile-details">
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                        <p><strong>Field 1:</strong> Information</p>
                        <p><strong>Field 2:</strong> Information</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="profile-actions">
                <?php if ($logged_in): ?>
                    <a href="account_info.php" class="btn-action btn-edit">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="change_pw.php" class="btn-action btn-password">
                        <i class="fas fa-key"></i> Change Password
                    </a>
                    <a href="logout.php" class="btn-action btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login/login.php" class="btn-action btn-edit">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="login/register.php" class="btn-action btn-password">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

</body>
</html>