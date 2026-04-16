<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoryn – Feedback</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #0D0D0D; color: #fff; min-height: 100vh; }

        .feedback-container {
            max-width: 800px;
            margin: 32px auto;
            padding: 32px;
            background: #1F1F1F;
            border: 1px solid #2E2E2E;
            border-radius: 16px;
        }

        .feedback-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .feedback-header h1 {
            color: #D4AF37;
            font-size: 1.5rem;
            margin-bottom: 8px;
        }
        .feedback-header p { color: #888; font-size: 0.9rem; }

        .order-info {
            background: #121212;
            border: 1px solid #2E2E2E;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            color: #B0B0B0;
            font-size: 14px;
        }

        .feedback-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .feedback-product-card {
            background: #121212;
            border: 1px solid #2E2E2E;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        .feedback-product-card:hover { border-color: rgba(212,175,55,0.2); transform: translateY(-3px); }
        .feedback-product-card img { width: 90px; height: 90px; object-fit: cover; border-radius: 12px; margin-bottom: 12px; }
        .feedback-product-card p { color: #D4AF37; font-weight: 600; margin: 8px 0; font-size: 0.9rem; }

        .feedback-rating-container { margin-top: 10px; }
        .feedback-rating { display: flex; flex-direction: row-reverse; justify-content: center; gap: 4px; }
        .feedback-rating input[type="radio"] { display: none; }
        .feedback-rating label { color: #2E2E2E; font-size: 22px; cursor: pointer; transition: all 0.2s; }
        .feedback-rating label:before { content: '★'; }
        .feedback-rating input:checked ~ label { color: #FFD700; }
        .feedback-rating:hover label { color: #2E2E2E; }
        .feedback-rating label:hover, .feedback-rating label:hover ~ label { color: #FFD700; }

        .feedback-comment { margin-top: 24px; }
        .feedback-comment label { display: block; margin-bottom: 8px; font-weight: 500; color: #D4AF37; font-size: 14px; }
        .feedback-comment textarea {
            width: 100%;
            padding: 14px;
            border: 1px solid #2E2E2E;
            border-radius: 12px;
            resize: vertical;
            min-height: 100px;
            background: #121212;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: border-color 0.3s;
            outline: none;
        }
        .feedback-comment textarea:focus { border-color: #D4AF37; box-shadow: 0 0 0 3px rgba(212,175,55,0.1); }
        .feedback-comment textarea::placeholder { color: #666; }

        .submit-feedback-btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #D4AF37, #B8921E);
            color: #0D0D0D;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            margin-top: 24px;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }
        .submit-feedback-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(212,175,55,0.3); }

        /* SweetAlert dark theme */
        .swal2-popup { font-family: 'Poppins', sans-serif !important; background: #1F1F1F !important; border: 1px solid #2E2E2E !important; border-radius: 16px !important; color: #fff !important; }
        .swal2-title { color: #D4AF37 !important; }
        .swal2-html-container { color: #B0B0B0 !important; }
        .swal2-confirm { background: linear-gradient(135deg, #F4D26B, #C99B2A) !important; color: #0D0D0D !important; border-radius: 10px !important; }
    </style>
</head>
<body>
    <?php include("../navigation/navbar.php"); ?>

    <div style="padding: 24px;">
        <div class="feedback-container">
            <div class="feedback-header">
                <div style="width:56px;height:56px;background:rgba(212,175,55,0.1);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <i class="fas fa-star" style="color:#D4AF37;font-size:1.3rem;"></i>
                </div>
                <h1>Order Feedback</h1>
                <p>We appreciate your honest feedback about your recent order</p>
            </div>
            <div class="feedback-form-container" id="feedbackForm"></div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const orderId = urlParams.get('order_id');

        if (!orderId) {
            Swal.fire({ title: 'Error', text: 'No order ID provided', icon: 'error', confirmButtonColor: '#D4AF37' })
            .then(() => window.location.href = 'home.php');
            return;
        }

        fetch('../backend/order_functions.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `action=check_feedback_exists&order_id=${orderId}` })
        .then(r => r.json()).then(data => {
            if (data.has_feedback) {
                Swal.fire({ title: 'Already Submitted', text: 'You already submitted feedback for this order.', icon: 'info', confirmButtonColor: '#D4AF37' })
                .then(() => window.location.href = 'home.php');
                return;
            }
            loadOrderDetails(orderId);
        }).catch(e => {
            Swal.fire({ title: 'Error', text: 'Failed to check feedback status', icon: 'error', confirmButtonColor: '#D4AF37' })
            .then(() => window.location.href = 'home.php');
        });
    });

    function loadOrderDetails(orderId) {
        fetch('../backend/order_manager.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `action=get_order&order_id=${orderId}` })
        .then(r => r.json()).then(data => {
            if (data.error || !data.items || !data.items.length) {
                Swal.fire({ title: 'Error', text: 'Failed to load order details', icon: 'error', confirmButtonColor: '#D4AF37' })
                .then(() => window.location.href = 'home.php');
                return;
            }
            const form = document.getElementById('feedbackForm');
            let html = `<div class="order-info"><i class="far fa-calendar" style="margin-right:6px;"></i>${new Date().toLocaleString()}</div><div class="feedback-products">`;
            data.items.forEach(item => {
                html += `
                    <div class="feedback-product-card">
                        <img src="../${item.image_path}" alt="${item.product_name}" onerror="this.src='../assets/zoryn/zoryn_logo.jpg';">
                        <p>${item.product_name}</p>
                        <div class="feedback-rating-container">
                            <div class="feedback-rating" id="rating-${item.product_id}">
                                ${[5,4,3,2,1].map(v => `<input type="radio" name="rating-${item.product_id}" value="${v}" id="r-${item.product_id}-${v}"><label for="r-${item.product_id}-${v}"></label>`).join('')}
                            </div>
                        </div>
                    </div>`;
            });
            html += `</div>
                <div class="feedback-comment">
                    <label for="feedback-comment"><i class="fas fa-comment" style="margin-right:6px;"></i>Additional Comments</label>
                    <textarea id="feedback-comment" rows="4" placeholder="Share your experience with us..."></textarea>
                </div>
                <button class="submit-feedback-btn" onclick="submitFeedback(${orderId})"><i class="fas fa-paper-plane" style="margin-right:8px;"></i>Submit Feedback</button>`;
            form.innerHTML = html;
        }).catch(e => {
            Swal.fire({ title: 'Error', text: 'Failed to load order details', icon: 'error', confirmButtonColor: '#D4AF37' })
            .then(() => window.location.href = 'home.php');
        });
    }

    function submitFeedback(orderId) {
        const ratings = {};
        document.querySelectorAll('.feedback-product-card').forEach(card => {
            const pid = card.querySelector('.feedback-rating').id.split('-')[1];
            const rating = document.querySelector(`input[name="rating-${pid}"]:checked`)?.value;
            if (rating) ratings[pid] = parseInt(rating);
        });
        const comment = document.getElementById('feedback-comment').value;
        if (!Object.keys(ratings).length) { Swal.fire({ title: 'Missing Rating', text: 'Please rate at least one product', icon: 'warning', confirmButtonColor: '#D4AF37' }); return; }

        fetch('../backend/order_manager.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `action=save_feedback&order_id=${orderId}&ratings=${JSON.stringify(ratings)}&comment=${encodeURIComponent(comment)}` })
        .then(r => r.json()).then(data => {
            if (data.error) Swal.fire({ title: 'Error', text: data.error, icon: 'error', confirmButtonColor: '#D4AF37' });
            else Swal.fire({ title: 'Thank You!', text: 'Your feedback has been submitted.', icon: 'success', confirmButtonColor: '#D4AF37' }).then(() => window.location.href = 'home.php');
        }).catch(() => Swal.fire({ title: 'Error', text: 'Failed to submit feedback', icon: 'error', confirmButtonColor: '#D4AF37' }));
    }
    </script>
</body>
</html>
