<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Handle add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    
    if (empty($name)) {
        $_SESSION['error'] = 'Category name is required';
    } else {
        $query = "INSERT INTO categories (name) VALUES (?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$name])) {
            $_SESSION['success'] = 'Category added successfully';
        } else {
            $_SESSION['error'] = 'Error adding category';
        }
    }
    
    header('Location: categories.php');
    exit;
}

// Handle update category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $category_id = (int)$_POST['category_id'];
    $name = trim($_POST['name'] ?? '');
    
    if (empty($name)) {
        $_SESSION['error'] = 'Category name is required';
    } else {
        $query = "UPDATE categories SET name = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$name, $category_id])) {
            $_SESSION['success'] = 'Category updated successfully';
        } else {
            $_SESSION['error'] = 'Error updating category';
        }
    }
    
    header('Location: categories.php');
    exit;
}

// Handle delete category
if (isset($_GET['delete'])) {
    $category_id = (int)$_GET['delete'];
    
    // Check if category has products
    $query = "SELECT COUNT(*) as product_count FROM products WHERE category_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$category_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['product_count'] > 0) {
        $_SESSION['error'] = 'Cannot delete category with products. Please reassign or delete the products first.';
    } else {
        $query = "DELETE FROM categories WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$category_id])) {
            $_SESSION['success'] = 'Category deleted successfully';
        } else {
            $_SESSION['error'] = 'Error deleting category';
        }
    }
    
    header('Location: categories.php');
    exit;
}

// Get all categories with product counts
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count
          FROM categories c
          ORDER BY c.name";
$stmt = $db->query($query);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
</head>
<body>
    <?php include 'includes/header.php'; ?>
    

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/slider.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Categories</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Add Category Form -->
                    <div class="col-md-5 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <?php echo isset($_GET['edit']) ? 'Edit Category' : 'Add New Category'; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $edit_category = null;
                                if (isset($_GET['edit'])) {
                                    $edit_id = (int)$_GET['edit'];
                                    foreach ($categories as $cat) {
                                        if ($cat['id'] == $edit_id) {
                                            $edit_category = $cat;
                                            break;
                                        }
                                    }
                                }
                                ?>
                                <form method="post" class="category-form">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Category Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" required
                                               value="<?php echo $edit_category ? htmlspecialchars($edit_category['name']) : ''; ?>">
                                    </div>
                                    <div class="d-grid gap-2">
                                        <?php if ($edit_category): ?>
                                            <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                                            <button type="submit" name="update_category" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i> Update Category
                                            </button>
                                            <a href="categories.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-times me-1"></i> Cancel
                                            </a>
                                        <?php else: ?>
                                            <button type="submit" name="add_category" class="btn btn-primary">
                                                <i class="fas fa-plus-circle me-1"></i> Add Category
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Categories List -->
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Categories List</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($categories)): ?>
                                    <div class="text-center p-4">
                                        <p class="text-muted mb-0">No categories found. Add your first category using the form.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Products</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($categories as $category): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                                        <td>
                                                            <span class="badge bg-primary rounded-pill">
                                                                <?php echo $category['product_count']; ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('M j, Y', strtotime($category['created_at'])); ?></td>
                                                        <td>
                                                            <a href="categories.php?edit=<?php echo $category['id']; ?>" 
                                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="#" 
                                                               onclick="confirmDelete(<?php echo $category['id']; ?>)" 
                                                               class="btn btn-sm btn-outline-danger" title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(categoryId) {
            if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
                window.location.href = 'categories.php?delete=' + categoryId;
            }
        }
    </script>
</body>
</html>
