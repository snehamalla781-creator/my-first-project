<?php
session_start();
include 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? 0;

// Get recipe id from GET
$recipe_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch single recipe with ratings and comment count
$stmt = $conn->prepare("
    SELECT r.*, u.username AS author_name,
           COALESCE(AVG(rt.rating), 0) AS avg_rating,
           COUNT(DISTINCT rt.user_id) AS rating_count,
           COUNT(DISTINCT c.id) AS comment_count
    FROM recipes r
    JOIN users u ON u.id = r.user_id
    LEFT JOIN ratings rt ON rt.recipe_id = r.id
    LEFT JOIN comments c ON c.recipe_id = r.id
    WHERE r.id = ?
    GROUP BY r.id
");
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$result = $stmt->get_result();
$recipe = $result->fetch_assoc();

if (!$recipe) {
    echo "Recipe not found!";
    exit;
}

// --- Increment views only once per user (except author) ---
if ($user_id && $user_id != $recipe['user_id']) {
    $checkView = $conn->prepare("SELECT 1 FROM recipe_views WHERE recipe_id = ? AND user_id = ?");
    $checkView->bind_param("ii", $recipe_id, $user_id);
    $checkView->execute();
    $viewResult = $checkView->get_result();

    if ($viewResult->num_rows === 0) {
        $insertView = $conn->prepare("INSERT INTO recipe_views (recipe_id, user_id) VALUES (?, ?)");
        $insertView->bind_param("ii", $recipe_id, $user_id);
        $insertView->execute();

        $updateViews = $conn->prepare("UPDATE recipes SET views = views + 1 WHERE id = ?");
        $updateViews->bind_param("i", $recipe_id);
        $updateViews->execute();

        // Update local variable for immediate display
        $recipe['views'] = $recipe['views'] + 1;
    }
}

// Decode ingredients & instructions if stored as JSON
function parseContent($data) {
    if (!$data) return [];
    $json = json_decode($data, true);
    if (is_array($json)) return $json;
    return array_filter(array_map('trim', explode("\n", $data)));
}

$ingredients = parseContent($recipe['ingredients']);
$instructions = parseContent($recipe['instructions']);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($recipe['title']) ?></title>
    <link rel="stylesheet" href="css/recipe_details.css?v=<?php echo time(); ?>">
</head>
<body class="recipe-details-page" data-recipe-id="<?= $recipe['id'] ?>">

<div class="page-title">
    <h2>Recipe Details</h2>
</div>

<div class="recipe-detail-container">
    <div class="recipe-image-column">
        <img src="images/<?= htmlspecialchars($recipe['image']) ?>" alt="<?= htmlspecialchars($recipe['title']) ?>" class="recipe-image">
    </div>

    <div class="recipe-info-column">
        <h1 class="recipe-title"><?= htmlspecialchars($recipe['title']) ?></h1>

        <div class="recipe-details-stats">
            <div class="recipe-details-stat-card">⭐ Rating: <?= number_format($recipe['avg_rating'], 1) ?> (<?= $recipe['rating_count'] ?>)</div>
            <div class="recipe-details-stat-card">👁️ Views: <?= $recipe['views'] ?></div>
            <div class="recipe-details-stat-card">💬 Comments: <?= $recipe['comment_count'] ?></div>
            <div class="recipe-details-stat-card">⚙ Difficulty: <?= htmlspecialchars($recipe['difficulty']) ?></div>
            <div class="recipe-details-stat-card">🍽 Servings: <?= htmlspecialchars($recipe['servings']) ?></div>
            <div class="recipe-details-stat-card">⏱ Prep Time: <?= htmlspecialchars($recipe['prep_time']) ?> mins</div>
            <div class="recipe-details-stat-card">🔥 Cook Time: <?= htmlspecialchars($recipe['cook_time']) ?> mins</div>
            <div class="recipe-details-stat-card">👤 Posted by: 
                <a href="user_profile.php?id=<?= $recipe['user_id'] ?>"><?= htmlspecialchars($recipe['author_name']) ?></a>
            </div>
        </div>
    </div>
</div>

<div class="recipe-details-container">
    <div class="recipe-card">
        <h2>Description</h2>
        <p><?= htmlspecialchars($recipe['description']) ?></p>
    </div>

    <div class="recipe-card">
        <h2>Ingredients</h2>
        <?php if (!empty($ingredients)): ?>
            <ul>
                <?php foreach($ingredients as $ing): ?>
                    <li><?= htmlspecialchars($ing) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><em>No ingredients added.</em></p>
        <?php endif; ?>
    </div>

    <div class="recipe-card">
        <h2>Instructions</h2>
        <?php if (!empty($instructions)): ?>
            <ol>
                <?php foreach($instructions as $step): ?>
                    <li><?= htmlspecialchars($step) ?></li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <p><em>No instructions added.</em></p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
