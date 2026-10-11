import { useEffect, useMemo, useRef, useState } from 'react';
import { PAGE_MENU, canSendSessionAction, normalizeSessionPage, presenceLabel } from '@shared';
import type { MenuPage } from '@shared';
import type { Session } from '../../lib/types';
import { screenKeyForPage } from '../../lib/safeScreens';
import { isPreviewSession } from '../../lib/previewSession';
import { adminApi } from '../../lib/adminApi';
import { SessionOps } from './SessionOps';

type CardDraft = {
  view: 'default' | 'invalid';
  hold: boolean;
  tailCode: string;
  channel: string;
  topic: string;
  callbackWindow: string;
  itemLabel: string;
  itemCount: string;
};

const emptyDraft = (): CardDraft => ({
  view: 'default',
  hold: false,
  tailCode: '',
  channel: '',
  topic: '',
  callbackWindow: '',
  itemLabel: '',
  itemCount: '',
});

export function SupportConsole({
  session,
  onSend,
  onPreview,
  onDemoFields,
}: {
  session: Session;
  onSend: (url: string, params?: Record<string, unknown>) => Promise<{ ok: boolean; message: string } | void>;
  onPreview: (url: string, params?: Record<string, unknown>) => void;
  onDemoFields?: (fields: { location: string; time: string; name: string }) => void;
}) {
  const [groups, setGroups] = useState<(typeof PAGE_MENU)[number][]>(PAGE_MENU);
  const current = normalizeSessionPage(session.currentPage);
  const matchedFamily =
    groups.find((group) => group.pages.some((page) => normalizeSessionPage(page.url) === current))
      ?.family || groups[0]?.family || '';
  const [family, setFamily] = useState(matchedFamily);
  const [openId, setOpenId] = useState('');
  const [sendNote, setSendNote] = useState<{ id: string; message: string } | null>(null);
  const [draft, setDraft] = useState<CardDraft>(emptyDraft);
  const [demoFields, setDemoFields] = useState({
    location: session.demoLocation || '',
    time: session.demoTime || '',
    name: session.demoName || session.contactName || '',
  });
  const demoTimer = useRef<number | null>(null);

  useEffect(() => {
    setFamily(matchedFamily);
  }, [matchedFamily]);

  useEffect(() => {
    let cancelled = false;
    void adminApi.pageMenu().then((data) => {
      if (cancelled || !data.ok || !Array.isArray(data.groups) || !data.groups.length) return;
      setGroups(data.groups as (typeof PAGE_MENU)[number][]);
    });
    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    setDraft(emptyDraft());
    setOpenId('');
    setDemoFields({
      location: session.demoLocation || '',
      time: session.demoTime || '',
      name: session.demoName || session.contactName || '',
    });
  }, [session.token, session.demoLocation, session.demoTime, session.demoName, session.contactName]);

  const active = useMemo(
    () => groups.find((group) => group.family === family) ?? groups[0],
    [groups, family],
  );

  const connected = session.status === 'connected';
  const previewOnly = isPreviewSession(session.token);
  const canSend = !previewOnly && canSendSessionAction(session.status);
  const deviceLine = [session.device, session.browser].filter(Boolean).join(', ') || 'Desktop browser';
  const ipLine = `${session.ip || 'unknown'}${session.location ? ` (${session.location})` : ''}`;

  const changeDemo = (next: { location: string; time: string; name: string }) => {
    setDemoFields(next);
    if (previewOnly || !onDemoFields) return;
    if (demoTimer.current) window.clearTimeout(demoTimer.current);
    demoTimer.current = window.setTimeout(() => onDemoFields(next), 250);
  };

  const cardParams = (page: MenuPage) => {
    const params: Record<string, unknown> = {};
    if (page.card === 'variant' || page.card === 'hold') params.view = draft.view;
    if (page.card === 'hold' && draft.hold) params.hold = true;
    if (page.fields === 'tail' && draft.tailCode) params.tailCode = draft.tailCode;
    if (page.fields === 'channel' && draft.channel) params.channel = draft.channel;
    if (page.fields === 'topic' && draft.topic) params.topic = draft.topic;
    if (page.fields === 'callback' && draft.callbackWindow) params.callbackWindow = draft.callbackWindow;
    if (page.fields === 'item') {
      if (draft.itemLabel) params.itemLabel = draft.itemLabel;
      if (draft.itemCount) params.itemCount = draft.itemCount;
    }
    if (page.fields === 'demo') {
      if (demoFields.location) params.location = demoFields.location;
      if (demoFields.time) params.time = demoFields.time;
      if (demoFields.name) params.name = demoFields.name;
    }
    return params;
  };

  const sendPage = async (page: MenuPage) => {
    if (!canSend) return;
    try {
      const result = await onSend(normalizeSessionPage(page.url), cardParams(page));
      setSendNote({ id: page.id, message: result?.ok ? 'Sent' : result?.message || 'Send failed.' });
    } catch (err) {
      setSendNote({ id: page.id, message: err instanceof Error ? err.message : 'Send failed.' });
    }
  };

  return (
    <section className="session-workspace">
      <aside className="session-meta-col">
        <div className={`status ${connected ? '' : 'waiting'}`}>
          <span className="dot">●</span>
          {presenceLabel(session.status)}
        </div>

        <label className="meta-row">
          <span>Page</span>
          <input readOnly value={screenKeyForPage(session.currentPage)} />
        </label>
        <label className="meta-row">
          <span>IP</span>
          <input readOnly value={ipLine} />
        </label>
        <label className="meta-row">
          <span>Device</span>
          <input readOnly value={deviceLine} />
        </label>
        <label className="meta-row">
          <span>Email</span>
          <div className="input-wrapper">
            <input
              id="demo-email"
              type="text"
              readOnly
              value={session.contactEmail || session.contactName || ''}
              aria-label="Email"
            />

            <button
              className="copy-btn"
              type="button"
              aria-label="Copy email"
              onClick={() => {
                const text = session.contactEmail || session.contactName || '';
                if (text) void navigator.clipboard.writeText(text);
              }}
            >
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path
                  d="M9 9h10v10H9z"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="1.8"
                />
                <path
                  d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="1.8"
                  strokeLinecap="round"
                />
              </svg>
            </button>
          </div>
        </label>
        <label className="meta-row">
          <span>Live</span>
          <input readOnly value={`[ ${session.liveText || ''} ]`} />
        </label>
        <SessionOps session={session} />
      </aside>

      <div className="session-actions-col">
        <div className="chip-grid">
          {groups.map((group) => (
            <button
              key={group.family}
              type="button"
              className={`chip ${group.family === family ? 'on' : ''}`}
              onClick={() => setFamily(group.family)}
            >
              {group.family}
            </button>
          ))}
        </div>

        <div className="accordion-list">
          {(active?.pages || []).map((page) => {
            const url = normalizeSessionPage(page.url);
            const applied = current === url;
            const expanded = openId === page.id;
            return (
              <div key={page.id} className={`accordion-item ${expanded ? 'open' : ''} ${applied ? 'applied' : ''}`}>
                <button
                  type="button"
                  className="accordion-hit"
                  onClick={() => setOpenId(expanded ? '' : page.id)}
                >
                  <span>
                    <span className="accordion-title">{page.name}</span>
                    <span className="accordion-hint">{page.hint}</span>
                  </span>
                  <span className={`settings-chevron ${expanded ? 'open' : ''}`}>▾</span>
                </button>
                {expanded ? (
                  <div className="accordion-body">
                    {page.card === 'hold' ? (
                      <div className="variant-row">
                        <button
                          type="button"
                          className={`variant-btn ${draft.hold ? 'on' : ''}`}
                          onClick={() => setDraft({ ...draft, hold: true })}
                        >
                          Hold
                        </button>
                        <button
                          type="button"
                          className={`variant-btn ${!draft.hold ? 'on' : ''}`}
                          onClick={() => setDraft({ ...draft, hold: false })}
                        >
                          Resume
                        </button>
                      </div>
                    ) : null}
                    {page.card === 'variant' || page.card === 'hold' ? (
                      <div className="variant-row">
                        <button
                          type="button"
                          className={`variant-btn ${draft.view === 'default' ? 'on' : ''}`}
                          onClick={() => setDraft({ ...draft, view: 'default' })}
                        >
                          Default
                        </button>
                        <button
                          type="button"
                          className={`variant-btn ${draft.view === 'invalid' ? 'on' : ''}`}
                          onClick={() => setDraft({ ...draft, view: 'invalid' })}
                        >
                          Invalid
                        </button>
                      </div>
                    ) : null}
                    {page.fields === 'demo' ? (
                      <div className="demo-fields">
                        <label>
                          <span>Location</span>
                          <input
                            value={demoFields.location}
                            onChange={(e) => changeDemo({ ...demoFields, location: e.target.value })}
                            placeholder="City, Region"
                          />
                        </label>
                        <label>
                          <span>Time</span>
                          <input
                            value={demoFields.time}
                            onChange={(e) => changeDemo({ ...demoFields, time: e.target.value })}
                            placeholder="Local time"
                          />
                        </label>
                        <label>
                          <span>Name</span>
                          <input
                            value={demoFields.name}
                            onChange={(e) => changeDemo({ ...demoFields, name: e.target.value })}
                            placeholder="Display name"
                          />
                        </label>
                      </div>
                    ) : null}
                    {page.fields === 'tail' ? (
                      <div className="demo-fields">
                        <label>
                          <span>Suffix</span>
                          <input
                            value={draft.tailCode}
                            onChange={(e) => setDraft({ ...draft, tailCode: e.target.value })}
                            placeholder="Public case suffix"
                            maxLength={8}
                          />
                        </label>
                      </div>
                    ) : null}
                    {page.fields === 'channel' ? (
                      <div className="variant-row">
                        {(['chat', 'phone', 'desk'] as const).map((channel) => (
                          <button
                            key={channel}
                            type="button"
                            className={`variant-btn ${draft.channel === channel ? 'on' : ''}`}
                            onClick={() => setDraft({ ...draft, channel })}
                          >
                            {channel[0].toUpperCase() + channel.slice(1)}
                          </button>
                        ))}
                      </div>
                    ) : null}
                    {page.fields === 'topic' ? (
                      <div className="demo-fields">
                        <label>
                          <span>Topic</span>
                          <input
                            value={draft.topic}
                            onChange={(e) => setDraft({ ...draft, topic: e.target.value })}
                            placeholder="Case topic"
                          />
                        </label>
                      </div>
                    ) : null}
                    {page.fields === 'callback' ? (
                      <div className="demo-fields">
                        <label>
                          <span>Window</span>
                          <input
                            value={draft.callbackWindow}
                            onChange={(e) => setDraft({ ...draft, callbackWindow: e.target.value })}
                            placeholder="Tomorrow 10:00"
                          />
                        </label>
                      </div>
                    ) : null}
                    {page.fields === 'item' ? (
                      <div className="demo-fields">
                        <label>
                          <span>Item</span>
                          <input
                            value={draft.itemLabel}
                            onChange={(e) => setDraft({ ...draft, itemLabel: e.target.value })}
                            placeholder="Line item"
                          />
                        </label>
                        <label>
                          <span>Count</span>
                          <input
                            value={draft.itemCount}
                            onChange={(e) => setDraft({ ...draft, itemCount: e.target.value })}
                            placeholder="1"
                          />
                        </label>
                      </div>
                    ) : null}
                    <div className="accordion-actions">
                      <button
                        type="button"
                        className="btn-send"
                        disabled={!canSend}
                        onClick={() => sendPage(page)}
                      >
                        {connected ? 'Send' : 'Idle'}
                      </button>
                      <button
                        type="button"
                        className="btn-preview"
                        onClick={() => onPreview(url, cardParams(page))}
                      >
                        Preview
                      </button>
                    </div>
                    {sendNote?.id === page.id ? <p className="accordion-hint">{sendNote.message}</p> : null}
                  </div>
                ) : null}
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
