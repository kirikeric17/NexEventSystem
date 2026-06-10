<?php
require_once __DIR__ . '/../config.php'; 

// SET THE ACTIVE PAGE VARIABLE
$current_page = 'register'; 
?>   
 <?php 
  include ROOT_PATH . '/include/topNav.php'; 
  ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>NexEvent</title>
  <link rel="stylesheet" href="<?php echo BASE_PATH_CSS; ?>style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  <style>
    /* ADDED THIS STYLE TO FIX THE SPACING */
    .form-group {
        margin-bottom: 20px; /* Space between different fields */
    }
    
    .form-group label {
        display: block;       /* Makes label take up its own line */
        margin-bottom: 8px;   /* The exact gap between label and input box */
        font-weight: 600;
        color: #1e293b;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="tel"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 14px;
    }

    /* Style for the Checkbox list */
    .checkbox-group label {
        display: inline-block;
        margin-right: 15px;
        margin-bottom: 5px;
        font-weight: normal;
        cursor: pointer;
    }
  </style>
</head>
<body>

  <header class="hero">
    <div class="overlay"></div>
    <div class="hero-content">
      <img src="<?php echo BASE_PATH_IMG; ?>logonew2.jpg" alt="NexEvent Logo" class="logo">
      <h1>NexEvent</h1>
      <p>Discover, organize, and join the best events happening at NexEvent.</p>
    </div>
  </header>

  <main>
    <section class="section-content">
      <h3>Register Now!</h3>
      
      <form action="register_action.php" method="post" name="registerForm">
        
        <fieldset style="margin-bottom: 20px; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px;">
          <legend style="padding: 0 5px; font-weight:600;">Category</legend>
          <label style="margin-right: 15px;">
            <input type="radio" name="category" value="staff" required> Staff
          </label>
          <label style="margin-right: 15px;">
            <input type="radio" name="category" value="student"> Student
          </label>
          <label>
            <input type="radio" name="category" value="public"> Public
          </label>
        </fieldset>

        <div class="form-group">
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name" required autocomplete="name" placeholder="Jane Doe">
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required autocomplete="email" placeholder="you@example.edu">
        </div>

        <div class="form-group">abel>
          <label for="phone">Phone</l
          <input type="tel" id="phone" name="phone" autocomplete="tel" placeholder="+60 12-345 6789">
        </div>        

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password" placeholder="Choose a password">
        </div>

        <div class="form-group checkbox-group">
            <label style="font-weight: 600; display:block; margin-bottom:10px;">Recommend event about:</label>
            
            <label><input type="checkbox" name="event[]" value="workshop"> Workshop</label>
            <label><input type="checkbox" name="event[]" value="seminar"> Seminar</label>
            <label><input type="checkbox" name="event[]" value="competition"> Competition</label>
            <br> <label><input type="checkbox" name="event[]" value="festival"> Festival</label>
            <label><input type="checkbox" name="event[]" value="sport"> Sport</label>
            <label><input type="checkbox" name="event[]" value="course"> Course</label>
        </div>

        <div>
            <button type="submit" style="margin-bottom: 15px; width:100%;">Register</button>
            <button type="reset" class="btn-secondary" style="width:100%; background:#94a3b8;">Reset</button>
            
            <div style="margin-top: 20px; text-align: center;">
                <p style="color: #64748b;">
                    Already have an account? 
                    <a href="login.php" style="color: #4f46e5; font-weight: bold; text-decoration: none;">Login Here</a>
                </p>
            </div>
        </div>

      </form>
      <p id="output"></p>
    </section>
  </main>

  <footer>
    <hr>
    <p>&copy; Mohd Nur Fitrie Bin Supidie | BI19110365</p>
  </footer>

  <script>
    // Toggle mobile menu
    const menuIcon = document.getElementById('menu-icon');
    const navLinks = document.getElementById('nav-links');
    if(menuIcon) {
        menuIcon.onclick = () => navLinks.classList.toggle('active');
    }

    // Form Validation
    document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("form");    

      form.addEventListener("submit", function (e) {
        const checkboxes = document.querySelectorAll('input[name="event[]"]');
        let checked = false;

        // Check if at least one checkbox is selected
        for (const box of checkboxes) {
          if (box.checked) {
            checked = true;
            break;
          }
        }

        if (!checked) {
          e.preventDefault(); // Stop form submission
          alert("Please select at least one recommended event.");
          const output = document.getElementById("output");
          output.style.color = "red";
          output.textContent = `Please select at least one recommended event.`;
          return;
        }    

        // this.submit(); // Allow default submission
      });
    }); 
  </script>
</body>
</html>