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
                <img src="assets/images/aquarium-hero.jpg" alt="Aquarium" class="img-fluid rounded-3">
            </div>
        </div>
    </div>
</div>

<!-- Categories -->
<section class="mb-5">
    <h2 class="text-center mb-4">Shop by Category</h2>
    <div class="row g-4">
        <?php foreach ($categories as $category): ?>
        <div class="col-md-3 col-6">
            <a href="products.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="fas fa-fish fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Featured Products -->
<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Featured Products</h2>
        <a href="products.php" class="btn btn-outline-primary">View All</a>
    </div>
    <div class="row g-4">
        <?php foreach ($products as $product): ?>
        <div class="col-md-3 col-6">
            <div class="card h-100">
                <img src="<?php echo $product['image_url'] ?: 'assets/images/placeholder.jpg'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                    <p class="card-text text-muted"><?php echo substr(htmlspecialchars($product['description']), 0, 60); ?>...</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="h5 mb-0">$<?php echo number_format($product['price'], 2); ?></span>
                        <button class="btn btn-sm btn-primary add-to-cart" data-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
