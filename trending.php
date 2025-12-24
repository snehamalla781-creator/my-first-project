<?php
include 'db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;

// Fetch trending recipes based on views + favourites + ratings
$query = "
    SELECT r.*,
           u.username AS user_name,
           COALESCE(AVG(rt.rating), 0) AS avg_rating,
           COUNT(rt.id) AS rating_count,
           SUM(f.id IS NOT NULL) AS favourite_count,
           ur.rating AS user_rating,
           MAX(ur.user_id IS NOT NULL) AS user_rated,
           (r.views + SUM(f.id IS NOT NULL) * 5 + COALESCE(AVG(rt.rating),0) * 3) AS trending_score
    FROM recipes r
    JOIN users u ON u.id = r.user_id
    LEFT JOIN ratings rt ON rt.recipe_id = r.id
    LEFT JOIN ratings ur ON ur.recipe_id = r.id AND ur.user_id = ?
    LEFT JOIN favourites f ON f.recipe_id = r.id
    WHERE r.is_exported = 1
    GROUP BY r.id
    ORDER BY trending_score DESC, r.id DESC
    LIMIT 12
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0):
    while ($row = $result->fetch_assoc()):

        $recipe_id = $row['id'];
        $imagePath = !empty($row['image']) ? "images/" . htmlspecialchars($row['image']) : "images/default_recipe.png";

        // Favorite check
        $isFav = false;
        if ($user_id) {
            $favStmt = $conn->prepare("SELECT 1 FROM favourites WHERE user_id = ? AND recipe_id = ?");
            $favStmt->bind_param("ii", $user_id, $recipe_id);
            $favStmt->execute();
            $favResult = $favStmt->get_result();
            $isFav = $favResult && $favResult->num_rows > 0;
        }
?>
<div class="feed-recipe-card">
    <div class="recipe-image-container">
        <a href="recipe_details.php?id=<?= $recipe_id ?>" onclick="openRecipeModal(event, this.href)">
            <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($row['title']) ?>" class="recipe-image">
        </a>

        <!-- Favorite Heart -->
        <div class="favorite-heart" data-recipe-id="<?= $recipe_id ?>"><?= $isFav ? '❤️' : '♡' ?></div>

        <!-- Average Rating Badge -->
        <div class="image-rating">⭐ <?= round($row['avg_rating'], 1) ?></div>
    </div>

    <!-- Clickable 5-star rating -->
    <div class="star-rating" data-recipe-id="<?= $recipe_id ?>" data-user-rated="<?= $row['user_rated'] ? 1 : 0 ?>">
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <span class="star <?= ($i <= ($row['user_rating'] ?? 0)) ? 'rated' : '' ?>" data-value="<?= $i ?>">★</span>
        <?php endfor; ?>
    </div>

    <!-- Average Rating Text -->
    <p class="avg-rating-text">⭐ <?= round($row['avg_rating'], 1) ?> (<?= $row['rating_count'] ?> ratings)</p>

    <div class="recipe-info">
        <h3 class="recipe-name"><?= htmlspecialchars($row['title']) ?>
            <span class="posted-time"><?= date("M d, Y", strtotime($row['created_at'])) ?></span>
        </h3>

        <p class="recipe-description"><?= htmlspecialchars(substr($row['description'],0,60)) ?>...</p>
        <p class="recipe-type"><?= htmlspecialchars($row['category']) ?> • <?= htmlspecialchars($row['difficulty']) ?></p>
        <p class="recipe-user">By <a href="profile.php?id=<?= $row['user_id'] ?>"><?= htmlspecialchars($row['user_name']) ?></a></p>

        <div class="recipe-stats">
            <span>👁️ <?= $row['views'] ?></span>
            <button class="comments-btn">💬 0</button>
        </div>
    </div>
</div>
<?php
    endwhile;
else:
    echo "<p>No trending recipes found this week.</p>";
endif;
?>
