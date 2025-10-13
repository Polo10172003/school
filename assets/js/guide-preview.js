'use strict';

(function () {
  const overlay = document.getElementById('guide-preview-overlay');
  if (!overlay) {
    return;
  }

  const titleEl = document.getElementById('guide-preview-title');
  const contentEl = document.getElementById('guide-preview-content');
  const closeBtn = overlay.querySelector('.guide-preview-close');
  const previewButtons = document.querySelectorAll('.preview-guide-btn');

  function closeOverlay() {
    overlay.classList.remove('is-open');
    overlay.setAttribute('hidden', '');
    contentEl.innerHTML = '';
    titleEl.textContent = '';
    document.body.classList.remove('guide-preview-open');
  }

  async function openPreview(button) {
    const fileUrl = button.getAttribute('data-guide-url');
    if (!fileUrl) {
      return;
    }

    const guideName = button.getAttribute('data-guide-name') || 'Workbook preview';
    titleEl.textContent = guideName;
    contentEl.innerHTML = '<p class="guide-preview-loading">Loading workbook…</p>';

    overlay.removeAttribute('hidden');
    overlay.classList.add('is-open');
    document.body.classList.add('guide-preview-open');

    if (typeof XLSX === 'undefined') {
      contentEl.innerHTML = '<p class="guide-preview-error">Unable to load preview library.</p>';
      return;
    }

    try {
      const response = await fetch(fileUrl, { credentials: 'same-origin' });
      if (!response.ok) {
        throw new Error('Server responded with ' + response.status);
      }
      const arrayBuffer = await response.arrayBuffer();
      const workbook = XLSX.read(arrayBuffer, { type: 'array' });
      const firstSheetName = workbook.SheetNames[0];
      if (!firstSheetName) {
        throw new Error('Workbook is empty.');
      }
      const worksheet = workbook.Sheets[firstSheetName];
      const tableHtml = XLSX.utils.sheet_to_html(worksheet, { header: '', footer: '' });
      contentEl.innerHTML = '<div class="guide-preview-sheet">' + tableHtml + '</div>';
    } catch (err) {
      contentEl.innerHTML = '<p class="guide-preview-error">Unable to preview this workbook. ' +
        (err && err.message ? err.message : 'Please download the file instead.') + '</p>';
    }
  }

  previewButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      openPreview(button);
    });
  });

  closeBtn.addEventListener('click', closeOverlay);
  overlay.addEventListener('click', function (event) {
    if (event.target === overlay) {
      closeOverlay();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && overlay.classList.contains('is-open')) {
      closeOverlay();
    }
  });
})();
