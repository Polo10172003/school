document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.auth-form').forEach((form) => {
    const submitButton = form.querySelector('button[type="submit"]');
    if (!submitButton) {
      return;
    }

    form.addEventListener('submit', () => {
      if (submitButton.classList.contains('is-loading')) {
        return;
      }

      submitButton.dataset.originalText = submitButton.textContent.trim();
      submitButton.classList.add('is-loading');
      submitButton.disabled = true;
      submitButton.textContent = 'Processing...';
    });
  });
});
