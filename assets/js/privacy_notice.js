/**
 * Display the privacy notice in a modal and persist acknowledgement.
 */
(function () {
  'use strict';

  const modalElement = document.getElementById('privacyNoticeModal');
  if (!modalElement) {
    return;
  }

  const storageKey = modalElement.getAttribute('data-storage-key') || 'esr_privacy_notice_v1';
  const forceShow = modalElement.getAttribute('data-force-show') === '1';
  const acceptedValue = 'acknowledged';

  const resolveStorage = () => {
    try {
      return window.localStorage || null;
    } catch (_) {
      return null;
    }
  };

  const storage = resolveStorage();

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

  const persistAcceptance = () => {
    if (!storage) {
      return;
    }
    try {
      storage.setItem(storageKey, JSON.stringify({ value: acceptedValue, ts: Date.now() }));
    } catch (_) {
      // Ignore storage failures (e.g. private browsing)
    }
  };

  let modalInstance = null;
  const ensureModalInstance = () => {
    if (!modalInstance && window.bootstrap && window.bootstrap.Modal) {
      modalInstance = new window.bootstrap.Modal(modalElement, {
        backdrop: 'static',
        keyboard: false,
      });
    }
    return modalInstance;
  };

  const showModal = () => {
    const instance = ensureModalInstance();
    if (instance) {
      instance.show();
    }
  };

  const hideModal = () => {
    const instance = ensureModalInstance();
    if (instance) {
      instance.hide();
    }
  };

  const scheduleDisplay = () => {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', showModal, { once: true });
    } else {
      showModal();
    }
  };

  if (forceShow) {
    scheduleDisplay();
  } else if (!hasAccepted()) {
    scheduleDisplay();
  }

  const acceptBtn = modalElement.querySelector('[data-privacy-action="accept"]');
  if (acceptBtn) {
    acceptBtn.addEventListener('click', () => {
      persistAcceptance();
      hideModal();
    });
  }

  if (storage && !forceShow) {
    window.addEventListener('storage', (event) => {
      if (event.key !== storageKey) {
        return;
      }
      if (hasAccepted()) {
        hideModal();
      }
    });
  }
})();
