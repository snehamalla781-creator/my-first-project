<?php
include 'db_connect.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0; // logged-in user

// Fetch exported recipes with user info + ratings, comments, favorite status
$query = "
  SELECT r.*, 
         u.id AS user_id, 
         u.username AS user_name,
         COALESCE(AVG(rt.rating), 0) AS avg_rating,
         COUNT(DISTINCT rt.user_id) AS rating_count,   
         COUNT(DISTINCT c.id) AS comment_count,
         ur.rating AS user_rating,                     
         MAX(rt.user_id = $user_id) AS user_rated
  FROM recipes r
  JOIN users u ON u.id = r.user_id
  LEFT JOIN ratings rt ON rt.recipe_id = r.id
  LEFT JOIN ratings ur ON ur.recipe_id = r.id AND ur.user_id = $user_id
  LEFT JOIN comments c ON c.recipe_id = r.id
  WHERE r.is_exported = 1
  GROUP BY r.id
  ORDER BY r.created_at DESC
  LIMIT 12
";

$result = $conn->query($query);

if ($result && $result->num_rows > 0):
    while ($row = $result->fetch_assoc()):
        $recipe_id = $row['id'];
        $author_id = $row['user_id'];      // recipe author
        $userRated = $row['user_rated'] ? true : false;

        // --- Increase views only once per user, except author ---
        if ($user_id && $user_id != $author_id) {
            $checkView = $conn->prepare("SELECT 1 FROM recipe_views WHERE recipe_id = ? AND user_id = ?");
            $checkView->bind_param("ii", $recipe_id, $user_id);
            $checkView->execute();
            $viewResult = $checkView->get_result();

            if ($viewResult->num_rows === 0) {
                // Insert new view record
                $insertView = $conn->prepare("INSERT INTO recipe_views (recipe_id, user_id) VALUES (?, ?)");
                $insertView->bind_param("ii", $recipe_id, $user_id);
                $insertView->execute();

                // Increment total views in recipes table
                $updateViews = $conn->prepare("UPDATE recipes SET views = views + 1 WHERE id = ?");
                $updateViews->bind_param("i", $recipe_id);
                $updateViews->execute();

                // --- Update $row['views'] so it shows immediately ---
                $row['views'] = $row['views'] + 1;
            }
        }

        // --- Check if favorite ---
        $isFav = false;
        if ($user_id) {
            $checkFav = $conn->prepare("SELECT 1 FROM favourites WHERE user_id = ? AND recipe_id = ?");
            $checkFav->bind_param("ii", $user_id, $recipe_id);
            $checkFav->execute();
            $favResult = $checkFav->get_result();
            $isFav = $favResult && $favResult->num_rows > 0;
        }

        // --- Image path ---
        $imagePath = !empty($row['image']) ? "images/" . htmlspecialchars($row['image']) : "images/default_recipe.png";
?>

<div class="feed-recipe-card">
  <div class="recipe-image-container">
    <a href="recipe_details.php?id=<?= $recipe_id ?>" onclick="openRecipeModal(event, this.href)">
      <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($row['title']) ?>" class="recipe-image">
    </a>

    <!-- Favorite Heart -->
    <div class="favorite-heart" data-recipe-id="<?= $recipe_id ?>"><?= $isFav ? '❤️' : '♡' ?></div>

<!-- Average Rating Badge -->
<div class="image-rating">⭐ <?= number_format($row['avg_rating'], 1) ?></div>
</div>

<!-- Clickable 5-star rating -->
<div class="star-rating" data-recipe-id="<?= $recipe_id ?>" data-user-rated="<?= $row['user_rating'] > 0 ? 1 : 0 ?>">
    <?php for ($i = 1; $i <= 5; $i++): ?>
        <span class="star <?= ($i <= $row['user_rating']) ? 'rated' : '' ?>" data-value="<?= $i ?>">★</span>
    <?php endfor; ?>
</div>

<!-- Average Rating Text (for JS updates) -->
<p class="avg-rating-text">⭐ <?= number_format($row['avg_rating'], 1) ?> (<?= $row['rating_count'] ?? 0 ?> ratings)</p>

  <div class="recipe-info">
    <h3 class="recipe-name"><?= htmlspecialchars($row['title']) ?>
      <span class="posted-time"><?= timeAgo($row['created_at']) ?></span>
    </h3>

    <p class="recipe-description"><?= htmlspecialchars(substr($row['description'],0,60)) ?>...</p>
    <p class="recipe-type"><?= htmlspecialchars($row['category']) ?> • <?= htmlspecialchars($row['difficulty']) ?></p>
    <p class="recipe-user">By <a href="profile.php?id=<?= $row['user_id'] ?>"><?= htmlspecialchars($row['user_name']) ?></a></p>

    <div class="recipe-stats">
      <span>👁️ <?= $row['views'] ?></span>
      <button class="comments-btn" onclick="openComments(<?= $recipe_id ?>)">💬 <?= $row['comment_count'] ?></button>
    </div>
  </div>
</div>
<?php
  endwhile;
else:
  echo "<p>No exported recipes found yet.</p>";
endif;
?>
