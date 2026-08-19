<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'] ?? 'Customer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cinna Cake & Desserts</title>
    <link rel="stylesheet" href="dashboardstyle.css">
</head>
<body>
        <nav class="navbar">
            <a class="logo" href="#about-container">
            <img src="images/Logo.png" alt="Logo">
        </a>
            <div class="nav-mid">
                <div class="menu">
                    <a href="#dashboard">Home</a>
                    <a href="#about-container">About</a>
                    <a href="#contact">Contact</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </nav>
    <section class="home" id="dashboard">
    <h1>Welcome to Cinna Cake & Desserts!</h1>
    <div class="dessert-categories">
        <ul>
            <li><a href="#cakes">Cakes</a></li>
            <li><a href="#cupcakes">Cupcakes</a></li>
            <li><a href="#cookies">Cookies</a></li>
            <li><a href="#pastries">Pastries</a></li>
        </ul>
    </div>
    </section>
    <section class="about" id="about-container">
        <div class="container">
            <h1>About Cinna Cake & Desserts</h1>
            <p>Welcome to Cinna Cake & Desserts! 🩷🍰</p>
            <p>Step into our cozy little dessert corner, where every treat is baked with love, sprinkled with sweetness, and made to brighten your day. Whether you're celebrating a special occasion or simply treating yourself after a long day, we're here to make every bite feel extra special.</p>
            <p>From fluffy cakes and adorable cupcakes to soft cookies and delightful pastries, our desserts are crafted with quality ingredients and lots of care. Every creation is designed to bring smiles, warm hearts, and sweet memories that you'll want to share with the people you love.</p>
            <p>At Cinna Cake & Desserts, we believe that happiness can be found in the little things: a cozy cafe atmosphere, pastel colors, delicious desserts, and moments spent with family and friends. That's why we've created a place where sweetness and comfort come together in every order.</p>
            <p>Thank you for stopping by our sweet little bakery! We hope our treats add a little extra joy to your day and keep you coming back whenever you're craving something delicious. 🍨🍧</p>
        </div>
    </section>

    <section class="dashboard">
            <h2>Our Products</h2>
        <div class="product-list">
            <div class="product-card" id="cakes">
                <img src="images/Strawberry_sunday.jpeg" alt="Strawberry Sundae">
                <h3>Strawberry Sundae</h3>
                <p>Delicious strawberry sundae with whipped cream.</p>
                <button class="order-button">Order Now</button>
            </div>
            <div class="product-card">
                <img src="images/Pink Chocolate Covered Strawberries.jpeg" alt="Pink Strawberries">
                <h3>Pink Chocolate Covered Strawberries</h3>
                <p>Delicious strawberries covered in pink chocolate.</p>
                <button class="order-button">Order Now</button>
            </div>
            <div class="product-card" id="pastries">
                <img src="images/Strawberry_Croissants.jpeg" alt="Strawberry Croissants">
                <h3>Strawberry Croissants</h3>
                <p>Delicious strawberry croissants with a flaky texture.</p>
                <button class="order-button">Order Now</button>
            </div>
            <div class="product-card">
                <img src="images/Strawberry_Cake.jpeg" alt="Strawberry Cake">
                <h3>Strawberry Cake</h3>
                <p>Delicious strawberry cake with a moist crumb and fresh strawberries.</p>
                <button class="order-button">Order Now</button>
            </div>
        </div>
    </section>
    <section class="contact" id="contact">
        <div class="container">
        <h2>Contact Us</h2>
        <p>If you have any questions or inquiries, feel free to reach out to us:</p>
        <ul>
            <li>Email: <a href="mailto:delabandagelayyy@gmail.com">delabandagelayyy@gmail.com</a></li>
        </ul>
        </div>
    </section>
</body>
</html>
