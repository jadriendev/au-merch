const addCartSelectionModal = document.getElementById('addCartSelectionModal');
const addCartSelectionBox = document.getElementById('addCartSelectionBox');
const closeSelectionModal = document.getElementById('closeSelectionModal');

const selectionProductName = document.getElementById('selectionProductName');
const modalColor = document.getElementById('modalColor');
const modalColorLabel = document.getElementById('modalColorLabel');
const modalQuantity = document.getElementById('modalQuantity');
const stockMessage = document.getElementById('stockMessage');

let selectedProductId = null;
let selectedStock = 1;

document.querySelectorAll('.add-to-cart-btn').forEach(button => {
    button.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();

        selectedProductId = button.dataset.productId;
        selectedStock = parseInt(button.dataset.productStock) || 1;

        selectionProductName.textContent = button.dataset.productName;

        modalColor.value = button.dataset.productColor;
        modalColorLabel.textContent = button.dataset.productColor;
        modalColor.checked = true;

        modalQuantity.textContent = '1';
        stockMessage.textContent = `${selectedStock} item${selectedStock > 1 ? 's' : ''} available`;

        document.querySelectorAll('input[name="modal_size"]').forEach(input => {
            input.checked = false;
        });

        addCartSelectionModal.classList.remove('hidden');
        addCartSelectionModal.classList.add('flex');

        requestAnimationFrame(() => {
            addCartSelectionBox.classList.remove('scale-95', 'opacity-0');
            addCartSelectionBox.classList.add('scale-100', 'opacity-100');
        });
    });
});

function closeSelection() {
    addCartSelectionBox.classList.remove('scale-100', 'opacity-100');
    addCartSelectionBox.classList.add('scale-95', 'opacity-0');

    setTimeout(() => {
        addCartSelectionModal.classList.add('hidden');
        addCartSelectionModal.classList.remove('flex');
    }, 200);
}

closeSelectionModal.addEventListener('click', closeSelection);

addCartSelectionModal.addEventListener('click', event => {
    if (event.target === addCartSelectionModal) {
        closeSelection();
    }
});

document.getElementById('modalDecrease').addEventListener('click', () => {
    let quantity = parseInt(modalQuantity.textContent);

    if (quantity > 1) {
        quantity--;
        modalQuantity.textContent = quantity;
    }
});

document.getElementById('modalIncrease').addEventListener('click', () => {
    let quantity = parseInt(modalQuantity.textContent);

    if (quantity < selectedStock) {
        quantity++;
        modalQuantity.textContent = quantity;
    }
});

document.getElementById('confirmAddToCart').addEventListener('click', async () => {
    const size = document.querySelector('input[name="modal_size"]:checked');

    if (!size) {
        alert('Please select a size.');
        return;
    }

    const formData = new FormData();

    formData.append('product_id', selectedProductId);
    formData.append('quantity', modalQuantity.textContent);
    formData.append('size', size.value);
    formData.append('color', modalColor.value);

    try {
        const response = await fetch('../pages/add_to_cart.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            document.querySelectorAll('#cartCount, #cartCountMobile, .cart-count').forEach(element => {
                element.textContent = data.cart_count;
            });

            closeSelection();

            setTimeout(() => {
                document.getElementById('cartModal').classList.remove('hidden');
                document.getElementById('cartModal').classList.add('flex');
            }, 200);
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error(error);
        alert('Something went wrong while adding the product to your cart.');
    }
});