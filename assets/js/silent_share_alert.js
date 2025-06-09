document.addEventListener("DOMContentLoaded", () => {
  fetch("/book-sharing-system/backend/check_unlock_shares.php", { credentials: "include" })
    .then(res => res.text())
    .then(text => {
      console.log("🔔 Check unlock shares response:", text); // Add this to debug
      if (text.trim() !== "none") {
        alert(text);  // Should show the popup
      }
    });
});
