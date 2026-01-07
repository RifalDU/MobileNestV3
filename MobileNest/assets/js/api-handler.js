/**
 * API Handler - MobileNest
 */

// Gunakan SITE_URL dari footer jika ada, kalau tidak fallback ke logic lama
const baseUrl = (typeof SITE_URL !== 'undefined') ? SITE_URL : window.location.origin + '/MobileNest';
const API_BASE = baseUrl + '/api/';

console.log('API Base configured:', API_BASE);

async function apiRequest(endpoint, method = 'GET', data = null) {
    try {
        const options = {
            method: method,
            headers: { 'Content-Type': 'application/json' }
        };

        if (method !== 'GET' && data) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(`${API_BASE}${endpoint}`, options);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'Connection error' };
    }
}

// Wrapper functions
async function getCartItems() { return await apiRequest('cart.php?action=get'); }
async function getCartCount() { return await apiRequest('cart.php?action=count'); }
async function addToCart(id_produk, quantity = 1) {
    return await apiRequest('cart.php?action=add', 'POST', { id_produk, quantity });
}
async function removeFromCart(id_produk) {
    return await apiRequest('cart.php?action=remove', 'POST', { id_produk });
}
async function updateCartQuantity(id_produk, quantity) {
    return await apiRequest('cart.php?action=update', 'POST', { id_produk, quantity });
}