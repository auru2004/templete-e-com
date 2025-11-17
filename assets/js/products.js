// PRODUCTS MANAGEMENT SCRIPT

// Get all products
function getProducts() {
  // Bisa diintegrasikan dengan API PHP nantinya
  return window.productsData || []
}

// Filter products by category
function filterByCategory(category) {
  return getProducts().filter((product) => product.category === category)
}

// Filter products by price range
function filterByPrice(minPrice, maxPrice) {
  return getProducts().filter((product) => product.price >= minPrice && product.price <= maxPrice)
}

// Sort products
function sortProducts(sortType) {
  const products = [...getProducts()]

  switch (sortType) {
    case "price-low":
      return products.sort((a, b) => a.price - b.price)
    case "price-high":
      return products.sort((a, b) => b.price - a.price)
    case "rating":
      return products.sort((a, b) => b.rating - a.rating)
    case "name":
      return products.sort((a, b) => a.name.localeCompare(b.name))
    default:
      return products
  }
}

// Search products
function searchProducts(query) {
  const q = query.toLowerCase()
  return getProducts().filter(
    (product) =>
      product.name.toLowerCase().includes(q) ||
      product.description.toLowerCase().includes(q) ||
      product.category.toLowerCase().includes(q),
  )
}

// Get product by ID
function getProductById(id) {
  return getProducts().find((product) => product.id == id)
}

// Get products stats
function getProductsStats() {
  const products = getProducts()
  return {
    totalProducts: products.length,
    averagePrice: Math.round(products.reduce((a, b) => a + b.price, 0) / products.length),
    averageRating: (products.reduce((a, b) => a + b.rating, 0) / products.length).toFixed(1),
    categories: [...new Set(products.map((p) => p.category))],
  }
}
