// Save and restore scroll position (you already have this)
window.addEventListener("beforeunload", function () {
    localStorage.setItem("scrollPos", window.scrollY);
  });
  
  window.addEventListener("load", function () {
    const scrollPos = localStorage.getItem("scrollPos");
    if (scrollPos) {
      window.scrollTo(0, parseInt(scrollPos));
      localStorage.removeItem("scrollPos");
    }
  });
  
  // Add AJAX filter submit to avoid page reload and flicker
  document.addEventListener("DOMContentLoaded", function () {
    const filterForm = document.getElementById("filterForm");
    if (!filterForm) return; // safeguard if form not present
  
    filterForm.addEventListener("submit", function (e) {
      e.preventDefault();
  
      const formData = new FormData(filterForm);
      const params = new URLSearchParams(formData);
  
      fetch("fetch_payments.php?" + params.toString())
        .then((response) => response.text())
        .then((html) => {
          document.getElementById("paymentRecords").innerHTML = html;
          // Optionally update URL without reload:
          window.history.replaceState(null, "", "cashier_dashboard.php?" + params.toString());
        })
        .catch((error) => console.error("Error fetching payments:", error));
    });
  });
  