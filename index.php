<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get featured products
$query = "SELECT * FROM products WHERE stock_quantity > 0 ORDER BY created_at DESC LIMIT 8";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$query = "SELECT * FROM categories";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<div class="hero-section bg-light p-5 rounded-3 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4 fw-bold">Welcome to GlasOnline</h1>
                <p class="lead">Your one-stop shop for all aquarium needs. Find the best fish, plants, and equipment.</p>
                <a href="products.php" class="btn btn-primary btn-lg">Shop Now</a>
            </div>
            <div class="col-md-6">
                <img src="https://vyapar-catalog.vypcdn.in/6449540f74bee/firmDetails/firmLogo.jpg?v1752244895.512" alt="Aquarium" class="img-fluid rounded-3">
            </div>
        </div>
    </div>
</div>

<!-- Categories -->
<section class="mb-5">
    <div class="d-flex flex-nowrap overflow-auto pb-2" style="scrollbar-width: thin;">
        <?php 
        // Array of different icons for categories
        $category_icons = [
            'fa-fish', 'fa-water', 'fa-leaf', 'fa-tint', 
            'fa-seedling', 'fa-coral', 'fa-bacteria', 'fa-filter'
        ];
        $i = 0;
        foreach ($categories as $category): 
            $icon = $category_icons[$i % count($category_icons)];
            $i++;
        ?>
        <div class="me-3 flex-shrink-0">
            <a href="products.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                <div class="d-flex flex-column align-items-center p-3 rounded-3 bg-light hover-shadow-sm" style="min-width: 120px;">
                    <i class="fas <?php echo $icon; ?> text-primary mb-2"></i>
                    <span class="small text-nowrap"><?php echo htmlspecialchars($category['name']); ?></span>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Featured Products -->
<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 fw-bold text-uppercase text-muted mb-0">Featured Products</h2>
        <a href="products.php" class="btn btn-outline-primary btn-sm">View All <i class="fas fa-arrow-right ms-1"></i></a>
    </div>
    <div class="row g-4">
        <?php 
        // Function to convert USD to INR (using approximate conversion rate)
        function usdToInr($usd) {
            $conversion_rate = 83.5; // 1 USD = 83.5 INR (approximate)
            return $usd * $conversion_rate;
        }
        
        foreach ($products as $product): 
            $inr_price = usdToInr($product['price']);
            $original_price = $product['price'];
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
                        <div class="text-end">
                            <small class="d-block text-success">
                                <i class="fas fa-check-circle"></i> In Stock
                            </small>
                            <small class="text-muted">
                                <?php echo $product['stock_quantity']; ?> units available
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>



<style>
    .product-card {
        transition: transform 0.3s, box-shadow 0.3s;
        border-radius: 10px;
        overflow: hidden;
    }
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    .product-card:hover .product-actions {
        bottom: 15px !important;
    }
    .hover-shadow {
        transition: box-shadow 0.3s;
    }
    .hover-shadow:hover {
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15) !important;
    }
    .object-fit-cover {
        object-fit: cover;
    }
</style>

<?php include 'includes/footer.php'; ?>
