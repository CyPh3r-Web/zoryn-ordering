<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn - Order Feedback</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="css/feedback.css">
    <link rel="stylesheet" href="css/rookie.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <!-- SweetAlert2 CSS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include("../navigation/navbar.php");?>
    
    <div class="feedback-container">
        <div class="feedback-header">
            <h1>Order Feedback</h1>
            <p>We appreciate your honest feedback about your recent order</p>
        </div>
        
        <div class="feedback-form-container" id="feedbackForm">
            <!-- Feedback form will be loaded here -->
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get order ID from URL
            const urlParams = new URLSearchParams(window.location.search);
            const orderId = urlParams.get('order_id');
            
            if (!orderId) {
                Swal.fire({
                    title: 'Error',
                    text: 'No order ID provided',
                    icon: 'error',
                    confirmButtonColor: '#5d4037'
                }).then(() => {
                    window.location.href = 'home.php';
                });
                return;
            }

            // Check if feedback already exists
            fetch('../backend/order_functions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=check_feedback_exists&order_id=${orderId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.has_feedback) {
                    Swal.fire({
                        title: 'Feedback Already Submitted',
                        text: 'You have already submitted feedback for this order.',
                        icon: 'info',
                        confirmButtonColor: '#5d4037'
                    }).then(() => {
                        window.location.href = 'home.php';
                    });
                    return;
                }
                // Load order details and show feedback form
                loadOrderDetails(orderId);
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to check feedback status',
                    icon: 'error',
                    confirmButtonColor: '#5d4037'
                }).then(() => {
                    window.location.href = 'home.php';
                });
            });
        });

        function loadOrderDetails(orderId) {
            fetch('../backend/order_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_order&order_id=${orderId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.error || !data.items || data.items.length === 0) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to load order details',
                        icon: 'error',
                        confirmButtonColor: '#5d4037'
                    }).then(() => {
                        window.location.href = 'home.php';
                    });
                    return;
                }

                const feedbackForm = document.getElementById('feedbackForm');
                let feedbackHTML = `
                    <div class="order-info">
                        <p>Date: ${new Date().toLocaleString()}</p>
                    </div>
                    <div class="feedback-products">
                `;

                data.items.forEach(item => {
                    feedbackHTML += `
                        <div class="feedback-product-card">
                            <img src="../${item.image_path}" 
                                 alt="${item.product_name}"
                                 onerror="this.onerror=null; this.src='../assets/zoryn/logo.png';">
                            <p>${item.product_name}</p>
                            <div class="feedback-rating-container">
                                <div class="feedback-rating" id="rating-${item.product_id}">
                                    <input type="radio" name="rating-${item.product_id}" value="5" id="r-${item.product_id}-5">
                                    <label for="r-${item.product_id}-5"></label>
                                    <input type="radio" name="rating-${item.product_id}" value="4" id="r-${item.product_id}-4">
                                    <label for="r-${item.product_id}-4"></label>
                                    <input type="radio" name="rating-${item.product_id}" value="3" id="r-${item.product_id}-3">
                                    <label for="r-${item.product_id}-3"></label>
                                    <input type="radio" name="rating-${item.product_id}" value="2" id="r-${item.product_id}-2">
                                    <label for="r-${item.product_id}-2"></label>
                                    <input type="radio" name="rating-${item.product_id}" value="1" id="r-${item.product_id}-1">
                                    <label for="r-${item.product_id}-1"></label>
                                </div>
                            </div>
                        </div>
                    `;
                });

                feedbackHTML += `
                    </div>
                    <div class="feedback-comment">
                        <label for="feedback-comment">Additional Comments</label>
                        <textarea id="feedback-comment" rows="4" placeholder="Share your experience with us..."></textarea>
                    </div>
                    <button class="submit-feedback-btn" onclick="submitFeedback(${orderId})">Submit Feedback</button>
                `;

                feedbackForm.innerHTML = feedbackHTML;
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to load order details',
                    icon: 'error',
                    confirmButtonColor: '#5d4037'
                }).then(() => {
                    window.location.href = 'home.php';
                });
            });
        }

        function submitFeedback(orderId) {
            const ratings = {};
            const items = document.querySelectorAll('.feedback-product-card');
            
            items.forEach(item => {
                const productId = item.querySelector('.feedback-rating').id.split('-')[1];
                const rating = document.querySelector(`input[name="rating-${productId}"]:checked`)?.value;
                if (rating) {
                    ratings[productId] = parseInt(rating);
                }
            });

            const comment = document.getElementById('feedback-comment').value;

            if (Object.keys(ratings).length === 0) {
                Swal.fire({
                    title: 'Error',
                    text: 'Please rate at least one product',
                    icon: 'error',
                    confirmButtonColor: '#5d4037'
                });
                return;
            }

            fetch('../backend/order_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=save_feedback&order_id=${orderId}&ratings=${JSON.stringify(ratings)}&comment=${encodeURIComponent(comment)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    Swal.fire({
                        title: 'Error',
                        text: data.error,
                        icon: 'error',
                        confirmButtonColor: '#5d4037'
                    });
                } else {
                    Swal.fire({
                        title: 'Thank You!',
                        text: 'Your feedback has been submitted successfully.',
                        icon: 'success',
                        confirmButtonColor: '#5d4037'
                    }).then(() => {
                        window.location.href = 'home.php';
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to submit feedback',
                    icon: 'error',
                    confirmButtonColor: '#5d4037'
                });
            });
        }
    </script>
</body>
</html>