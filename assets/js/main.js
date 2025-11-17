// MAIN JAVASCRIPT FILE

// Sample Products Data
const productsData = [
  {
    id: 1,
    name: "Ikan Lele Premium",
    category: "lele",
    price: 75000,
    image: "🐟",
    description: "Lele segar berkualitas premium dari kolam terbaik",
    rating: 4.5,
  },
  {
    id: 2,
    name: "Ikan Nila Besar",
    category: "nila",
    price: 95000,
    image: "🐟",
    description: "Nila besar berisi dengan tekstur empuk",
    rating: 4.7,
  },
  {
    id: 3,
    name: "Ikan Gurame Gold",
    category: "gurame",
    price: 120000,
    image: "🐟",
    description: "Gurame emas dengan ukuran ideal untuk hidangan",
    rating: 4.8,
  },
  {
    id: 4,
    name: "Ikan Mas Jumbo",
    category: "mas",
    price: 110000,
    image: "🐟",
    description: "Mas jumbo pilihan untuk acara besar",
    rating: 4.6,
  },
  {
    id: 5,
    name: "Ikan Lele Ukuran Medium",
    category: "lele",
    price: 55000,
    image: "🐟",
    description: "Lele medium cocok untuk keluarga",
    rating: 4.4,
  },
  {
    id: 6,
    name: "Ikan Nila Medium",
    category: "nila",
    price: 65000,
    image: "🐟",
    description: "Nila ukuran menengah segar langsung dari kolam",
    rating: 4.3,
  },
  {
    id: 7,
    name: "Ikan Gurame Medium",
    category: "gurame",
    price: 85000,
    image: "🐟",
    description: "Gurame ukuran sedang berkualitas tinggi",
    rating: 4.7,
  },
  {
    id: 8,
    name: "Ikan Mas Medium",
    category: "mas",
    price: 75000,
    image: "🐟",
    description: "Mas berukuran sedang sempurna untuk berbagai masakan",
    rating: 4.5,
  },
]

// DOM Elements
const navButtons = document.querySelectorAll(".nav-btn")
const categoryCards = document.querySelectorAll(".category-card")
const searchInput = document.getElementById("search-input")
const productModal = document.getElementById("product-modal")
const modalClose = document.querySelector(".close")
const cartCountDisplay = document.getElementById("cart-count")

// Event Listeners
navButtons.forEach((btn) => {
  btn.addEventListener("click", handleNavigation)
})

categoryCards.forEach((card) => {
  card.addEventListener("click", handleCategoryClick)
})

if (searchInput) {
  searchInput.addEventListener("input", handleSearch)
}

if (modalClose) {
  modalClose.addEventListener("click", closeModal)
}

// Navigation Handler
function handleNavigation(e) {
  const page = e.currentTarget.getAttribute("data-page")
  console.log("Navigating to:", page)

  switch (page) {
    case "home":
      scrollToTop()
      break
    case "products":
      displayProducts()
      scrollToSection(".featured-products")
      break
    case "cart":
      displayCart()
      break
    case "contact":
      displayContact()
      break
    case "login":
      displayLogin()
      break
  }
}

// Category Click Handler
function handleCategoryClick(e) {
  const category = e.currentTarget.getAttribute("data-category")
  console.log("Selected category:", category)

  const filtered = productsData.filter((p) => p.category === category)
  displayProductsGrid(filtered)
  scrollToSection(".featured-products")
}

// Search Handler
function handleSearch(e) {
  const searchTerm = e.target.value.toLowerCase()
  const filtered = productsData.filter(
    (p) => p.name.toLowerCase().includes(searchTerm) || p.description.toLowerCase().includes(searchTerm),
  )

  if (searchTerm.length > 0) {
    displayProductsGrid(filtered)
  } else {
    displayProducts()
  }
}

// Display Products
function displayProducts() {
  displayProductsGrid(productsData)
}

// Display Products Grid
function displayProductsGrid(products) {
  const container = document.getElementById("featured-products")

  if (!container) return

  if (products.length === 0) {
    container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; padding: 2rem;">Produk tidak ditemukan</p>'
    return
  }

  container.innerHTML = products
    .map(
      (product) => `
        <div class="product-card" data-id="${product.id}">
            <div class="product-image">${product.image}</div>
            <div class="product-info">
                <h3 class="product-name">${product.name}</h3>
                <p class="product-category">${product.category.toUpperCase()}</p>
                <p class="product-price">Rp ${product.price.toLocaleString("id-ID")}</p>
                <p class="product-description">${product.description}</p>
                <div class="product-rating">
                    ${"⭐".repeat(Math.floor(product.rating))} (${product.rating})
                </div>
                <div class="product-buttons">
                    <button class="product-buttons button btn-details" onclick="viewProductDetails(${product.id})">
                        <i class="fas fa-eye"></i> Detail
                    </button>
                    <button class="product-buttons button btn-add-cart" onclick="addProductToCart(${product.id})">
                        <i class="fas fa-shopping-cart"></i> Beli
                    </button>
                </div>
            </div>
        </div>
    `,
    )
    .join("")
}

// View Product Details
function viewProductDetails(productId) {
  const product = productsData.find((p) => p.id === productId)
  if (!product) return

  const modalBody = document.getElementById("modal-body")
  modalBody.innerHTML = `
        <div style="text-align: center;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">${product.image}</div>
            <h2 style="color: var(--dark); margin-bottom: 1rem;">${product.name}</h2>
            <p style="color: var(--primary); font-size: 1.1rem; margin-bottom: 1rem;">Kategori: ${product.category.toUpperCase()}</p>
            <p style="font-size: 1.3rem; color: var(--accent); font-weight: bold; margin-bottom: 1rem;">Rp ${product.price.toLocaleString("id-ID")}</p>
            <p style="color: #666; margin-bottom: 1rem; line-height: 1.6;">${product.description}</p>
            <div style="color: #ffc107; margin-bottom: 1.5rem;">Rating: ${"⭐".repeat(Math.floor(product.rating))} (${product.rating})</div>
            <input type="number" id="quantity" value="1" min="1" max="100" style="width: 80px; padding: 0.5rem; margin-right: 1rem; border: 1px solid var(--gray); border-radius: 5px;">
            <button onclick="addProductToCart(${product.id})" class="btn btn-primary">
                <i class="fas fa-shopping-cart"></i> Tambah ke Keranjang
            </button>
        </div>
    `

  productModal.style.display = "block"
}

// Add Product to Cart
function addProductToCart(productId) {
  const quantity = Number.parseInt(document.getElementById("quantity")?.value || 1)

  if (!window.cart) {
    window.cart = {}
  }

  if (window.cart[productId]) {
    window.cart[productId] += quantity
  } else {
    window.cart[productId] = quantity
  }

  updateCartCount()
  closeModal()
  showNotification("Produk ditambahkan ke keranjang!")
}

// Update Cart Count
function updateCartCount() {
  const count = Object.values(window.cart || {}).reduce((a, b) => a + b, 0)
  cartCountDisplay.textContent = count
}

// Display Cart
function displayCart() {
  if (!window.cart || Object.keys(window.cart).length === 0) {
    showNotification("Keranjang belanja Anda kosong")
    return
  }

  let total = 0
  let cartHTML =
    '<div style="text-align: center;"><h2>Keranjang Belanja</h2><table style="width: 100%; margin-top: 1rem;">'
  cartHTML +=
    '<tr style="background: var(--light);"><th>Produk</th><th>Jumlah</th><th>Harga</th><th>Total</th><th>Aksi</th></tr>'

  Object.keys(window.cart).forEach((productId) => {
    const product = productsData.find((p) => p.id == productId)
    const qty = window.cart[productId]
    const subtotal = product.price * qty
    total += subtotal

    cartHTML += `
            <tr style="border-bottom: 1px solid var(--gray); padding: 1rem;">
                <td>${product.name}</td>
                <td>${qty}</td>
                <td>Rp ${product.price.toLocaleString("id-ID")}</td>
                <td>Rp ${subtotal.toLocaleString("id-ID")}</td>
                <td><button class="btn btn-secondary" onclick="removeFromCart(${productId})">Hapus</button></td>
            </tr>
        `
  })

  cartHTML += "</table>"
  cartHTML += `<div style="text-align: right; margin-top: 1rem; font-size: 1.3rem;"><strong>Total: Rp ${total.toLocaleString("id-ID")}</strong></div>`
  cartHTML += `<button class="btn btn-primary" style="margin-top: 1rem;">Checkout</button></div>`

  const modalBody = document.getElementById("modal-body")
  modalBody.innerHTML = cartHTML
  productModal.style.display = "block"
}

// Remove From Cart
function removeFromCart(productId) {
  delete window.cart[productId]
  updateCartCount()
  displayCart()
  showNotification("Produk dihapus dari keranjang")
}

// Display Contact
function displayContact() {
  const modalBody = document.getElementById("modal-body")
  modalBody.innerHTML = `
        <div>
            <h2 style="color: var(--dark); margin-bottom: 1.5rem;">Hubungi Kami</h2>
            <div style="margin-bottom: 2rem;">
                <h3 style="color: var(--primary);">📞 Telepon</h3>
                <p>+62 812 3456 7890</p>
            </div>
            <div style="margin-bottom: 2rem;">
                <h3 style="color: var(--primary);">📧 Email</h3>
                <p>info@lumbungdigital.com</p>
            </div>
            <div style="margin-bottom: 2rem;">
                <h3 style="color: var(--primary);">📍 Alamat</h3>
                <p>Jl. Mitra Ikan No. 123<br>Kalsel, 12345<br>Indonesia</p>
            </div>
            <div>
                <h3 style="color: var(--primary);">⏰ Jam Operasional</h3>
                <p>Senin - Jumat: 08:00 - 18:00<br>Sabtu: 08:00 - 16:00<br>Minggu: Tutup</p>
            </div>
        </div>
    `
  productModal.style.display = "block"
}

// Display Login
function displayLogin() {
  const modalBody = document.getElementById("modal-body")
  modalBody.innerHTML = `
        <form style="max-width: 400px; margin: 0 auto;">
            <h2 style="color: var(--dark); text-align: center; margin-bottom: 2rem;">Login</h2>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem;">Email</label>
                <input type="email" required style="width: 100%; padding: 0.8rem; border: 1px solid var(--gray); border-radius: 5px;">
            </div>
            <div style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem;">Password</label>
                <input type="password" required style="width: 100%; padding: 0.8rem; border: 1px solid var(--gray); border-radius: 5px;">
            </div>
            <button type="button" onclick="handleLogin()" class="btn btn-primary" style="width: 100%; justify-content: center;">
                Login
            </button>
            <p style="text-align: center; margin-top: 1rem;">Belum punya akun? <a href="#" style="color: var(--primary);">Daftar di sini</a></p>
        </form>
    `
  productModal.style.display = "block"
}

// Handle Login
function handleLogin() {
  showNotification("Fitur login sedang dalam pengembangan")
}

// Close Modal
function closeModal() {
  productModal.style.display = "none"
}

// Scroll to Section
function scrollToSection(selector) {
  const element = document.querySelector(selector)
  if (element) {
    element.scrollIntoView({ behavior: "smooth", block: "start" })
  }
}

// Scroll to Top
function scrollToTop() {
  window.scrollTo({ top: 0, behavior: "smooth" })
}

// Show Notification
function showNotification(message) {
  const notification = document.createElement("div")
  notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #006994 0%, #00a8d8 100%);
        color: white;
        padding: 1rem 2rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 105, 148, 0.3);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
    `
  notification.textContent = message
  document.body.appendChild(notification)

  setTimeout(() => {
    notification.style.animation = "slideInRight 0.3s ease reverse"
    setTimeout(() => notification.remove(), 300)
  }, 3000)
}

// Close modal when clicking outside
window.addEventListener("click", (e) => {
  if (e.target === productModal) {
    closeModal()
  }
})

// Initialize
document.addEventListener("DOMContentLoaded", () => {
  window.cart = {}
  displayProducts()
  updateCartCount()
})
