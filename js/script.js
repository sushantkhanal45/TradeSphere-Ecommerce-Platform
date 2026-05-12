document.addEventListener("DOMContentLoaded", function () {

    // MENU TOGGLE
    const menuToggle = document.getElementById("menuToggle");
    const navLinks = document.getElementById("navLinks");

    if (menuToggle && navLinks) {
        menuToggle.addEventListener("click", () => {
            navLinks.classList.toggle("active");
        });
    }

    // PROFILE DROPDOWN
    const profileToggle = document.getElementById("profileToggle");
    const profileDropdown = document.getElementById("profileDropdown");

    if (profileToggle && profileDropdown) {
        profileToggle.addEventListener("click", function (e) {
            e.stopPropagation();
            profileDropdown.classList.toggle("show");
        });

        profileDropdown.addEventListener("click", function (e) {
            e.stopPropagation();
        });

        document.addEventListener("click", function () {
            profileDropdown.classList.remove("show");
        });
    }

});

function updateCartBadge(count) {
    const cart = document.getElementById("floatingCart");
    if (!cart) return;

    let badge = cart.querySelector(".cart-count-badge");

    if (count > 0) {
        if (!badge) {
            badge = document.createElement("span");
            badge.className = "cart-count-badge";
            cart.appendChild(badge);
        }

        badge.textContent = count;
        badge.classList.remove("badge-pop");
        void badge.offsetWidth;
        badge.classList.add("badge-pop");
    } else {
        if (badge) {
            badge.remove();
        }
        cart.classList.remove("cart-active");
    }
}

// TOAST FUNCTION
function showToast(message) {
    let toast = document.getElementById("cartToast");

    if (!toast) {
        toast = document.createElement("div");
        toast.id = "cartToast";
        toast.style.position = "fixed";
        toast.style.bottom = "20px";
        toast.style.right = "20px";
        toast.style.background = "#111827";
        toast.style.color = "#ffffff";
        toast.style.padding = "12px 16px";
        toast.style.borderRadius = "10px";
        toast.style.boxShadow = "0 10px 24px rgba(0,0,0,0.18)";
        toast.style.zIndex = "9999";
        toast.style.opacity = "0";
        toast.style.transition = "0.3s ease";
        document.body.appendChild(toast);
    }

    toast.innerText = message;
    toast.style.opacity = "1";

    setTimeout(() => {
        toast.style.opacity = "0";
    }, 2000);
}
function toggleNotifications() {
    const dropdown = document.getElementById("notificationDropdown");

    if (dropdown) {
        dropdown.classList.toggle("show");
    }
}

function markNotificationsRead() {
    fetch("ajax_mark_notifications_read.php", {
        method: "POST"
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            window.location.reload();
        }
    })
    .catch(() => {
        console.log("Could not mark notifications as read.");
    });
}

document.addEventListener("click", function(e) {
    const wrap = document.querySelector(".notification-wrap");

    if (wrap && !wrap.contains(e.target)) {
        const dropdown = document.getElementById("notificationDropdown");

        if (dropdown) {
            dropdown.classList.remove("show");
        }
    }
});