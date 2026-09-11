document.querySelectorAll(".add-to-cart-form").forEach(form => {
    form.addEventListener("submit", async function(e) {
        e.preventDefault();

        const formData = new FormData(form);

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

            document.getElementById("cartModalMessage").textContent = data.message;

            const modal = document.getElementById("cartModal");
            modal.classList.remove("hidden");
            modal.classList.add("flex");
        }
    });
});

document.getElementById("continueShopping").addEventListener("click", function() {
    const modal = document.getElementById("cartModal");

    modal.classList.add("hidden");
    modal.classList.remove("flex");
});