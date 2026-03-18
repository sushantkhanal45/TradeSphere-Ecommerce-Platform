document.addEventListener("DOMContentLoaded", function () {

    // MENU TOGGLE
    const menuToggle = document.getElementById("menuToggle");
    const navLinks = document.getElementById("navLinks");

    if (menuToggle) {
        menuToggle.addEventListener("click", () => {
            navLinks.classList.toggle("active");
        });
    }

    // PROFILE DROPDOWN
    const profileToggle = document.getElementById("profileToggle");
    const profileDropdown = document.getElementById("profileDropdown");

    if (profileToggle) {
        profileToggle.addEventListener("click", function (e) {
            e.stopPropagation();
            profileDropdown.classList.toggle("show");
        });

        document.addEventListener("click", function () {
            profileDropdown.classList.remove("show");
        });
    }

    // ADD TO CART (AJAX)
    document.querySelectorAll(".add-to-cart-btn").forEach(button => {
        button.addEventListener("click", function () {

            const productId = this.getAttribute("data-id");

            fetch("ajax_add_to_cart.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "product_id=" + productId
            })
            .then(res => res.json())
            .then(data => {

                if (data.status === "success") {

                    // CART ANIMATION
                    const cart = document.querySelector(".floating-cart");

                    if (cart) {
                        cart.classList.add("cart-bounce");
                        setTimeout(() => {
                            cart.classList.remove("cart-bounce");
                        }, 600);
                    }

                    // TOAST
                    showToast("Added to cart");

                } else {
                    showToast(data.message);
                }

            })
            .catch(() => {
                showToast("Something went wrong");
            });
        });
    });

});

// TOAST FUNCTION
function showToast(message) {

    let toast = document.getElementById("cartToast");

    if (!toast) {
        toast = document.createElement("div");
        toast.id = "cartToast";
        document.body.appendChild(toast);
    }

    toast.innerText = message;
    toast.className = "toast show";

    setTimeout(() => {
        toast.className = "toast";
    }, 2000);
}