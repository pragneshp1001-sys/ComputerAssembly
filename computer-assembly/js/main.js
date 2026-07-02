// Check user login status
document.addEventListener('DOMContentLoaded', function() {
    checkUserStatus();
    loadCategories();
});

function checkUserStatus() {
    const user = localStorage.getItem('user');
    const navAuth = document.getElementById('nav-auth');
    const navUser = document.getElementById('nav-user');

    if (user) {
        const userData = JSON.parse(user);
        navAuth.style.display = 'none';
        navUser.style.display = 'block';
        document.getElementById('username-display').textContent = userData.username;
        updateCartCount();
    } else {
        if (navAuth) navAuth.style.display = 'block';
        if (navUser) navUser.style.display = 'none';
    }
}

function logout() {
    localStorage.removeItem('user');
    window.location.href = 'index.html';
}

// Load categories for preview/filter
async function loadCategories() {
    try {
        const response = await fetch('api/categories.php');
        const data = await response.json();

        if (data.success) {
            // Home page category preview
            const previewContainer = document.getElementById('categories-preview');
            if (previewContainer) {
                previewContainer.innerHTML = data.data.map(cat => `
                    <div class="col-md-4 mb-4">
                        <a href="shop.html?category=${cat.id}" class="card text-decoration-none h-100 feature-card">
                            <div class="card-body text-center">
                                <i class="fas ${cat.icon}" style="font-size: 40px; color: var(--primary-color);"></i>
                                <h5 class="mt-3 mb-2">${cat.name}</h5>
                                <p class="text-muted mb-0">${cat.product_count} products</p>
                            </div>
                        </a>
                    </div>
                `).join('');
            }

            // Shop page category filter
            const filterContainer = document.getElementById('categories-filter');
            if (filterContainer) {
                filterContainer.innerHTML = data.data.map(cat => `
                    <div class="form-check">
                        <input class="form-check-input category-filter" type="checkbox" value="${cat.id}" id="cat-${cat.id}">
                        <label class="form-check-label" for="cat-${cat.id}">
                            ${cat.name} (${cat.product_count})
                        </label>
                    </div>
                `).join('');

                // Add event listeners
                document.querySelectorAll('.category-filter').forEach(checkbox => {
                    checkbox.addEventListener('change', () => loadProducts(1));
                });
            }
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

// Update cart count in navbar
async function updateCartCount() {
    const user = localStorage.getItem('user');
    if (!user) return;

    try {
        const response = await fetch('api/cart.php');
        const data = await response.json();
        
        if (data.success) {
            const count = data.data.item_count || 0;
            const cartCountEl = document.getElementById('cart-count');
            if (cartCountEl) {
                cartCountEl.textContent = count;
            }
        }
    } catch (error) {
        console.error('Error updating cart count:', error);
    }
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0
    }).format(amount || 0);
}

// Show message
function showMessage(message, type = 'success') {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    alert(`${message}`);
}
