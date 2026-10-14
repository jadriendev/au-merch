document.querySelectorAll(".add-to-cart-form").forEach(form => {
    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: "POST",
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                const cartCount = document.getElementById("cartCount");
                const cartCountMobile = document.getElementById("cartCountMobile");

                if (cartCount) {
                    cartCount.textContent = data.cart_count;
                }

                if (cartCountMobile) {
                    cartCountMobile.textContent = data.cart_count;
                }

                const message = document.getElementById("cartModalMessage");
                const modal = document.getElementById("cartModal");

                if (message) {
                    message.textContent = data.message;
                }

                if (modal) {
                    modal.classList.remove("hidden");
                    modal.classList.add("flex");
                }
            } else {
                alert(data.message || "Failed to add product to cart.");
            }
        } catch (error) {
            console.error("Add to cart error:", error);
            alert("Something went wrong. Please try again.");
        }
    });
});

const continueShopping = document.getElementById("continueShopping");

if (continueShopping) {
    continueShopping.addEventListener("click", function () {
        const modal = document.getElementById("cartModal");

        if (modal) {
            modal.classList.add("hidden");
            modal.classList.remove("flex");
        }
    });
}