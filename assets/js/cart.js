// SHOPPING CART MANAGEMENT

function getProductById(productId) {
  // Placeholder function to simulate product retrieval by ID
  // In a real application, this would fetch data from a server or a database
  const products = {
    1: { name: "Product 1", price: 10 },
    2: { name: "Product 2", price: 20 },
    3: { name: "Product 3", price: 30 },
  }
  return products[productId]
}

class ShoppingCart {
  constructor() {
    this.items = this.loadCart()
  }

  addItem(productId, quantity = 1) {
    if (this.items[productId]) {
      this.items[productId].quantity += quantity
    } else {
      const product = getProductById(productId)
      if (product) {
        this.items[productId] = {
          ...product,
          quantity: quantity,
        }
      }
    }
    this.saveCart()
    return true
  }

  removeItem(productId) {
    delete this.items[productId]
    this.saveCart()
    return true
  }

  updateQuantity(productId, quantity) {
    if (this.items[productId]) {
      if (quantity <= 0) {
        this.removeItem(productId)
      } else {
        this.items[productId].quantity = quantity
        this.saveCart()
      }
      return true
    }
    return false
  }

  getTotal() {
    return Object.values(this.items).reduce((total, item) => total + item.price * item.quantity, 0)
  }

  getItemCount() {
    return Object.values(this.items).reduce((count, item) => count + item.quantity, 0)
  }

  getItems() {
    return this.items
  }

  isEmpty() {
    return Object.keys(this.items).length === 0
  }

  clear() {
    this.items = {}
    this.saveCart()
  }

  saveCart() {
    localStorage.setItem("lumbungdigital_cart", JSON.stringify(this.items))
  }

  loadCart() {
    const saved = localStorage.getItem("lumbungdigital_cart")
    return saved ? JSON.parse(saved) : {}
  }
}

// Initialize cart
const shoppingCart = new ShoppingCart()

function updateCartCount() {
  fetch('api/get-cart-count.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const cartCountElement = document.getElementById('cart-count');
        if (cartCountElement) {
          cartCountElement.textContent = data.count;
        }
      }
    })
    .catch(error => console.error('Error updating cart count:', error));
}

document.addEventListener('DOMContentLoaded', function() {
  updateCartCount();
});
