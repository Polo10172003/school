/**
 * Lightweight privacy notice banner.
 * Stores acknowledgement in localStorage so it persists across visits.
 */
(function () {
  'use strict';

  const banner = document.getElementById('privacyNotice');
  if (!banner) {
    return;
  }

  const storageKey = banner.getAttribute('data-storage-key') || 'esr_privacy_notice_v1';
  const acceptedValue = 'acknowledged';

  const getStorage = () => {
    try {
      return window.localStorage || null;
    } catch (_) {
      return null;
    }
  };

  const storage = getStorage();

  const hide = () => {
    banner.setAttribute('hidden', 'hidden');
    banner.classList.remove('animate__fadeInDown');
  };

  const show = () => {
    banner.removeAttribute('hidden');
    banner.classList.add('animate__animated', 'animate__fadeInDown');
  };

  const markAccepted = () => {
    if (storage) {
      try {
        storage.setItem(storageKey, JSON.stringify({ value: acceptedValue, ts: Date.now() }));
      } catch (_) {
        // Ignore storage failures (e.g., Safari private mode).
      }
    }
    hide();
  };

  const hasAccepted = () => {
    if (!storage) {
      return false;
    }
    try {
      const raw = storage.getItem(storageKey);
      if (!raw) {
        return false;
      }
      const parsed = JSON.parse(raw);
      return parsed && parsed.value === acceptedValue;
    } catch (_) {
      return false;
    }
  };

  if (hasAccepted()) {
    hide();
  } else {
    show();
  }

  const acceptBtn = banner.querySelector('[data-privacy-action="accept"]');
  if (acceptBtn) {
    acceptBtn.addEventListener('click', markAccepted);
  }

  if (storage) {
    window.addEventListener('storage', (event) => {
      if (event.key !== storageKey) {
        return;
      }
      if (hasAccepted()) {
        hide();
      }
    });
  }
})();
