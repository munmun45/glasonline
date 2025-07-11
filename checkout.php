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
    $country = trim($_POST['country'] ?? '');
    
    $errors = [];
    
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (empty($phone)) $errors[] = 'Phone number is required';
    if (empty($address)) $errors[] = 'Address is required';
    if (empty($city)) $errors[] = 'City is required';
    if (empty($postal_code)) $errors[] = 'Postal code is required';
    if (empty($country)) $errors[] = 'Country is required';
    
    if (empty($errors)) {
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Calculate order total
            $subtotal = 0;
            $order_items = [];
            
            foreach ($_SESSION['cart'] as $product_id => $item) {
                // Get current stock and price
                $query = "SELECT price, stock_quantity FROM products WHERE id = ? FOR UPDATE";
                $stmt = $db->prepare($query);
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product || $product['stock_quantity'] < $item['quantity']) {
                    throw new Exception('Insufficient stock for ' . $item['name']);
                }
                
                $item_total = $product['price'] * $item['quantity'];
                $subtotal += $item_total;
                
                $order_items[] = [
                    'product_id' => $product_id,
                    'quantity' => $item['quantity'],
                    'price' => $product['price'],
                    'total' => $item_total
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
            $message .= $city . ", " . $postal_code . "\n";
            $message .= $country . "\n\n";
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

if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $query = "SELECT id, name, price, stock_quantity FROM products WHERE id IN ($placeholders)";
    $stmt = $db->prepare($query);
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']]['quantity'];
        $item_total = $product['price'] * $quantity;
        $subtotal += $item_total;
        
        $cart_items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'stock_quantity' => $product['stock_quantity'],
            'total' => $item_total
        ];
    }
}

$shipping = $subtotal > 0 ? 10.00 : 0;
$total = $subtotal + $shipping;
?>

<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-md-8">
        <h1 class="mb-4">Checkout</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Shipping Information</h5>
            </div>
            <div class="card-body">
                <form method="post" id="checkout-form">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required 
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Address *</label>
                            <input type="text" class="form-control" id="address" name="address" required
                                   value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="city" class="form-label">City *</label>
                            <input type="text" class="form-control" id="city" name="city" required
                                   value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="postal_code" class="form-label">Postal Code *</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" required
                                   value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="country" class="form-label">Country *</label>
                            <select class="form-select" id="country" name="country" required>
                                <option value="">Select Country</option>
                                <option value="United States" <?php echo (isset($_POST['country']) && $_POST['country'] === 'United States') ? 'selected' : ''; ?>>United States</option>
                                <option value="Canada" <?php echo (isset($_POST['country']) && $_POST['country'] === 'Canada') ? 'selected' : ''; ?>>Canada</option>
                                <option value="United Kingdom" <?php echo (isset($_POST['country']) && $_POST['country'] === 'United Kingdom') ? 'selected' : ''; ?>>United Kingdom</option>
                                <!-- Add more countries as needed -->
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Order Summary</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Shipping</span>
                    <span>$<?php echo number_format($shipping, 2); ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <strong>Total</strong>
                    <strong>$<?php echo number_format($total, 2); ?></strong>
                </div>
                
                <button type="submit" form="checkout-form" class="btn btn-primary w-100">
                    Place Order
                </button>
                
                <div class="mt-3 text-center">
                    <img src="assets/images/payment-methods.png" alt="Payment Methods" class="img-fluid" style="max-width: 250px;">
                    <p class="small text-muted mt-2">Your payment information is secure. We don't store your credit card details.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
