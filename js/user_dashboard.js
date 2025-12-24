document.addEventListener("DOMContentLoaded", function () {
  const navLinks = document.querySelectorAll(".nav-links a");
  const mainSections = document.querySelectorAll(".main-section");

  // Dashboard nav and feed subsections
  const dashboardNav = document.querySelector(".dashboard-nav");
  const feedSubsections = document.querySelectorAll(".feed-subsection");

  // --- Default: show Feed section ---
  mainSections.forEach(sec => sec.style.display = "none");
  document.getElementById("feed-section").style.display = "block";
  if (dashboardNav) dashboardNav.style.display = "block";
  feedSubsections.forEach(sub => (sub.style.display = "block"));

  navLinks.forEach(link => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      const targetId = this.dataset.target;
      console.log("Clicked:", targetId);

      // Remove active class from nav links
      navLinks.forEach(nav => nav.classList.remove("active"));
      this.classList.add("active");

      // Hide all main sections
      mainSections.forEach(sec => sec.style.display = "none");

      // Show the selected section
      const targetSection = document.getElementById(targetId);
      if (!targetSection) return;

      if (targetId === "feed-section") {
        targetSection.style.display = "block";
        if (dashboardNav) dashboardNav.style.display = "block";
        feedSubsections.forEach(sub => (sub.style.display = "block"));
      } else {
        targetSection.style.display = "block";
        if (dashboardNav) dashboardNav.style.display = "none";
        feedSubsections.forEach(sub => (sub.style.display = "none"));
      }
    });
  });
});
// ///////////




// //////////////

document.addEventListener("DOMContentLoaded", function () {
    const navItems = document.querySelectorAll(".dashboard-nav .nav-item");
    const sections = document.querySelectorAll(".dashboard-section");
  
    // Hide all sections
    function hideAllSections() {
        sections.forEach(section => {
            section.style.display = "none";
        });
    }
  
    // Handle nav click
    navItems.forEach(item => {
        item.addEventListener("click", function () {
            const target = this.getAttribute("data-target");
  
            // Hide all sections
            hideAllSections(); 
            // Show the clicked section
            document.getElementById(target).style.display = "block"; 
            // Remove 'active' class from all nav items
            navItems.forEach(i => i.classList.remove("active"));
            // Add 'active' class to clicked nav item
            this.classList.add("active");
        });
    });
  
    // Initialize: show first section and make first nav active
    if (sections.length > 0) {
        sections.forEach(section => section.style.display = "none");
        sections[0].style.display = "block";
    }
  
    if (navItems.length > 0) {
        navItems.forEach(item => item.classList.remove("active"));
        navItems[0].classList.add("active");
    }

  });

// //////

// popup ko lagi

document.addEventListener("DOMContentLoaded", function () {
    // Select the New Recipe button and the modal
    const newRecipeBtn = document.querySelector('.new-recipe-btn');
    const modal = document.getElementById('recipeModal');
    const closeBtn = document.getElementById('closeModalBtn');
    

    // Open modal when clicking the New Recipe button
    newRecipeBtn.addEventListener('click', () => {
        modal.style.display = 'block';
    });

    // Close modal when clicking the X button
    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    // Close modal when clicking outside the modal content
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
});

// extra
function resetForm() {
    const form = document.getElementById('recipeForm');
    form.reset();             // Clear all inputs
    document.getElementById('recipeModal').style.display = 'none'; // Close modal
    document.getElementById('preview').src = ""; // Clear image preview if needed
}


// image preview
const imageInput = document.getElementById('recipeImage');
const previewImg = document.getElementById('preview');

imageInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        previewImg.src = "";
    }
});
//  yaha favourite ko

// yaha samma favurite

// star rating wala

document.querySelectorAll('.star-rating').forEach(container => {
    const stars = container.querySelectorAll('.star');
    const recipeId = container.dataset.recipeId;

    stars.forEach(star => {
        star.addEventListener('click', () => {
            const rating = star.dataset.value;

            // Highlight stars
            stars.forEach(s => s.classList.remove('selected'));
            stars.forEach(s => {
                if (parseFloat(s.dataset.value) <= rating) s.classList.add('selected');
            });

            // Send rating via AJAX
            fetch('user_dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `ajax=1&recipe_id=${recipeId}&rating=${rating}`
            })
            .then(res => res.json())
            .then(data => {
                // Update average rating display
                document.getElementById('avg-rating-'+recipeId).innerText =
                    `⭐ ${parseFloat(data.avg_rating).toFixed(1)} (${data.rating_count})`;
            });
        });
    });
});

// yeha samma star rating

// delete
document.querySelectorAll(".delete-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        const id = btn.dataset.id;

        if(confirm("Are you sure you want to delete this recipe?")) {
            // Send AJAX request to the same page
            fetch("user_dashboard.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "delete_id=" + id
            })
            .then(res => res.text())
            .then(() => {
                // Remove the card from the DOM instantly
                btn.closest(".recipe-card").remove();
            })
            .catch(() => {
                alert("Failed to delete recipe.");
            });
        }
    });
});

  
// edit ko update

document.addEventListener("DOMContentLoaded", () => {
    const editButtons = document.querySelectorAll(".edit-btn");
    const popup = document.getElementById("recipeModal");
    const form = document.getElementById("recipeForm");
    const previewImg = form.querySelector("#preview"); // show current image
    const oldImageInput = form.querySelector("#old_image");
  
    editButtons.forEach(btn => {
      btn.addEventListener("click", function () {
        popup.style.display = "block";
  
        // Fill form fields
        form.querySelector('input[name="id"]').value = this.dataset.id;
        form.querySelector('input[name="title"]').value = this.dataset.title;
        form.querySelector('textarea[name="description"]').value = this.dataset.description;
        form.querySelector('select[name="category"]').value = this.dataset.category;
        form.querySelector('select[name="difficulty"]').value = this.dataset.difficulty;
        form.querySelector('input[name="servings"]').value = this.dataset.servings;
        form.querySelector('input[name="cook_time"]').value = this.dataset.cook_time;
        form.querySelector('input[name="prep_time"]').value = this.dataset.prep_time;
        form.querySelector('input[name="emoji"]').value = this.dataset.emoji;
        form.querySelector('textarea[name="ingredients"]').value = this.dataset.ingredients;
        form.querySelector('textarea[name="instructions"]').value = this.dataset.instructions;
  
        // Set old image for PHP and show current preview
        oldImageInput.value = this.dataset.image;
        previewImg.src = "images/" + this.dataset.image; // adjust path if needed
      });
    });
  
    // Close modal button
    document.getElementById("closeModalBtn").addEventListener("click", () => {
      popup.style.display = "none";
      form.reset();
      previewImg.src = "";
    });
  
    // Optional: close modal by clicking outside
    window.addEventListener("click", function(event) {
      if (event.target == popup) {
        popup.style.display = "none";
        form.reset();
        previewImg.src = "";
      }
    });
  });
  

//   ------- section search ko lagi-----//

document.addEventListener("DOMContentLoaded", function() {
  const searchBar = document.querySelector(".search-bar");
  const categoryFilter = document.querySelector(".filter-category");
  const sortOrder = document.querySelector(".sort-order");
  const recipeList = document.querySelector(".recipe-list");
  const recipeCards = Array.from(document.querySelectorAll(".recipe-card"));

  // Get timestamp from data-time
  function getTimestamp(card) {
    const s = card.querySelector(".posted-time").getAttribute("data-time");
    return new Date(s.replace(" ", "T")).getTime() || 0;
  }

  // Filter by search and category
  function filterRecipes() {
    const query = searchBar.value.toLowerCase().trim();
    const selectedCategory = categoryFilter.value.toLowerCase().replace(/[^a-z]/g, '');

    recipeCards.forEach(card => {
      const recipeName = card.querySelector(".recipe-name").textContent.toLowerCase();
      const recipeCategory = card.querySelector(".recipe-type").textContent.toLowerCase().replace(/[^a-z]/g, '');

      const matchesSearch = recipeName.includes(query);
      const matchesCategory =
        selectedCategory === "allcategories" || recipeCategory.includes(selectedCategory);

      card.style.display = (matchesSearch && matchesCategory) ? "" : "none";
    });
  }

  // Sort by newest/oldest
  // function sortRecipes() {
  //   const order = sortOrder.value.toLowerCase();
  //   const visibleCards = recipeCards.filter(card => card.style.display !== "none");

  //   visibleCards.sort((a, b) => {
  //     const aT = getTimestamp(a);
  //     const bT = getTimestamp(b);
  //     return order === "oldest" ? aT - bT : bT - aT;
  //   });

  //   visibleCards.forEach(card => recipeList.appendChild(card));
  // }



  // /////////
// //////
  function updateRecipes() {
    filterRecipes();
    sortRecipes();
  }

  // Event listeners
  searchBar.addEventListener("input", updateRecipes);
  categoryFilter.addEventListener("change", updateRecipes);
  sortOrder.addEventListener("change", updateRecipes);

  // Initial run
  updateRecipes();
});

// ////
document.addEventListener("DOMContentLoaded", () => {
  const starsContainers = document.querySelectorAll(".stars");

  starsContainers.forEach(container => {
    const rating = parseFloat(container.dataset.rating) || 0;
    container.innerHTML = "";

    for (let i = 1; i <= 5; i++) {
      const star = document.createElement("span");
      star.style.fontSize = "1.2rem";
      star.style.marginRight = "2px";

      if (i <= Math.floor(rating)) {
        star.textContent = "★";
        star.style.color = "#ffd700";
      } else if (i - 0.5 <= rating) {
        star.textContent = "★";
        star.style.color = "#ffd700";
      } else {
        star.textContent = "☆";
        star.style.color = "#ddd";
      }

      container.appendChild(star);
    }

    const number = container.parentElement.querySelector(".rating-number");
    if (number) number.textContent = rating.toFixed(1);
  });
});

//  recipe model open garna

document.addEventListener('DOMContentLoaded', function() {
  function openRecipeModal(event, url) {
    event.preventDefault();

    const modal = document.getElementById('viewRecipeModal');
    const modalBody = document.getElementById('recipeModalBody');

    modal.style.display = 'flex';
    modalBody.innerHTML = 'Loading...';

    fetch(url)
      .then(response => response.text())
      .then(data => {
        modalBody.innerHTML = data;
      })
      .catch(error => {
        modalBody.innerHTML = '<p style="color:red;">Error loading recipe details.</p>';
        console.error(error);
      });
  }

  document.getElementById('closeViewModal').onclick = function() {
    document.getElementById('viewRecipeModal').style.display = 'none';
  };

  window.addEventListener('click', function(e) {
    const modal = document.getElementById('viewRecipeModal');
    if (e.target === modal) modal.style.display = 'none';
  });

  // Make the function global so it can be called in onclick=""
  window.openRecipeModal = openRecipeModal;
});

// ///// export ko lagi
function exportRecipe() {
  if (confirm("Do you want to export this recipe to the public feed?")) {
    const form = document.getElementById('recipeForm');
    document.getElementById('is_exported').value = 1;
    form.submit();
  }
}
// ////
// favourite section ko lagi

//  yaha bata trending ko

function initTrendingControls(sectionSelector) {
  const section = document.querySelector(sectionSelector);
  if (!section) return;

  const searchBar = section.querySelector(".search-bar");
  const categoryFilter = section.querySelector(".filter-category");
  const sortOrder = section.querySelector(".sort-order"); // optional, if you add sorting
  const recipeList = section.querySelector(".recipe-list");
  const recipeCards = Array.from(section.querySelectorAll(".recipe-card"));

  function getTimestamp(card) {
      const s = card.querySelector(".posted-time")?.getAttribute("data-time");
      return s ? new Date(s.replace(" ", "T")).getTime() : 0;
  }

  function filterRecipes() {
      const query = searchBar.value.toLowerCase().trim();
      const selectedCategory = categoryFilter.value.toLowerCase().replace(/[^a-z]/g, '');

      recipeCards.forEach(card => {
          const recipeName = card.querySelector(".recipe-name").textContent.toLowerCase();
          const recipeCategory = card.querySelector(".recipe-type")?.textContent.toLowerCase().replace(/[^a-z]/g, '') || '';

          const matchesSearch = recipeName.includes(query);
          const matchesCategory = selectedCategory === "allcategories" || recipeCategory.includes(selectedCategory);

          card.style.display = (matchesSearch && matchesCategory) ? "" : "none";
      });
  }

  function sortRecipes() {
      if (!sortOrder) return; // skip if no sort select
      const order = sortOrder.value.toLowerCase();
      const visibleCards = recipeCards.filter(card => card.style.display !== "none");

      visibleCards.sort((a, b) => {
          const aT = getTimestamp(a);
          const bT = getTimestamp(b);
          return order === "oldest" ? aT - bT : bT - aT;
      });

      visibleCards.forEach(card => recipeList.appendChild(card));
  }

  function updateRecipes() {
      filterRecipes();
      sortRecipes();
  }

  searchBar.addEventListener("input", updateRecipes);
  categoryFilter.addEventListener("change", updateRecipes);
  if (sortOrder) sortOrder.addEventListener("change", updateRecipes);

  updateRecipes();
}

// Initialize trending controls
document.addEventListener("DOMContentLoaded", function() {
  initTrendingControls("#trending-section");
});



// /////////////////////////////////
// mathi ko search ko lagi 
// //////////////////
let isCategorySearch = false; // flag to differentiate category vs manual search

function handleSearch(query) {
  const dropdown = document.getElementById("search-dropdown");
  const liveResults = document.getElementById("live-results");
  const defaultContent = document.querySelector(".search-default");
  const input = document.getElementById("search-input");

  if (!dropdown || !liveResults || !defaultContent) return;

  query = query.trim();
  if (!query) {
    liveResults.innerHTML = "";
    defaultContent.style.display = "block";
    dropdown.classList.remove("hidden");
    return showPastSearches();
  }

  // Hide default content
  defaultContent.style.display = "none";

  // Fetch live results (never save on typing)
  fetch("search_recipes.php?q=" + encodeURIComponent(query))
    .then(res => res.json())
    .then(data => {
      liveResults.innerHTML = "";

      if (data.length > 0) {
        data.forEach(item => {
          const imageSrc = item.image && item.image.trim() !== "" ? item.image : "images/default.png";

          const div = document.createElement("div");
          div.className = "search-result-item";

          const img = document.createElement("img");
          img.src = imageSrc;
          img.alt = item.title;

          const textDiv = document.createElement("div");
          textDiv.innerHTML = `<strong>${item.title}</strong><br><small>${item.category}</small>`;

          div.appendChild(img);
          div.appendChild(textDiv);

          // Clicking a live result → open modal AND save that recipe
          div.addEventListener("click", () => {
            const modal = document.getElementById("viewRecipeModal");
            const modalBody = document.getElementById("recipeModalBody");

            fetch("recipe_details.php?id=" + item.id)
              .then(res => res.text())
              .then(html => {
                modalBody.querySelector(".view-modal-content-wrapper").innerHTML = html;
                modal.style.display = "block";

                // Save clicked recipe to past searches
                fetch("save_user_search.php?q=" + encodeURIComponent(item.title))
                  .catch(err => console.error(err));

                // Clear input only if it was a manual typed search
                if (!isCategorySearch) input.value = "";
                isCategorySearch = false; // reset flag after click
              })
              .catch(err => console.error(err));
          });

          liveResults.appendChild(div);
        });
      } else {
        liveResults.innerHTML = `<div class="search-result-item">No results found</div>`;
      }

      dropdown.classList.remove("hidden");
    })
    .catch(err => console.error(err));
}

// Show past searches
function showPastSearches() {
  fetch("get_user_searches.php")
    .then(res => res.json())
    .then(data => {
      const container = document.getElementById("past-searches-buttons");
      if (!container) return;
      container.innerHTML = "";

      if (!data.length) {
        container.innerHTML = `<p>No past searches yet</p>`;
        return;
      }

      data.slice(0, 9).forEach(fullQuery => {
        const btn = document.createElement("button");
        btn.textContent = fullQuery;
        btn.addEventListener("click", () => {
          document.getElementById("search-input").value = "";
          handleSearch(fullQuery); // search but do NOT save again
        });
        container.appendChild(btn);
      });
    })
    .catch(err => console.error(err));
}

// Category click → save category search
function searchByCategory(category) {
  const input = document.getElementById("search-input");
  input.value = "";
  isCategorySearch = true; // set flag
  handleSearch(category);
  fetch("save_user_search.php?q=" + encodeURIComponent(category)).catch(console.error);
}

// Search button click → save typed search
document.getElementById("search-btn")?.addEventListener("click", function () {
  const input = document.getElementById("search-input");
  const query = input.value.trim();
  if (!query) return;
  handleSearch(query);
  fetch("save_user_search.php?q=" + encodeURIComponent(query)).catch(console.error);
  input.value = ""; // clear input
});

// Enter key → save typed search
document.getElementById("search-input")?.addEventListener("keydown", function (e) {
  if (e.key === "Enter") {
    e.preventDefault();
    const query = this.value.trim();
    if (!query) return;
    handleSearch(query);
    fetch("save_user_search.php?q=" + encodeURIComponent(query)).catch(console.error);
    this.value = ""; // clear input
  }
});

// Live typing → never save
document.getElementById("search-input")?.addEventListener("input", function () {
  handleSearch(this.value);
});

// Show past searches on focus if input empty
document.getElementById("search-input")?.addEventListener("focus", () => {
  const input = document.getElementById("search-input");
  if (!input.value.trim()) handleSearch("");
});

// Hide dropdown when clicking outside
document.addEventListener("click", function (e) {
  const dropdown = document.getElementById("search-dropdown");
  const input = document.getElementById("search-input");
  if (!dropdown || !input) return;
  if (!input.contains(e.target) && !dropdown.contains(e.target)) {
    dropdown.classList.add("hidden");
  }
});
/// search ko sakiyo////////

///// logo profile ///
// //// Profile Dropdown + Add Another Account ////

document.addEventListener("DOMContentLoaded", function () {
  const profiles = document.querySelectorAll(".profile");

  profiles.forEach(profile => {
    const icon = profile.querySelector(".profile-pic"); // use the image
    const dropdown = profile.querySelector(".profile-dropdown");
    const logoutLink = dropdown.querySelector('a[href="logout.php"]');

    // Toggle dropdown on profile image click
    icon.addEventListener("click", function (e) {
      e.stopPropagation();
      // Close other dropdowns
      document.querySelectorAll(".profile-dropdown.show").forEach(dd => {
        if (dd !== dropdown) dd.classList.remove("show");
      });
      dropdown.classList.toggle("show");
    });

    // Prevent closing when clicking inside dropdown
    dropdown.addEventListener("click", e => e.stopPropagation());

    // Logout confirmation
    if (logoutLink) {
      logoutLink.addEventListener("click", e => {
        if (!confirm("Do you really want to logout?")) e.preventDefault();
      });
    }
  });

  // Close all dropdowns when clicking outside
  document.addEventListener("click", () => {
    document.querySelectorAll(".profile-dropdown.show").forEach(dd => dd.classList.remove("show"));
  });
});

//  comment ko
// let currentRecipeId = null;

// function openComments(recipeId) {
//     currentRecipeId = recipeId;
//     const modal = document.getElementById('commentModal'); 
//     modal.classList.add('show'); 
//     loadComments(recipeId);
// }

// function closeComments() {
//     const modal = document.getElementById('commentModal');
//     modal.classList.remove('show'); 
//     document.getElementById('commentsContainer').innerHTML = '';
//     document.getElementById('newComment').value = '';
// }

// function loadComments(recipeId) {
//     fetch(`get_comments.php?recipe_id=${recipeId}`)
//     .then(res => res.json())
//     .then(data => {
//         const container = document.getElementById('commentsContainer');
//         container.innerHTML = '';
//         data.forEach(comment => {
//             const deleteBtn = comment.can_delete 
//                 ? `<button onclick="deleteComment(${comment.id})" class="delete-btn">❌</button>` 
//                 : '';
//             container.innerHTML += `<div class="comment"><strong>${comment.username}</strong>: ${comment.comment} ${deleteBtn}</div>`;
//         });
//     })
//     .catch(err => console.error('Failed to load comments:', err));
// }

// function submitComment() {
//     const text = document.getElementById('newComment').value.trim();
//     if (!text) return;

//     fetch('submit_comment.php', {
//         method: 'POST',
//         headers: {'Content-Type': 'application/json'},
//         body: JSON.stringify({recipe_id: currentRecipeId, comment: text})
//     })
//     .then(res => res.json())
//     .then(data => {
//         if (data.success) {
//             loadComments(currentRecipeId);
//             document.getElementById('newComment').value = '';
//         } else {
//             alert('Failed to submit comment.');
//         }
//     })
//     .catch(err => console.error('Failed to submit comment:', err));
// }

// function deleteComment(commentId) {
//     if (!confirm('Are you sure you want to delete this comment?')) return;

//     fetch('delete_comment.php', {
//         method: 'POST',
//         headers: {'Content-Type': 'application/json'},
//         body: JSON.stringify({comment_id: commentId})
//     })
//     .then(res => res.json())
//     .then(data => {
//         if (data.success) {
//             loadComments(currentRecipeId);
//         } else {
//             alert('Failed to delete comment.');
//         }
//     })
//     .catch(err => console.error('Failed to delete comment:', err));
// }
let currentRecipeId = null;

// Open the comment modal
function openComments(recipeId) {
    currentRecipeId = recipeId;
    const modal = document.getElementById('commentModal');
    modal.classList.add('show');
    loadComments(recipeId);
}

// Close the comment modal
function closeComments() {
    const modal = document.getElementById('commentModal');
    modal.classList.remove('show');
    document.getElementById('commentsContainer').innerHTML = '';
    document.getElementById('newComment').value = '';
}

// Load comments for a recipe
function loadComments(recipeId) {
    fetch(`get_comments.php?recipe_id=${recipeId}`)
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('commentsContainer');
            container.innerHTML = '';

            if (!data.length) {
                container.innerHTML = '<p class="no-comments">No comments yet.</p>';
                return;
            }

            data.forEach(comment => {
                const commentDiv = document.createElement('div');
                commentDiv.classList.add('comment');

                // Left side: username + comment
                const commentLeft = document.createElement('div');
                commentLeft.classList.add('comment-left');
                commentLeft.innerHTML = `
                    <strong class="comment-username">${comment.username}</strong>
                    <div class="comment-body">${comment.comment}</div>
                `;

                commentDiv.appendChild(commentLeft);

                // Right side: delete button if allowed
                if (comment.can_delete) {
                    const deleteBtn = document.createElement('button');
                    deleteBtn.classList.add('delete-btn');
                    deleteBtn.textContent = 'Delete';
                    deleteBtn.onclick = () => deleteComment(comment.id, commentDiv);
                    commentDiv.appendChild(deleteBtn);
                }

                container.appendChild(commentDiv);
            });
        })
        .catch(err => console.error('Failed to load comments:', err));
}

// Submit a new comment
function submitComment() {
    const text = document.getElementById('newComment').value.trim();
    if (!text) return;

    fetch('submit_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ recipe_id: currentRecipeId, comment: text })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadComments(currentRecipeId);
            document.getElementById('newComment').value = '';
            // yo add gareko
            // 
        } else {
            alert(data.message || 'Failed to submit comment.');
        }
    })
    .catch(err => console.error('Failed to submit comment:', err));
}

// Delete a comment
function deleteComment(commentId, commentDiv) {
    if (!confirm('Are you sure you want to delete this comment?')) return;

    fetch('delete_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ comment_id: commentId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Remove the comment from DOM immediately
            commentDiv.remove();
            // 

            // 
        } else {
            alert(data.message || 'Failed to delete comment.');
        }
    })
    .catch(err => console.error('Failed to delete comment:', err));
}

// Update the comment count shown on the recipe card
function updateCommentCount(recipeId) {
  fetch(`get_comment_count.php?id=${recipeId}`)
      .then(res => res.text())
      .then(count => {
          const btn = document.querySelector(`.comments-btn[onclick="openComments(${recipeId})"]`);
          if (btn) {
              btn.textContent = `💬 ${count}`;
          }
      })
      .catch(err => console.error('Failed to update comment count:', err));
}
// /////////////////////////
// notification ko lagi

const notifIcon = document.getElementById('notification');
const notifDropdown = document.getElementById('notification-dropdown');
const notifCount = document.getElementById('notif-count');

notifIcon.addEventListener('click', () => {
    notifDropdown.classList.toggle('hidden');

    // Mark notifications as read when dropdown opens
    if (!notifDropdown.classList.contains('hidden')) {
        fetch('mark_read.php')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    notifCount.textContent = '';
                }
            });
    }
});

// Fetch notifications dynamically
function loadNotifications() {
    fetch('notifications.php')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const notifList = notifDropdown.querySelector('.notif-list');
                notifList.innerHTML = '';

                data.notifications.forEach(notif => {
                    notifList.innerHTML += `<div class="notif-item">${notif.message}</div>`;
                });

                // Update badge count
                notifCount.textContent = data.unread_count > 0 ? data.unread_count : '';
            }
        });
}

// Optional: auto-refresh every 10 seconds
setInterval(loadNotifications, 10000);
loadNotifications();

// Clear all notifications
function clearNotifications() {
    fetch('mark_read.php') // marks all as read
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                notifDropdown.querySelector('.notif-list').innerHTML = '';
                notifCount.textContent = '';
            }
        });
}
