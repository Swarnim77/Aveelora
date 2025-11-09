<?php session_start(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Aveelora</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<header class="site-header">
  <div class="wrap">
    <a class="logo" href="/aveelora_khalti/">TechZone</a>
    <nav>
      <a href="index.php">Home</a>
      <a href="category.php?cat=Earring">Ear Ring</a>
      <a href="category.php?cat=Nosepin">Nose pin</a>
      <a href="category.php?cat=Watch">Watches</a>
      <a href="cart.php">Cart</a>
      <?php if(isset($_SESSION['user'])): ?>
        <span class="user">Hello, <?=htmlspecialchars($_SESSION['user']['name'])?></span>
        <a href="logout.php">Logout</a>
        <?php if($_SESSION['user']['role']==='admin'): ?><a href="admin/view_products.php">Admin</a><?php endif; ?>
      <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>