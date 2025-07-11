<?php
session_start();
header('Content-Type: application/json');

// Check if required parameters are set
if (!isset($_POST['product_id']) || !isset($_POST['quantity']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];
$action = $_POST['action'];

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get product details from database
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, name, price, stock_quantity FROM products WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Handle different cart actions
switch ($action) {
    case 'add':
        // Check if product already in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] === $product_id) {
                // Check stock before adding
                if (($item['quantity'] + $quantity) > $product['stock_quantity']) {
                    echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
                    exit;
                }
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        
        // If product not in cart, add it
        if (!$found) {
            // Check stock
            if ($quantity > $product['stock_quantity']) {
                echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
                exit;
            }
            
            $_SESSION['cart'][] = [
                'product_id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity
            ];
        }
        break;
        
    // You can add more actions like 'update' or 'remove' as needed
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

// Return success response
echo json_encode([
    'success' => true,
    'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity')),
    'message' => 'Product added to cart successfully'
]);
?>
