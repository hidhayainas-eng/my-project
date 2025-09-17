function confirmDelete(formId, message) {
  const overlay = document.getElementById("confirm-overlay");
  const text = document.getElementById("confirm-text");
  const yesBtn = document.getElementById("confirm-yes");
  const noBtn = document.getElementById("confirm-no");
  text.textContent = message || "Are you sure you want to delete this record?";
  overlay.style.display = "flex";
  yesBtn.onclick = () => {
    overlay.style.display = "none";
    document.getElementById(formId).submit();
  };
  noBtn.onclick = () => (overlay.style.display = "none");
}
