/**
 * Cart Management - Handle cart display and interactions
 */

/**
 * Update cart count badge in navbar
 */
async function updateCartCount() {
    try {
        const result = await getCartCount();
        const badge = document.getElementById('cart-count-badge');
        
        if (badge && result.success) {
            if (result.count > 0) {
                badge.textContent = result.count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error updating cart count:', error);
    }
}

/**
 * Load and display cart items
 */
async function loadCartItems() {
    try {
        const container = document.getElementById('cart-items-container');
        const summary = document.getElementById('cart-summary');
        
        if (!container) {
            console.log('Cart container not found');
            return;
        }
        
        console.log('Loading cart items...');
        const result = await getCartItems();
        console.log('Cart result:', result);
        
        if (!result.success) {
            container.innerHTML = `
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle"></i>
                    <p class="mt-2">Gagal memuat keranjang: ${result.message}</p>
                </div>
            `;
            return;
        }
        
        if (result.count === 0) {
            container.innerHTML = `
                <div class="alert alert-info text-center">
                    <i class="bi bi-cart-x" style="font-size: 2rem;"></i>
                    <h5 class="mt-3">Keranjang Anda Kosong</h5>
                    <p>Mulai berbelanja dan tambahkan produk ke keranjang Anda.</p>
                    <a href="../produk/list-produk.php" class="btn btn-primary">
                        <i class="bi bi-shop"></i> Lanjut Belanja
                    </a>
                </div>
            `;
            if (summary) {
                summary.innerHTML = `
                    <h5>Ringkasan Belanja</h5>
                    <hr>
                    <p class="text-muted">Keranjang kosong</p>
                `;
            }
            return;
        }
        
        // Build cart items HTML
        let html = '';
        let totalPrice = 0;
        
        result.items.forEach(item => {
            const subtotal = item.harga * item.quantity;
            totalPrice += subtotal;
            
            html += `
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6 class="mb-0">${item.nama_produk}</h6>
                                <small class="text-muted">Rp ${formatPrice(item.harga)}</small>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-sm">
                                    <button class="btn btn-outline-secondary" onclick="updateQuantity(${item.id_produk}, ${item.quantity - 1})">
                                        <i class="bi bi-dash"></i>
                                    </button>
                                    <input type="text" class="form-control text-center" value="${item.quantity}" readonly>
                                    <button class="btn btn-outline-secondary" onclick="updateQuantity(${item.id_produk}, ${item.quantity + 1})">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <button class="btn btn-sm btn-danger" onclick="removeItem(${item.id_produk})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="row mt-2 pt-2 border-top">
                            <div class="col-md-6">
                                <small class="text-muted">Subtotal</small>
                            </div>
                            <div class="col-md-6 text-end">
                                <strong>Rp ${formatPrice(subtotal)}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
        
        // Update summary
        if (summary) {
            summary.innerHTML = `
                <h5>Ringkasan Belanja</h5>
                <hr>
                <div class="row mb-2">
                    <div class="col-6">Total Item</div>
                    <div class="col-6 text-end">${result.count} item</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">Total Harga</div>
                    <div class="col-6 text-end"><strong>Rp ${formatPrice(totalPrice)}</strong></div>
                </div>
                <hr>
                <div class="d-grid gap-2">
                    <a href="checkout.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle"></i> Lanjut Checkout
                    </a>
                    <a href="../produk/list-produk.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Lanjut Belanja
                    </a>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading cart items:', error);
        const container = document.getElementById('cart-items-container');
        if (container) {
            container.innerHTML = `
                <div class="alert alert-danger">Terjadi kesalahan saat memuat keranjang: ${error.message}</div>
            `;
        }
    }
}

/**
 * Update item quantity
 */
async function updateQuantity(id_produk, newQuantity) {
    if (newQuantity <= 0) {
        removeItem(id_produk);
        return;
    }
    
    const result = await updateCartQuantity(id_produk, newQuantity);
    if (result.success) {
        loadCartItems();
        updateCartCount();
    } else {
        alert('Gagal update quantity: ' + result.message);
    }
}

/**
 * Remove item from cart
 */
async function removeItem(id_produk) {
    if (confirm('Apakah Anda yakin ingin menghapus item ini?')) {
        const result = await removeFromCart(id_produk);
        if (result.success) {
            loadCartItems();
            updateCartCount();
            showNotification('success', 'Item berhasil dihapus dari keranjang');
        } else {
            alert('Gagal menghapus item: ' + result.message);
        }
    }
}

/**
 * Format price to Indonesian currency
 */
function formatPrice(price) {
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(price);
}

/**
 * Show notification
 */
function showNotification(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alert.style.zIndex = '9999';
    alert.innerHTML = `
        <i class="bi bi-${icon}"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 3000);
}

/**
 * Initialize on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Cart JS initialized');
    
    // Load cart if on cart page
    if (document.getElementById('cart-items-container')) {
        console.log('Cart page detected, loading items...');
        loadCartItems();
    }
    
    // Update cart count
    updateCartCount();
});