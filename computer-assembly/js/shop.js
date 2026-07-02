// Current product and page state
let currentPage = 1;
let currentProduct = null;
const productsPerPage = 12;

document.addEventListener('DOMContentLoaded', function() {
    loadProducts(1);
    setupEventListeners();
    loadURLParams();
});

function setupEventListeners() {

    const searchInput=document.getElementById('search-input');

    if(searchInput){
        searchInput.addEventListener(
            'input',
            ()=>loadProducts(1)
        );
    }

    const sortSelect=document.getElementById('sort-select');

    if(sortSelect){
        sortSelect.addEventListener(
            'change',
            ()=>loadProducts(1)
        );
    }

    const minPrice=document.getElementById('min-price');
    const maxPrice=document.getElementById('max-price');

    if(minPrice && maxPrice){

        minPrice.addEventListener(
            'change',
            ()=>loadProducts(1)
        );

        maxPrice.addEventListener(
            'change',
            ()=>loadProducts(1)
        );
    }
}


function loadURLParams(){

    const params=
    new URLSearchParams(window.location.search);

    const categoryId=
    params.get('category');

    if(categoryId){

        const checkbox=
        document.getElementById(
            `cat-${categoryId}`
        );

        if(checkbox){

            checkbox.checked=true;
            loadProducts(1);

        }
    }

}



async function loadProducts(page=1){

currentPage=page;

const loading=
document.getElementById('loading');

const productsGrid=
document.getElementById('products-grid');

if(loading)
loading.style.display='block';

if(productsGrid)
productsGrid.innerHTML='';


const searchValue=
document.getElementById('search-input')?.value || '';

const minPrice=
document.getElementById('min-price')?.value || '';

const maxPrice=
document.getElementById('max-price')?.value || '';

const sortValue=
document.getElementById('sort-select')?.value || 'name';


const selectedCategories=
Array.from(
document.querySelectorAll(
'.category-filter:checked'
)
).map(cb=>cb.value);

const categoryParam=
selectedCategories.length>0
? selectedCategories[0]
: '';


let queryParams=
new URLSearchParams();

if(searchValue)
queryParams.append(
 'search',
 searchValue
);

if(categoryParam)
queryParams.append(
 'category_id',
 categoryParam
);

if(minPrice)
queryParams.append(
 'min_price',
 minPrice
);

if(maxPrice)
queryParams.append(
 'max_price',
 maxPrice
);

if(sortValue)
queryParams.append(
 'sort',
 sortValue
);

queryParams.append(
 'page',
 page
);


try{

const response=
await fetch(
 `api/products.php?${queryParams}`
);

const data=
await response.json();

if(loading)
loading.style.display='none';

if(data.success){

displayProducts(
 data.data.products
);

document.getElementById(
 'product-count'
).textContent=
 data.data.pagination.total;

displayPagination(
 data.data.pagination
);

}

}
catch(error){

console.log(error);

if(loading)
loading.style.display='none';

productsGrid.innerHTML=
 '<p class="text-danger text-center">Error loading products</p>';

}

}


/* Helper to render stars (local to shop.js) */
function renderStarsLocal(rating){
    rating = Math.round(Number(rating) || 0);
    let out = '';
    for(let i=1;i<=5;i++){
        out += i <= rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
    }
    return out;
}

/* PRODUCT DISPLAY */

function displayProducts(products){

const grid=
document.getElementById(
 'products-grid'
);

if(!grid) return;

if(products.length===0){

grid.innerHTML=`
<div class="col-12">
<p class="text-center">
No products found
</p>
</div>
`;

return;

}

// Prefer using the template if present
const template = document.getElementById('product-card-template');
if(template){
    grid.innerHTML = '';
    products.forEach(product => {
        const clone = template.content.cloneNode(true);
        const card = clone.querySelector('.product-card');
        if(card){
            // set product id on dataset
            const pid = product.id || product.sku || product.slug || '';
            card.dataset.productId = pid;
        }
        // image
        const img = clone.querySelector('.product-image');
        if(img){
            const imgEl = img.tagName === 'IMG' ? img : img.querySelector('img');
            if(imgEl) imgEl.src = product.image ? `uploads/products/${product.image}` : 'images/default.jpg';
        }
        const nameEl = clone.querySelector('.product-name');
        if(nameEl) nameEl.textContent = product.name;
        const brandEl = clone.querySelector('.product-brand');
        if(brandEl) brandEl.textContent = product.brand || 'Generic';
        const priceEl = clone.querySelector('.product-price');
        if(priceEl) priceEl.textContent = formatCurrency(product.price);
        const stockEl = clone.querySelector('.product-stock');
        if(stockEl) stockEl.textContent = product.stock_quantity != null ? product.stock_quantity : '0';
        const reviewsCountEl = clone.querySelector('.product-reviews-count');
        if(reviewsCountEl) reviewsCountEl.textContent = product.review_count || 0;
        const ratingStarsEl = clone.querySelector('.product-rating-stars');
        if(ratingStarsEl) ratingStarsEl.innerHTML = renderStarsLocal(product.average_rating || 0);

        // buttons and interactions
        const imgClickEl = clone.querySelector('.product-image');
        if(imgClickEl){
            // Ensure clicks open full detail (fetches latest data)
            imgClickEl.addEventListener('click', ()=> showProductDetail(product.id));
        }
        const nameClickEl = clone.querySelector('.product-name');
        if(nameClickEl){
            nameClickEl.addEventListener('click', ()=> showProductDetail(product.id));
        }
        const viewBtn = clone.querySelector('.product-view-btn');
        if(viewBtn){
            viewBtn.addEventListener('click', ()=> openProductModalWithData(product));
        }
        const addCartBtn = clone.querySelector('.btn-add-cart');
        if(addCartBtn){
            addCartBtn.addEventListener('click', ()=> quickAddToCart(product.id, product.stock_quantity));
        }

        grid.appendChild(clone);
    });
    return;
}

// Fallback: older string-based rendering
grid.innerHTML=

products.map(product=>`

<div class="col-sm-6 col-lg-4">

<div class="product-card">

<div class="product-image">

<img
src="${
 product.image
 ? `uploads/products/${product.image}`
 : 'images/default.jpg'
}"

alt="${product.name}"

onclick="showProductDetail(${product.id})"

style="
width:100%;
height:220px;
object-fit:cover;
border-radius:10px;
cursor:pointer;
"
>

</div>

<div class="product-info">

<h6
class="product-name"
onclick="showProductDetail(${product.id})"
style="cursor:pointer"
>

${product.name}

</h6>

<p class="product-brand">

${product.brand || 'Generic'}

</p>

<div class="product-rating">

<i class="fas fa-star"
style="color:#f39c12">
</i>

${product.average_rating || 0}

(${product.review_count || 0}
reviews)

</div>

<p class="product-price">

${formatCurrency(product.price)}

</p>

<small class="text-muted">

Stock:
${product.stock_quantity}

</small>

<br><br>

<button
class="btn btn-add-cart"

onclick="quickAddToCart(
${product.id},
${product.stock_quantity}
)"
>

<i class="fas fa-shopping-cart"></i>

Add To Cart

</button>

</div>

</div>

</div>

`).join('');

}


/* PAGINATION */

function displayPagination(pagination){

const paginationContainer=
document.getElementById(
 'pagination'
);

if(!paginationContainer)
return;

let html='';

if(pagination.page>1){

html+=`
<li class="page-item">

<a class="page-link"

onclick="loadProducts(
${pagination.page-1}
)"

href="#">

Previous

</a>

</li>
`;

}


for(
let i=1;
i<=pagination.pages;
i++
){

html+=`

<li class="page-item
${i===pagination.page?'active':''}
">

<a
class="page-link"

href="#"

onclick="loadProducts(${i})"

>

${i}

</a>

</li>

`;

}

paginationContainer.innerHTML=
html;

}


/* PRODUCT DETAIL */

async function showProductDetail(
productId
){

try{

const response=
await fetch(
 `api/products.php?id=${productId}`
);

const data=
await response.json();

if(data.success){

currentProduct=
data.data;

document.getElementById(
 'modal-title'
).textContent=
data.data.name;

document.getElementById(
 'modal-image'
).src=

data.data.image
? `uploads/products/${data.data.image}`
: 'images/default.jpg';

document.getElementById(
 'modal-brand'
).textContent=
data.data.brand;

document.getElementById(
 'modal-category'
).textContent=
data.data.category_name;

document.getElementById(
 'modal-price'
).textContent=
formatCurrency(
 data.data.price
);

document.getElementById(
 'modal-rating'
).textContent=
data.data.average_rating;

document.getElementById(
 'modal-reviews'
).textContent=
data.data.review_count;

document.getElementById(
 'modal-stock'
).textContent=
data.data.stock_quantity;

document.getElementById(
 'modal-description'
).textContent=
data.data.description ||
'No description';


new bootstrap.Modal(
 document.getElementById(
 'productModal'
 )
).show();

}

}
catch(error){

console.log(error);

alert(
 'Error loading product'
);

}

}


/* CART FUNCTIONS */

function quickAddToCart(
productId,
stockQuantity
){

addProductToCart(
productId,
1
);

}

function addToCart(){

const quantity=parseInt(
document.getElementById(
 'modal-quantity'
).value
);

if(currentProduct){

addProductToCart(
 currentProduct.id,
 quantity
);

}

}

async function addProductToCart(
productId,
quantity
){

try{

const response=
await fetch(
 'api/cart.php',
 {
 method:'POST',
 headers:{
 'Content-Type':
 'application/json'
 },
 body:JSON.stringify({
 
 product_id:productId,
 quantity:quantity
 
 })
 }
 );

const data=
await response.json();

if(data.success){

alert(
 'Added to cart'
 );

updateCartCount();

}

}
catch(error){

console.log(error);

}

}


function resetFilters(){

document.getElementById(
 'search-input'
).value='';

document.getElementById(
 'min-price'
).value='';

document.getElementById(
 'max-price'
).value='';

loadProducts(1);

}
