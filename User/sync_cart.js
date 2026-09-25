const userId = document.body.dataset.userId;

if (userId) {

    const CART_STORAGE_KEY = `au_cart_${userId}`;

    const savedCart = localStorage.getItem(CART_STORAGE_KEY);

    if (savedCart) {

        const cart = JSON.parse(savedCart);

        fetch('../pages/sync_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(cart)
        })
        .then(response => response.json())
        .then(data => {

            if (data.success) {

                console.log('Cart restored.');

                const cartCount = document.getElementById('cartCount');
                const cartCountMobile = document.getElementById('cartCountMobile');

                if (cartCount) {
                    cartCount.textContent = data.cart_count;
                }

                if (cartCountMobile) {
                    cartCountMobile.textContent = data.cart_count;
                }
            }

        })
        .catch(error => {
            console.error('Cart sync error:', error);
        });
    }
}