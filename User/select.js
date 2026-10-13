const addCartSelectionModal = document.getElementById("addCartSelectionModal");
const addCartSelectionBox = document.getElementById("addCartSelectionBox");
const closeSelectionModal = document.getElementById("closeSelectionModal");

const selectionProductName = document.getElementById("selectionProductName");
const modalColor = document.getElementById("modalColor");
const modalColorLabel = document.getElementById("modalColorLabel");
const modalQuantity = document.getElementById("modalQuantity");
const stockMessage = document.getElementById("stockMessage");

const confirmAddToCart = document.getElementById("confirmAddToCart");

let selectedProductId = null;
let selectedStock = 0;

document.addEventListener("click", event => {
    const button = event.target.closest(".add-to-cart-btn");

    if (!button) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    selectedProductId = button.dataset.productId;
    selectedStock = parseInt(button.dataset.productStock, 10) || 0;

    if (selectedStock <= 0) {
        alert("This product is out of stock.");
        return;
    }

    selectionProductName.textContent = button.dataset.productName;

    modalColor.value = button.dataset.productColor;
    modalColorLabel.textContent = button.dataset.productColor;
    modalColor.checked = true;

    modalQuantity.textContent = "1";

    stockMessage.textContent =
        `${selectedStock} item${selectedStock > 1 ? "s" : ""} available`;

    document.querySelectorAll('input[name="modal_size"]').forEach(input => {
        input.checked = false;
    });

    addCartSelectionModal.classList.remove("hidden");
    addCartSelectionModal.classList.add("flex");

    requestAnimationFrame(() => {
        addCartSelectionBox.classList.remove("scale-95", "opacity-0");
        addCartSelectionBox.classList.add("scale-100", "opacity-100");
    });
});

function closeSelection() {
    addCartSelectionBox.classList.remove("scale-100", "opacity-100");
    addCartSelectionBox.classList.add("scale-95", "opacity-0");

    setTimeout(() => {
        addCartSelectionModal.classList.add("hidden");
        addCartSelectionModal.classList.remove("flex");
    }, 200);
}

closeSelectionModal.addEventListener("click", closeSelection);

addCartSelectionModal.addEventListener("click", event => {
    if (event.target === addCartSelectionModal) {
        closeSelection();
    }
});

document.getElementById("modalDecrease").addEventListener("click", () => {
    const quantity = parseInt(modalQuantity.textContent, 10) || 1;

    if (quantity > 1) {
        modalQuantity.textContent = String(quantity - 1);
    }
});

document.getElementById("modalIncrease").addEventListener("click", () => {
    const quantity = parseInt(modalQuantity.textContent, 10) || 1;

    if (quantity < selectedStock) {
        modalQuantity.textContent = String(quantity + 1);
    }
});

confirmAddToCart.addEventListener("click", async () => {
    if (!selectedProductId) {
        alert("Please select a product.");
        return;
    }

    const size = document.querySelector('input[name="modal_size"]:checked');

    if (!size) {
        alert("Please select a size.");
        return;
    }

    const quantity = parseInt(modalQuantity.textContent, 10) || 1;

    if (quantity < 1 || quantity > selectedStock) {
        alert("Please select a valid quantity.");
        return;
    }

    const formData = new FormData();

    formData.append("product_id", selectedProductId);
    formData.append("quantity", String(quantity));
    formData.append("size", size.value);
    formData.append("color", modalColor.value);

    confirmAddToCart.disabled = true;

    try {
        const response = await fetch("../api/index.php?route=cart-add", {
            method: "POST",
            body: formData,
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            alert(data.message || "Unable to add this product to your cart.");
            return;
        }

        document.querySelectorAll(
            "#cartCount, #cartCountMobile, .cart-count"
        ).forEach(element => {
            element.textContent = data.cart_count;
        });

        const cartModal = document.getElementById("cartModal");
        const cartModalMessage = document.getElementById("cartModalMessage");

        if (cartModalMessage) {
            cartModalMessage.textContent = data.message;
        }

        closeSelection();

        setTimeout(() => {
            if (cartModal) {
                cartModal.classList.remove("hidden");
                cartModal.classList.add("flex");
            }
        }, 200);

    } catch (error) {
        console.error("Add to cart error:", error);
        alert("Something went wrong while adding the product to your cart.");
    } finally {
        confirmAddToCart.disabled = false;
    }
});