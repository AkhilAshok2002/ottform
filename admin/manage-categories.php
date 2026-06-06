<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check admin access
$auth->requireAdmin();

$message = '';
$error = '';

// Handle category actions
if (isset($_GET['action'])) {
    $id = $_GET['id'] ?? 0;
    
    if ($_GET['action'] === 'delete' && $id) {
        try {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = 'Category deleted successfully!';
            } else {
                $error = 'Failed to delete category';
            }
        } catch (PDOException $e) {
            $error = 'Cannot delete category with existing videos';
        }
    }
}

// Handle form submission for add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? 'movie';
    $action = $_POST['action'] ?? 'add';
    $id = $_POST['id'] ?? 0;
    
    if (empty($name)) {
        $error = 'Category name is required';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO categories (name, type) VALUES (?, ?)");
            if ($stmt->execute([$name, $type])) {
                $message = 'Category added successfully!';
            } else {
                $error = 'Failed to add category';
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE categories SET name = ?, type = ? WHERE id = ?");
            if ($stmt->execute([$name, $type, $id])) {
                $message = 'Category updated successfully!';
            } else {
                $error = 'Failed to update category';
            }
        }
    }
}

// Get all categories
$categories = $db->query("SELECT * FROM categories ORDER BY type, name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1>Manage Categories</h1>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="dashboard-grid">
                <!-- Add Category Form -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2>Add New Category</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="admin-form">
                            <input type="hidden" name="action" value="add">
                            
                            <div class="form-group">
                                <label for="name">Category Name *</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="type">Type</label>
                                <select id="type" name="type">
                                    <option value="movie">Movie</option>
                                    <option value="series">Series</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Add Category</button>
                        </form>
                    </div>
                </div>
                
                <!-- Categories List -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2>All Categories</h2>
                    </div>
                    <div class="card-body">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Videos</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                <?php
                                $videoCount = $db->prepare("SELECT COUNT(*) as count FROM videos WHERE category_id = ?");
                                $videoCount->execute([$category['id']]);
                                $count = $videoCount->fetch()['count'];
                                ?>
                                <tr>
                                    <td><?php echo $category['id']; ?></td>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $category['type']; ?>">
                                            <?php echo ucfirst($category['type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $count; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-icon edit-cat" data-id="<?php echo $category['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                data-type="<?php echo $category['type']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?action=delete&id=<?php echo $category['id']; ?>" 
                                           class="btn-icon delete"
                                           onclick="return confirm('Delete this category? Videos in this category will be uncategorized.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Edit Category Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Edit Category</h2>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label for="edit_name">Category Name</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_type">Type</label>
                    <select id="edit_type" name="type">
                        <option value="movie">Movie</option>
                        <option value="series">Series</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Category</button>
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // Edit category modal
    document.querySelectorAll('.edit-cat').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_name').value = this.dataset.name;
            document.getElementById('edit_type').value = this.dataset.type;
            document.getElementById('editModal').style.display = 'block';
        });
    });
    
    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    </script>
    
    <style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
        background-color: white;
        margin: 10% auto;
        padding: 30px;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
    }
    
    .btn-icon {
        padding: 5px 10px;
        margin: 0 2px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        background: transparent;
        color: #6b7280;
    }
    
    .btn-icon:hover {
        background: #f3f4f6;
    }
    
    .btn-icon.delete:hover {
        color: #ef4444;
    }
    
    .badge-movie {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .badge-series {
        background: #fef3c7;
        color: #92400e;
    }
    </style>
</body>
</html>