<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check admin access
$auth->requireAdmin();

$message = '';
$error = '';

// Handle user actions
if (isset($_GET['action'])) {
    $id = $_GET['id'] ?? 0;
    
    if ($_GET['action'] === 'delete' && $id && $id != $_SESSION['user_id']) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = 'User deleted successfully!';
        } else {
            $error = 'Failed to delete user';
        }
    }
}

// Handle user update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $subscription = $_POST['subscription_status'] ?? 'free';
    
    if ($id && $name && $email) {
        $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, role = ?, subscription_status = ? WHERE id = ?");
        if ($stmt->execute([$name, $email, $role, $subscription, $id])) {
            $message = 'User updated successfully!';
        } else {
            $error = 'Failed to update user';
        }
    }
}

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Search
$search = $_GET['search'] ?? '';
if ($search) {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE name LIKE ? OR email LIKE ?");
    $stmt->execute(["%$search%", "%$search%"]);
    $totalUsers = $stmt->fetch()['count'];
    
    $stmt = $db->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute(["%$search%", "%$search%", $limit, $offset]);
    $users = $stmt->fetchAll();
} else {
    $totalUsers = $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
    $users = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT $limit OFFSET $offset")->fetchAll();
}

$totalPages = ceil($totalUsers / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1>Manage Users</h1>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Search Bar -->
            <div class="search-section">
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Search by name or email..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if ($search): ?>
                        <a href="?" class="btn btn-outline">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Users Table -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>All Users (<?php echo $totalUsers; ?>)</h2>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Subscription</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $user['subscription_status']; ?>">
                                        <?php echo ucfirst($user['subscription_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button class="btn-icon edit-user" 
                                            data-id="<?php echo $user['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                            data-role="<?php echo $user['role']; ?>"
                                            data-subscription="<?php echo $user['subscription_status']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="?action=delete&id=<?php echo $user['id']; ?>" 
                                       class="btn-icon delete"
                                       onclick="return confirm('Delete this user? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Edit User</h2>
            <form method="POST" class="admin-form">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label for="edit_name">Name</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_role">Role</label>
                    <select id="edit_role" name="role">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_subscription">Subscription</label>
                    <select id="edit_subscription" name="subscription_status">
                        <option value="free">Free</option>
                        <option value="basic">Basic</option>
                        <option value="premium">Premium</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update User</button>
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <style>
    .search-section {
        margin-bottom: 20px;
    }
    
    .search-form {
        display: flex;
        gap: 10px;
        max-width: 500px;
    }
    
    .search-form input {
        flex: 1;
        padding: 10px;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
    }
    
    .badge-admin {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .badge-user {
        background: #e5e7eb;
        color: #374151;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 5px;
        margin-top: 20px;
    }
    
    .page-btn {
        padding: 5px 10px;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
        text-decoration: none;
        color: #374151;
    }
    
    .page-btn.active {
        background: var(--admin-primary);
        color: white;
        border-color: var(--admin-primary);
    }
    </style>
    
    <script>
    document.querySelectorAll('.edit-user').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_name').value = this.dataset.name;
            document.getElementById('edit_email').value = this.dataset.email;
            document.getElementById('edit_role').value = this.dataset.role;
            document.getElementById('edit_subscription').value = this.dataset.subscription;
            document.getElementById('editModal').style.display = 'block';
        });
    });
    
    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    </script>
</body>
</html>