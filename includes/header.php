<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        /* Make sure the body has no margin that could cause scrolling */
        body {
            padding-top: 0;
        }
        
        /* Add smooth scrolling to all links */
        html {
            scroll-behavior: smooth;
        }
    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlasOnline - Your Aquarium Shop</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">GlasOnline</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Navigation Links -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                </ul>

                <!-- Search Form -->
                <form class="d-flex me-3" action="products.php" method="get">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search products..." aria-label="Search products" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>

                <!-- Cart Button -->
                <div class="d-flex">
                    <a href="cart.php" class="btn btn-outline-light">
                        <i class="fas fa-shopping-cart"></i> Cart
                        <span class="badge bg-danger cart-count">
                            <?php 
                            // Display cart count if cart exists in session
                            session_start();
                            if(isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                                echo array_sum(array_column($_SESSION['cart'], 'quantity'));
                            } else {
                                echo '0';
                            }
                            ?>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Add padding to the top of the main content to account for fixed navbar -->
    <div class="container" style="padding-top: 80px;">
