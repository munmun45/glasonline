<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category_id = $_GET['category'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.stock_quantity > 0";
$params = [];

if (!empty($search)) {
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($category_id)) {
    $query .= " AND p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM ($query) as total";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_products / $per_page);

// Add pagination and ordering
$query .= " ORDER BY p.created_at DESC LIMIT :offset, :per_page";
$params[':offset'] = $offset;
$params[':per_page'] = $per_page;

// Get products
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    if ($key === ':offset' || $key === ':per_page') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$query = "SELECT * FROM categories";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>Our Products</h1>
    </div>
    <div class="col-md-4">
        <form action="products.php" method="get" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>
</div>

<div class="row">
    <!-- Sidebar -->
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Categories</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="products.php" class="list-group-item list-group-item-action <?php echo empty($category_id) ? 'active' : ''; ?>">
                    All Categories
                </a>
                <?php foreach ($categories as $category): ?>
                <a href="products.php?category=<?php echo $category['id']; ?>" 
                   class="list-group-item list-group-item-action <?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Products -->
    <div class="col-md-9">
        <?php if (empty($products)): ?>
        <div class="alert alert-info">
            No products found. Try a different search or category.
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($products as $product): ?>
            <div class="col-md-4 col-6">
                <div class="card h-100">
                    <img src="<?php echo $product['image_url'] ?: 'assets/images/placeholder.jpg'; ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="card-body">
                        <span class="badge bg-secondary mb-2"><?php echo htmlspecialchars($product['category_name']); ?></span>
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="card-text text-muted">
                            <?php echo substr(htmlspecialchars($product['description']), 0, 80); ?>...
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 mb-0">$<?php echo number_format($product['price'], 2); ?></span>
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <span class="text-success small">In Stock (<?php echo $product['stock_quantity']; ?>)</span>
                                <button class="btn btn-sm btn-primary add-to-cart" data-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            <?php else: ?>
                                <span class="text-danger small">Out of Stock</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav class="mt-5">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_id) ? '&category=' . $category_id : ''; ?>">
                        Previous
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_id) ? '&category=' . $category_id : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category_id) ? '&category=' . $category_id : ''; ?>">
                        Next
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
