<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get total products
$query = "SELECT COUNT(*) as total_products FROM products";
$stmt = $db->query($query);
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

// Get total orders
$query = "SELECT COUNT(*) as total_orders FROM orders";
$stmt = $db->query($query);
$total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];

// Get total revenue
$query = "SELECT COALESCE(SUM(total_amount), 0) as total_revenue FROM orders WHERE status = 'completed'";
$stmt = $db->query($query);
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];

// Get recent orders
$query = "SELECT o.*, 
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
          (SELECT SUM(quantity * price) FROM order_items WHERE order_id = o.id) as order_total
          FROM orders o 
          ORDER BY o.created_at DESC 
          LIMIT 5";
$stmt = $db->query($query);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/slider.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                            <i class="fas fa-calendar me-1"></i> This week
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body py-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase mb-0">Total Revenue</h6>
                                        <h2 class="mt-2 mb-0">₹<?php echo number_format($total_revenue, 2); ?></h2>
                                    </div>
                                    <div class="card-icon">
                                        <i class="fas fa-dollar-sign"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between small bg-primary bg-opacity-75">
                                <a class="text-white text-decoration-none" href="orders.php">View Details</a>
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body py-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase mb-0">Total Orders</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $total_orders; ?></h2>
                                    </div>
                                    <div class="card-icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between small bg-success bg-opacity-75">
                                <a class="text-white text-decoration-none" href="orders.php">View Details</a>
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card bg-warning text-dark h-100">
                            <div class="card-body py-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase mb-0">Total Products</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $total_products; ?></h2>
                                    </div>
                                    <div class="card-icon">
                                        <i class="fas fa-box"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between small bg-warning bg-opacity-75">
                                <a class="text-dark text-decoration-none" href="products.php">View Details</a>
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Orders</h5>
                        <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_orders)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">No recent orders found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_orders as $order): 
                                            $status_class = [
                                                'pending' => 'bg-warning',
                                                'processing' => 'bg-info',
                                                'completed' => 'bg-success',
                                                'cancelled' => 'bg-danger'
                                            ][$order['status']] ?? 'bg-secondary';
                                        ?>
                                            <tr>
                                                <td>#<?php echo $order['id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                                <td><?php echo $order['item_count']; ?> items</td>
                                                <td>₹<?php echo number_format($order['order_total'], 2); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $status_class; ?> status-badge">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="add-product.php" class="btn btn-outline-primary text-start">
                                        <i class="fas fa-plus-circle me-2"></i> Add New Product
                                    </a>
                                    <a href="categories.php" class="btn btn-outline-secondary text-start">
                                        <i class="fas fa-tags me-2"></i> Manage Categories
                                    </a>
                                    <a href="orders.php?status=pending" class="btn btn-outline-warning text-start">
                                        <i class="fas fa-clock me-2"></i> View Pending Orders
                                    </a>
                                    <a href="products.php?stock=low" class="btn btn-outline-danger text-start">
                                        <i class="fas fa-exclamation-triangle me-2"></i> View Low Stock Items
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">System Information</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        PHP Version
                                        <span class="badge bg-primary rounded-pill"><?php echo phpversion(); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        MySQL Version
                                        <span class="badge bg-primary rounded-pill">
                                            <?php 
                                                $stmt = $db->query('SELECT VERSION() as version');
                                                echo $stmt->fetch(PDO::FETCH_ASSOC)['version'];
                                            ?>
                                        </span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        Server Software
                                        <span class="badge bg-primary rounded-pill"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        Server Name
                                        <span class="badge bg-primary rounded-pill"><?php echo $_SERVER['SERVER_NAME']; ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
