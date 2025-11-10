<?php include 'includes/header.php'; ?>
<style>
    .thank-you {
        text-align: center;
        padding: 60px 20px;
    }
    
    .thank-you h1 {
        color: #88A71C;
        font-size: 36px;
        margin-bottom: 20px;
    }
    
    .thank-you p {
        color: #666;
        font-size: 18px;
        margin-bottom: 15px;
    }
    
    .thank-you a {
        color: #88A71C;
        text-decoration: none;
        font-weight: 500;
    }
    
    .thank-you a:hover {
        text-decoration: underline;
    }
</style>

<main>
    <div class="content-card">
        <div class="thank-you">
            <h1>Thank You!</h1>
            <p>Your order has been placed successfully.</p>
            <p>Check admin panel to view orders (demo).</p>
            <p style="margin-top: 30px;">
                <a href="index.php" class="btn">Back to Home</a>
            </p>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
