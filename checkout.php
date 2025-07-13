<?php
session_start();
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    $_SESSION['error'] = 'Your cart is empty.';
    header('Location: cart.php');
    exit;
}

// Process checkout form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    
    $errors = [];
    
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (empty($phone)) $errors[] = 'Phone number is required';
    if (empty($address)) $errors[] = 'Address is required';
    if (empty($city)) $errors[] = 'City is required';
    if (empty($postal_code)) $errors[] = 'Postal code is required';
    
    if (empty($errors)) {
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Calculate order total
            $subtotal = 0;
            $order_items = [];
            
            // First, get all product details in one query
            $product_ids = [];
            $cart_quantities = [];
            
            // Process cart items to get product IDs and quantities
            foreach ($_SESSION['cart'] as $item) {
                $product_id = $item['product_id'] ?? ($item['id'] ?? null);
                if ($product_id) {
                    $product_ids[] = $product_id;
                    $cart_quantities[$product_id] = $item['quantity'];
                }
            }
            
            if (empty($product_ids)) {
                throw new Exception('Your cart is empty');
            }
            
            // Get all product details in one query
            $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
            $query = "SELECT id, name, price, stock_quantity FROM products WHERE id IN ($placeholders) FOR UPDATE";
            $stmt = $db->prepare($query);
            $stmt->execute($product_ids);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Check stock and calculate totals
            foreach ($products as $product) {
                $product_id = $product['id'];
                $quantity = $cart_quantities[$product_id] ?? 0;
                
                if ($quantity <= 0) {
                    continue;
                }
                
                if ($product['stock_quantity'] < $quantity) {
                    throw new Exception('Insufficient stock for ' . $product['name']);
                }
                
                $item_total = $product['price'] * $quantity;
                $subtotal += $item_total;
                
                $order_items[] = [
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'price' => $product['price'],
                    'total' => $item_total,
                    'name' => $product['name']
                ];
            }
            
            $shipping = $subtotal > 0 ? 10.00 : 0;
            $total = $subtotal + $shipping;
            
            // Create order
            $query = "INSERT INTO orders (customer_name, customer_email, customer_phone, total_amount) 
                     VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $email, $phone, $total]);
            $order_id = $db->lastInsertId();
            
            // Add order items and update stock
            foreach ($order_items as $item) {
                // Add order item
                $query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                         VALUES (?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                
                // Update product stock
                $query = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Send email notification to admin
            $to = 'admin@glasonline.com';
            $subject = 'New Order #' . $order_id;
            $message = "New order received:\n\n";
            $message .= "Order ID: " . $order_id . "\n";
            $message .= "Customer: " . $name . "\n";
            $message .= "Email: " . $email . "\n";
            $message .= "Phone: " . $phone . "\n\n";
            $message .= "Shipping Address:\n";
            $message .= $address . "\n";
            $message .= $city . " - " . $postal_code . "\n\n";
            $message .= "Order Total: $" . number_format($total, 2) . "\n\n";
            $message .= "Order Items:\n";
            
            foreach ($order_items as $item) {
                $message .= "- " . $item['quantity'] . " x " . 
                           $_SESSION['cart'][$item['product_id']]['name'] . 
                           " ($" . number_format($item['price'], 2) . " each)\n";
            }
            
            $headers = 'From: no-reply@glasonline.com' . "\r\n" .
                      'Reply-To: ' . $email . "\r\n" .
                      'X-Mailer: PHP/' . phpversion();
            
            mail($to, $subject, $message, $headers);
            
            // Commit transaction
            $db->commit();
            
            // Clear cart
            $_SESSION['cart'] = [];
            
            // Redirect to thank you page
            $_SESSION['order_id'] = $order_id;
            header('Location: thank-you.php');
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollBack();
            $errors[] = 'Error processing your order: ' . $e->getMessage();
        }
    }
}

// Calculate cart totals for display
$subtotal = 0;
$cart_items = [];

// Debug: Check session cart data
error_log('Session cart data: ' . print_r($_SESSION['cart'], true));

if (!empty($_SESSION['cart'])) {
    // Get all product IDs from cart
    $product_ids = [];
    $cart_quantities = [];
    
    foreach ($_SESSION['cart'] as $item) {
        if (is_array($item)) {
            if (isset($item['product_id'])) {
                $product_ids[] = $item['product_id'];
                $cart_quantities[$item['product_id']] = $item['quantity'];
            } elseif (isset($item['id'])) {
                $product_ids[] = $item['id'];
                $cart_quantities[$item['id']] = $item['quantity'];
            }
        }
    }
    
    if (!empty($product_ids)) {
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        
        $query = "SELECT id, name, price, stock_quantity, image_url FROM products WHERE id IN ($placeholders)";
        $stmt = $db->prepare($query);
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    
        
        foreach ($products as $product) {
            $product_id = $product['id'];
            $quantity = $cart_quantities[$product_id] ?? 1;
            $price_inr = $product['price'];
            $item_total = $price_inr * $quantity;
            $subtotal += $item_total;
            
            $cart_items[] = [
                'id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'price_inr' => $price_inr,
                'quantity' => $quantity,
                'stock_quantity' => $product['stock_quantity'],
                'image_url' => $product['image_url'] ?? 'assets/images/placeholder.jpg',
                'total' => $item_total
            ];
        }
    }
}

// Debug: Check calculated data
error_log('Calculated cart items: ' . print_r($cart_items, true));
error_log('Subtotal: ' . $subtotal);

$shipping = 0; // Free shipping
$total = $subtotal;
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="d-flex align-items-center mb-4">
                <a href="cart.php" class="btn btn-outline-secondary btn-sm me-3">
                    <i class="fas fa-arrow-left me-1"></i> Back to Cart
                </a>
                <h1 class="h3 mb-0">Checkout</h1>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <h5 class="alert-heading">Please fix the following issues:</h5>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light py-3">
                    <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i> Shipping Information</h5>
                </div>
                <div class="card-body p-4">
                    <form method="post" id="checkout-form" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="name" name="name" required 
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                    <div class="invalid-feedback">
                                        Please enter your full name
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                    <div class="invalid-feedback">
                                        Please enter a valid email address
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" class="form-control" id="phone" name="phone" required
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                    <div class="invalid-feedback">
                                        Please enter your phone number
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <input type="text" class="form-control" id="address" name="address" required
                                           value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                                    <div class="invalid-feedback">
                                        Please enter your shipping address
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-city"></i></span>
                                    <input type="text" class="form-control" id="city" name="city" required
                                           value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                                    <div class="invalid-feedback">
                                        Please enter your city
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="postal_code" class="form-label">Postal Code <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-mail-bulk"></i></span>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" required
                                           value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>">
                                    <div class="invalid-feedback">
                                        Please enter your postal code
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="d-none d-md-block">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="products.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Continue Shopping
                    </a>
                    <button type="submit" form="checkout-form" class="btn btn-primary px-4">
                        Place Order <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-light py-3">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i> Order Summary</h5>
                </div>
                <div class="card-body p-0">
                    <div class="p-3 border-bottom">
                        <?php foreach ($cart_items as $item): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0 me-3">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="rounded-2" 
                                     style="width: 64px; height: 64px; object-fit: cover;">
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                            </div>
                            <div class="text-end">
                                <div class="fw-semibold">₹<?php echo number_format($item['total'], 2); ?></div>
                                <small class="text-muted">₹<?php echo number_format($item['price'], 2); ?> each</small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="p-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span>₹<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Shipping</span>
                            <span class="text-success">Free</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fw-bold">Total</span>
                            <div class="d-flex flex-column align-items-end">
                                <span class="h5 mb-0">₹<?php echo number_format($total, 2); ?></span>
                                <small class="text-muted">Inclusive of all taxes</small>
                            </div>
                        </div>
                        
                        <button type="submit" form="checkout-form" class="btn btn-primary btn-lg w-100 d-md-none mb-3">
                            Place Order <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                        
                        <div class="text-center mt-4">
                            <div class="d-flex justify-content-center gap-3 mb-3">
                                <i class="fas fa-lock fa-2x text-primary"></i>
                                <i class="fas fa-shield-alt fa-2x text-primary"></i>
                                <i class="fas fa-credit-card fa-2x text-primary"></i>
                            </div>
                            <p class="small text-muted mb-0">
                                <i class="fas fa-lock me-1"></i> Your payment information is secure. 
                                We don't store your credit card details.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-control:focus, .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
    }
    .input-group-text {
        background-color: #f8f9fa;
        border-right: none;
    }
    .input-group .form-control {
        border-left: none;
    }
    .input-group .form-control:focus {
        border-left: 1px solid #0d6efd;
    }
    .card {
        border-radius: 0.75rem;
        overflow: hidden;
    }
    .card-header {
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .btn-primary {
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        transition: all 0.2s;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
    }
    .was-validated .form-control:invalid, .form-control.is-invalid {
        border-color: #dc3545;
        padding-right: calc(1.5em + 0.75rem);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }
</style>

<script>
// Form validation
(function () {
    'use strict'
    
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.querySelectorAll('.needs-validation')
    
    // Loop over them and prevent submission
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            
            form.classList.add('was-validated')
        }, false)
    })
})()

// Format phone number
const phoneInput = document.getElementById('phone');
if (phoneInput) {
    phoneInput.addEventListener('input', function(e) {
        let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
        e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
