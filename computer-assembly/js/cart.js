// Load cart on page load
document.addEventListener('DOMContentLoaded', function() {
    checkUserLogin();
    loadCart();
});

// Check if user is logged in
function checkUserLogin() {
    const user = localStorage.getItem('user');
    if (!user) {
        alert('Please login first');
        window.location.href = 'login.html';
    }
}

// Load cart items
async function loadCart() {
    try {
        const response = await fetch('api/cart.php');
        const data = await response.json();

        if (!data.success) {
            alert(data.message);
            window.location.href = 'login.html';
            return;
        }

        const cartItems = data.data.items;
        const total = data.data.total;

        if (cartItems.length === 0) {
            document.getElementById('cart-empty').classList.remove('d-none');
            document.getElementById('cart-items').classList.add('d-none');
            document.getElementById('checkout-btn').disabled = true;
        } else {
            document.getElementById('cart-empty').classList.add('d-none');
            document.getElementById('cart-items').classList.remove('d-none');
            document.getElementById('checkout-btn').disabled = false;
            
            displayCartItems(cartItems);
            updateSummary(cartItems);
        }
    } catch (error) {
        console.error('Error loading cart:', error);
        alert('Error loading cart');
    }
}

// Display cart items
function displayCartItems(items) {
    const cartList = document.getElementById('cart-items-list');
    
    cartList.innerHTML = items.map(item => `
        <tr>
            <td>
                <div class="d-flex">
                    <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-box" style="font-size: 24px; color: #ccc;"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="mb-1">${item.name}</h6>
                        <small class="text-muted">${item.id}</small>
                    </div>
                </div>
            </td>
            <td>${formatCurrency(item.price)}</td>
            <td>
                <input type="number" class="quantity-input form-control" value="${item.quantity}" min="1" max="${item.stock_quantity}" onchange="updateQuantity(${item.id}, this.value)">
            </td>
            <td>${formatCurrency(item.subtotal)}</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="removeFromCart(${item.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// Update quantity
async function updateQuantity(cartId, quantity) {
    quantity = parseInt(quantity);
    if (quantity < 1) quantity = 1;

    try {
        const response = await fetch('api/cart.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                cart_id: cartId,
                quantity: quantity
            })
        });

        const data = await response.json();

        if (data.success) {
            loadCart();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error updating cart:', error);
        alert('Error updating cart');
    }
}

// Remove from cart
async function removeFromCart(cartId) {
    if (!confirm('Are you sure you want to remove this item?')) return;

    try {
        const response = await fetch('api/cart.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                cart_id: cartId
            })
        });

        const data = await response.json();

        if (data.success) {
            loadCart();
            updateCartCount();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error removing from cart:', error);
        alert('Error removing item');
    }
}

// Update summary
function updateSummary(items) {
    let subtotal = 0;
    items.forEach(item => {
        subtotal += item.subtotal;
    });

    const shipping = subtotal > 100 ? 0 : 10;
    const tax = subtotal * 0.08;
    const total = subtotal + shipping + tax;

    document.getElementById('subtotal').textContent = formatCurrency(subtotal);
    document.getElementById('shipping').textContent = formatCurrency(shipping);
    document.getElementById('tax').textContent = formatCurrency(tax);
    document.getElementById('total').textContent = formatCurrency(total);
}

// Proceed to checkout
function goToCheckout() {
    // In a real application, this would go to a checkout page
    // For now, we'll create an order directly
    showCheckoutForm();
}

// Show checkout form
function showCheckoutForm() {
    const userStr = localStorage.getItem('user');
    const user = JSON.parse(userStr);

    const shippingAddress = prompt('Enter your shipping address:');
    if (!shippingAddress) return;

    const paymentMethod = confirm('Choose payment method:\n\nOK = Credit Card\nCancel = PayPal');

    createOrder(shippingAddress, paymentMethod ? 'Credit Card' : 'PayPal');
}

// Create order
async function createOrder(shippingAddress, paymentMethod) {
    try {
        const response = await fetch('api/checkout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                shipping_address: shippingAddress,
                payment_method: paymentMethod
            })
        });

        const data = await response.json();

        if (data.success) {
            alert(`Order created successfully!\nOrder Number: ${data.data.order_number}\nTotal: ${formatCurrency(data.data.total_amount)}`);
            window.location.href = 'shop.html';
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error creating order:', error);
        alert('Error creating order');
    }
}
