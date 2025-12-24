// ====== RECENT RECIPE SLIDER ======
const recentSlider = document.getElementById('recent-recipes');
const leftArrow = document.getElementById('recent-left');
const rightArrow = document.getElementById('recent-right');
const slideAmount = 240;

if (leftArrow && rightArrow && recentSlider) {
    leftArrow.addEventListener('click', () => {
        recentSlider.scrollBy({ left: -slideAmount, behavior: 'smooth' });
    });

    rightArrow.addEventListener('click', () => {
        recentSlider.scrollBy({ left: slideAmount, behavior: 'smooth' });
    });
}

// ====== MAIN LOGIC ======
document.addEventListener("DOMContentLoaded", () => {

    document.addEventListener("DOMContentLoaded", () => {

        document.addEventListener("click", (e) => {
            const heart = e.target.closest(".favorite-heart");
            if (!heart) return;
    
            const recipeId = heart.dataset.recipeId;
            if (!recipeId) return;
    
            // Prevent multiple clicks
            if (heart.dataset.loading === '1') return;
            heart.dataset.loading = '1';
    
            // Toggle UI immediately
            const currentlyFav = heart.classList.contains('favorited');
            heart.classList.toggle('favorited');
            heart.textContent = currentlyFav ? '♡' : '❤️';
    
            fetch("toggle_favourite.php", {
                method: "POST",
                body: new URLSearchParams({ recipe_id: recipeId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Sync UI with server response
                    if (data.favourited) {
                        heart.classList.add('favorited');
                        heart.textContent = '❤️';
                        showPopupMessage('Added to favourites!');
                    } else {
                        heart.classList.remove('favorited');
                        heart.textContent = '♡';
                        showPopupMessage('Removed from favourites!');
                    }
                } else {
                    // Revert UI if server failed
                    heart.classList.toggle('favorited');
                    heart.textContent = currentlyFav ? '❤️' : '♡';
                    alert(data.message);
                }
            })
            .catch(() => {
                // Revert UI if fetch failed
                heart.classList.toggle('favorited');
                heart.textContent = currentlyFav ? '❤️' : '♡';
                alert("Something went wrong with favourites!");
            })
            .finally(() => {
                heart.dataset.loading = '0';
            });
        });
    
    });
    
    
   
    // --- ⭐ Star Rating ---
    document.querySelectorAll(".star-rating").forEach(ratingDiv => {
        const stars = ratingDiv.querySelectorAll(".star");
        const recipeId = ratingDiv.dataset.recipeId;

        // Check if user already rated
        let userRated = ratingDiv.dataset.userRated === '1';

        // --- Hover effect ---
        stars.forEach((star, index) => {
            star.addEventListener("mouseenter", () => {
                if (userRated) return; // do nothing if already rated
                stars.forEach((s, i) => s.classList.toggle("hovered", i <= index));
            });

            star.addEventListener("mouseleave", () => {
                if (userRated) return; // do nothing if already rated
                stars.forEach(s => s.classList.remove("hovered"));
            });
        });

        // --- Click event ---
        stars.forEach(star => {
            star.addEventListener("click", () => {
                if (userRated) {
                    alert("You have already rated this recipe!");
                    return;
                }

                const value = star.dataset.value;

                fetch("toggle_rating.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `recipe_id=${recipeId}&rating=${value}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Fill stars according to clicked rating
                        stars.forEach(s => s.classList.remove('rated'));
                        stars.forEach(s => {
                            if (s.dataset.value <= value) s.classList.add('rated');
                        });

                        // Update average rating badge
                        const imageRating = ratingDiv.parentElement.querySelector(".image-rating");
                        if (imageRating) imageRating.textContent = `⭐ ${data.avg_rating}`;

                        // Update average rating text below stars
                        const avgText = ratingDiv.parentElement.querySelector(".avg-rating-text");
                        if (avgText) avgText.textContent = `⭐ ${data.avg_rating} (${data.total_ratings} ratings)`;

                        // Mark as rated
                        ratingDiv.dataset.userRated = '1';
                        userRated = true;
                    } else {
                        alert(data.message);
                    }
                })
                .catch(() => alert("Something went wrong with rating!"));
            });
        });
    });
});
