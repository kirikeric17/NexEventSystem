<?php
// 1. Load Config
require_once '../config.php'; 

// 2. Include Top Nav
include '../include/topNav.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>NexEvent | Login</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* CSS for the Password Toggle */
        .password-container {
            position: relative;
            width: 100%;
        }
        
        /* Position the eye icon inside the input field */
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            z-index: 10;
        }

        /* Ensure text doesn't go under the icon */
        #password {
            padding-right: 40px; 
        }
    </style>
</head>
<body>

<header class="hero">
    <div class="overlay"></div>
    <div class="hero-content">
        <img src="../assets/img/logonew2.jpg" alt="NexEvent Logo" class="logo">
        <h1>NexEvent</h1>
        <p>Discover, organize, and join the best events happening at NexEvent.</p>
    </div>
</header>

<main>
    <section class="section-content">        
        <h3>Login to Your Account</h3>
        
        <form action="login_action.php" method="post">
            
            <div style="margin-bottom: 15px;">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email" style="width: 100%; padding: 10px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="password">Password:</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required placeholder="Enter your password" style="width: 100%; padding: 10px; box-sizing: border-box;">
                    <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                </div>
            </div>

            <button type="submit">Login</button>
            
            <p style="margin-top: 15px; text-align: center;">
                Don't have an account? <a href="register.php" style="color: #4f46e5; text-decoration: none;">Register here</a>
            </p>
        </form>
    </section>
</main>

<footer>
    <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 20px;">
    <p>&copy; 2024 NexEvent | Mohd Nur Fitrie Bin Supidie | BI19110365</p>
</footer>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById("password");
        const icon = document.querySelector(".toggle-password");

        // Toggle the type attribute
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            passwordInput.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
</script>

</body>
</html>