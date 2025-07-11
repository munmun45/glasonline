<?php
session_start();
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle update cart request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    if (isset($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $product_id => $quantity) {
            $product_id = (int)$product_id;
            $quantity = (int)$quantity;
            
            // Find the product in cart
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ((isset($item['product_id']) && $item['product_id'] == $product_id) || 
                    (isset($item['id']) && $item['id'] == $product_id)) {
                    
                    if ($quantity > 0) {
                        // Check stock before updating
                        $query = "SELECT stock_quantity FROM products WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$product_id]);
                        $product = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($product && $product['stock_quantity'] >= $quantity) {
                            $item['quantity'] = $quantity;
                            $found = true;
                        } else {
                            $_SESSION['error'] = 'Insufficient stock for one or more products.';
                        }
                    } else {
                        // Remove item if quantity is 0 or less
                        $_SESSION['cart'] = array_filter($_SESSION['cart'], function($i) use ($product_id) {
                            return (isset($i['product_id']) ? $i['product_id'] : $i['id']) != $product_id;
                        });
                        $found = true;
                    }
                    break;
                }
            }
            
            // If product not found in cart but has quantity > 0, add it
            if (!$found && $quantity > 0) {
                $query = "SELECT id, name, price, stock_quantity FROM products WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product && $product['stock_quantity'] >= $quantity) {
                    $_SESSION['cart'][] = [
                        'product_id' => $product['id'],
                        'quantity' => $quantity
                    ];
                } else {
                    $_SESSION['error'] = 'Insufficient stock for one or more products.';
                }
            }
        }
        
        // Re-index array to prevent issues with array_filter
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Handle remove from cart request
if (isset($_GET['remove'])) {
    $product_id = (int)$_GET['remove'];
    $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id) {
        return $item['product_id'] !== $product_id;
    });
    $_SESSION['success'] = 'Product removed from cart.';
    header('Location: cart.php');
    exit;
}

// Function to convert USD to INR
function usdToInr($usd) {
    return $usd * 83.5; // Current conversion rate
}

// Calculate cart totals
$subtotal = 0;
$cart_items = [];

if (!empty($_SESSION['cart'])) {
    // Get product details for all items in cart
    $product_ids = array_column($_SESSION['cart'], 'product_id');
    if (!empty($product_ids)) {
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        $query = "SELECT id, name, price, stock_quantity, image_url FROM products WHERE id IN ($placeholders)";
        $stmt = $db->prepare($query);
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create a lookup array for products
        $product_lookup = [];
        foreach ($products as $product) {
            $product_lookup[$product['id']] = $product;
        }
        
        // Prepare cart items with product details
        foreach ($_SESSION['cart'] as $cart_item) {
            if (isset($product_lookup[$cart_item['product_id']])) {
                $product = $product_lookup[$cart_item['product_id']];
                $quantity = $cart_item['quantity'];
                $price_inr = usdToInr($product['price']);
                $item_total = $price_inr * $quantity;
                $subtotal += $item_total;
                
                $cart_items[] = [
                    'id' => $product['id'],
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
}

$shipping = 0; // Free shipping
$total = $subtotal; // Total is just the subtotal since shipping is free
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Shopping Cart</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']); 
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']); 
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div class="alert alert-info">
            <div class="d-flex align-items-center">
                <i class="fas fa-shopping-cart me-2"></i>
                <div>
                    <p class="mb-0">Your cart is empty.</p>
                    <a href="products.php" class="alert-link">Continue shopping</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <form action="cart.php" method="post" id="cart-form">
            <div class="table-responsive">
                <table class="table table-hover align-middle d-none d-md-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50%;">Product</th>
                            <th class="text-end" style="width: 15%;">Price</th>
                            <th style="width: 20%;">Quantity</th>
                            <th class="text-end" style="width: 10%;">Total</th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="img-thumbnail me-3" 
                                         style="width: 80px; height: 80px; object-fit: cover;">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted">SKU: <?php echo $item['id']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">₹<?php echo number_format($item['price_inr'], 2); ?></td>
                            <td>
                                <div class="input-group input-group-sm" style="max-width: 120px;">
                                    <input type="number" 
                                           name="quantities[<?php echo $item['id']; ?>]" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" 
                                           max="<?php echo $item['stock_quantity']; ?>" 
                                           class="form-control text-center" 
                                           onchange="document.getElementById('update-cart-btn').classList.remove('d-none')">
                                </div>
                                <small class="text-muted">Max: <?php echo $item['stock_quantity']; ?> available</small>
                            </td>
                            <td class="text-end">₹<?php echo number_format($item['total'], 2); ?></td>
                            <td class="text-center">
                                <a href="cart.php?remove=<?php echo $item['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Are you sure you want to remove this item from your cart?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Mobile View -->
                <div class="d-md-none">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex">
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="img-thumbnail me-2" 
                                         style="width: 80px; height: 80px; object-fit: cover;">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <p class="mb-1 text-muted small">₹<?php echo number_format($item['price_inr'], 2); ?> each</p>
                                        <p class="mb-1 text-muted small">SKU: <?php echo $item['id']; ?></p>
                                    </div>
                                </div>
                                <a href="cart.php?remove=<?php echo $item['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Are you sure you want to remove this item from your cart?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="w-50 me-2">
                                    <label class="form-label small mb-1">Quantity</label>
                                    <input type="number" 
                                           name="quantities[<?php echo $item['id']; ?>]" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" 
                                           max="<?php echo $item['stock_quantity']; ?>" 
                                           class="form-control form-control-sm" 
                                           onchange="document.getElementById('update-cart-btn').classList.remove('d-none')">
                                    <small class="text-muted">Max: <?php echo $item['stock_quantity']; ?></small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold">₹<?php echo number_format($item['total'], 2); ?></div>
                                    <small class="text-muted">Total</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                    </tbody>
                </table>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-8">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                        <a href="products.php" class="btn btn-outline-secondary mb-3 mb-md-0">
                            <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                        </a>
                        <button type="submit" name="update_cart" class="btn btn-outline-primary d-none" id="update-cart-btn">
                            <i class="fas fa-sync-alt me-2"></i> Update Cart
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Order Summary</h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal:</span>
                                <span>₹<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Shipping:</span>
                                <span class="text-success">Free</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold mb-4">
                                <span>Total:</span>
                                <span class="h5 mb-0">₹<?php echo number_format($total, 2); ?></span>
                            </div>
                            <a href="checkout.php" class="btn btn-primary w-100 py-2" id="checkout-btn">
                                <i class="fas fa-lock me-2"></i> Proceed to Checkout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <script>
            // Show update button when quantity changes
            document.querySelectorAll('input[type="number"]').forEach(input => {
                input.addEventListener('change', function() {
                    document.getElementById('update-cart-btn').classList.remove('d-none');
                    
                    // Auto-submit on mobile after delay
                    if (window.innerWidth < 768) {
                        clearTimeout(window.updateTimeout);
                        window.updateTimeout = setTimeout(() => {
                            document.getElementById('update-cart-btn').click();
                        }, 1000);
                    }
                });
            });
            
            // Handle checkout button click
            document.getElementById('checkout-btn').addEventListener('click', function(e) {
                // First submit the cart form to save any changes
                const updateBtn = document.getElementById('update-cart-btn');
                if (!updateBtn.classList.contains('d-none')) {
                    e.preventDefault();
                    document.getElementById('update-cart-btn').click();
                    // After the form submits, it will redirect to checkout.php
                    setTimeout(() => {
                        window.location.href = 'checkout.php';
                    }, 500);
                }
            });
        </script>
        
        <style>
            /* Improve mobile experience */
            @media (max-width: 767.98px) {
                .card {
                    border-radius: 0.5rem;
                    overflow: hidden;
                }
                .img-thumbnail {
                    padding: 0.25rem;
                    border-radius: 0.375rem;
                }
                .btn {
                    padding: 0.4rem 0.8rem;
                    font-size: 0.9rem;
                }
                .form-control {
                    padding: 0.4rem 0.5rem;
                    font-size: 0.9rem;
                }
                .card-body {
                    padding: 1rem;
                }
            }
        </style>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
