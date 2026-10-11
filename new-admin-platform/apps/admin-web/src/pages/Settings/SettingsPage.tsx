import { useEffect, useMemo, useState } from 'react';
import { PAGE_MENU, normalizeSessionPage } from '@shared';
import { adminApi } from '../../lib/adminApi';

export type PanelSettings = {
  enabled: boolean;
  notifications: boolean;
  startingPage: string;
  sessionTimeoutMinutes: number;
  sound: boolean;
  theme: string;
};

export function SettingsPanel({
  settings,
  onChange,
  onSave,
  onPreview,
  onKeyRotated,
}: {
  settings: PanelSettings;
  onChange: (next: PanelSettings) => void;
  onSave: (next?: PanelSettings) => void;
  onPreview?: (url?: string) => void;
  onKeyRotated?: () => void;
}) {
  const groups = PAGE_MENU;
  const starting = normalizeSessionPage(settings.startingPage);
  const matchedFamily =
    groups.find((group) => group.pages.some((page) => normalizeSessionPage(page.url) === starting))
      ?.family || groups[0]?.family || '';
  const [family, setFamily] = useState(matchedFamily);
  const [openId, setOpenId] = useState('');

  useEffect(() => {
    setFamily(matchedFamily);
  }, [matchedFamily]);

  const active = useMemo(
    () => groups.find((group) => group.family === family) ?? groups[0],
    [groups, family],
  );

  const commit = (next: PanelSettings) => {
    onChange(next);
    onSave(next);
  };

  return (
    <section className="settings settings-firstpage">
      <KeyRotateCard onKeyRotated={onKeyRotated} />

      <div className="settings-card">
        <div className="settings-row">
          <div>
            <div className="settings-title">Enable Panel</div>
            <div className="settings-hint">Panel will redirect when disabled</div>
          </div>
          <Toggle on={settings.enabled} onClick={() => commit({ ...settings, enabled: !settings.enabled })} />
        </div>
      </div>

      <div className="settings-card">
        <div className="settings-row">
          <div>
            <div className="settings-title">Notification Sounds</div>
            <div className="settings-hint">Sounds will play when notifications are received</div>
          </div>
          <Toggle on={settings.sound} onClick={() => commit({ ...settings, sound: !settings.sound })} />
        </div>
      </div>

      <div className="settings-card settings-initial">
        <div className="settings-initial-head">
          <div className="settings-title">Initial page</div>
          <div className="settings-hint">First page after visiting the panel</div>
        </div>

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

        {(active?.pages || []).map((page) => {
          const url = normalizeSessionPage(page.url);
          const selected = starting === url;
          const expanded = openId === page.id;
          return (
            <div key={page.id} className={`accordion-item ${expanded ? 'open' : ''} ${selected ? 'applied' : ''}`}>
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
                  {page.fields === 'demo' ? (
                    <div className="demo-fields">
                      <label>
                        <span>Location</span>
                        <input readOnly placeholder="Shown after Send on a live case" />
                      </label>
                      <label>
                        <span>Time</span>
                        <input readOnly placeholder="Shown after Send on a live case" />
                      </label>
                      <label>
                        <span>Name</span>
                        <input readOnly placeholder="Shown after Send on a live case" />
                      </label>
                    </div>
                  ) : null}
                  <div className="accordion-actions">
                    <button
                      type="button"
                      className="btn-send"
                      onClick={() => commit({ ...settings, startingPage: url })}
                    >
                      Select
                    </button>
                    <button type="button" className="btn-preview" onClick={() => onPreview?.(url)}>
                      Preview
                    </button>
                  </div>
                </div>
              ) : null}
            </div>
          );
        })}
      </div>
    </section>
  );
}

function KeyRotateCard({ onKeyRotated }: { onKeyRotated?: () => void }) {
  const [currentKey, setCurrentKey] = useState('');
  const [nextKey, setNextKey] = useState('');
  const [message, setMessage] = useState('');
  const [busy, setBusy] = useState(false);

  const rotate = async () => {
    setBusy(true);
    setMessage('');
    try {
      const data = await adminApi.rotateKey(currentKey, nextKey);
      if (!data.ok) {
        setMessage(String(data.message || 'Rotate failed'));
        return;
      }
      setMessage(String(data.message || 'Key rotated'));
      setCurrentKey('');
      setNextKey('');
      onKeyRotated?.();
    } catch {
      setMessage('Rotate failed — is the API running?');
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="settings-card">
      <div className="settings-title">Admin key (one active)</div>
      <div className="settings-hint">
        Educational rotation — only one key works at a time. After rotate, sign in again with the new key.
      </div>
      <div className="demo-fields" style={{ marginTop: 12 }}>
        <label>
          <span>Current key</span>
          <input
            type="password"
            autoComplete="off"
            value={currentKey}
            onChange={(e) => setCurrentKey(e.target.value)}
          />
        </label>
        <label>
          <span>New key (min 8 chars)</span>
          <input
            type="password"
            autoComplete="off"
            value={nextKey}
            onChange={(e) => setNextKey(e.target.value)}
          />
        </label>
      </div>
      <div className="accordion-actions" style={{ marginTop: 12 }}>
        <button type="button" className="btn-send" disabled={busy} onClick={() => void rotate()}>
          Rotate key
        </button>
      </div>
      {message ? <p className="settings-hint" style={{ marginTop: 8 }}>{message}</p> : null}
    </div>
  );
}

function Toggle({ on, onClick }: { on: boolean; onClick: () => void }) {
  return (
    <button
      type="button"
      className={`settings-toggle ${on ? 'on' : ''}`}
      onClick={onClick}
      aria-pressed={on}
    >
      <span />
    </button>
  );
}
