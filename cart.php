<?php
session_start();
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Get product details
    $query = "SELECT id, name, price, stock_quantity FROM products WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product && $product['stock_quantity'] >= $quantity) {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity
            ];
        }
        $_SESSION['success'] = 'Product added to cart successfully!';
    } else {
        $_SESSION['error'] = 'Product not available or insufficient stock.';
    }
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Handle update cart request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $product_id => $quantity) {
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;
        
        if ($quantity > 0) {
            // Check stock before updating
            $query = "SELECT stock_quantity FROM products WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product && $product['stock_quantity'] >= $quantity) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            } else {
                $_SESSION['error'] = 'Insufficient stock for one or more products.';
            }
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
    }
    header('Location: cart.php');
    exit;
}

// Handle remove from cart request
if (isset($_GET['remove'])) {
    $product_id = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['success'] = 'Product removed from cart.';
    }
    header('Location: cart.php');
    exit;
}

// Calculate cart totals
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

$shipping = $subtotal > 0 ? 10.00 : 0; // Example shipping cost
$total = $subtotal + $shipping;
?>

<?php include 'includes/header.php'; ?>

<h1 class="mb-4">Shopping Cart</h1>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<?php if (empty($cart_items)): ?>
    <div class="alert alert-info">
        Your cart is empty. <a href="products.php" class="alert-link">Continue shopping</a>.
    </div>
<?php else: ?>
    <form action="cart.php" method="post">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td>
                            <input type="number" 
                                   name="quantities[<?php echo $item['id']; ?>]" 
                                   value="<?php echo $item['quantity']; ?>" 
                                   min="1" 
                                   max="<?php echo $item['stock_quantity']; ?>" 
                                   class="form-control d-inline-block" 
                                   style="width: 80px;">
                        </td>
                        <td>$<?php echo number_format($item['total'], 2); ?></td>
                        <td>
                            <a href="cart.php?remove=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure you want to remove this item?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Subtotal</strong></td>
                        <td colspan="2">$<?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Shipping</strong></td>
                        <td colspan="2">$<?php echo number_format($shipping, 2); ?></td>
                    </tr>
                    <tr class="table-active">
                        <td colspan="3" class="text-end"><strong>Total</strong></td>
                        <td colspan="2"><strong>$<?php echo number_format($total, 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="d-flex justify-content-between">
            <a href="products.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Continue Shopping
            </a>
            <div>
                <button type="submit" name="update_cart" class="btn btn-outline-primary me-2">
                    <i class="fas fa-sync-alt"></i> Update Cart
                </button>
                <a href="checkout.php" class="btn btn-primary">
                    Proceed to Checkout <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </form>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
