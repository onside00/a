document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('img').forEach((img) => {
    img.addEventListener('error', () => {
      img.style.display = 'none';
    }, { once: true });
  });
});
