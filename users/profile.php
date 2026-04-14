<?php
session_start();
require_once '../backend/dbconn.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Debug database connection
error_log("Database connection status: " . ($conn ? "Connected" : "Not connected"));

// Debug the query
$debug_query = "SELECT username, full_name, email, profile_picture FROM users WHERE username = '" . $_SESSION['username'] . "'";
error_log("Debug query: " . $debug_query);

// Try direct query
$debug_result = $conn->query($debug_query);
if ($debug_result) {
    $debug_row = $debug_result->fetch_assoc();
    error_log("Debug result: " . print_r($debug_row, true));
} else {
    error_log("Debug query failed: " . $conn->error);
}

// Get user data
$user = null;
try {
    // Debug: Print the username we're searching for
    error_log("Searching for user: " . $_SESSION['username']);
    
    // First, let's check if the full_name column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'full_name'");
    if ($checkColumn->num_rows === 0) {
        // If full_name column doesn't exist, add it
        $conn->query("ALTER TABLE users ADD COLUMN full_name VARCHAR(255) AFTER username");
        error_log("Added full_name column to users table");
    }
    
    $stmt = $conn->prepare("SELECT username, email, profile_picture FROM users WHERE username = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $_SESSION['username']);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Get result failed: " . $stmt->error);
    }
    
    $user = $result->fetch_assoc();
    
    // Debug: Print the fetched user data
    error_log("Fetched user data: " . print_r($user, true));
    
    if (!$user) {
        error_log("No user found for username: " . $_SESSION['username']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
        $uploadDir = '../uploads/profile_pictures/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            try {
                // Update database
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE username = ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("ss", $fileName, $_SESSION['username']);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $stmt->close();
                
                // Delete old profile picture if exists
                if ($user && isset($user['profile_picture']) && file_exists($uploadDir . $user['profile_picture'])) {
                    unlink($uploadDir . $user['profile_picture']);
                }
                
                header("Location: profile.php?success=1");
                exit();
            } catch (Exception $e) {
                error_log("Error updating profile picture: " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Zoryn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #634832;
            --secondary-color: #F5EBE0;
            --text-dark: #2D3436;
            --text-light: #636E72;
            --border-color: #E9ECEF;
            --success-color: #28A745;
            --error-color: #DC3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--secondary-color);
            min-height: 100vh;
            color: var(--text-dark);
        }

        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
        }

        .profile-header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .profile-header::after {
            content: '';
            position: absolute;
            bottom: -1rem;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 3px;
        }

        .profile-picture-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 3rem;
            position: relative;
        }

        .profile-picture {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            overflow: hidden;
            background: var(--secondary-color);
            border: 4px solid var(--primary-color);
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .profile-picture:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .upload-btn {
            background: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            box-shadow: 0 4px 12px rgba(99, 72, 50, 0.2);
        }

        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(99, 72, 50, 0.3);
        }

        .profile-info {
            display: grid;
            gap: 2rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .info-group {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .info-group label {
            color: var(--text-dark);
            font-weight: 500;
            font-size: 1.1rem;
            margin-left: 0.5rem;
        }

        .info-group input {
            padding: 1rem 1.2rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background-color: var(--secondary-color);
            color: var(--text-dark);
        }

        .info-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 72, 50, 0.1);
        }

        .info-group input:read-only {
            background-color: #f8f9fa;
            cursor: not-allowed;
            opacity: 0.8;
        }

        .save-btn {
            background: var(--primary-color);
            color: white;
            padding: 1.2rem 2rem;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            width: 100%;
            margin-top: 2rem;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            display: block;
            box-shadow: 0 4px 12px rgba(99, 72, 50, 0.2);
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(99, 72, 50, 0.3);
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            animation: slideIn 0.3s ease-out;
        }

        .success-message i {
            font-size: 1.2rem;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .profile-card {
                padding: 1.5rem;
            }

            .profile-picture {
                width: 150px;
                height: 150px;
            }

            .profile-info {
                gap: 1.5rem;
            }

            .info-group input {
                padding: 0.8rem 1rem;
                font-size: 1rem;
            }

            .save-btn {
                padding: 1rem 2rem;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../navigation/navbar.php'; ?>

    <div class="container">
        <div class="profile-card">
            <div class="profile-header">
                <h1>Profile Settings</h1>
                <?php if (isset($_GET['success'])): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        Profile picture updated successfully!
                    </div>
                <?php endif; ?>
            </div>

            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <div class="profile-picture-section">
                    <div class="profile-picture">
                        <?php if ($user && isset($user['profile_picture']) && $user['profile_picture']): ?>
                            <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <i class="fas fa-user" style="font-size: 5rem; color: var(--text-light); position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);"></i>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*" style="display: none;">
                    <label for="profile_picture" class="upload-btn">
                        <i class="fas fa-camera"></i> Change Profile Picture
                    </label>
                </div>

                <div class="profile-info">
                    <div class="info-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" value="<?php echo $user && isset($user['username']) ? htmlspecialchars($user['username']) : htmlspecialchars($_SESSION['username']); ?>" readonly>
                    </div>

                    <div class="info-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" value="<?php echo $user && isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" readonly>
                    </div>
                </div>

                <button type="submit" class="save-btn">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-picture img').src = e.target.result;
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>
</html> 