let quantity = 1;

const quantityDisplay = document.getElementById('quantity');

document.getElementById('decreaseBtn').addEventListener('click', () => {
    if (quantity > 1) {
        quantity--;
        quantityDisplay.textContent = quantity;
    }
});

document.getElementById('increaseBtn').addEventListener('click', () => {
    quantity++;
    quantityDisplay.textContent = quantity;
});