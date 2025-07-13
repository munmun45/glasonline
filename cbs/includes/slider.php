<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="col-md-3 col-lg-2 d-md-block sidebar">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage == 'products.php') ? 'active' : '' ?>" href="products.php">
                    <i class="fas fa-box me-2"></i> Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage == 'orders.php') ? 'active' : '' ?>" href="orders.php">
                    <i class="fas fa-shopping-cart me-2"></i> Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage == 'categories.php') ? 'active' : '' ?>" href="categories.php">
                    <i class="fas fa-tags me-2"></i> Categories
                </a>
            </li>
        </ul>
    </div>
</div>
