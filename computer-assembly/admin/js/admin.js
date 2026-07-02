'use strict';

/* ═══════════════════════════════════════════════════════════
   CONFIG
═══════════════════════════════════════════════════════════ */
const API = '../api';

/* ═══════════════════════════════════════════════════════════
   UTILITY
═══════════════════════════════════════════════════════════ */
function $(id) { return document.getElementById(id); }

/* Image preview */
$('productImage')?.addEventListener('change', function() {

    const file=this.files[0];

    if(!file) return;

    const reader=new FileReader();

    reader.onload=e=>{
        $('productPreview').src=e.target.result;
    };

    reader.readAsDataURL(file);

});

function toast(msg, type = 'success') {
  const t = $('toast');
  t.textContent = msg;
  t.className = `toast show ${type}`;
  clearTimeout(t._timer);
  t._timer = setTimeout(() => { t.className = 'toast'; }, 3000);
}

function fmt(n)  { return Number(n || 0).toLocaleString('en-IN'); }
function date(s) { return s ? new Date(s).toLocaleDateString('en-IN') : '—'; }

function stars(n) {
  n = Math.round(n || 0);
  return '<span class="stars">' + '★'.repeat(n) + '☆'.repeat(5 - n) + '</span>';
}

function statusBadge(s) {
  const map = { pending:'badge-pending', processing:'badge-processing',
                shipped:'badge-shipped', delivered:'badge-delivered', cancelled:'badge-cancelled' };
  return `<span class="badge ${map[s] || 'badge-pending'}">${s}</span>`;
}

function stockChip(n) {
  n = +n;
  if (n === 0) return `<span class="stock-chip stock-out">Out</span>`;
  if (n <= 10) return `<span class="stock-chip stock-low">${n}</span>`;
  return `<span class="stock-chip stock-ok">${n}</span>`;
}

async function api(path, opts = {}) {
  const res = await fetch(API + path, {
    headers: { 'Content-Type': 'application/json' },
    ...opts
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}

/* ═══════════════════════════════════════════════════════════
   MODAL HELPERS
═══════════════════════════════════════════════════════════ */
function openModal(id)  { $(id).classList.add('open');    }
function closeModal(id) { $(id).classList.remove('open'); }

document.querySelectorAll('[data-close]').forEach(btn => {
  btn.addEventListener('click', () => closeModal(btn.dataset.close));
});
document.querySelectorAll('.modal-overlay').forEach(ov => {
  ov.addEventListener('click', e => { if (e.target === ov) closeModal(ov.id); });
});

/* ═══════════════════════════════════════════════════════════
   NAVIGATION
═══════════════════════════════════════════════════════════ */
const pages = { dashboard: loadDashboard, products: loadProducts,
                orders: loadOrders, users: loadUsers,
                categories: loadCategories, reviews: loadReviews };

document.querySelectorAll('.nav-link[data-page]').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    switchPage(link.dataset.page);
  });
});

function switchPage(name) {
  document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
  document.querySelector(`.nav-link[data-page="${name}"]`)?.classList.add('active');
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  $(`page-${name}`)?.classList.add('active');
  $('topbarTitle').textContent = name.charAt(0).toUpperCase() + name.slice(1);
  pages[name]?.();
}

/* Sidebar toggle (mobile) */
$('sidebarToggle').addEventListener('click', () => {
  $('sidebar').classList.toggle('open');
});

/* Logout */
$('logoutBtn').addEventListener('click', e => {
  e.preventDefault();
  localStorage.removeItem('user');
  window.location.href = '../login.html';
});

/* ═══════════════════════════════════════════════════════════
   AUTH GUARD
═══════════════════════════════════════════════════════════ */
(function checkAuth() {
  const user = JSON.parse(localStorage.getItem('user') || 'null');
  if (!user) { window.location.href = '../login.html'; return; }
  $('adminName').textContent = user.username || user.email || 'Admin';
})();

/* ═══════════════════════════════════════════════════════════
   DASHBOARD
═══════════════════════════════════════════════════════════ */
async function loadDashboard() {
  try {
    const d = await api('/admin.php?action=stats');

    $('s-orders').textContent   = fmt(d.total_orders);
    $('s-revenue').textContent  = '₹' + fmt(d.total_revenue);
    $('s-products').textContent = fmt(d.total_products);
    $('s-users').textContent    = fmt(d.total_users);

    // Recent orders table
    const tbody = $('dashOrders');
    if (!d.recent_orders?.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="loading-row">No orders yet</td></tr>';
    } else {
      tbody.innerHTML = d.recent_orders.slice(0, 8).map(o => `
        <tr>
          <td><strong>${o.order_number}</strong></td>
          <td>${o.username || '—'}</td>
          <td>₹${fmt(o.total_amount)}</td>
          <td>${statusBadge(o.order_status)}</td>
          <td>${date(o.created_at)}</td>
        </tr>`).join('');
    }

    // Pending actions panel
    const panel = $('pendingActions');
    const items = [];
    if (+d.pending_orders > 0)
      items.push({ dot:'orange', text: `${d.pending_orders} pending order${d.pending_orders > 1 ? 's' : ''} need attention` });
    if (d.low_stock_count > 0)
      items.push({ dot:'red', text: `${d.low_stock_count} products are low on stock` });
    if (!items.length)
      items.push({ dot:'blue', text: 'Everything looks good!' });

    panel.innerHTML = items.map(i => `
      <div class="pending-item">
        <span class="pending-dot ${i.dot}"></span>
        <span>${i.text}</span>
      </div>`).join('');
  } catch (e) {
    console.error(e);
    toast('Failed to load dashboard', 'error');
  }
}

/* ═══════════════════════════════════════════════════════════
   PRODUCTS
═══════════════════════════════════════════════════════════ */
let productPage = 1;
let allCategories = [];

async function loadCategories_forSelect() {
  if (allCategories.length) return;
  try {
    const data = await api('/admin.php?action=categories');
    allCategories = data.categories || [];
    // Populate filter dropdown
    const catFilter = $('productCatFilter');
    allCategories.forEach(c => {
      catFilter.insertAdjacentHTML('beforeend',
        `<option value="${c.id}">${c.name}</option>`);
    });
    // Populate product form select
    populateCategorySelect($('productCategory'));
  } catch (e) { console.error('category load', e); }
}

function populateCategorySelect(sel) {
  sel.innerHTML = '<option value="">Select…</option>';
  allCategories.forEach(c => {
    sel.insertAdjacentHTML('beforeend', `<option value="${c.id}">${c.name}</option>`);
  });
}

async function loadProducts(page = 1) {
  productPage = page;
  await loadCategories_forSelect();

  const search   = $('productSearch').value.trim();
  const catId    = $('productCatFilter').value;
  const stockF   = $('productStockFilter').value;

  let qs = `?page=${page}`;
  if (search) qs += `&search=${encodeURIComponent(search)}`;
  if (catId)  qs += `&category_id=${catId}`;

  try {
    const d = await api('/products.php' + qs);
    let products = d.data?.products || [];

    // Client-side stock filter (API doesn't support it)
    if (stockF === 'low') products = products.filter(p => +p.stock_quantity > 0 && +p.stock_quantity <= 10);
    if (stockF === 'out') products = products.filter(p => +p.stock_quantity === 0);

    const tbody = $('productsBody');
    if (!products.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="loading-row">No products found</td></tr>';
    } else {
      tbody.innerHTML = products.map(p => `
        <tr>
          <td><span style="color:var(--text-sub)">#${p.id}</span></td>
          <td><strong>${esc(p.name)}</strong></td>
          <td>${esc(p.category_name || '—')}</td>
          <td>${esc(p.brand || '—')}</td>
          <td>₹${fmt(p.price)}</td>
          <td>${stockChip(p.stock_quantity)}</td>
          <td>${stars(p.average_rating)} <small style="color:var(--text-sub)">(${p.review_count || 0})</small></td>
          <td>
            <button class="btn-icon edit" title="Edit" onclick="editProduct(${p.id})"><i class="fas fa-pen"></i></button>
            <button class="btn-icon del"  title="Delete" onclick="deleteProduct(${p.id}, '${esc(p.name)}')"><i class="fas fa-trash"></i></button>
          </td>
        </tr>`).join('');
    }

    // Pagination
    buildPagination('productsPagination', d.data?.pagination?.pages || 1, page, loadProducts);
  } catch (e) {
    console.error(e);
    $('productsBody').innerHTML = '<tr><td colspan="8" class="loading-row">Error loading products</td></tr>';
    toast('Failed to load products', 'error');
  }
}

// Debounced search
let productSearchTimer;
$('productSearch').addEventListener('input', () => {
  clearTimeout(productSearchTimer);
  productSearchTimer = setTimeout(() => loadProducts(1), 350);
});
$('productCatFilter').addEventListener('change', () => loadProducts(1));
$('productStockFilter').addEventListener('change', () => loadProducts(1));

/* Add product */
$('addProductBtn').addEventListener('click', () => {
  $('productModalTitle').textContent = 'Add Product';
  $('productForm').reset();
  $('productId').value = '';
  populateCategorySelect($('productCategory'));
  openModal('productModal');
});

/* Edit product — fetch single product then pre-fill */
async function editProduct(id) {

  try {

    const d = await api(`/products.php?id=${id}`);
    const p = d.data || d;

    $('productModalTitle').textContent='Edit Product';

    $('productId').value=p.id;
    $('productName').value=p.name || '';
    $('productBrand').value=p.brand || '';
    $('productPrice').value=p.price || '';
    $('productStock').value=p.stock_quantity || '';
    $('productDescription').value=p.description || '';

    populateCategorySelect($('productCategory'));

    $('productCategory').value=p.category_id || '';

    $('productPreview').src=
        p.image
        ? `../uploads/products/${p.image}`
        : '../images/default.jpg';

    openModal('productModal');

  }

  catch(e){

    console.log(e);
    toast('Could not load product','error');

  }

}

/* Save product (create / update) */
/* Save product (create / update) */
$('productForm').addEventListener('submit', async e => {

    e.preventDefault();

    const id=$('productId').value;

    let formData=new FormData();

    formData.append('name',$('productName').value);
    formData.append('brand',$('productBrand').value);
    formData.append('category_id',$('productCategory').value);
    formData.append('price',$('productPrice').value);
    formData.append('stock_quantity',$('productStock').value);
    formData.append('description',$('productDescription').value);

    const image=$('productImage').files[0];

    if(image){
        formData.append('image',image);
    }

    if(id){
        formData.append('id',id);
    }

    try{

        const action=id
        ? 'update_product'
        : 'create_product';

        const response=await fetch(
            `../api/admin.php?action=${action}`,
            {
                method:'POST',
                body:formData
            }
        );

        const data=await response.json();

        if(data.success){

            toast(
                id
                ? 'Product updated'
                : 'Product added'
            );

            closeModal('productModal');

            loadProducts();

        }else{

            toast(data.message,'error');

        }

    }

    catch(error){

        console.log(error);

        toast('Save failed','error');

    }

});
/* Delete product */
let _deleteProductId = null;
function deleteProduct(id, name) {
  _deleteProductId = id;
  $('confirmMsg').textContent = `Delete "${name}"? This cannot be undone.`;
  openModal('confirmModal');
}
$('confirmDeleteBtn').addEventListener('click', async () => {
  if (!_deleteProductId) return;
  try {
    await api(`/admin.php?action=delete_product`, {
      method: 'POST',
      body: JSON.stringify({ id: _deleteProductId })
    });
    toast('Product deleted');
    closeModal('confirmModal');
    loadProducts(productPage);
  } catch (e) {
    toast('Delete failed', 'error');
  }
  _deleteProductId = null;
});

/* ═══════════════════════════════════════════════════════════
   ORDERS
═══════════════════════════════════════════════════════════ */
let ordersPage = 1;
let currentOrderId = null;

async function loadOrders(page = 1) {
  ordersPage = page;
  const status = $('orderStatusFilter').value;
  let qs = `?action=orders&page=${page}`;
  if (status) qs += `&status=${status}`;

  try {
    const d = await api('/admin.php' + qs);
    const tbody = $('ordersBody');
    if (!d.orders?.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="loading-row">No orders found</td></tr>';
    } else {
      tbody.innerHTML = d.orders.map(o => `
        <tr>
          <td><strong>${o.order_number}</strong></td>
          <td>${esc(o.username || '—')}<br><small style="color:var(--text-sub)">${esc(o.email || '')}</small></td>
          <td>₹${fmt(o.total_amount)}</td>
          <td>${esc(o.payment_method || '—')}</td>
          <td>${statusBadge(o.order_status)}</td>
          <td>${date(o.created_at)}</td>
          <td>
            <button class="btn-icon view" title="Details" onclick="viewOrder(${o.id})"><i class="fas fa-eye"></i></button>
          </td>
        </tr>`).join('');
    }
    buildPagination('ordersPagination', d.pages || 1, page, loadOrders);
  } catch (e) {
    $('ordersBody').innerHTML = '<tr><td colspan="7" class="loading-row">Error loading orders</td></tr>';
    toast('Failed to load orders', 'error');
  }
}

$('orderStatusFilter').addEventListener('change', () => loadOrders(1));

async function viewOrder(id) {
  currentOrderId = id;
  $('orderModalTitle').textContent = 'Order #' + id;
  $('orderModalBody').textContent = 'Loading…';
  openModal('orderModal');

  try {
    /* Use the existing orders API — fetch the detail via admin orders list filtered by a search
       Since the admin API doesn't have a single-order endpoint, we re-query the orders list */
    const d = await api(`/admin.php?action=orders&page=1`);
    const order = d.orders?.find(o => o.id === id);
    if (!order) { $('orderModalBody').textContent = 'Order not found.'; return; }

    $('orderStatusUpdate').value = order.order_status;

    $('orderModalBody').innerHTML = `
      <div class="order-detail-grid">
        <div class="order-detail-block">
          <h4>Order Info</h4>
          <p><strong>Number:</strong> ${order.order_number}<br>
             <strong>Date:</strong> ${date(order.created_at)}<br>
             <strong>Payment:</strong> ${order.payment_method || '—'}<br>
             <strong>Items:</strong> ${order.item_count || '—'}</p>
        </div>
        <div class="order-detail-block">
          <h4>Customer</h4>
          <p><strong>${esc(order.username)}</strong><br>
             ${esc(order.email)}</p>
          <h4 style="margin-top:12px">Shipping</h4>
          <p>${esc(order.shipping_address || 'Not provided')}</p>
        </div>
      </div>
      <div class="order-total">Total: ₹${fmt(order.total_amount)}</div>`;
  } catch (e) {
    $('orderModalBody').textContent = 'Could not load order details.';
  }
}

$('updateStatusBtn').addEventListener('click', async () => {
  if (!currentOrderId) return;
  const newStatus = $('orderStatusUpdate').value;
  try {
    await api('/admin.php?action=update_order_status', {
      method: 'POST',
      body: JSON.stringify({ order_id: currentOrderId, status: newStatus })
    });
    toast(`Order marked as ${newStatus}`);
    closeModal('orderModal');
    loadOrders(ordersPage);
  } catch (e) {
    toast('Status update failed', 'error');
  }
});

/* ═══════════════════════════════════════════════════════════
   USERS
═══════════════════════════════════════════════════════════ */
let usersPage = 1;
let userSearchTimer;

async function loadUsers(page = 1) {
  usersPage = page;
  const search = $('userSearch').value.trim();
  let qs = `?action=users&page=${page}`;
  if (search) qs += `&search=${encodeURIComponent(search)}`;

  try {
    const d = await api('/admin.php' + qs);
    const tbody = $('usersBody');
    if (!d.users?.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="loading-row">No users found</td></tr>';
    } else {
      tbody.innerHTML = d.users.map(u => `
        <tr>
          <td><span style="color:var(--text-sub)">#${u.id}</span></td>
          <td><strong>${esc(u.username)}</strong></td>
          <td>${esc(u.email)}</td>
          <td>${esc(u.full_name || '—')}</td>
          <td><span class="badge badge-${u.role || 'customer'}">${u.role || 'customer'}</span></td>
          <td>${fmt(u.total_orders)}</td>
          <td>₹${fmt(u.total_spent)}</td>
          <td>${date(u.created_at)}</td>
        </tr>`).join('');
    }
    buildPagination('usersPagination', d.pages || 1, page, loadUsers);
  } catch (e) {
    $('usersBody').innerHTML = '<tr><td colspan="8" class="loading-row">Error loading users</td></tr>';
    toast('Failed to load users', 'error');
  }
}

$('userSearch').addEventListener('input', () => {
  clearTimeout(userSearchTimer);
  userSearchTimer = setTimeout(() => loadUsers(1), 350);
});

/* ═══════════════════════════════════════════════════════════
   CATEGORIES
═══════════════════════════════════════════════════════════ */
async function loadCategories() {
  try {
          const data = await api('/admin.php?action=categories');
          const cats = data.categories || [];
    allCategories = cats; // keep in sync

    const tbody = $('categoriesBody');
    if (!cats.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="loading-row">No categories</td></tr>';
    } else {
      tbody.innerHTML = cats.map(c => `
        <tr>
          <td><span style="color:var(--text-sub)">#${c.id}</span></td>
          <td><strong>${esc(c.name)}</strong></td>
          <td>${esc(c.description || '—')}</td>
          <td>${fmt(c.product_count)}</td>
          <td>${date(c.created_at)}</td>
          <td>
            <button class="btn-icon edit" onclick="editCategory(${c.id}, '${esc(c.name)}', '${esc(c.description || '')}')"><i class="fas fa-pen"></i></button>
          </td>
        </tr>`).join('');
    }
  } catch (e) {
    $('categoriesBody').innerHTML = '<tr><td colspan="6" class="loading-row">Error</td></tr>';
    toast('Failed to load categories', 'error');
  }
}

$('addCategoryBtn').addEventListener('click', () => {
  $('categoryModalTitle').textContent = 'Add Category';
  $('categoryForm').reset();
  $('categoryId').value = '';
  openModal('categoryModal');
});

function editCategory(id, name, desc) {
  $('categoryModalTitle').textContent = 'Edit Category';
  $('categoryId').value          = id;
  $('categoryName').value        = name;
  $('categoryDescription').value = desc;
  openModal('categoryModal');
}

$('categoryForm').addEventListener('submit', async e => {
  e.preventDefault();
  const id   = $('categoryId').value;
  const name = $('categoryName').value.trim();
  const desc = $('categoryDescription').value.trim();

  const action = id ? 'update_category' : 'create_category';
  const body   = id ? { id: +id, name, description: desc } : { name, description: desc };

  try {
    await api(`/admin.php?action=${action}`, {
      method: 'POST',
      body: JSON.stringify(body)
    });
    toast(id ? 'Category updated' : 'Category created');
    allCategories = []; // invalidate cache
    closeModal('categoryModal');
    loadCategories();
  } catch (e) {
    toast('Save failed', 'error');
  }
});

/* ═══════════════════════════════════════════════════════════
   REVIEWS
═══════════════════════════════════════════════════════════ */
async function loadReviews() {
  const rating = $('reviewRatingFilter').value;
  let qs = `?action=list`;
  if (rating) qs += `&rating=${rating}`;

  /* The reviews API requires a product_id. We'll fetch reviews for all products
     by using the admin endpoint if it exists, otherwise show a note. */
  try {
    const d = await api('/admin.php?action=reviews');
    renderReviews(d.reviews || []);
  } catch {
    // Fallback: load reviews from the reviews API for product 1 as demo
    try {
      const d = await api('/reviews.php?action=list&product_id=1');
      renderReviews(d.reviews || []);
    } catch (e) {
      $('reviewsBody').innerHTML = '<tr><td colspan="8" class="loading-row">Add the reviews action to admin.php (see below)</td></tr>';
    }
  }
}

function renderReviews(reviews) {
  const ratingF = $('reviewRatingFilter').value;
  let list = ratingF ? reviews.filter(r => +r.rating === +ratingF) : reviews;

  const tbody = $('reviewsBody');
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="8" class="loading-row">No reviews found</td></tr>';
    return;
  }
  tbody.innerHTML = list.map(r => `
    <tr>
      <td>${esc(r.product_name || r.product_id || '—')}</td>
      <td>${esc(r.username || '—')}</td>
      <td>${stars(r.rating)}</td>
      <td>${esc(r.title || '—')}</td>
      <td style="max-width:200px;white-space:normal">${esc(r.comment || '—').slice(0, 80)}${r.comment?.length > 80 ? '…' : ''}</td>
      <td>${r.verified_purchase ? '<span class="badge badge-delivered">Yes</span>' : '—'}</td>
      <td>${date(r.created_at)}</td>
      <td>
        <button class="btn-icon del" title="Delete" onclick="deleteReview(${r.id})"><i class="fas fa-trash"></i></button>
      </td>
    </tr>`).join('');
}

$('reviewRatingFilter').addEventListener('change', loadReviews);

let _deleteReviewId = null;
function deleteReview(id) {
  _deleteReviewId = id;
  $('confirmMsg').textContent = 'Delete this review? This cannot be undone.';
  openModal('confirmModal');
  $('confirmDeleteBtn').onclick = async () => {
    try {
      await api('/reviews.php', {
        method: 'DELETE',
        body: JSON.stringify({ review_id: id })
      });
      toast('Review deleted');
      closeModal('confirmModal');
      loadReviews();
    } catch { toast('Delete failed', 'error'); }
    $('confirmDeleteBtn').onclick = null;
  };
}

/* ═══════════════════════════════════════════════════════════
   PAGINATION HELPER
═══════════════════════════════════════════════════════════ */
function buildPagination(containerId, totalPages, currentPage, loadFn) {
  const c = $(containerId);
  if (totalPages <= 1) { c.innerHTML = ''; return; }

  let html = '';
  if (currentPage > 1)
    html += `<button class="page-btn" onclick="${loadFn.name}(${currentPage - 1})">‹</button>`;

  for (let i = 1; i <= totalPages; i++) {
    if (i === 1 || i === totalPages || Math.abs(i - currentPage) <= 1) {
      html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="${loadFn.name}(${i})">${i}</button>`;
    } else if (Math.abs(i - currentPage) === 2) {
      html += `<span style="padding:6px 4px;color:var(--text-sub)">…</span>`;
    }
  }

  if (currentPage < totalPages)
    html += `<button class="page-btn" onclick="${loadFn.name}(${currentPage + 1})">›</button>`;

  c.innerHTML = html;
}

/* ═══════════════════════════════════════════════════════════
   XSS ESCAPE
═══════════════════════════════════════════════════════════ */
function esc(s) {
  if (s == null) return '';
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

/* ═══════════════════════════════════════════════════════════
   INIT
═══════════════════════════════════════════════════════════ */
loadDashboard();