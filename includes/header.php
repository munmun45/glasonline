<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlasOnline - Your Aquarium Shop</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1a1a1a;
            --secondary-color: #4CAF50;
            --text-light: #ffffff;
            --text-dark: #333333;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
            padding-top: 0;
            scroll-behavior: smooth;
        }
        
        /* Navigation */
        .navbar {
            background: var(--primary-color);
            padding: 0.8rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 800;
            color: var(--secondary-color) !important;
            font-size: 1.8rem;
            letter-spacing: 0.5px;
        }
        
        .nav-link {
            color: var(--text-light) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            margin: 0 0.2rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--secondary-color) !important;
        }
        
        /* Buttons */
        .btn-outline-light {
            border: 2px solid var(--secondary-color);
            color: var(--secondary-color);
            font-weight: 500;
            padding: 0.4rem 0.7rem;
            border-radius: 4px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .btn-outline-light:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            color: var(--text-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Search Form */
        .form-control {
            border-radius: 4px 0 0 4px;
            border: 1px solid #ced4da;
            padding: 0.5rem 1rem;
            height: 38px;
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }
        
        /* Cart Badge */
        .cart-count {
            position: relative;
            top: -12px;
            left: -6px;
            font-size: 0.7rem;
            background-color: var(--secondary-color) !important;
            padding: 0.25rem 0.4rem;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: var(--primary-color);
                padding: 1rem;
                margin-top: 0.5rem;
                border-radius: 4px;
            }
            
            .nav-item {
                margin: 0.3rem 0;
            }
            
            .d-flex {
                margin-top: 1rem;
            }
        }
    </style>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">GlasOnline</a>


            <div class="d-flex gap-2">

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" style="border: 2px solid var(--secondary-color);color: var(--secondary-color);font-weight: 500;padding: 0.4rem 0.7rem;border-radius: 4px;transition: all 0.3s ease;position: relative;">
                <span class="fas fa-search"></span>
            </button>
            <div class="d-flex d-md-none" style="margin-top: 0px;">
                    <a href="cart.php" class="btn btn-outline-light">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="badge cart-count" style="position: absolute; top: -7px; width: fit-content; left: 29px; background-color: red !important; border-radius: 4px;">
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
                

                <div class="d-flex" style="margin-top: 0px;">
                    <a href="cart.php" class="btn btn-outline-light">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="badge cart-count" style="position: absolute; top: -7px; width: fit-content; left: 29px; background-color: red !important; border-radius: 4px;">
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


            <!-- Cart Button -->
            
        </div>
    </nav>
    
    <!-- Add padding to the top of the main content to account for fixed navbar -->
    <div class="container" style="padding-top: 80px;">
