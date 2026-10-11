import type { ReactNode } from 'react';

function PanelIcon() {
  return (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path d="M4 7h10" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
      <circle cx="16.5" cy="7" r="2.2" stroke="currentColor" strokeWidth="1.8" />
      <path d="M20 12H10" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
      <circle cx="7.5" cy="12" r="2.2" stroke="currentColor" strokeWidth="1.8" />
      <path d="M4 17h10" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
      <circle cx="16.5" cy="17" r="2.2" stroke="currentColor" strokeWidth="1.8" />
    </svg>
  );
}

function SettingsIcon() {
  return (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path
        d="M12 3.5 14.2 5l2.5-.4 1.3 2.2 2.2 1.3-.4 2.5L21.5 12l-1.7 1.4.4 2.5-2.2 1.3-1.3 2.2-2.5-.4L12 20.5l-2.2-1.5-2.5.4-1.3-2.2-2.2-1.3.4-2.5L2.5 12l1.7-1.4-.4-2.5 2.2-1.3 1.3-2.2 2.5.4L12 3.5Z"
        stroke="currentColor"
        strokeWidth="1.5"
        strokeLinejoin="round"
      />
      <circle cx="12" cy="12" r="2.6" stroke="currentColor" strokeWidth="1.5" />
    </svg>
  );
}

export function AdminShell({
  view,
  onView,
  onLogout,
  children,
}: {
  view: 'panel' | 'settings';
  onView: (next: 'panel' | 'settings') => void;
  onLogout?: () => void;
  children: ReactNode;
}) {
  return (
    <div className="app-shell">
      <div className="app-body">
        <nav className="rail">
          <div className="rail-spacer" />
          <button type="button" className={view === 'panel' ? 'active' : ''} onClick={() => onView('panel')}>
            <PanelIcon />
            Panel
          </button>
          <button
            type="button"
            className={view === 'settings' ? 'active' : ''}
            onClick={() => onView('settings')}
          >
            <SettingsIcon />
            Settings
          </button>
          {onLogout ? (
            <button type="button" className="rail-logout" onClick={onLogout}>
              Logout
            </button>
          ) : null}
        </nav>
        <div className="main-split">{children}</div>
      </div>
    </div>
  );
}

export function AdminLayout({ children }: { children: ReactNode }) {
  return children;
}
