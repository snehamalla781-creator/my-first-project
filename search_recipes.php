<?php
include 'db_connect.php';
header('Content-Type: application/json');

$q = $_GET['q'] ?? '';
if (empty($q)) {
    echo json_encode([]);
    exit;
}

// Only search exported/public recipes
// Assuming you have a column like `is_exported` = 1 for exported recipes
$stmt = $conn->prepare("SELECT id, title, category, image FROM recipes WHERE is_exported=1 AND (title LIKE ? OR category LIKE ?) LIMIT 10");
$search = "%" . $q . "%";
$stmt->bind_param("ss", $search, $search);
$stmt->execute();
$result = $stmt->get_result();

$recipes = [];
while ($row = $result->fetch_assoc()) {
    // Handle image path
    if (empty($row['image'])) {
        $row['image'] = 'images/default.png';
    } else {
        $row['image'] = 'images/' . $row['image'];
    }
    $recipes[] = $row;
}

echo json_encode($recipes, JSON_UNESCAPED_SLASHES);
?>
