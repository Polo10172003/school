(function () {
  'use strict';

  const config = window.SESSION_MONITOR || {};
  const pingUrl = config.pingUrl || '';
  const redirectUrl = config.redirectUrl || '';
  const message = config.message || 'New login detected. Please sign in again.';
  const storageKey = config.storageKey || '';
  const sessionToken = config.sessionToken || '';

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

  const parsePayload = (rawValue) => {
    if (!rawValue) {
      return null;
    }
    try {
      const data = JSON.parse(rawValue);
      return typeof data === 'object' && data !== null ? data : null;
    } catch (_) {
      return null;
    }
  };

  const evaluateIncomingToken = (incomingToken) => {
    if (!incomingToken || incomingToken === sessionToken) {
      return;
    }
    handleSessionExpired();
  };

  if (storageKey && sessionToken) {
    try {
      localStorage.setItem(
        storageKey,
        JSON.stringify({ token: sessionToken, ts: Date.now() })
      );
    } catch (_) {
      // Access to localStorage can fail (e.g. privacy mode); ignore silently.
    }

    window.addEventListener('storage', (event) => {
      if (event.key !== storageKey || isHandlingLogout) {
        return;
      }
      const payload = parsePayload(event.newValue);
      if (payload && typeof payload.token === 'string') {
        evaluateIncomingToken(payload.token);
      } else {
        handleSessionExpired();
      }
    });
  }

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
})();
