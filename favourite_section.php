<?php
include 'db_connect.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;

// ----------------------------
// Fetch Favorite Recipes
// ----------------------------
$fav_query = "
    SELECT r.*, 
           u.id AS user_id, 
           u.username AS user_name,
           COALESCE(AVG(rt.rating), 0) AS avg_rating,
           COUNT(DISTINCT c.id) AS comment_count
    FROM favourites f
    JOIN recipes r ON f.recipe_id = r.id
    JOIN users u ON r.user_id = u.id
    LEFT JOIN ratings rt ON rt.recipe_id = r.id
    LEFT JOIN comments c ON c.recipe_id = r.id
    WHERE f.user_id = ?
    GROUP BY r.id
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($fav_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_fav = $stmt->get_result();

// ----------------------------
// Count stats
// ----------------------------
$fav_total_count = 0;
$fav_breakfast_count = 0;
$fav_lunch_count = 0;
$fav_snacks_count = 0;
$fav_dinner_count = 0;
$fav_dessert_count = 0;
$fav_drinks_count = 0;

while ($row = $result_fav->fetch_assoc()) {
    $fav_recipes[] = $row;
    $fav_total_count++;

    $category = strtolower(trim($row['category'] ?? ''));
    if ($category === 'breakfast') $fav_breakfast_count++;
    elseif ($category === 'lunch') $fav_lunch_count++;
    elseif ($category === 'snacks' || $category === 'snack') $fav_snacks_count++;
    elseif ($category === 'dinner') $fav_dinner_count++;
    elseif ($category === 'dessert') $fav_dessert_count++;
    elseif ($category === 'drinks' || $category === 'drink') $fav_drinks_count++;
}

// ----------------------------
// Helper: Time Ago Function
// ----------------------------
function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;

    if ($time_difference < 60) return "Just now";
    $minutes = round($time_difference / 60);
    if ($minutes < 60) return "$minutes min ago";
    $hours = round($minutes / 60);
    if ($hours < 24) return "$hours hrs ago";
    $days = round($hours / 24);
    return "$days days ago";
}
?>

<!-- ========== Favourite Section HTML ========== -->
<div id="favourite-section" class="dashboard-section" style="display:none;">

  <h2 class="recipes-title">My Favourite Recipes ❤️</h2>
  <p class="recipes-subtitle">Your saved recipes, all in one place.</p>

  <!-- Recipe Stats -->
  <div class="recipe-stats">
    <div class="stat-card"><p>Total</p><span><?= $fav_total_count ?></span></div>
    <div class="stat-card"><p>Breakfast</p><span><?= $fav_breakfast_count ?></span></div>
    <div class="stat-card"><p>Lunch</p><span><?= $fav_lunch_count ?></span></div>
    <div class="stat-card"><p>Snacks</p><span><?= $fav_snacks_count ?></span></div>
    <div class="stat-card"><p>Dinner</p><span><?= $fav_dinner_count ?></span></div>
    <div class="stat-card"><p>Dessert</p><span><?= $fav_dessert_count ?></span></div>
    <div class="stat-card"><p>Drinks</p><span><?= $fav_drinks_count ?></span></div>
  </div>

  <!-- Favourite Recipes Grid -->
  <div class="recipe-list">
    <?php if (!empty($fav_recipes)): ?>
      <?php foreach ($fav_recipes as $recipe): ?>
        <div class="recipe-card">
          <div class="recipe-image">
            <img src="uploads/<?= htmlspecialchars($recipe['image']) ?>" alt="<?= htmlspecialchars($recipe['title']) ?>">
          </div>
          <div class="recipe-info">
            <h3><?= htmlspecialchars($recipe['title']) ?></h3>
            <p class="recipe-meta">
              <span>By <?= htmlspecialchars($recipe['user_name']) ?></span> • 
              <span><?= timeAgo($recipe['created_at']) ?></span>
            </p>
            <p class="recipe-extra">
              ⭐ <?= number_format($recipe['avg_rating'], 1) ?> | 💬 <?= $recipe['comment_count'] ?>
            </p>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="no-recipes">You haven’t added any favourites yet.</p>
    <?php endif; ?>
  </div>
</div>
