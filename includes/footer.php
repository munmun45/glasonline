    </div>
    <!-- End of container -->

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add to cart functionality
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.getAttribute('data-id');
                
                // Show loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                this.disabled = true;
                
                // Send AJAX request to add to cart
                fetch('add-to-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId + '&quantity=1&action=add'
                })
                .then(response => response.json())
                .then(data => {
                    // Reset button state
                    this.innerHTML = originalText;
                    this.disabled = false;
                    
                    if (data.success) {
                        // Show success message
                        const toast = document.createElement('div');
                        toast.className = 'position-fixed bottom-0 end-0 m-3 p-3 bg-success text-white rounded-3';
                        toast.innerHTML = '<i class="fas fa-check-circle me-2"></i> Added to cart successfully!';
                        toast.style.zIndex = '9999';
                        document.body.appendChild(toast);
                        
                        // Remove toast after 3 seconds
                        setTimeout(() => {
                            toast.style.opacity = '0';
                            toast.style.transition = 'opacity 0.5s';
                            setTimeout(() => toast.remove(), 500);
                        }, 3000);
                        
                        // Update cart count
                        updateCartCount();
                    } else {
                        showAlert('Error: ' + (data.message || 'Could not add to cart'), 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.innerHTML = originalText;
                    this.disabled = false;
                    showAlert('An error occurred. Please try again.', 'danger');
                });
            });
        });
        
        // Function to update cart count
        function updateCartCount() {
            fetch('get-cart-count.php')
                .then(response => response.json())
                .then(data => {
                    const cartCounts = document.querySelectorAll('.cart-count');
                    cartCounts.forEach(span => {
                        span.textContent = data.count || '0';
                    });
                });
        }
        
        // Function to show alert messages
        function showAlert(message, type = 'success') {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
            alert.role = 'alert';
            alert.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
                    <span>${message}</span>
                </div>
            `;
            alert.style.zIndex = '9999';
            document.body.appendChild(alert);
            
            // Remove alert after 3 seconds
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            }, 3000);
        }
        
        // Initialize cart count on page load
        updateCartCount();
    });
</script>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <h5 class="text-uppercase mb-4" style="color: #4CAF50;">About GlasOnline</h5>
                    <p class="text-muted">Your premier destination for premium aquarium supplies and exotic fish. We're dedicated to providing the highest quality products for aquarists of all levels.</p>
                    <div class="mt-4">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="text-uppercase mb-4" style="color: #4CAF50;">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php" class="text-white text-decoration-none hover-text"><i class="fas fa-chevron-right me-2" style="color: #4CAF50;"></i>Home</a></li>
                        <li class="mb-2"><a href="products.php" class="text-white text-decoration-none hover-text"><i class="fas fa-chevron-right me-2" style="color: #4CAF50;"></i>Products</a></li>
                        <li class="mb-2"><a href="about.php" class="text-white text-decoration-none hover-text"><i class="fas fa-chevron-right me-2" style="color: #4CAF50;"></i>About Us</a></li>
                        <li class="mb-2"><a href="contact.php" class="text-white text-decoration-none hover-text"><i class="fas fa-chevron-right me-2" style="color: #4CAF50;"></i>Contact</a></li>
                        <li class="mb-2"><a href="privacy-policy.php" class="text-white text-decoration-none hover-text"><i class="fas fa-chevron-right me-2" style="color: #4CAF50;"></i>Privacy Policy</a></li>
                        <li class="mb-2"><a href="terms.php" class="text-white text-decoration-none hover-text"><i class="fas fa-chevron-right me-2" style="color: #4CAF50;"></i>Terms & Conditions</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="text-uppercase mb-4" style="color: #4CAF50;">Contact Us</h5>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <div class="d-flex">
                                <i class="fas fa-map-marker-alt me-3 mt-1" style="color: #4CAF50;"></i>
                                <span>123 Aqua Street, Marine City, MC 12345</span>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex">
                                <i class="fas fa-phone me-3 mt-1" style="color: #4CAF50;"></i>
                                <span>+1 (555) 123-4567</span>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex">
                                <i class="fas fa-envelope me-3 mt-1" style="color: #4CAF50;"></i>
                                <span>info@glasonline.com</span>
                            </div>
                        </li>
                        <li>
                            <div class="d-flex">
                                <i class="fas fa-clock me-3 mt-1" style="color: #4CAF50;"></i>
                                <span>Mon - Fri: 9:00 AM - 6:00 PM</span>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="text-uppercase mb-4" style="color: #4CAF50;">Newsletter</h5>
                    <p class="text-muted">Subscribe to our newsletter for the latest updates and offers.</p>
                    <form class="mb-3">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Your Email" aria-label="Your Email" required>
                            <button class="btn btn-success" type="submit">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                    <div class="payment-methods">
                        <h6 class="mb-2">We Accept:</h6>
                        <img src="assets/images/visa.png" alt="Visa" class="me-2" style="height: 30px;">
                        <img src="assets/images/mastercard.png" alt="Mastercard" class="me-2" style="height: 30px;">
                        <img src="assets/images/paypal.png" alt="PayPal" style="height: 30px;">
                    </div>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <span style="color: #4CAF50; font-weight: 600;">GlasOnline</span>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="#" class="text-white me-3"><i class="fab fa-cc-visa fa-lg"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-cc-mastercard fa-lg"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-cc-paypal fa-lg"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-cc-apple-pay fa-lg"></i></a>
                </div>
            </div>
        </div>
    </footer>
    
    <style>
        .hover-text:hover {
            color: #4CAF50 !important;
            padding-left: 5px;
            transition: all 0.3s ease;
        }
        
        footer a {
            transition: all 0.3s ease;
        }
        
        footer a:hover {
            color: #4CAF50 !important;
            text-decoration: none;
        }
        
        .btn-success {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }
        
        .btn-success:hover {
            background-color: #3e8e41;
            border-color: #3e8e41;
        }
        
        .payment-methods img {
            filter: grayscale(100%);
            opacity: 0.7;
            transition: all 0.3s ease;
        }
        
        .payment-methods img:hover {
            filter: grayscale(0%);
            opacity: 1;
        }
    </style>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
