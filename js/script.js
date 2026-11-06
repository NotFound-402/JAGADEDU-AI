// ===============================
// MENU NAVIGASI MOBILE
// ===============================

const menuToggle = document.getElementById("menuToggle");
const navMenu = document.getElementById("navMenu");

menuToggle.addEventListener("click", function () {
    navMenu.classList.toggle("show");

    if (navMenu.classList.contains("show")) {
        menuToggle.innerHTML = "✕";
    } else {
        menuToggle.innerHTML = "☰";
    }
});

// Menutup menu setelah link diklik
const navLinks = document.querySelectorAll(".nav-menu a");

navLinks.forEach(function (link) {
    link.addEventListener("click", function () {
        navMenu.classList.remove("show");
        menuToggle.innerHTML = "☰";
    });
});


// ===============================
// MODE GELAP DAN TERANG
// ===============================

const themeButton = document.getElementById("themeButton");

themeButton.addEventListener("click", function () {

    document.body.classList.toggle("light-mode");

    if (document.body.classList.contains("light-mode")) {
        themeButton.innerHTML = "☀";
    } else {
        themeButton.innerHTML = "☾";
    }

});


// ===============================
// ANIMASI SAAT SCROLL
// ===============================

const animatedElements = document.querySelectorAll(
    ".feature-card, .wisdom-card, .learning-card"
);

const observer = new IntersectionObserver(
    function (entries) {

        entries.forEach(function (entry) {

            if (entry.isIntersecting) {
                entry.target.classList.add("visible");
            }

        });

    },
    {
        threshold: 0.15
    }
);

animatedElements.forEach(function (element) {

    element.classList.add("reveal");

    observer.observe(element);

});


// ===============================
// TAHUN OTOMATIS
// ===============================

const footerCopy = document.querySelector(".footer-copy");

if (footerCopy) {
    footerCopy.innerHTML =
        `© ${new Date().getFullYear()} JAGATEDU AI. All Rights Reserved.`;
}


// ===============================
// EFEK NAVBAR SAAT SCROLL
// ===============================

window.addEventListener("scroll", function () {

    const navbar = document.querySelector(".navbar");

    if (window.scrollY > 50) {
        navbar.classList.add("navbar-scrolled");
    } else {
        navbar.classList.remove("navbar-scrolled");
    }

});