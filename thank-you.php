<?php
session_start();

// Redirect if no order ID in session
if (!isset($_SESSION['order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = $_SESSION['order_id'];
unset($_SESSION['order_id']); // Clear the order ID from session
?>

<?php include 'includes/header.php'; ?>

<div class="text-center py-5">
    <div class="mb-4">
        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
    </div>
    <h1 class="display-4 fw-bold mb-3">Thank You for Your Order!</h1>
    <p class="lead mb-4">Your order has been placed successfully.</p>
    <p class="mb-4">Order Number: <strong>#<?php echo htmlspecialchars($order_id); ?></strong></p>
    <p class="mb-5">We've sent an order confirmation to your email. You will receive another email when your order ships.</p>
    
    <div class="d-flex justify-content-center gap-3">
        <a href="products.php" class="btn btn-outline-primary btn-lg">Continue Shopping</a>
        <a href="index.php" class="btn btn-primary btn-lg">Back to Home</a>
    </div>
    
    <div class="mt-5 pt-4 border-top">
        <h5 class="mb-3">Need Help?</h5>
        <p class="mb-0">
            <a href="contact.php" class="text-decoration-none">
                <i class="fas fa-envelope me-2"></i>Contact Us
            </a>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
