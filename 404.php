<?php
$page_title = 'Page Not Found';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="error-content">
                <h1 class="display-1 text-primary">404</h1>
                <h2 class="mb-4">Oops! Page Not Found</h2>
                <p class="lead mb-4">
                    The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
                </p>
                <a href="index.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-home me-2"></i> Back to Home
                </a>
                <a href="products.php" class="btn btn-outline-primary btn-lg ms-2">
                    <i class="fas fa-shopping-bag me-2"></i> Browse Products
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
