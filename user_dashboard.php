 
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include_once 'db_connect.php';
// include 'favourite_section.php';

$username = $_SESSION['username'];
// $is_owner = ($recipe['user_id'] == $_SESSION['user_id']);
$user_id = $_SESSION['user_id'];

// Fetch only this user's recipes (My Recipes)
$sql_my = "SELECT * FROM recipes WHERE user_id = $user_id ORDER BY created_at DESC";
$result_my = $conn->query($sql_my);
$my_recipe_count = $result_my ? $result_my->num_rows : 0;

/* ------------------------------
   3. Category counts (for sidebar)
------------------------------ */
$breakfast_count = $conn->query("SELECT * FROM recipes WHERE user_id = $user_id AND category='Breakfast'")->num_rows;
$lunch_count = $conn->query("SELECT * FROM recipes WHERE user_id = $user_id AND category='lunch'")->num_rows;
$snacks_count = $conn->query("SELECT * FROM recipes WHERE user_id = $user_id AND category='Snacks'")->num_rows;
$dinner_count = $conn->query("SELECT * FROM recipes WHERE user_id = $user_id AND category='Dinner'")->num_rows;
$dessert_count = $conn->query("SELECT * FROM recipes WHERE user_id = $user_id AND category='Dessert'")->num_rows;
$drinks_count = $conn->query("SELECT * FROM recipes WHERE user_id = $user_id AND category='Drinks'")->num_rows;

// Handle rating submission
if (isset($_POST['rating']) && isset($_POST['recipe_id'])) {
  $user_id = $_SESSION['user_id'];
  $recipe_id = $_POST['recipe_id'];
  $rating = floatval($_POST['rating']); // allow halves

  // Check if user already rated
  $query = $conn->prepare("SELECT id FROM ratings WHERE user_id=? AND recipe_id=?");
  $query->execute([$user_id, $recipe_id]);

  if ($query->rowCount() > 0) {
      $update = $conn->prepare("UPDATE ratings SET rating=? WHERE user_id=? AND recipe_id=?");
      $update->execute([$rating, $user_id, $recipe_id]);
  } else {
      $insert = $conn->prepare("INSERT INTO ratings (user_id, recipe_id, rating) VALUES (?, ?, ?)");
      $insert->execute([$user_id, $recipe_id, $rating]);
  }

  // Recalculate average and count
  $avgQuery = $conn->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM ratings WHERE recipe_id=?");
  $avgQuery->execute([$recipe_id]);
  $result = $avgQuery->fetch();

  $avg_rating = $result['avg_rating'];
  $total = $result['total'];

  $updateRecipe = $conn->prepare("UPDATE recipes SET avg_rating=?, rating_count=? WHERE id=?");
  $updateRecipe->execute([$avg_rating, $total, $recipe_id]);

  // Stop further processing for AJAX
  if(isset($_POST['ajax'])) {
      echo json_encode(['avg_rating'=>$avg_rating,'rating_count'=>$total]);
      exit;
  }
}

// Fetch recipes
// $result = $conn->query("SELECT * FROM recipes ORDER BY created_at DESC");

$user_id = $_SESSION['user_id'] ?? 0;

$query = "
  SELECT r.*,
         u.username AS author_name,
         COALESCE(AVG(rt.rating), 0) AS avg_rating,
         COUNT(DISTINCT rt.user_id) AS rating_count,
         COUNT(DISTINCT c.id) AS comment_count
  FROM recipes r
  JOIN users u ON u.id = r.user_id
  LEFT JOIN ratings rt ON rt.recipe_id = r.id
  LEFT JOIN comments c ON c.recipe_id = r.id
  WHERE 1
  GROUP BY r.id
  ORDER BY r.created_at DESC
";

$result = $conn->query($query);


// delete
if(isset($_POST['delete_id'])) {
  $id = intval($_POST['delete_id']);

  // Get the image filename first
  $stmt = $conn->prepare("SELECT image FROM recipes WHERE id=? AND user_id=?");
  $stmt->bind_param("ii", $id, $_SESSION['user_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  if($result->num_rows > 0){
      $recipe = $result->fetch_assoc();
      $imagePath = "images/" . $recipe['image'];
      if(file_exists($imagePath)){
          unlink($imagePath); // deletes the image file
      }
  }

  // Delete the recipe row
  $stmt = $conn->prepare("DELETE FROM recipes WHERE id=? AND user_id=?");
  $stmt->bind_param("ii", $id, $_SESSION['user_id']);
  $stmt->execute();

  echo "success";
  exit;
}

// //////yo recipe section show garna 
$showMyRecipes = isset($_GET['show']) && $_GET['show'] === 'my-recipes';


// =========================
// FETCH USER FAVOURITES
// =========================
$fav_query = "
  SELECT r.*, f.created_at AS fav_added_at, u.username AS author_name,
         COALESCE(AVG(rt.rating), 0) AS avg_rating,
         COUNT(DISTINCT rt.user_id) AS rating_count
  FROM favourites f
  JOIN recipes r ON f.recipe_id = r.id
  JOIN users u ON u.id = r.user_id
  LEFT JOIN ratings rt ON rt.recipe_id = r.id
  WHERE f.user_id = ?
  GROUP BY r.id
  ORDER BY f.created_at DESC
";

$stmt = $conn->prepare($fav_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$fav_result = $stmt->get_result();
$fav_total = $fav_result->num_rows;

// =========================
// COUNT BY CATEGORY
// =========================
function favCount($conn, $user_id, $category) {
    $sql = "SELECT COUNT(*) AS total
            FROM favourites f
            JOIN recipes r ON f.recipe_id = r.id
            WHERE f.user_id = ? AND r.category = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $category);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['total'] ?? 0;
}

$fav_breakfast = favCount($conn, $user_id, 'Breakfast');
$fav_lunch     = favCount($conn, $user_id, 'Lunch');
$fav_snack     = favCount($conn, $user_id, 'Snacks');
$fav_dinner    = favCount($conn, $user_id, 'Dinner');
$fav_dessert   = favCount($conn, $user_id, 'Dessert');
$fav_drinks    = favCount($conn, $user_id, 'Drinks');

// ////////////////////////////////////////////
// Total trending recipes
$trending_total = $conn->query("SELECT COUNT(*) FROM recipes WHERE is_exported=1")->fetch_row()[0];

function trendingCount($conn, $category){
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM recipes WHERE is_exported=1 AND category=?");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['total'] ?? 0;
}

$trending_breakfast = trendingCount($conn, 'Breakfast');
$trending_lunch     = trendingCount($conn, 'Lunch');
$trending_snack     = trendingCount($conn, 'Snacks');
$trending_dinner    = trendingCount($conn, 'Dinner');
$trending_dessert   = trendingCount($conn, 'Dessert');
$trending_drinks    = trendingCount($conn, 'Drinks');

// 2. Fetch trending recipes for the section
$user_id = $_SESSION['user_id'] ?? 0;
$trending_recipes = [];

$query = "
    SELECT r.*, u.username AS user_name,
           COALESCE(AVG(rt.rating),0) AS avg_rating,
           COUNT(DISTINCT rt.user_id) AS rating_count
    FROM recipes r
    JOIN users u ON u.id = r.user_id
    LEFT JOIN ratings rt ON rt.recipe_id = r.id
    WHERE r.is_exported=1
    GROUP BY r.id
    ORDER BY r.views DESC, avg_rating DESC
    LIMIT 12
";

$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
        $trending_recipes[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'image' => $row['image'],
            'category' => $row['category'],
            'difficulty' => $row['difficulty'],
            'views' => $row['views'],
            'avg_rating' => round($row['avg_rating'],1),
            'rating_count' => $row['rating_count'],
            'user_id' => $row['user_id'],
            'user_name' => $row['user_name'],
            'created_at' => $row['created_at']
        ];
    }
}
///////////// 

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>

<?php
date_default_timezone_set('Asia/Kathmandu');

function timeAgo($datetime) {
    $now = new DateTime();
    $created = new DateTime($datetime);
    $created_at = date('Y-m-d H:i:s'); // now in Nepal time

    $diff = $now->getTimestamp() - $created->getTimestamp(); // difference in seconds

    if ($diff < 60) {
        return $diff . ' sec ago';
    } elseif ($diff < 3600) { // less than 1 hour
        $minutes = floor($diff / 60);
        return $minutes . ' min ago';
    } elseif ($diff < 86400) { // less than 1 day
        $hours = floor($diff / 3600);
        return $hours . ' hr ago';
    } elseif ($diff < 2592000) { // less than 30 days
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 31104000) { // less than 12 months
        $months = floor($diff / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else { // more than 1 year
        $years = floor($diff / 31104000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}
?>



<script>
history.pushState(null, null, location.href);
window.onpopstate = function() {
    history.go(1);
};
</script>
 

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bites & Tales</title>
 <!-- <link rel="stylesheet" href="css/style.css">  -->

 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <link rel="stylesheet" href="css/user_dashboard.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="css/recent_recipes.css?v=<?php echo time(); ?>">
  <!-- <link rel="stylesheet" href="css/favourite_recipes.css?v=<?php echo time(); ?>"> -->
  <link rel="stylesheet" href="css/trending.css?v=<?php echo time(); ?>">
  <!-- <link rel="stylesheet" href="css/ebook-section.css?v=<?php echo time(); ?>"> -->
  <link rel="stylesheet" href="css/community.css?v=<?php echo time(); ?>">

  <link rel="stylesheet" href="css/about.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="css/footer.css?v=<?php echo time(); ?>">

</head>

<body>

<header>
  <div class="header-container">
    
    <!-- Logo left -->
    <div class="logo">
      <span class="logo-icon">🍽️</span>
      <span class="logo-text">Bites & Tales</span>
    </div>

    <!-- Navigation  -->
    <!-- <div class="right-nav"> -->
      <nav class="nav-links">
        <a href="#" data-target="feed-section">Home</a>
        <!-- <a href="#" data-target="favourite-section">Favourites</a> -->
        <a href="#" data-target="community-section">Community</a>
        <a href="#" data-target="about-section">About</a>
      </nav>

       <!-- Right Actions -->
    <div class="header-actions">
      <div class="search-container">
        <input 
          type="text" 
          id="search-input" 
          placeholder="🔍︎ Search recipes..." 
          oninput="handleSearch(this.value)" 
          autocomplete="off"
        >

        <div id="search-dropdown" class="search-dropdown hidden">
    
          <!-- Default content (when not typing) -->
        <div class="search-default">
          <div class="search-section">
             <h4>Browse by Category</h4>
            <div class="category-buttons">
              <button onclick="searchByCategory('breakfast')">🍳 Breakfast</button>
              <button onclick="searchByCategory('lunch')">🥗 Lunch</button>
              <button onclick="searchByCategory('snacks')">🍪 Snacks</button>
              <button onclick="searchByCategory('dinner')">🍽️ Dinner</button>
              <button onclick="searchByCategory('dessert')">🍰 Dessert</button>
              <button onclick="searchByCategory('drinks')">🍹 Drinks</button>
            </div>
          </div>

          <div class="search-section" id="past-searches-section">
            <h4>Your Past Searches</h4>
            <div class="category-buttons" id="past-searches-buttons">
              <!-- JS will insert past searches here -->
            </div>
          </div>
    </div>

    <!-- Dynamic live results will appear here -->
        <div id="live-results"></div>
      </div>
    </div>

  </div>  

<!-- 🔔 NOTIFICATION -->
<div class="notification" id="notification">
    <i class="fa-solid fa-bell notif-icon"></i>
    <span class="notif-badge" id="notif-count"></span>

    <!-- Dropdown -->
    <div class="notification-dropdown hidden" id="notification-dropdown">
        <div class="notif-header">
            <span>Notifications</span>
            <button onclick="clearNotifications()">Clear All</button>
        </div>
        <div class="notif-list">
            <!-- Dynamic notifications will be inserted here -->
        </div>
    </div>
</div>


    <!-- 👤 PROFILE -->
    <div class="profile">
    <img src="<?= isset($_SESSION['user_photo']) ? $_SESSION['user_photo'] : 'images/default_profile.png'; ?>" 
         alt="User Photo" class="profile-pic">
    <div class="profile-dropdown">
        <!-- <a href="my_profile.php">My Profile</a> -->
        <!-- <a href="#">Settings</a> -->
        <a href="logout.php" onclick="return confirmLogout()">Logout</a>
    </div>
</div>

    
  </div>
</header>

<!-- Sub-header / Welcome -->
<!-- <div class="dashboard-subheader">
  <p class="welcome">Welcome, <span id="username"><?php echo htmlspecialchars($username); ?></span>!</p>
  <p class="tagline">“Discover, cook, and share your favorite recipes!”</p>
</div> -->

<!-- Dashboard Navigation -->
<nav class="dashboard-nav">
  <ul>
    <li class="nav-item active" data-target="feed-section">Feed</li>
    <li class="nav-item" data-target="my-recipes-section">My Recipes</li>
    <li class="nav-item" data-target="favourite-section">Favourite</li>
    <li class="nav-item" data-target="trending-section">Trending</li>
    <!-- <li class="nav-item" data-target="suggested-users-section">Suggested Users</li> -->
  </ul>
</nav>

<!-- Sections -->

<!-- Feed Section -->
<div id="feed-section" class="dashboard-section main-section active">
  <!-- Sub-header / Welcome -->
  <div class="dashboard-subheader">
    <!-- <p class="welcome">Welcome, <span id="username">!</span></p> -->
    <p class="welcome">Welcome, <span id="username"><?php echo htmlspecialchars($username); ?></span>!</p>
    <p class="tagline">“Discover, cook, and share your favorite recipes!”</p>
  </div>
  <!-- Recent Recipes Subsection -->
  <div class="feed-subsection">
    <div class="feed-header">
      <h2>Recent Recipes</h2>
      <div class="slider-controls">
        <button class="slider-arrow" id="recent-left">&lt;</button>
        <button class="slider-arrow" id="recent-right">&gt;</button>
      </div>
    </div>
    <div class="feed-recipes-slider" id="recent-recipes">
      <!-- recent_recipes.php will dynamically load cards here -->
      <?php include 'recent_recipes.php'; ?>

    </div>
  </div>

  <!-- Trending Recipes Subsection -->
  <div class="feed-subsection">
    <div class="feed-header">
      <h2>Trending Recipes</h2>
      <div class="slider-controls">
        <button class="slider-arrow" id="trending-left">&lt;</button>
        <button class="slider-arrow" id="trending-right">&gt;</button>
      </div>
    </div>
    <div class="feed-recipes-slider" id="trending-recipes">
      <!-- trending_recipes.php will dynamically load cards here -->
      <?php include 'trending.php'; ?>

    </div>
  </div>

  <!-- Top Rated Recipes Subsection -->
  <div class="feed-subsection">
      <div class="feed-header">
      <h2>Top Rated Recipes</h2>
      <div class="slider-controls">
      <button class="slider-arrow" id="top-rated-left">&lt;</button>
      <button class="slider-arrow" id="top-rated-right">&gt;</button>
      </div>
    </div>
    <div class="feed-recipes-slider" id="top-rated-recipes">
      <!-- top_rated_recipes.php will dynamically load cards here -->
      <?php include 'top_rated_recipes.php'; ?>
    </div>
  </div>

</div>

<!-- My Recipes Section -->

<div id="my-recipes-section" class="dashboard-section" style="display:none;">

  <!-- <h2 class="recipes-title">My Recipes</h2> -->
  <p class="recipes-subtitle">Create, edit, and organize your personal cookbook.</p>

  <!-- Controls -->
  <div class="recipes-controls">
    <input type="text" placeholder="🔍︎ Search my recipes..." class="search-bar">
    <select class="filter-category">
      <option>All categories</option>
      <option>🍳Breakfast</option>
      <option>🥗Lunch</option>
      <option>🍪Snacks</option>
      <option>🍽️Dinner</option>
      <option>🍰Dessert</option>
      <option>🍹Drinks</option>

    </select>
    
    <select class="sort-order">
    <option value="all">all</option>
    <option value="saved">saved</option>
    <option value="exported">exported</option>
    </select>

    <button class="new-recipe-btn">+ New Recipe</button>
  </div>

  <!-- Recipe Stats -->
  <div class="recipe-stats">
    <div class="stat-card">
        <p>Total</p>
        <span><?= $my_recipe_count ?></span>
    </div>
    <div class="stat-card">
        <p>Breakfast</p>
        <span><?= $breakfast_count ?></span>
    </div>
    <div class="stat-card">
        <p>Lunch</p>
        <span><?= $lunch_count ?></span>
    </div>
    <div class="stat-card">
        <p>Snack</p>
        <span><?= $snacks_count ?></span>
    </div>
    <div class="stat-card">
        <p>Dinner</p>
        <span><?= $dinner_count ?></span>
    </div>
    <div class="stat-card">
        <p>Dessert</p>
        <span><?= $dessert_count ?></span>
    </div>
    <div class="stat-card">
        <p>Drinks</p>
        <span><?= $drinks_count ?></span>
    </div>
  </div>


  <!-- Recipe List -->
  <div class="recipe-list">
  <?php if($my_recipe_count> 0): ?>
    <?php while($recipe = $result_my->fetch_assoc()): ?>
      <?php $is_owner = true; // all recipes here belong to logged-in user ?>

        <div class="recipe-card own-recipe">
          <!-- **** -->

            <!-- Recipe Image & Rating -->
            <div class="recipe-image-container">
            <a href="recipe_details.php?id=<?= $recipe['id'] ?>" 
            onclick="openRecipeModal(event, this.href)">
            <img src="images/<?= $recipe['image'] ?>" 
                alt="<?= htmlspecialchars($recipe['title']) ?>" 
                class="recipe-image">
          </a>

            <!-- Exported tag -->
            <?php if($recipe['is_exported']): ?>
            <div class="exported-tag">Exported</div>
            <?php endif; ?>
          </a>

            </div>

            <!-- Recipe Info -->
            <div class="recipe-info">
                <h3 class="recipe-name"><?= htmlspecialchars($recipe['title']) ?></h3>
                <p class="recipe-type">
                <?= htmlspecialchars($recipe['category']) ?> 
               
                <!-- Time ago displayed via PHP -->
                <span class="posted-time" data-time="<?= $recipe['created_at'] ?>">
                    <?= timeAgo($recipe['created_at']) ?>
                </span>

                <p class="recipe-description"><?= htmlspecialchars($recipe['description']) ?></p>

                <!-- Meta: Views & Comments -->
                <div class="recipe-meta">
                  <div class="views">👁️ <?= $recipe['views'] ?? 0 ?></div>
                  <button class="comments-btn">💬 <?= $recipe['comment_count'] ?? 0 ?></button>
                  <!-- <button class="comments-btn" onclick="openComments(<?= $recipe_id ?>)">💬 <?= $row['comment_count'] ?></button> -->

                </div>

                <!-- Recipe Actions -->
                <button 
                  class="edit-btn"
                  data-id="<?= $recipe['id'] ?>"
                  data-title="<?= htmlspecialchars($recipe['title'], ENT_QUOTES) ?>"
                  data-description="<?= htmlspecialchars($recipe['description'], ENT_QUOTES) ?>"
                  data-category="<?= htmlspecialchars($recipe['category'], ENT_QUOTES) ?>"
                  data-difficulty="<?= htmlspecialchars($recipe['difficulty'], ENT_QUOTES) ?>"
                  data-servings="<?= $recipe['servings'] ?>"
                  data-cook_time="<?= $recipe['cook_time'] ?>"
                  data-prep_time="<?= $recipe['prep_time'] ?>"
                  data-emoji="<?= htmlspecialchars($recipe['emoji'], ENT_QUOTES) ?>"
                  data-ingredients="<?= htmlspecialchars($recipe['ingredients'], ENT_QUOTES) ?>"
                  data-instructions="<?= htmlspecialchars($recipe['instructions'], ENT_QUOTES) ?>"
                  data-image="<?= $recipe['image'] ?>" <!-- 🔹 this is the DB image -->

                  ✏️ Edit
                </button>

                <button class="delete-btn" data-id="<?= $recipe['id'] ?>">🗑 Delete</button>

                </div>
            </div>
        <!-- </div> -->
    <?php endwhile; ?>
    <?php else: ?>
    <!-- <p class="no-recipes">You haven’t added any recipes yet. Click “+ New Recipe” to start!</p> -->
    <?php endif; ?>
  </div>

<!-- add Recipe Modal -->
<div id="recipeModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" id="closeModalBtn">&times;</span>
      <h2>Add Recipe</h2>
            
    <!-- Include your form here -->
    
<form id="recipeForm" method="POST" action="save_recipe.php" enctype="multipart/form-data">
  <!--  -->
  <input type="hidden" name="id" value=""> <!-- 🔹 For edit mode -->
<!--  -->
<!-- export ko lagi -->
<input type="hidden" name="is_exported" id="is_exported" value="0"> <!-- For export flag -->

<!-- ///// -->

      <!-- <form id="recipeForm"> -->
        <!-- Title -->
        <div class="form-group">
          <label>Recipe Title *</label>
          <input type="text" name="title" placeholder="e.g., Grandma’s Chocolate Chip Cookies" required>
        </div>

        <!-- Category and Servings -->
        <div class="row">
          <div class="form-group">
            <label>Category *</label>
            <select name="category" required>
              <option value="">Select a category</option>
              <option>Breakfast</option>
              <option>lunch</option>
              <option>Snacks</option>
              <option>Dinner</option>
              <option>Dessert</option>
              <option>Drinks</option>
             
            </select>
          </div>
          <div class="form-group">
            <label>Servings *</label>
            <input type="number" name="servings" value="1" min="1" required>
          </div>
        </div>

        <!-- Prep & Cook Time -->
        <div class="row">
          <div class="form-group">
            <!--  -->
            <label>Prep Time *<span class="optional">(optional)</span></label>
            <!--  -->
            <input type="text" name="prep_time" placeholder="e.g., 15 min">
          </div>
          <div class="form-group">
            <label>Cook Time *</label>
            <input type="text" name="cook_time" placeholder="e.g., 25 min" required>
          </div>
        </div>

        <!-- Difficulty & Emoji -->
        <div class="row">
          <div class="form-group">
            <label>Difficulty Level</label>
            <select name="difficulty">
              <option>Easy</option>
              <option>Medium</option>
              <option>Hard</option>
            </select>
          </div>
          <div class="form-group">
            <label>Recipe Emoji <span class="optional">(optional)</span></label>
            <input type="text" name="emoji" placeholder="🍪">
          </div>
        </div>

        <!-- Image Upload -->
        <div class="form-group">
          <label>Recipe Image *</label>
          <input type="file" id="recipeImage" name="image" accept="image/*">
          <input type="hidden" name="old_image" id="old_image" value="">
          <!-- /// -->
          <!-- <input type="hidden" name="is_exported" id="is_exported" value="0"> -->
          <!-- //// -->

          <div id="imagePreview"><img id="preview" src="" alt="Image Preview"></div>
        </div>

        <!-- Description -->
        <div class="form-group">
          <label>Recipe Description</label>
          <textarea name="description" placeholder="Tell us about this recipe…"></textarea>
        </div>

        <!-- Ingredients -->
        <div class="form-group">
          <label>Ingredients *</label>
          <textarea name="ingredients" placeholder="Write all ingredients here…"></textarea>
        </div>

        <!-- Steps -->
        <div class="form-group">
          <label>Step-by-Step Instructions *</label>
          <textarea name="instructions" placeholder="Write all steps here…"></textarea>
        </div>

        <!-- Buttons -->
        <div class="buttons-row">
    <div class="left-buttons">
        <!-- <button type="button" class="btn btn-export" onclick="exportRecipe()">Export</button> -->
        <button type="button" class="btn btn-export" onclick="exportRecipe()">Export</button>

        <button type="submit" class="btn btn-save">Save Recipe</button>
    </div>
    <div class="right-buttons">
        <button type="button" class="btn btn-cancel" onclick="resetForm()">Cancel</button>
    </div>
</div>

      </form>
    </div>
  </div>
</div>

<!-- 🆕 View Recipe Details Modal -->

<div id="viewRecipeModal" class="modal">
  <div class="modal-content view-modal">
    <span class="close-btn" id="closeViewModal">&times;</span>
    <div id="recipeModalBody">
      <div class="view-modal-content-wrapper">
        <!-- your fetched recipe_details.php content will appear here -->
      </div>
    </div>
  </div>
</div>

<!-- /// -->
 
<!-- Favourite Section -->

<div id="favourite-section" class="dashboard-section" style="display:none;">

  <p class="recipes-subtitle">View and manage your favorite saved recipes.</p>

  <!-- Controls -->
  <div class="recipes-controls">
    <input type="text" placeholder="🔍︎ Search favourites..." class="search-bar">
    <select class="filter-category">
      <option>All categories</option>
      <option>🍳Breakfast</option>
      <option>🥗Lunch</option>
      <option>🍪Snacks</option>
      <option>🍽️Dinner</option>
      <option>🍰Dessert</option>
      <option>🍹Drinks</option>
    </select>
    <!-- <select class="sort-order">
      <option value="newest">Newest first</option>
      <option value="oldest">Oldest first</option>
    </select> -->
  </div>

  <!-- Favourite Stats -->
  <div class="recipe-stats">
    <div class="stat-card"><p>Total</p><span><?= $fav_total ?></span></div>
    <div class="stat-card"><p>Breakfast</p><span><?= $fav_breakfast ?></span></div>
    <div class="stat-card"><p>Lunch</p><span><?= $fav_lunch ?></span></div>
    <div class="stat-card"><p>Snack</p><span><?= $fav_snack ?></span></div>
    <div class="stat-card"><p>Dinner</p><span><?= $fav_dinner ?></span></div>
    <div class="stat-card"><p>Dessert</p><span><?= $fav_dessert ?></span></div>
    <div class="stat-card"><p>Drinks</p><span><?= $fav_drinks ?></span></div>
  </div>

<!-- Favourite Recipes Grid -->
<div class="recipe-list">
    <?php
    if ($fav_result && $fav_result->num_rows > 0):
        while ($row = $fav_result->fetch_assoc()):
            $recipe_id = $row['id'];
            $imagePath = $row['image'];
            $isFav = true; // already favourite
    ?>
    <div class="recipe-card" 
         data-category="<?= htmlspecialchars($row['category']) ?>" 
         data-created-at="<?= $row['fav_added_at'] ?>">

        <!-- Recipe Image -->
        <div class="recipe-image-container">
          <a href="recipe_details.php?id=<?= $recipe_id ?>" onclick="openRecipeModal(event, this.href)">
            <img src="images/<?= htmlspecialchars($row['image']) ?>" 
              alt="<?= htmlspecialchars($row['title']) ?>" 
              class="recipe-image">
          </a>

            <!-- Favourite Heart -->
            <div class="favorite-heart" data-recipe-id="<?= $recipe_id ?>">❤️</div>

            <!-- Average Rating Badge -->
            <div class="image-rating">⭐ <?= number_format($row['avg_rating'], 1) ?></div>
        </div>

        <!-- Star Rating -->
        <div class="star-rating" data-recipe-id="<?= $recipe_id ?>" data-user-rated="<?= !empty($row['rating_count']) ? 1 : 0 ?>">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star <?= ($i <= ($row['avg_rating'] ?? 0)) ? 'rated' : '' ?>" data-value="<?= $i ?>">★</span>
            <?php endfor; ?>
        </div>

        <!-- Average Rating Text -->
        <p class="avg-rating-text">⭐ <?= number_format($row['avg_rating'], 1) ?> (<?= $row['rating_count'] ?? 0 ?> ratings)</p>

        <!-- Recipe Info -->
        <div class="recipe-info">
            <h3 class="recipe-name"><?= htmlspecialchars($row['title']) ?>
                <span class="posted-time"><?= timeAgo($row['fav_added_at']) ?></span>
            </h3>

            <p class="recipe-description"><?= htmlspecialchars(substr($row['description'],0,60)) ?>...</p>
            <p class="recipe-type"><?= htmlspecialchars($row['category']) ?> • <?= htmlspecialchars($row['difficulty']) ?></p>
            <p class="recipe-user">By <a href="profile.php?id=<?= $row['user_id'] ?>"><?= htmlspecialchars($row['author_name']) ?></a></p>

            <div class="recipe-stats">
                <span>👁️ <?= $row['views'] ?></span>
                <button class="comments-btn" onclick="openComments(<?= $recipe_id ?>)">💬 <?= $row['comment_count'] ?? 0 ?></button>
            </div>
        </div>
    </div>
    <?php
        endwhile;
    else:
        echo "<p class='no-favourites'>You have no favourite recipes yet.</p>";
    endif;
    ?>
</div>


</div>

<!--  yaha samma favourite -->

<!-- Trending Section -->
<div id="trending-section" class="dashboard-section" style="display:none;">

  <p class="recipes-subtitle">Check out the most popular trending recipes this week.</p>

  <!-- Controls -->
  <div class="recipes-controls">
    <input type="text" placeholder="🔍︎ Search trending..." class="search-bar">
    <select class="filter-category">
      <option>All categories</option>
      <option>🍳Breakfast</option>
      <option>🥗Lunch</option>
      <option>🍪Snacks</option>
      <option>🍽️Dinner</option>
      <option>🍰Dessert</option>
      <option>🍹Drinks</option>
    </select>
  </div>

  <!-- Trending Stats -->
  <div class="recipe-stats">
    <div class="stat-card"><p>Total</p><span><?= $trending_total ?? 0 ?></span></div>
    <div class="stat-card"><p>Breakfast</p><span><?= $trending_breakfast ?? 0 ?></span></div>
    <div class="stat-card"><p>Lunch</p><span><?= $trending_lunch ?? 0 ?></span></div>
    <div class="stat-card"><p>Snack</p><span><?= $trending_snack ?? 0 ?></span></div>
    <div class="stat-card"><p>Dinner</p><span><?= $trending_dinner ?? 0 ?></span></div>
    <div class="stat-card"><p>Dessert</p><span><?= $trending_dessert ?? 0 ?></span></div>
    <div class="stat-card"><p>Drinks</p><span><?= $trending_drinks ?? 0 ?></span></div>
  </div>

  <!-- Trending Recipes Grid -->
  <div class="recipe-list">
    <?php if (!empty($trending_recipes)): ?>
      <?php foreach ($trending_recipes as $row):
          $recipe_id = $row['id'] ?? 0;
          $imagePath = !empty($row['image']) ? "images/" . $row['image'] : "images/default_recipe.png";
          $isFav = false;

          if ($user_id) {
              $checkFav = $conn->prepare("SELECT 1 FROM favourites WHERE user_id = ? AND recipe_id = ?");
              $checkFav->bind_param("ii", $user_id, $recipe_id);
              $checkFav->execute();
              $favResult = $checkFav->get_result();
              $isFav = $favResult && $favResult->num_rows > 0;
          }

          $description = $row['description'] ?? 'No description';
          $avgRating = $row['avg_rating'] ?? 0;
          $ratingCount = $row['rating_count'] ?? 0;
      ?>
      <div class="recipe-card" data-category="<?= htmlspecialchars($row['category'] ?? '') ?>">

          <!-- Recipe Image -->
          <div class="recipe-image-container">
            <a href="recipe_details.php?id=<?= $recipe_id ?>" onclick="openRecipeModal(event, this.href)">
              <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($row['title'] ?? '') ?>" class="recipe-image">
            </a>

            <!-- Favourite Heart -->
            <div class="favorite-heart" data-recipe-id="<?= $recipe_id ?>"><?= $isFav ? '❤️' : '♡' ?></div>

            <!-- Average Rating Badge -->
            <div class="image-rating">⭐ <?= number_format($avgRating, 1) ?></div>
          </div>

          <!-- Star Rating (average rating) -->
          <div class="star-rating" data-recipe-id="<?= $recipe_id ?>">
              <?php
                  $rounded = round($avgRating * 2) / 2; // round to nearest 0.5
                  for ($i = 1; $i <= 5; $i++):
                      if ($i <= floor($rounded)) {
                          $class = 'rated';
                      } elseif ($i - 0.5 == $rounded) {
                          $class = 'half';
                      } else {
                          $class = '';
                      }
              ?>
              <span class="star <?= $class ?>" data-value="<?= $i ?>">★</span>
              <?php endfor; ?>
          </div>

          <!-- Average Rating Text -->
          <p class="avg-rating-text">⭐ <?= number_format($avgRating, 1) ?> (<?= $ratingCount ?> ratings)</p>

          <!-- Recipe Info -->
          <div class="recipe-info">
              <h3 class="recipe-name"><?= htmlspecialchars($row['title'] ?? '') ?>
                  <span class="posted-time"><?= timeAgo($row['created_at'] ?? '') ?></span>
              </h3>

              <p class="recipe-description"><?= htmlspecialchars(substr($description, 0, 60)) ?>...</p>
              <p class="recipe-type"><?= htmlspecialchars($row['category'] ?? '') ?> • <?= htmlspecialchars($row['difficulty'] ?? '') ?></p>
              <p class="recipe-user">By <a href="profile.php?id=<?= $row['user_id'] ?? 0 ?>"><?= htmlspecialchars($row['user_name'] ?? '') ?></a></p>

              <div class="recipe-stats">
                  <span>👁️ <?= $row['views'] ?? 0 ?></span>
                  <button class="comments-btn">💬 0</button>
              </div>
          </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class='no-trending'>No trending recipes found this week.</p>
    <?php endif; ?>
  </div>

</div>

<!-- /////////////// -->
<!-- Suggested Users Section -->
<div id="suggested-users-section" class="dashboard-section">
  <h2>Suggested Users Section</h2>
  <p>Suggested users content goes here.</p>
</div>

<!-- @@@@@ main navigation  -->
 <!-- community ko lagi -->
<div id="community-section" class="main-section">
  <!-- <h2>Community Section</h2> -->
  <!-- <p>This is a placeholder for community content.</p> -->
  <?php include 'community.php'; ?>

</div>

<!-- about ko lagi -->
<div id="about-section" class="main-section">
  <!-- <h2>About Section</h2>
  <p>Information about the site.</p> -->
  <?php include 'about.php'; ?>

</div>

<!-- comment ko lagi -->

<!-- Comment Modal -->
<!-- <div id="commentModal" class="comment-modal">
  <div class="modal-content">
    <span class="close" onclick="closeComments()">&times;</span>
    <h3>Comments</h3>
    <div id="commentsContainer"></div>
    <textarea id="newComment" placeholder="Write your comment..."></textarea>
    <button onclick="submitComment()">Submit</button>
  </div>
</div> -->

<div id="commentModal" class="comment-modal">
  <div class="modal-content">
    <span class="close" onclick="closeComments()">&times;</span>
    <h3>Comments</h3>

    <!-- Comments list -->
    <div id="commentsContainer" class="comments-container"></div>

    <!-- Add new comment -->
    <div class="new-comment-box">
      <textarea id="newComment" placeholder="Write your comment..."></textarea>
      <button onclick="submitComment()">Submit</button>
    </div>
  </div>
</div>


<!-- yaha ebook thyo -->
<?php include 'footer.php'; ?>


<!-- @@@@@@@@@@@@@ -->
 <script src="js/user_dashboard.js?v=<?php echo time(); ?>"></script>
 <script src="js/recent_recipes.js?v=<?php echo time(); ?>"></script>
 <script src="js/top_rated_recipes.js?v=<?php echo time(); ?>"></script>
 <script src="js/trending.js?v=<?php echo time(); ?>"></script>



</body>