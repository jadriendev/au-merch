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

    const userId = document.body.dataset.userId;
    const CART_STORAGE_KEY = `au_cart_${userId}`;

    let savedCart = JSON.parse(localStorage.getItem(CART_STORAGE_KEY));

let cart = savedCart && !Array.isArray(savedCart)
    ? savedCart
    : {};

    cart[data.cart_key] = data.cart_item;

    localStorage.setItem(
        CART_STORAGE_KEY,
        JSON.stringify(cart)
    );

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