// Dropdown Menu Handler
document.addEventListener("DOMContentLoaded", () => {
  // Handle dropdown toggle click
  const dropdownToggles = document.querySelectorAll(".dropdown-toggle")

  dropdownToggles.forEach((toggle) => {
    toggle.addEventListener("click", function (e) {
      // Prevent default navigation
      e.preventDefault()

      // Get the parent nav-dropdown
      const navDropdown = this.closest(".nav-dropdown")
      const dropdownMenu = navDropdown.querySelector(".dropdown-menu")

      // Toggle display
      if (dropdownMenu) {
        const isVisible = dropdownMenu.style.display === "block"
        dropdownMenu.style.display = isVisible ? "none" : "block"

        // Close other dropdowns
        document.querySelectorAll(".dropdown-menu").forEach((menu) => {
          if (menu !== dropdownMenu) {
            menu.style.display = "none"
          }
        })
      }
    })
  })

  // Close dropdown when clicking outside
  document.addEventListener("click", (e) => {
    const isDropdownToggle = e.target.closest(".dropdown-toggle")
    const isDropdownMenu = e.target.closest(".dropdown-menu")

    if (!isDropdownToggle && !isDropdownMenu) {
      document.querySelectorAll(".dropdown-menu").forEach((menu) => {
        menu.style.display = "none"
      })
    }
  })

  // Handle menu toggle for mobile
  const menuToggle = document.querySelector(".menu-toggle")
  if (menuToggle) {
    menuToggle.addEventListener("click", () => {
      const navButtons = document.querySelector(".nav-buttons")
      navButtons.style.display = navButtons.style.display === "flex" ? "none" : "flex"
    })
  }
})
