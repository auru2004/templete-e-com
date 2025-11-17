// LOADING SCREEN SCRIPT

document.addEventListener("DOMContentLoaded", () => {
  // Simulasi loading selama 3.5 detik
  setTimeout(() => {
    const loadingScreen = document.getElementById("loading-screen")
    const mainContent = document.getElementById("main-content")

    // Fade out loading screen
    loadingScreen.style.opacity = "0"
    loadingScreen.style.visibility = "hidden"

    // Tampilkan main content dengan animasi
    mainContent.style.animation = "fadeIn 0.8s ease"

    // Hapus loading screen dari DOM setelah animasi selesai
    setTimeout(() => {
      loadingScreen.style.display = "none"
    }, 500)
  }, 3500)
})
