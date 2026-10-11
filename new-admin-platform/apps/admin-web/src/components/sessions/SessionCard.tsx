import { memo } from 'react';
import { presenceLabel } from '@shared';
import type { Session } from '../../lib/types';
import { screenKeyForPage } from '../../lib/safeScreens';

function SessionCardView({
  session,
  onView,
  onClose,
}: {
  session: Session;
  onView: (token: string) => void;
  onClose: (token: string) => void;
}) {
  const connected = session.status === 'connected';
  return (
    <article className="session-card">
      <div className="session-card-head">
        <div className={`status ${connected ? '' : 'idle'}`}>
          <span className="dot">●</span>
          {presenceLabel(session.status)}
        </div>
        <button type="button" className="icon-btn" aria-label="End session" onClick={() => onClose(session.token)}>
          ×
        </button>
      </div>
      <label className="session-field">
        <span>Page</span>
        <input readOnly value={screenKeyForPage(session.currentPage)} />
      </label>
      <label className="session-field">
        <span>Email</span>
        <input readOnly value={session.contactEmail || session.contactName || ''} />
      </label>
      <label className="session-field">
        <span>IP</span>
        <input readOnly value={`${session.ip || 'unknown'}${session.location ? ` (${session.location})` : ''}`} />
      </label>
      <label className="session-field">
        <span>Device</span>
        <input readOnly value={[session.device, session.browser].filter(Boolean).join(', ') || 'Desktop'} />
      </label>
      {/* <label className="session-field">
        <span>Live</span>
        <input readOnly value={`[ ${session.liveText || ''} ]`} />
      </label>  */}
      <button type="button" className="session-view" onClick={() => onView(session.token)}>
        View
      </button>
    </article>
  );
}

export const SessionCard = memo(SessionCardView);
