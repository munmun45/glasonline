<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get search and category parameters
$search = $_GET['search'] ?? '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query
$query = "SELECT p.* FROM products p WHERE p.stock_quantity > 0";
$params = [];

if (!empty($search)) {
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$search%";
}

// Add category filter if category_id is provided and greater than 0
if ($category_id > 0) {
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

// Get category name if filtering by category
$category_name = '';
if ($category_id > 0) {
    $stmt = $db->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    $category_name = $category ? $category['name'] : '';
}

// Get products
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    if ($key === ':offset' || $key === ':per_page' || $key === ':category_id') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-0">
                <?php 
                if (!empty($category_name)) {
                    echo 'Category: ' . htmlspecialchars($category_name);
                } else {
                    echo 'Our Products';
                }
                ?>
            </h1>
            <?php if (!empty($category_name)): ?>
                <a href="products.php" class="text-muted small">← Back to all products</a>
            <?php endif; ?>
        </div>
        
    </div>

    <?php if (empty($products)): ?>
    <div class="alert alert-info">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle me-2"></i>
            <div>
                <p class="mb-0">No products found. Try a different search.</p>
                <a href="products.php" class="alert-link">View all products</a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php 
        // Function to convert USD to INR
        function usdToInr($usd) {
            return $usd * 83.5; // Current conversion rate
        }
        
        foreach ($products as $product): 
            $inr_price = usdToInr($product['price']);
            $discount = (isset($product['discount']) && $product['discount'] > 0) ? $product['discount'] : 0;
            $final_price = $inr_price * (1 - ($discount/100));
        ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="card h-100 product-card border-0 shadow-sm hover-shadow transition-all">
                <!-- Badge for discount -->
                <?php if($discount > 0): ?>
                    <span class="badge bg-danger position-absolute m-2"><?php echo $discount; ?>% OFF</span>
                <?php endif; ?>
                
                <!-- Product Image -->
                <div class="position-relative overflow-hidden" style="height: 200px;">
                    <img src="<?php echo $product['image_url'] ?: 'assets/images/placeholder.jpg'; ?>" 
                         class="card-img-top h-100 w-100 object-fit-cover" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="product-actions position-absolute w-100 d-flex justify-content-center" style="bottom: -50px; transition: all 0.3s;">
                        <button class="btn btn-sm btn-primary rounded-pill px-3 add-to-cart" 
                                data-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                        </button>
                    </div>
                </div>
                
                <!-- Product Details -->
                <div class="card-body p-3">
                    <h5 class="card-title mb-2">
                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="text-dark text-decoration-none">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </a>
                    </h5>
                    
                    <p class="card-text text-muted small mb-2" style="min-height: 40px;">
                        <?php echo substr(htmlspecialchars($product['description']), 0, 60); ?>...
                    </p>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="h5 mb-0 text-primary fw-bold">₹<?php echo number_format($final_price, 2); ?></span>
                            <?php if($discount > 0): ?>
                                <small class="text-muted text-decoration-line-through ms-2">₹<?php echo number_format($inr_price, 2); ?></small>
                            <?php endif; ?>
                        </div>
                        <span class="badge bg-<?php echo $product['stock_quantity'] > 0 ? 'success' : 'danger'; ?> small">
                            <?php echo $product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                        </span>
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
                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php 
                echo !empty($search) ? '&search=' . urlencode($search) : ''; 
                echo $category_id > 0 ? '&category=' . $category_id : ''; 
            ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            </li>
            <?php endif; ?>
            
            <?php 
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $start_page + 4);
            $start_page = max(1, $end_page - 4);
            
            if ($start_page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=1<?php 
                echo !empty($search) ? '&search=' . urlencode($search) : ''; 
                echo $category_id > 0 ? '&category=' . $category_id : ''; 
            ?>">1</a></li>
            <?php if ($start_page > 2): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; endif; ?>
            
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?><?php 
                echo !empty($search) ? '&search=' . urlencode($search) : ''; 
                echo $category_id > 0 ? '&category=' . $category_id : ''; 
            ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>
            
            <?php if ($end_page < $total_pages): ?>
            <?php if ($end_page < $total_pages - 1): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
            <li class="page-item"><a class="page-link" href="?page=<?php echo $total_pages; ?><?php 
                echo !empty($search) ? '&search=' . urlencode($search) : ''; 
                echo $category_id > 0 ? '&category=' . $category_id : ''; 
            ?>"><?php echo $total_pages; ?></a></li>
            <?php endif; ?>
            
            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php 
                    echo !empty($search) ? '&search=' . urlencode($search) : ''; 
                    echo $category_id > 0 ? '&category=' . $category_id : ''; 
                ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.product-card {
    transition: transform 0.3s, box-shadow 0.3s;
    border: 1px solid rgba(0,0,0,0.1);
}
.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}
.product-card .product-actions {
    opacity: 0;
    transition: all 0.3s;
}
.product-card:hover .product-actions {
    bottom: 10px !important;
    opacity: 1;
}
.hover-shadow {
    transition: box-shadow 0.3s ease-in-out;
}
.hover-shadow:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add to cart functionality
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-id');
            const button = this;
            const originalText = button.innerHTML;
            
            // Show loading state
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            button.disabled = true;
            
            // Send AJAX request to add to cart
            fetch('add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=1&action=add'
            })
            .then(response => response.json())
            .then(data => {
                // Reset button state
                button.innerHTML = originalText;
                button.disabled = false;
                
                if (data.success) {
                    // Show success message
                    const toast = document.createElement('div');
                    toast.className = 'position-fixed bottom-0 end-0 m-3 p-3 bg-success text-white rounded-3';
                    toast.innerHTML = '<i class="fas fa-check-circle me-2"></i> Added to cart successfully!';
                    toast.style.zIndex = '9999';
                    document.body.appendChild(toast);
                    
                    // Remove toast after 3 seconds
                    setTimeout(() => {
                        toast.style.opacity = '0';
                        toast.style.transition = 'opacity 0.5s';
                        setTimeout(() => toast.remove(), 500);
                    }, 3000);
                    
                    // Update cart count
                    updateCartCount();
                } else {
                    showAlert('Error: ' + (data.message || 'Could not add to cart'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                button.innerHTML = originalText;
                button.disabled = false;
                showAlert('An error occurred. Please try again.', 'danger');
            });
        });
    });
    
    // Function to update cart count
    function updateCartCount() {
        fetch('get-cart-count.php')
            .then(response => response.json())
            .then(data => {
                const cartCounts = document.querySelectorAll('.cart-count');
                cartCounts.forEach(span => {
                    span.textContent = data.count || '0';
                });
            });
    }
    
    // Function to show alert messages
    function showAlert(message, type = 'success') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
        alert.role = 'alert';
        alert.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
                <span>${message}</span>
            </div>
        `;
        alert.style.zIndex = '9999';
        document.body.appendChild(alert);
        
        // Remove alert after 3 seconds
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
