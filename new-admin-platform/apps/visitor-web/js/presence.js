import { api, buildSessionState } from './session.js';

function presenceStatus() {
  return document.visibilityState === 'visible' ? 'connected' : 'idle';
}

export function startPresence(token, page) {
  async function heartbeat(extra = {}) {
    const payload = {
      ...buildSessionState(token, page),
      status: presenceStatus(),
      ...extra,
    };
    delete payload.page;
    return api('/api/visitor/presence', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  }

  const report = () => {
    void heartbeat({ status: presenceStatus() });
  };

  document.addEventListener('visibilitychange', report);
  window.addEventListener('pagehide', report);
  window.addEventListener('pageshow', report);

  setInterval(report, 5000);
  report();

  return heartbeat;
}
