import { useEffect, useMemo, useState } from 'react';
import { canSendSessionAction, normalizeSessionPage, type AdminDraft } from '@shared';
import type { PageGroup } from '../../lib/types';

const DESCRIPTIONS: Record<string, string> = {
  '/welcome': 'First page after visiting the panel',
  '/loading': 'Shared loading screen',
  '/waiting': 'Waiting for an agent',
  '/contact': 'Optional contact details',
  '/success': 'Case complete',
  '/offline': 'Shown when the panel is disabled',
  '/demo/family-a/page-1': 'Service B demo page 1',
  '/demo/family-a/page-2': 'Service B demo page 2',
  '/demo/family-a/loading': 'Service B loading',
  '/demo/family-b/page-1': 'Service C demo page 1',
  '/demo/family-b/page-2': 'Service C demo page 2',
  '/demo/family-b/loading': 'Service C loading',
};

export function PageMenu({
  groups,
  adminDraft,
  appliedPage,
  connected,
  onSelect,
  onSend,
  onPreview,
}: {
  groups: PageGroup[];
  adminDraft: AdminDraft;
  appliedPage: string;
  connected: boolean;
  contactEmail?: string;
  onSelect: (url: string, family: string) => void;
  onSend: () => void;
  onPreview: () => void;
}) {
  const selectedPage = adminDraft.selectedPage;
  const [family, setFamily] = useState(adminDraft.selectedFamily || groups[0]?.family || '');
  const [open, setOpen] = useState('');

  useEffect(() => {
    const match = groups.find((group) =>
      group.pages.some((page) => normalizeSessionPage(page.url) === selectedPage),
    );
    if (match) setFamily(match.family);
  }, [groups, selectedPage]);

  const active = useMemo(() => groups.find((group) => group.family === family) ?? groups[0], [groups, family]);
  const pending = selectedPage !== appliedPage;

  return (
    <aside className="pages">
      <div className="tabs">
        {groups.map((group) => (
          <button
            key={group.family}
            type="button"
            className={`tab ${group.family === family ? 'on' : ''}`}
            onClick={() => setFamily(group.family)}
          >
            {group.family}
          </button>
        ))}
      </div>
      {pending && (
        <div className="pages-note">Draft: {selectedPage} — visitor stays until Send</div>
      )}
      {!connected && <div className="pages-note">Idle — Send does nothing until the tab is viewed.</div>}
      {(active?.pages || []).map((page) => {
        const url = normalizeSessionPage(page.url);
        const expanded = open === page.id;
        const selected = selectedPage === url;
        return (
          <div key={page.id} className={`page-card ${expanded ? 'selected' : ''}`}>
            <button
              type="button"
              className="page-hit"
              onClick={() => {
                setOpen(expanded ? '' : page.id);
                onSelect(url, active?.family || family);
              }}
            >
              {page.name}
              <small>{DESCRIPTIONS[url] || url}</small>
            </button>
            {expanded && (
              <div className="settings-actions">
                <button
                  type="button"
                  className={selected ? 'settings-pick on' : 'settings-pick'}
                  disabled={!connected}
                  onClick={onSend}
                >
                  Send
                </button>
                <button type="button" className="settings-preview" onClick={onPreview}>
                  Preview
                </button>
              </div>
            )}
          </div>
        );
      })}
    </aside>
  );
}
export { canSendSessionAction };
