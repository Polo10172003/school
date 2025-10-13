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
  const viewerBase = 'https://view.officeapps.live.com/op/embed.aspx?src=';

  function closeOverlay() {
    overlay.classList.remove('is-open');
    overlay.setAttribute('hidden', '');
    contentEl.innerHTML = '';
    titleEl.textContent = '';
    document.body.classList.remove('guide-preview-open');
  }

  function buildAbsoluteUrl(relativeUrl) {
    try {
      return new URL(relativeUrl, window.location.href).href;
    } catch (error) {
      return null;
    }
  }

  function openPreview(button) {
    const fileUrl = button.getAttribute('data-guide-url');
    if (!fileUrl) {
      return;
    }

    const absoluteUrl = buildAbsoluteUrl(fileUrl);
    if (!absoluteUrl) {
      return;
    }

    const guideName = button.getAttribute('data-guide-name') || 'Workbook preview';
    titleEl.textContent = guideName;
    document.body.classList.add('guide-preview-open');
    overlay.removeAttribute('hidden');
    overlay.classList.add('is-open');

    const viewerUrl = viewerBase + encodeURIComponent(absoluteUrl);
    const loading = document.createElement('p');
    loading.className = 'guide-preview-loading';
    loading.textContent = 'Loading preview…';

    const iframe = document.createElement('iframe');
    iframe.className = 'guide-preview-frame';
    iframe.src = viewerUrl;
    iframe.title = guideName;
    iframe.setAttribute('loading', 'lazy');
    iframe.setAttribute('allowfullscreen', 'true');
    iframe.addEventListener('load', function () {
      loading.remove();
    }, { once: true });

    const fallback = document.createElement('p');
    fallback.className = 'guide-preview-fallback';
    const fallbackLink = document.createElement('a');
    fallbackLink.href = absoluteUrl;
    fallbackLink.target = '_blank';
    fallbackLink.rel = 'noopener';
    fallbackLink.textContent = 'Open in a new tab';
    fallback.append('If the preview does not load, ', fallbackLink, '.');

    contentEl.innerHTML = '';
    contentEl.appendChild(loading);
    contentEl.appendChild(iframe);
    contentEl.appendChild(fallback);
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
