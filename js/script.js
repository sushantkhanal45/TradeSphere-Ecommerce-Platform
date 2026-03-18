/* =========================
   PROFILE DROPDOWN ONLY
========================= */

const profileToggle = document.getElementById("profileToggle");
const profileDropdown = document.getElementById("profileDropdown");

if (profileToggle && profileDropdown) {

    // toggle dropdown
    profileToggle.addEventListener("click", function (e) {
        e.stopPropagation();

        if (profileDropdown.style.display === "block") {
            profileDropdown.style.display = "none";
        } else {
            profileDropdown.style.display = "block";
        }
    });

    // close when clicking outside
    window.addEventListener("click", function (e) {
        if (!e.target.closest(".profile-menu")) {
            profileDropdown.style.display = "none";
        }
    });
}