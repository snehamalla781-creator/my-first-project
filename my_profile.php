<?php
session_start();
include_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Determine which profile to show
$logged_in_user = $_SESSION['user_id'];
$profile_user_id = $_GET['id'] ?? $logged_in_user; // default to own profile

// Fetch profile user info
$user_stmt = $conn->prepare("SELECT username, photo, bio FROM users WHERE id = ?");
$user_stmt->bind_param("i", $profile_user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Fallback for photo
if (empty($user['photo'])) {
    $user['photo'] = 'images/default_profile.png';
}

// Fetch recipes for profile user
$recipes_stmt = $conn->prepare("SELECT image FROM recipes WHERE user_id = ? AND is_exported = 1");
$recipes_stmt->bind_param("i", $profile_user_id);
$recipes_stmt->execute();
$recipes_result = $recipes_stmt->get_result();
$posts = $recipes_result->fetch_all(MYSQLI_ASSOC);
$posts_count = count($posts);


// Count followers (users who follow this profile)
$followers_stmt = $conn->prepare("SELECT COUNT(*) as followers FROM followers WHERE following_id = ?");
$followers_stmt->bind_param("i", $profile_user_id);
$followers_stmt->execute();
$followers = $followers_stmt->get_result()->fetch_assoc()['followers'] ?? 0;

// Count following (users this profile follows)
$following_stmt = $conn->prepare("SELECT COUNT(*) as following FROM followers WHERE follower_id = ?");
$following_stmt->bind_param("i", $profile_user_id);
$following_stmt->execute();
$following = $following_stmt->get_result()->fetch_assoc()['following'] ?? 0;

// Check if logged-in user follows this profile
$is_following = false;
if ($profile_user_id != $logged_in_user) {
    $check_stmt = $conn->prepare("SELECT * FROM followers WHERE follower_id = ? AND following_id = ?");
    $check_stmt->bind_param("ii", $logged_in_user, $profile_user_id);
    $check_stmt->execute();
    $is_following = $check_stmt->get_result()->num_rows > 0;
}
?>
<div class="profile-top-section">
    <!-- LEFT: Profile Photo -->
    <div class="profile-left">
        <img src="<?php echo htmlspecialchars($user['photo']); ?>" 
             alt="Profile Photo" class="profile-photo">
    </div>

    <!-- RIGHT: User Info -->
    <div class="profile-right">
        <!-- Username + Edit / Follow button -->
        <div class="profile-header-row">
            <h2 class="username"><?php echo htmlspecialchars($user['username']); ?></h2>

            <?php if ($profile_user_id == $logged_in_user): ?>
                <a href="edit_profile.php" class="edit-btn">Edit Profile</a>
            <?php else: ?>
                <form action="follow_action.php" method="POST" class="follow-form">
                    <input type="hidden" name="following_id" value="<?php echo $profile_user_id; ?>">
                    <button type="submit" name="<?php echo $is_following ? 'unfollow' : 'follow'; ?>" 
                            class="follow-btn">
                        <?php echo $is_following ? 'Unfollow' : 'Follow'; ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Stats -->
        <div class="profile-stats">
            <span><strong><?php echo $posts_count; ?></strong> posts</span>
            <span><strong><?php echo $followers; ?></strong> followers</span>
            <span><strong><?php echo $following; ?></strong> following</span>
        </div>

        <!-- Bio -->
        <div class="profile-bio">
            <p><?php echo !empty($user['bio']) ? nl2br(htmlspecialchars($user['bio'])) : "No bio yet."; ?></p>
        </div>
    </div>
</div>