(function () {
  'use strict';

  const config = window.SESSION_MONITOR || {};
  const pingUrl = config.pingUrl || '';
  const redirectUrl = config.redirectUrl || '';
  const message = config.message || 'New login detected. Please sign in again.';
  const intervalMs = Number(config.intervalMs || 15000);

  if (!pingUrl || !redirectUrl) {
    return;
  }

  let isHandlingLogout = false;

  const handleSessionExpired = () => {
    if (isHandlingLogout) {
      return;
    }
    isHandlingLogout = true;
    try {
      alert(message);
    } catch (_) {
      // ignore alert failures (e.g. document already unloading)
    } finally {
      window.location.replace(redirectUrl);
    }
  };

  const performPing = () => {
    fetch(pingUrl, {
      method: 'GET',
      credentials: 'include',
      cache: 'no-store',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
      .then((response) => {
        if (response.status === 401) {
          handleSessionExpired();
        }
      })
      .catch(() => {
        // Network hiccups shouldn't immediately boot the user; ignore and retry.
      });
  };

  performPing();
  setInterval(performPing, intervalMs);
})();
