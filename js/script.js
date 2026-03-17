const toggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");

if (toggle && navLinks) {
    toggle.addEventListener("click", () => {
        navLinks.classList.toggle("active");
    });

    document.querySelectorAll("#navLinks a").forEach(link => {
        link.addEventListener("click", () => {
            navLinks.classList.remove("active");
        });
    });
}

const profileToggle = document.getElementById("profileToggle");
const profileDropdown = document.getElementById("profileDropdown");

if (profileToggle && profileDropdown) {
    profileToggle.addEventListener("click", function (e) {
        e.stopPropagation();
        profileDropdown.style.display =
            profileDropdown.style.display === "block" ? "none" : "block";
    });

    window.addEventListener("click", function (e) {
        if (!e.target.closest(".profile-menu")) {
            profileDropdown.style.display = "none";
        }
    });
}