<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Redirect if already logged in as admin
if ($auth->isLoggedIn() && $auth->isAdmin()) {
    header('Location: ' . SITE_URL . '/admin/dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $result = $auth->login($email, $password);
        if ($result['success'] && $auth->isAdmin()) {
            header('Location: ' . SITE_URL . '/admin/dashboard.php');
            exit();
        } else {
            $error = 'Invalid admin credentials';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-login">
    <div class="login-container">
        <div class="login-box">
            <h1>Admin Panel</h1>
            <p>Please login to continue</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <a href="<?php echo SITE_URL; ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Site
            </a>
        </div>
    </div>
</body>
</html>