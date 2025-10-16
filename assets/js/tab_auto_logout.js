(function () {
  'use strict';

  if (window.__ESR_AUTO_LOGOUT_BOUND__) {
    return;
  }
  window.__ESR_AUTO_LOGOUT_BOUND__ = true;

  const config = window.AUTO_LOGOUT_CONFIG || {};
  const logoutUrl = typeof config.logoutUrl === 'string' ? config.logoutUrl : '';

  if (!logoutUrl) {
    return;
  }

  const navigationGrace =
    typeof config.navigationGrace === 'number' && config.navigationGrace >= 0
      ? config.navigationGrace
      : 1200;

  const resolveUrl = (rawUrl) => {
    try {
      return new URL(rawUrl, window.location.href);
    } catch (_) {
      return null;
    }
  };

  const appendAutoFlag = (urlObj) => {
    if (!urlObj) {
      return null;
    }
    const copy = new URL(urlObj.href);
    copy.searchParams.set('auto', '1');
    return copy;
  };

  const resolvedLogout = resolveUrl(logoutUrl);
  if (!resolvedLogout) {
    return;
  }
  const beaconUrl = appendAutoFlag(resolvedLogout);

  let isNavigating = false;
  let isLoggingOut = false;
  let navigationTimer = null;

  const resetNavigationFlag = () => {
    isNavigating = false;
    navigationTimer = null;
  };

  const markNavigation = () => {
    if (isLoggingOut) {
      return;
    }
    isNavigating = true;
    if (navigationTimer) {
      clearTimeout(navigationTimer);
    }
    navigationTimer = window.setTimeout(resetNavigationFlag, navigationGrace);
  };

  const wrapLocationMethod = (methodName) => {
    try {
      const original = window.location[methodName];
      if (typeof original !== 'function') {
        return;
      }
      window.location[methodName] = function wrappedLocationMethod() {
        markNavigation();
        return original.apply(window.location, arguments);
      };
    } catch (_) {
      // Some browsers do not allow overriding location methods; ignore errors.
    }
  };

  wrapLocationMethod('assign');
  wrapLocationMethod('replace');
  wrapLocationMethod('reload');

  const sendLogout = () => {
    if (isLoggingOut || isNavigating) {
      return;
    }
    isLoggingOut = true;

    if (!beaconUrl) {
      return;
    }

    const attempts = [];

    try {
      if (navigator.sendBeacon) {
        const beaconSent = navigator.sendBeacon(beaconUrl.href);
        attempts.push(beaconSent);
      }
    } catch (_) {
      attempts.push(false);
    }

    try {
      fetch(beaconUrl.href, {
        method: 'GET',
        credentials: 'include',
        cache: 'no-store',
        keepalive: true,
      })
        .then(() => {
          attempts.push(true);
        })
        .catch(() => {
          attempts.push(false);
        });
      attempts.push(true);
    } catch (_) {
      attempts.push(false);
    }

    if (!attempts.some(Boolean)) {
      isLoggingOut = false;
    }
  };

  document.addEventListener(
    'click',
    (event) => {
      const anchor = event.target ? event.target.closest('a') : null;
      if (!anchor) {
        return;
      }
      if (event.defaultPrevented) {
        return;
      }

      const href = anchor.getAttribute('href');
      if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
        return;
      }

      const target = anchor.getAttribute('target');
      if (target && target !== '_self' && target !== '') {
        return;
      }

      const destination = resolveUrl(href);
      if (!destination) {
        return;
      }

      if (destination.href === resolvedLogout.href) {
        isLoggingOut = true;
        return;
      }

      if (destination.origin === window.location.origin) {
        markNavigation();
      }
    },
    true
  );

  document.addEventListener(
    'submit',
    (event) => {
      if (event.defaultPrevented) {
        return;
      }
      markNavigation();
    },
    true
  );

  window.addEventListener('beforeunload', sendLogout);
  window.addEventListener('pagehide', (event) => {
    if (event.persisted) {
      return;
    }
    sendLogout();
  });
})();
