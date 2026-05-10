<?php
session_start();
include 'koneksidb.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Default values
$first_name = '';
$last_name = '';
$email = '';
$age = '';
$school = '';
$social_media = '';
$profile_pic = 'assets/default_pp.png';
$profile_pic_db = '';

// Fetch current user data
$query_user = "SELECT first_name, last_name, email FROM users WHERE id = ?";
$stmt_user = $conn->prepare($query_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows > 0) {
    $user = $result_user->fetch_assoc();
    $first_name = $user['first_name'];
    $last_name = $user['last_name'];
    $email = $user['email'];
}
$stmt_user->close();

// Fetch biodata
$query_bio = "SELECT age, school, social_media, profile_pic FROM user_biodata WHERE user_id = ?";
$stmt_bio = $conn->prepare($query_bio);
$stmt_bio->bind_param("i", $user_id);
$stmt_bio->execute();
$result_bio = $stmt_bio->get_result();
if ($result_bio->num_rows > 0) {
    $bio = $result_bio->fetch_assoc();
    $age = $bio['age'];
    $school = $bio['school'];
    $social_media = $bio['social_media'];
    $profile_pic_db = $bio['profile_pic'];
    $profile_pic = $profile_pic_db ?: 'assets/default_pp.png';
}
$stmt_bio->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $school = trim($_POST['school'] ?? '');
    $social_media = trim($_POST['social_media'] ?? '');
    $remove_photo = $_POST['remove_photo'] ?? 'no';

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid.';
    }

    // Validate age
    if ($age !== '' && (!ctype_digit($age) || (int)$age < 0)) {
        $errors[] = 'Umur tidak boleh minus.';
    }

    if ($remove_photo === 'yes') {
        $profile_pic = 'assets/default_pp.png';
        $profile_pic_db = '';
    }

    // Handle profile pic upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'assets/';
        $file_name = basename($_FILES['profile_pic']['name']);
        $target_file = $upload_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES['profile_pic']['tmp_name']);
        if ($check === false) {
            $errors[] = 'File bukan gambar.';
        }

        // Check file size (max 5MB)
        if ($_FILES['profile_pic']['size'] > 5000000) {
            $errors[] = 'Ukuran file terlalu besar (max 5MB).';
        }

        // Allow certain file formats
        if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
            $errors[] = 'Hanya file JPG, JPEG, PNG & GIF yang diperbolehkan.';
        }

        if (empty($errors)) {
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                $profile_pic = $target_file;
                $profile_pic_db = $target_file;
            } else {
                $errors[] = 'Gagal mengupload gambar.';
            }
        }
    }

    if ($remove_photo === 'yes' && empty($_FILES['profile_pic']['name'])) {
        $profile_pic = 'assets/default_pp.png';
        $profile_pic_db = '';
    }

    if (empty($errors)) {
        // Update users table
        $update_user = "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?";
        $stmt_update_user = $conn->prepare($update_user);
        $stmt_update_user->bind_param("sssi", $first_name, $last_name, $email, $user_id);
        $stmt_update_user->execute();
        $stmt_update_user->close();

        // Insert or update biodata
        $check_bio = "SELECT id FROM user_biodata WHERE user_id = ?";
        $stmt_check = $conn->prepare($check_bio);
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            // Update
            $update_bio = "UPDATE user_biodata SET age = ?, school = ?, social_media = ?, profile_pic = ? WHERE user_id = ?";
            $stmt_update_bio = $conn->prepare($update_bio);
            $stmt_update_bio->bind_param("ssssi", $age, $school, $social_media, $profile_pic_db, $user_id);
        } else {
            // Insert
            $insert_bio = "INSERT INTO user_biodata (user_id, age, school, social_media, profile_pic) VALUES (?, ?, ?, ?, ?)";
            $stmt_update_bio = $conn->prepare($insert_bio);
            $stmt_update_bio->bind_param("issss", $user_id, $age, $school, $social_media, $profile_pic_db);
        }
        $stmt_update_bio->execute();
        $stmt_update_bio->close();
        $stmt_check->close();

        $success = 'Informasi akun berhasil diperbarui.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YukKerjain! - Account Information</title>
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
        .modal-buttons {
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
    </style>
</head>
<body>

<div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <!-- User Profile Section -->
        <div class="user-profile">
            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile" class="profile-pic">
            <h3><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></h3>
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
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
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

        <div class="content account-content">
            <div class="page-header">
                <div>
                    <h2>Account Information</h2>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div style="color:#B91C1C; margin-bottom:16px; font-size:14px;">
                    <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div style="color:#10B981; margin-bottom:16px; font-size:14px;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="account-card">
                <div class="account-user">
                    <div class="profile-avatar-wrapper">
                        <img id="profileAvatar" src="<?php echo htmlspecialchars($profile_pic); ?>" alt="profile" class="account-avatar">
                    </div>
                    <div class="profile-avatar-actions">
                        <button type="button" class="profile-edit-button" onclick="togglePhotoModal()">
                            <i class="fas fa-pen"></i> Edit
                        </button>
                        <div id="photoModal" class="photo-modal">
                            <div class="photo-modal-backdrop" onclick="closePhotoModal()"></div>
                            <div class="photo-modal-content">
                                <div class="photo-modal-header">
                                    <h3>Change Profile Photo</h3>
                                    <button type="button" class="modal-close-btn" onclick="closePhotoModal()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="photo-modal-options">
                                    <button type="button" class="photo-modal-option camera-option" onclick="openUpload('camera')">
                                        <div class="photo-option-icon">
                                            <i class="fas fa-camera"></i>
                                        </div>
                                        <div class="photo-option-info">
                                            <p class="photo-option-title">Take Photo</p>
                                        </div>
                                    </button>
                                    <button type="button" class="photo-modal-option library-option" onclick="openUpload('library')">
                                        <div class="photo-option-icon">
                                            <i class="fas fa-images"></i>
                                        </div>
                                        <div class="photo-option-info">
                                            <p class="photo-option-title">Choose from Library</p>
                                        </div>
                                    </button>
                                    <button type="button" class="photo-modal-option delete-option" onclick="removePhoto()">
                                        <div class="photo-option-icon">
                                            <i class="fas fa-trash-alt"></i>
                                        </div>
                                        <div class="photo-option-info">
                                            <p class="photo-option-title">Delete Photo</p>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="account-user-text">
                        <h3><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></h3>
                        <p><?php echo htmlspecialchars($email); ?></p>
                    </div>
                </div>

                <form id="photoForm" class="account-form" method="post" enctype="multipart/form-data">
                    <input id="profile_pic_input" type="file" name="profile_pic" accept="image/*" style="display:none;">
                    <input id="remove_photo" type="hidden" name="remove_photo" value="no">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first-name">First Name</label>
                            <input id="first-name" type="text" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last-name">Last Name</label>
                            <input id="last-name" type="text" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="email">Email Address</label>
                            <input id="email" type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="age">Age</label>
                            <input id="age" type="number" name="age" min="0" value="<?php echo htmlspecialchars($age); ?>">
                        </div>
                        <div class="form-group">
                            <label for="school">School/University</label>
                            <input id="school" type="text" name="school" value="<?php echo htmlspecialchars($school); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="social_media">Social Media Link</label>
                            <input id="social_media" type="url" name="social_media" value="<?php echo htmlspecialchars($social_media); ?>" placeholder="https://...">
                        </div>
                    </div>
                    <div class="form-buttons">
                        <button type="submit" class="btn-primary">Save Changes</button>
                        <button type="button" class="btn-secondary" onclick="window.location.href='index.php'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<!-- Remove Photo Modal -->
<div id="removePhotoModal" class="modal">
    <div class="modal-overlay" onclick="hideRemovePhotoModal()"></div>
    <div class="modal-content">
        <h1>Are You Sure?</h1>
        <p>You will delete your profile photo and it will be replaced with the default photo.</p>
        <div class="modal-buttons">
            <button type="button" onclick="confirmRemovePhoto()" class="btn-yes">Yes</button>
            <button type="button" onclick="hideRemovePhotoModal()" class="btn-no">No</button>
        </div>
    </div>
</div>

<script>
    const photoModal = document.getElementById('photoModal');
    const fileInput = document.getElementById('profile_pic_input');
    const removePhotoInput = document.getElementById('remove_photo');
    const photoForm = document.getElementById('photoForm');

    function togglePhotoModal() {
        photoModal.classList.toggle('open');
    }

    function closePhotoModal() {
        photoModal.classList.remove('open');
    }

    function openUpload(mode) {
        if (mode === 'camera') {
            fileInput.setAttribute('capture', 'environment');
        } else {
            fileInput.removeAttribute('capture');
        }
        removePhotoInput.value = 'no';
        fileInput.click();
        closePhotoModal();
    }

    function removePhoto() {
        showRemovePhotoModal();
    }

    function showRemovePhotoModal() {
        const modal = document.getElementById('removePhotoModal');
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('show'), 10);
    }

    function hideRemovePhotoModal() {
        const modal = document.getElementById('removePhotoModal');
        modal.classList.remove('show');
        setTimeout(() => modal.style.display = 'none', 300);
    }

    function confirmRemovePhoto() {
        removePhotoInput.value = 'yes';
        photoForm.submit();
    }

    fileInput.addEventListener('change', function () {
        if (fileInput.files.length > 0) {
            removePhotoInput.value = 'no';
            photoForm.submit();
        }
    });

    document.addEventListener('click', function (event) {
        if (photoModal && !photoModal.contains(event.target) && !event.target.closest('.profile-edit-button')) {
            closePhotoModal();
        }
    });
</script>
</body>
</html>