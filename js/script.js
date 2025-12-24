// Get elements
const authBtn = document.getElementById('authBtn');
const signupModal = document.getElementById('signupModal');
const loginModal = document.getElementById('loginModal');
const closeSignup = document.getElementById('closeSignup');
const closeLogin = document.getElementById('closeLogin');
const openLoginFromSignup = document.getElementById('openLoginFromSignup');
const openSignupFromLogin = document.getElementById('openSignupFromLogin');


// Open signup modal
authBtn.addEventListener('click', e => {
  e.preventDefault();
  signupModal.style.display = 'block';
});

// Close buttons
closeSignup.addEventListener('click', () => signupModal.style.display = 'none');
closeLogin.addEventListener('click', () => loginModal.style.display = 'none');

// Switch forms
openLoginFromSignup.addEventListener('click', e => {
  e.preventDefault();
  signupModal.style.display = 'none';
  loginModal.style.display = 'block';
});

openSignupFromLogin.addEventListener('click', e => {
  e.preventDefault();
  loginModal.style.display = 'none';
  signupModal.style.display = 'block';
});

// Close modal when clicking outside content
window.addEventListener('click', e => {
  if (e.target === signupModal) signupModal.style.display = 'none';
  if (e.target === loginModal) loginModal.style.display = 'none';
});

// Make sure this runs after the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  const signupModal = document.getElementById('signupModal');
  const forgotModal = document.getElementById('forgotModal');
  const closeForgot = document.getElementById('closeForgot');
  const openForgotFromSignup = document.getElementById('openForgotFromSignup');


  const forgotEmail = document.getElementById('forgot_email');
  // const forgotSecurityQuestion = document.getElementById('forgot_security_question');
  const forgotSecurityQuestion = document.getElementById('forgotSecurityQuestion');


  // Open forgot password modal from signup
  openForgotFromSignup.addEventListener('click', e => {
    e.preventDefault();
    signupModal.style.display = 'none'; // hide signup
    forgotModal.style.display = 'block'; // show forgot
  });

  // Close forgot password modal
  closeForgot.addEventListener('click', () => {
    forgotModal.style.display = 'none';
  });
  forgotEmail.addEventListener('blur', () => {
    const email = forgotEmail.value.trim();
    if (!email) return;
  
    fetch('get_security_question.php?email=' + encodeURIComponent(email))
      .then(res => res.text())
      .then(question => {
        if (question === 'not_found') {
          forgotSecurityQuestion.value = 'No account found';
          forgotSecurityQuestion.disabled = true;
        } else {
          forgotSecurityQuestion.value = question;
          forgotSecurityQuestion.disabled = false;
        }
      });
   });                                                 
  
  // Close modal if clicking outside
  window.addEventListener('click', e => {
    if (e.target === forgotModal) {
      forgotModal.style.display = 'none';
    }
  });
});

const openForgotFromLogin = document.getElementById('openForgotFromLogin');

openForgotFromLogin.addEventListener('click', e => {
  e.preventDefault();
  loginModal.style.display = 'none';   // hide login modal
  signupModal.style.display = 'none';  // hide signup just in case
  forgotModal.style.display = 'block'; // show forgot modal
});


// Select all buttons
const buttons = document.querySelectorAll('.show-recipe-btn');

// Add click event to each button
buttons.forEach(button => {
  button.addEventListener('click', () => {
    const details = button.nextElementSibling; // the .recipe-details div
    if (details.style.display === 'none') {
      details.style.display = 'block';
      button.textContent = 'Hide Recipe';
    } else {
      details.style.display = 'none';
      button.textContent = 'Show Recipe';
    }
  });
});


// recipe gallery
// Show login modal if guest tries to view a recipe
document.querySelectorAll('.view-recipe').forEach(link => {
  link.addEventListener('click', function(e) {
      if (!userLoggedIn) {
          e.preventDefault(); // prevent link from opening
          const loginModal = document.getElementById('loginModal');
          if (loginModal) {
              loginModal.style.display = 'flex'; // show modal
          }
      }
  });
});

// trending ko lagi

const trendingSlider = document.getElementById("trending-recipes");
const leftBtn = document.getElementById("trending-left");
const rightBtn = document.getElementById("trending-right");

leftBtn.addEventListener("click", () => {
  trendingSlider.scrollBy({ left: -300, behavior: "smooth" });
});

rightBtn.addEventListener("click", () => {
  trendingSlider.scrollBy({ left: 300, behavior: "smooth" });
});

// /// about ko lagi

