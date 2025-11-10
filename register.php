<?php
require 'includes/db.php';
session_start();
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name = $_POST['name'] ?? '';
  $email = $_POST['email'] ?? '';
  $pw = $_POST['password'] ?? '';
  if($name && $email && $pw){
    $stmt = $conn->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $stmt->bind_param('s',$email);
    $stmt->execute();
    if($stmt->get_result()->num_rows > 0){
      $msg='Email already registered';
    } else {
      $hashed = password_hash($pw, PASSWORD_DEFAULT);
      $stmt = $conn->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
      $role = 'user';
      $stmt->bind_param('ssss',$name,$email,$hashed,$role);
      if($stmt->execute()){
        $msg='Registration successful! <a href="login.php">Login here</a>';
      } else {
        $msg='Registration failed';
      }
    }
  } else {
    $msg='Please fill all fields';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Aveelora</title>
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
            color: #333;
            text-decoration: none;
        }
        
        nav {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        nav a {
            color: #333;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-weight: 500;
        }
        
        nav a:hover {
            background-color: rgba(0,0,0,0.1);
        }
        
        /* Main Content */
        main {
            flex: 1;
            max-width: 500px;
            width: 100%;
            margin: 50px auto;
            padding: 0 20px;
        }
        
        .content-card {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .content-card h1 {
            color: #333;
            margin-bottom: 25px;
            font-size: 28px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            font-family: inherit;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #88A71C;
            box-shadow: 0 0 0 2px rgba(136, 167, 28, 0.2);
        }
        
        .btn {
            background-color: #88A71C;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            transition: background-color 0.3s, transform 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-top: 10px;
        }
        
        .btn:hover {
            background-color: #7A951A;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .error {
            color: #d32f2f;
            padding: 12px;
            background-color: #ffebee;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success {
            color: #88A71C;
            padding: 12px;
            background-color: #f1f8e9;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .login-link a {
            color: #88A71C;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        /* Footer */
        footer {
            background-color: #000000;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: auto;
            border-top: 1px solid #d3d3d3;
        }
        
        footer p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            main {
                margin: 30px auto;
            }
            
            .content-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="header-content">
                <a href="index.php" class="logo">Aveelora</a>
                <nav>
                    <a href="index.php">Home</a>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                </nav>
            </div>
        </div>
    </header>
    
    <main>
        <div class="content-card">
            <h1>Register</h1>
            <?php if($msg): ?>
                <div class="<?= strpos($msg, 'successful') !== false ? 'success' : 'error' ?>"><?=$msg?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Register</button>
            </form>
            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </main>
    
    <footer>
        <p>&copy; 2025 Aveelora.np | All Rights Reserved</p>
        <p>Beautiful Accessories for Beautiful Souls</p>
    </footer>
</body>
</html>
