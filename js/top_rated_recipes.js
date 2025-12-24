document.addEventListener("DOMContentLoaded", () => {
    // ---------- Slider ----------
    const topRatedSlider = document.getElementById('top-rated-recipes');
    const leftArrow = document.getElementById('top-rated-left');
    const rightArrow = document.getElementById('top-rated-right');
    const slideAmount = 240;
  
    if (topRatedSlider && leftArrow && rightArrow) {
      leftArrow.addEventListener('click', () => {
        topRatedSlider.scrollBy({ left: -slideAmount, behavior: 'smooth' });
      });
      rightArrow.addEventListener('click', () => {
        topRatedSlider.scrollBy({ left: slideAmount, behavior: 'smooth' });
      });
    } else {
      console.warn('Top-rated slider elements not found:', { topRatedSlider, leftArrow, rightArrow });
    }
  
    // ---------- Favourite Heart (delegated) ----------
    document.body.addEventListener('click', async (e) => {
      const heart = e.target.closest('.favorite-heart');
      if (!heart) return;
  
      const recipeId = heart.dataset.recipeId;
      if (!recipeId) return console.warn('favorite-heart missing data-recipe-id');
  
      try {
        const res = await fetch("toggle_favourite.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: `recipe_id=${encodeURIComponent(recipeId)}`
        });
        const data = await res.json();
        if (data.success) {
          heart.classList.toggle('favorited', !!data.favourited);
          heart.textContent = data.favourited ? '❤️' : '♡';
        } else {
          alert(data.message || 'Favourite failed');
        }
      } catch (err) {
        console.error('Favourite error', err);
        alert('Something went wrong with favourites!');
      }
    });
  
    // ---------- Star rating (per element) ----------
    document.querySelectorAll(".star-rating").forEach(ratingDiv => {
      const stars = Array.from(ratingDiv.querySelectorAll(".star"));
      const recipeId = ratingDiv.dataset.recipeId;
      let userRated = ratingDiv.dataset.userRated === '1';
  
      // hover highlight
      stars.forEach((star, index) => {
        star.addEventListener('mouseenter', () => {
          if (userRated) return;
          stars.forEach((s, i) => s.classList.toggle('hovered', i <= index));
        });
        star.addEventListener('mouseleave', () => {
          if (userRated) return;
          stars.forEach(s => s.classList.remove('hovered'));
        });
      });
  
      // click
      stars.forEach(star => {
        star.addEventListener('click', async () => {
          if (userRated) {
            alert("You have already rated this recipe!");
            return;
          }
          const value = star.dataset.value;
          try {
            const res = await fetch("toggle_rating.php", {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: `recipe_id=${encodeURIComponent(recipeId)}&rating=${encodeURIComponent(value)}`
            });
            const data = await res.json();
            if (data.success) {
              // Update stars UI
              stars.forEach(s => s.classList.remove('rated'));
              stars.forEach(s => { if (parseFloat(s.dataset.value) <= parseFloat(value)) s.classList.add('rated'); });
  
              // Update image-rating and avg text if present
              const imageRating = ratingDiv.parentElement.querySelector(".image-rating");
              if (imageRating) imageRating.textContent = `⭐ ${data.avg_rating}`;
  
              const avgText = ratingDiv.parentElement.querySelector(".avg-rating-text");
              if (avgText) avgText.textContent = `⭐ ${data.avg_rating} (${data.total_ratings} ratings)`;
  
              ratingDiv.dataset.userRated = '1';
              userRated = true;
            } else {
              alert(data.message || 'Rating failed');
            }
          } catch (err) {
            console.error('Rating error', err);
            alert('Something went wrong with rating!');
          }
        });
      });
    });
  
  });
  