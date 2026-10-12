import { useEffect, useState } from 'react';
import { adminApi, type ApiAudit, type ApiMessage, type ApiNote } from '../../lib/adminApi';
import { isPreviewSession } from '../../lib/previewSession';
import type { Session } from '../../lib/types';

const CASE_STATUSES = ['OPEN', 'WAITING_ON_CUSTOMER', 'WAITING_ON_AGENT', 'RESOLVED', 'CLOSED'] as const;

export function SessionOps({ session }: { session: Session }) {
  const preview = isPreviewSession(session.token);
  const [messages, setMessages] = useState<ApiMessage[]>([]);
  const [notes, setNotes] = useState<ApiNote[]>([]);
  const [audits, setAudits] = useState<ApiAudit[]>([]);
  const [draft, setDraft] = useState('');
  const [noteDraft, setNoteDraft] = useState('');
  const [status, setStatus] = useState(session.caseStatus || 'OPEN');
  const [error, setError] = useState('');

  useEffect(() => {
    setStatus(session.caseStatus || 'OPEN');
    setDraft('');
    setNoteDraft('');
    setError('');
    if (preview) {
      setMessages([]);
      setNotes([]);
      setAudits([]);
      return;
    }
    let cancelled = false;
    const load = async () => {
      const [msg, note, audit] = await Promise.all([
        adminApi.messages(session.token),
        adminApi.notes(session.token),
        adminApi.audit(session.token),
      ]);
      if (cancelled) return;
      if (msg.ok) setMessages(msg.messages || []);
      if (note.ok) setNotes(note.notes || []);
      if (audit.ok) setAudits(audit.audits || []);
    };
    void load();
    return () => {
      cancelled = true;
    };
  }, [session.token, session.lastResponse, preview]);

  if (preview) return null;

  const sendAgent = async () => {
    const body = draft.trim();
    if (!body) return;
    const data = await adminApi.postMessage(session.token, body);
    if (!data.ok) {
      setError('Message failed.');
      return;
    }
    setDraft('');
    setError('');
    if (data.message) setMessages((prev) => [...prev, data.message as ApiMessage]);
  };

  const saveNote = async () => {
    const body = noteDraft.trim();
    if (!body) return;
    const data = await adminApi.addNote(session.token, body);
    if (!data.ok) {
      setError('Note failed.');
      return;
    }
    setNoteDraft('');
    setError('');
    if (data.note) setNotes((prev) => [data.note as ApiNote, ...prev]);
  };

  const saveStatus = async (next: string) => {
    setStatus(next);
    const data = await adminApi.caseStatus(session.token, next);
    if (!data.ok) setError('Case status failed.');
  };

  return (
    <div className="session-ops">
      <label className="meta-row">
        <span>Case</span>
        <select value={status} onChange={(e) => void saveStatus(e.target.value)}>
          {CASE_STATUSES.map((value) => (
            <option key={value} value={value}>
              {value.replace(/_/g, ' ')}
            </option>
          ))}
        </select>
      </label>
      {session.publicAddress ? (
        <label className="meta-row">
          <span>Address</span>
          <input readOnly value={session.publicAddress} />
        </label>
      ) : null}
      {session.theme || session.language ? (
        <label className="meta-row">
          <span>Prefs</span>
          <input readOnly value={[session.theme, session.language].filter(Boolean).join(' / ')} />
        </label>
      ) : null}

      {/* <div className="session-ops-block">
        <span>Messages</span>
        <ul>
          {messages.map((item) => (
            <li key={item.id}>
              <em>{item.authorRole}</em> {item.body}
            </li>
          ))}
        </ul>
        <textarea value={draft} onChange={(e) => setDraft(e.target.value)} rows={2} maxLength={2000} placeholder="Agent reply" />
        <button type="button" className="btn-send" onClick={() => void sendAgent()}>
          Send message
        </button>
      </div>

      <div className="session-ops-block">
        <span>Notes</span>
        <ul>
          {notes.slice(0, 6).map((item) => (
            <li key={item.id}>{item.body}</li>
          ))}
        </ul>
        <textarea value={noteDraft} onChange={(e) => setNoteDraft(e.target.value)} rows={2} maxLength={4000} placeholder="Internal note" />
        <button type="button" className="btn-preview" onClick={() => void saveNote()}>
          Add note
        </button>
      </div>

      <div className="session-ops-block">
        <span>Audit</span>
        <ul>
          {audits.slice(0, 8).map((item) => (
            <li key={item.id}>{item.event_type || item.eventType}</li>
          ))}
        </ul>
      </div> */}
      
      {error ? <p className="accordion-hint">{error}</p> : null}
    </div>
  );
}
