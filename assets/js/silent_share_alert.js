document.addEventListener("DOMContentLoaded", () => {
  fetch("/book-sharing-system/backend/check_unlock_shares.php", { credentials: "include" })
    .then(res => res.json())
    .then(data => {
      if (data.success && data.shares && data.shares.length > 0) {
        const msg = data.shares.map(s => 
          `📚 ${s.title}（來自 ${s.sender_name}）\n📝 ${s.message}`
        ).join('\n\n');
        if (confirm(`📬 你收到新的書籍分享：\n\n${msg}\n\n點選「確定」前往「我的書櫃」查看`)) {
          window.location.href = "/book-sharing-system/frontend/bookshelf_list.php";
        }
      }
    });
});
