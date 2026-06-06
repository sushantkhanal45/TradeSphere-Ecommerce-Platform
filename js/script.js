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
function showToast(message, type = "success") {
    let toast = document.getElementById("cartToast");

    if (!toast) {
        toast = document.createElement("div");
        toast.id = "cartToast";
        toast.style.position = "fixed";
        toast.style.top = "20px";
        toast.style.right = "20px";
        toast.style.padding = "14px 18px";
        toast.style.borderRadius = "12px";
        toast.style.color = "#ffffff";
        toast.style.fontWeight = "700";
        toast.style.zIndex = "99999";
        toast.style.opacity = "0";
        toast.style.transform = "translateY(-15px)";
        toast.style.transition = "0.3s ease";
        toast.style.boxShadow = "0 10px 24px rgba(0,0,0,0.18)";
        document.body.appendChild(toast);
    }

    if (type === "error") {
        toast.style.background = "#dc2626";
    } else if (type === "warning") {
        toast.style.background = "#f59e0b";
    } else {
        toast.style.background = "#16a34a";
    }

    toast.innerText = message;
    toast.style.opacity = "1";
    toast.style.transform = "translateY(0)";

    setTimeout(() => {
        toast.style.opacity = "0";
        toast.style.transform = "translateY(-15px)";
    }, 2500);
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
            document.querySelectorAll(".notification-item.unread").forEach(item => {
                item.classList.remove("unread");
            });

            document.querySelectorAll(".notification-count").forEach(badge => {
                badge.remove();
            });

            // No toast needed here
        } else {
            showToast(data.message || "Could not mark notifications as read.", "error");
        }
    })
    .catch(() => {
        showToast("Network error while updating notifications.", "error");
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
function markSingleNotificationRead(event, notificationId, redirectUrl) {
    event.preventDefault();

    const formData = new URLSearchParams();
    formData.append("notification_id", notificationId);

    fetch("ajax_mark_single_notification_read.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(() => {
        window.location.href = redirectUrl;
    })
    .catch(() => {
        window.location.href = redirectUrl;
    });
}
