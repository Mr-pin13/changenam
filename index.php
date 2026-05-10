<?php
session_start();
include 'koneksidb.php';

// Handle logout confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    if ($_POST['confirm'] === 'yes') {
        // Destroy session and logout user
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        header('Location: index.php');
        exit;
    }
    // If 'no', just continue to show the page
}

// Default values for guest
$name = 'Guest';
$email = '';
$profile_pic = 'assets/default_pp.png';
$age = '';
$school = '';
$social_media = '';
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
    }
    $stmt->close();

    // Query biodata
    $query_bio = "SELECT age, school, social_media, profile_pic FROM user_biodata WHERE user_id = ?";
    $stmt_bio = $conn->prepare($query_bio);
    $stmt_bio->bind_param("i", $user_id);
    $stmt_bio->execute();
    $result_bio = $stmt_bio->get_result();
    if ($result_bio->num_rows > 0) {
        $bio = $result_bio->fetch_assoc();
        $profile_pic = $bio['profile_pic'] ?: 'assets/default_pp.png';
        $age = $bio['age'];
        $school = $bio['school'];
        $social_media = $bio['social_media'];
    }
    $stmt_bio->close();
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
    </style>
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
                <button onclick="showLogoutModal()" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
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
                        <p><strong>Age:</strong> <?php echo htmlspecialchars($age ?: 'Not set'); ?></p>
                        <p><strong>School/University:</strong> <?php echo htmlspecialchars($school ?: 'Not set'); ?></p>
                        <p><strong>Social Media:</strong> <?php echo $social_media ? '<a href="' . htmlspecialchars($social_media) . '" target="_blank">' . htmlspecialchars($social_media) . '</a>' : 'Not set'; ?></p>
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
                    <button onclick="showLogoutModal()" class="btn-action btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                <?php else: ?>
                    <a href="login.php" class="btn-action btn-edit">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="register.php" class="btn-action btn-password">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Logout Modal -->
<div id="logoutModal" class="modal">
    <div class="modal-overlay" onclick="hideLogoutModal()"></div>
    <div class="modal-content">
        <h1>Are You Sure?</h1>
        <p>You will be logged out of the current account and returned to the Guest profile view.</p>
        <form method="post" class="logout-buttons">
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
</script>

</body>
</html>