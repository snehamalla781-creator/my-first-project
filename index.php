<!-- <?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect logged-in users to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: user_dashboard.php");
    exit();
}

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include database connection
include 'db_connect.php';

// timeAgo function
function timeAgo($datetime, $full = false) {
  $now = new DateTime;
  $ago = new DateTime($datetime);
  $diff = $now->diff($ago);

  $diff->w = floor($diff->d / 7);
  $diff->d -= $diff->w * 7;

  $string = array(
      'y' => 'year',
      'm' => 'month',
      'w' => 'week',
      'd' => 'day',
      'h' => 'hour',
      'i' => 'minute',
      's' => 'second',
  );
  foreach ($string as $k => &$v) {
      if ($diff->$k) {
          $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
      } else {
          unset($string[$k]);
      }
  }

  if (!$full) $string = array_slice($string, 0, 1);
  return $string ? implode(', ', $string) . ' ago' : 'just now';
}
//  recipe card ko detail fetch garna
// Fetch latest 4 exported recipes

$sql = "
  SELECT r.*, 
         u.id AS user_id, 
         u.username AS user_name,
         COALESCE(AVG(rt.rating), 0) AS avg_rating,
         COUNT(DISTINCT rt.user_id) AS rating_count
  FROM recipes r
  JOIN users u ON u.id = r.user_id
  LEFT JOIN ratings rt ON rt.recipe_id = r.id
  WHERE r.is_exported = 1
  GROUP BY r.id
  ORDER BY avg_rating DESC, r.created_at DESC
  LIMIT 4
";

$result = mysqli_query($conn, $sql);

$recipes = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Use image from DB if it exists, otherwise fallback
        $imageFile = trim($row['image']);
        if (empty($imageFile) || !file_exists(__DIR__ . '/images/' . $imageFile)) {
            $row['image_path'] = 'images/default_recipe.png';
        } else {
            $row['image_path'] = 'images/' . $imageFile;
        }

        // Default username
        if (empty($row['user_name'])) {
            $row['user_name'] = 'Unknown';
        }

        // Default comment count
        if (!isset($row['comment_count'])) {
            $row['comment_count'] = 0;
        }

        $recipes[] = $row;
    }
  }
?>
<!--  -->

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bites & Tales</title>

  <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <!-- <link rel="stylesheet" href="css/trending.css?v=<?php echo time(); ?>"> -->

  <!-- <link rel="stylesheet" href="css/ebook-section.css?v=<?php echo time(); ?>"> -->
  <link rel="stylesheet" href="css/about.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="css/community.css?v=<?php echo time(); ?>">

  <link rel="stylesheet" href="css/footer.css?v=<?php echo time(); ?>">

     <!-- Pass PHP login status to JS  -->
    <script>
      var userLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
  </script>

</head>

<body>

<!-- Top Bar  -->
<div class="top-bar">
  COLLECT RECIPES, ONE CLICK AT A TIME
  <a href="#" id="authBtn">SIGN UP</a>
</div>

<!-- Signup Modal -->
<div id="signupModal" class="modal">
  <div class="modal-content">
    <span class="close" id="closeSignup">&times;</span>
    <div class="modal-bg"></div>
    <form action="signup.php" method="POST" class="signup-form">

    <div class="logo">
        <span class="logo-icon">🍽️</span> 
        <span class="logo-text">Bites & Tales</span>
      </div>
      <h2>Sign Up</h2>
      
      <input type="text" name="username" placeholder="Username" required>
      <input type="email" name="email" placeholder="Email" required>

      <!-- Security Question -->
      <select name="security_question" required>
      <option value="" disabled selected>Select Security Question</option>
      <option value="Your country name?">Your country name?</option>
      <option value="Your favorite color?">Your favorite color?</option>
      </select>
      <!-- <input type="text" name="security_answer" placeholder="Answer" required autocomplete="off"> -->
      <input type="text" id="security_answer" name="security_answer" placeholder="Answer" required autocomplete="off">
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Sign Up</button>
      <p class="switch-form">
        Already have an account? <a href="#" id="openLoginFromSignup">Login here</a>
      </p>
      <p class="switch-form">
     <a href="#" id="openForgotFromSignup">Forgot Password?</a>
      </p>
    </form>
  </div>
</div>

<!-- Login Modal -->
<div id="loginModal" class="modal">
  <div class="modal-content">
    <span class="close" id="closeLogin">&times;</span>
    <div class="modal-bg"></div>
    <form action="login.php" method="POST" class="login-form">

    <div class="logo">
        <span class="logo-icon">🍽️</span> 
        <span class="logo-text">Bites & Tales</span>
      </div>
       <h2>Login</h2>
       <input type="email" name="email" placeholder="Email" required autocomplete="off">
      <input type="password" name="password" placeholder="Password" required autocomplete="new-password">
      <!-- <a href="forgot.php" class="forgot-link">Forgot Password?</a> -->
      <button type="submit">Login</button>
      <p class="switch-form">
        Don't have an account? <a href="#" id="openSignupFromLogin">Sign Up</a>
      </p>
      <p class="switch-form">
      <a href="#" id="openForgotFromLogin" class="forgot-link">Forgot Password?</a>
      </p>
    </form>
  </div>
</div>
 

<!-- Forgot Modal -->
<div id="forgotModal" class="modal">
  <div class="modal-content">
    <span class="close" id="closeForgot">&times;</span>
    <div class="modal-bg"></div>
    <form action="forgot.php" method="POST" class="forgot-form">
      <div class="logo">
        <span class="logo-icon">🍽️</span>
        <span class="logo-text">Bites & Tales</span>
      </div>
      <h2>Reset Password</h2>

      <!-- Email input -->
      <input type="email" id="forgot_email" name="email" placeholder="Enter your email" required>

      <!-- Security Question (readonly) -->
      <!-- <input type="text" id="forgot_security_question" name="security_question" placeholder="Security Question" readonly required> -->
      <input type="text" id="forgotSecurityQuestion" name="security_question" placeholder="Security Question" readonly required>

      <!-- Security Answer -->
      <input type="text" id="forgot_security_answer" name="security_answer" placeholder="Enter your answer" required>

      <!-- New password -->
      <input type="password" name="new_password" placeholder="New Password" required>

      <button type="submit">Reset Password</button>
    </form>
  </div>
</div>


   <header>
  <div class="header-container">
    
    <!-- Logo left -->
    <div class="logo">
      <span class="logo-icon">🍽️</span>
      <span class="logo-text">Bites & Tales</span>
    </div>

    <!-- Navigation  -->
    <!-- <div class="right-nav">
      <nav class="nav-links">
        <a href="#">Home</a>
        <a href="#">About</a>
        <a href="#">Recipes</a>
        <a href="#" id="search-icon">🔍</a>
      </nav>
    </div> -->
    <div class="right-nav">
    <nav class="nav-links">
        <a href="index.php">Home</a>
        <a href="#about">About</a> <!-- Scrolls to About on the current page -->
        <a href="#community">Community</a>
        <!-- <a href="#" id="search-icon">🔍</a> -->
    </nav>
</div>
   
  </div>
</header>

    <!-- Tagline -->
  <div class="tagline">
  <em>Cook Simple.</em> <em>Live Delicious.</em>
  </div>

<!-- Recipe Gallery -->
<section class="gallery">
<?php
if (!empty($recipes)):
    foreach ($recipes as $row):
        $recipe_id = $row['id'];
        $imagePath = !empty($row['image']) ? 'images/' . $row['image'] : 'images/default_recipe.png';
        $avgRating = $row['avg_rating'] ?? 0;
        $recipeName = htmlspecialchars($row['title']);
?>
<div class="feed-recipe-card" >
    <a href="<?= isset($_SESSION['user_id']) ? "recipe_details.php?id=$recipe_id" : "#" ?>" 
       <?= isset($_SESSION['user_id']) ? "" : "onclick=\"document.getElementById('loginModal').style.display='flex'\"" ?>>
        <img src="<?= $imagePath ?>" alt="<?= $recipeName ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;">
    </a>

    <!-- Average Rating -->
    <?php if ($avgRating > 0): ?>
    <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.6); color: #fff; padding: 5px 8px; border-radius: 8px; font-weight: bold; font-size: 14px;">
        ⭐ <?= number_format($avgRating, 1) ?>
    </div>
    <?php endif; ?>

    <!-- Recipe Name overlay -->
    <div style="position: absolute; bottom: 0; width: 100%; background: rgba(0,0,0,0.5); color: #fff; text-align: center; padding: 8px 0; font-weight: bold; font-size: 16px;">
        <?= $recipeName ?>
    </div>
</div>
<?php
    endforeach;
else:
    echo "<p>No exported recipes found yet.</p>";
endif;
?>
</section>

<!-- </section> -->
</section>


<section class="trending-recipes">
  <div class="feed-header">
    <h2>Trending Recipes</h2>
    <div class="slider-controls">
      <button class="slider-arrow" id="trending-left">&lt;</button>
      <button class="slider-arrow" id="trending-right">&gt;</button>
    </div>
  </div>

  <div class="trending-grid" id="trending-recipes">
    <?php include 'trending.php'; ?>
  </div>
  </section>

<!--yaha bata mathi ko links  -->



 <!--@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@  -->
  <?php include 'about.php'; ?>
  <?php include 'community.php'; ?>

  <?php include 'footer.php'; ?>

  <!-- <script src="js/script.js"></script> -->
  <script src="js/script.js?v=<?php echo time(); ?>"></script>
  <script src="js/trending.js?v=<?php echo time(); ?>"></script>

  <!-- yo trending ko lagi -->
<script>
document.addEventListener("DOMContentLoaded", () => {

    const loginModal = document.getElementById("loginModal");
    const closeModal = document.getElementById("closeLogin");

    function openLoginModal() {
        if (!loginModal) return;
        loginModal.style.display = "flex";
    }

    // Close modal
    closeModal?.addEventListener("click", () => {
        loginModal.style.display = "none";
    });
    window.addEventListener("click", (e) => {
        if (e.target === loginModal) loginModal.style.display = "none";
    });

    // Is user logged in?
    const isLoggedIn = <?= isset($_SESSION['user_id']) && $_SESSION['user_id'] ? 'true' : 'false' ?>;

    if (!isLoggedIn) {
        // Intercept favorite clicks
        document.querySelectorAll(".favorite-heart").forEach(heart => {
            heart.addEventListener("click", (e) => {
                e.preventDefault();
                openLoginModal();
            });
        });

        // Intercept star rating clicks
        document.querySelectorAll(".star-rating .star").forEach(star => {
            star.addEventListener("click", (e) => {
                e.preventDefault();
                openLoginModal();
            });
        });

        // Intercept recipe image clicks
        document.querySelectorAll(".recipe-image-container a").forEach(link => {
            link.addEventListener("click", (e) => {
                e.preventDefault();
                openLoginModal();
            });
        });
    }

});
</script>
<!-- ////// -->



</body>
</html>
 
