<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aveelora - Beautiful Accessories</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Calibri', 'Roboto', 'Poppins', sans-serif;
            background-color: #f5f5dc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles */
        header {
            background-color: #d3d3d3;
            padding: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: Black;
            text-decoration: none;
        }
        
        .logo:hover {
            color: #88A71C;
        }
        
        nav {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        nav a {
            color: Black;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-weight: 500;
        }
        
        nav a:hover {
            background-color: rgba(0,0,0,0.1);
        }
        
        .user {
            color: Black;
            font-weight: 500;
            padding: 8px 16px;
        }
        
        /* Main Content */
        main {
            flex: 1;
            max-width: 1200px;
            width: 100%;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        /* Buttons */
        .btn {
            background-color: #88A71C;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s, transform 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .btn:hover {
            background-color: #7A951A;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        /* Content Card */
        .content-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: Black;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            font-family: inherit;
            max-width: 500px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #88A71C;
            box-shadow: 0 0 0 2px rgba(136, 167, 28, 0.2);
        }
        
        /* Cart Count Badge */
        .cart-count {
            background-color: #88A71C;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 12px;
            margin-left: 5px;
            display: none;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            nav {
                width: 100%;
                margin-top: 15px;
            }
            
            main {
                padding: 0 15px;
            }
        }
    </style>
    <script>
        // Initialize cart count on header load
        function initCartCount() {
            try {
                const cart = JSON.parse(localStorage.getItem('cart')) || [];
                const count = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
                const cartCountElements = document.querySelectorAll('.cart-count');
                cartCountElements.forEach(el => {
                    el.textContent = count;
                    if (count > 0) {
                        el.style.display = 'inline-block';
                    } else {
                        el.style.display = 'none';
                    }
                });
            } catch (e) {
                // localStorage might not be available
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCartCount);
        } else {
            initCartCount();
        }
    </script>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="header-content">
                <a href="index.php" class="logo">Aveelora</a>
                <nav>
                    <a href="index.php">Home</a>
                    <a href="cart.php">Cart <span class="cart-count" style="background-color: #88A71C; color: white; border-radius: 50%; padding: 2px 8px; font-size: 12px; margin-left: 5px; display: none;">0</span></a>
                    <?php if(isset($_SESSION['user'])): ?>
                        <span class="user">Hello, <?=htmlspecialchars($_SESSION['user']['name'])?></span>
                        <a href="logout.php">Logout</a>
                        <?php if($_SESSION['user']['role']==='admin'): ?><a href="admin/dashboard.php">Admin</a><?php endif; ?>
                    <?php else: ?>
                        <a href="login.php">Login</a>
                        <a href="register.php">Register</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>
