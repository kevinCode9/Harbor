/**
 * Visitor WebSocket → joins session:{token} with Admin for that case.
 * REST still owns durable writes; this channel delivers session:command.
 */

function loadSocketIo() {
  return new Promise((resolve, reject) => {
    if (typeof window !== 'undefined' && window.io) {
      resolve(window.io);
      return;
    }
    const script = document.createElement('script');
    script.src = '/socket.io/socket.io.js';
    script.async = true;
    script.onload = () => {
      if (window.io) resolve(window.io);
      else reject(new Error('socket.io client failed to load'));
    };
    script.onerror = () => reject(new Error('socket.io client script error'));
    document.head.appendChild(script);
  });
}

export async function connectVisitorSocket(token) {
  try {
    const ioFactory = await loadSocketIo();
    const socket = ioFactory({ transports: ['websocket'], autoConnect: true });

    socket.on('connect', () => {
      socket.emit('visitor:join', {
        role: 'visitor',
        token,
        page: window.location.pathname.replace(/\/$/, '') || '/welcome',
        status: document.visibilityState === 'visible' ? 'connected' : 'idle',
        browser: navigator.userAgent.slice(0, 80),
        device: /Mobi|Android|iPhone/i.test(navigator.userAgent) ? 'Mobile' : 'Desktop',
        location: 'Local',
        ip: '127.0.0.1',
      });
    });

    return socket;
  } catch {
    return null;
  }
}
