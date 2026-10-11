import { presenceLabel } from '@shared';
import type { Session } from '../../lib/types';

function Field({ label, value }: { label: string; value: string }) {
  return (
    <label className="field">
      <span>{label}</span>
      <input readOnly value={value} />
    </label>
  );
}

export function SessionMeta({ session }: { session: Session }) {
  const connected = session.status === 'connected';
  const caseTitle = session.name || session.caseNumber || 'Case';
  return (
    <section className="detail">
      <div className="detail-head">
        <div className={`status ${connected ? '' : 'waiting'}`}>
          <span className="dot">●</span>
          {presenceLabel(session.status)}
        </div>
      </div>
      <Field label="Page" value={session.currentPage} />
      <Field label="IP" value={session.ip || 'unknown'} />
      <Field label="Device" value={session.device || session.browser || 'Desktop'} />
      <div className="case-area">
        <div className="case-title">{caseTitle}</div>
        <div className="case-body">{session.lastResponse || session.contactEmail || session.contactName || ''}</div>
      </div>
    </section>
  );
}

