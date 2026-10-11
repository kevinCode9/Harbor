import { useMemo, useState } from "react";
import { AppRail } from "../components/AppRail";

const SETTINGS_KEY = "harbor_panel_settings";

type PanelSettings = {
  enabled: boolean;
  sound: boolean;
  startingPage: string;
};

const FAMILIES = [
  {
    family: "Example",
    pages: [
      { id: "welcome", name: "Welcome", hint: "First page after visiting the panel" },
      { id: "help", name: "Help landing", hint: "Help center landing page" },
      { id: "waiting", name: "Waiting", hint: "Waiting for an agent" },
      { id: "contact", name: "Contact", hint: "Optional name and email" },
      { id: "complete", name: "Complete", hint: "Case complete" },
    ],
  },
  {
    family: "Mail",
    pages: [
      { id: "contact-mail", name: "Contact", hint: "Optional contact details" },
      { id: "status-mail", name: "Support status", hint: "Case status for the visitor" },
      { id: "waiting-mail", name: "Waiting", hint: "Waiting for an agent" },
    ],
  },
  {
    family: "Service",
    pages: [
      { id: "case-id", name: "Case ID", hint: "Show the support case number" },
      { id: "status-svc", name: "Support status", hint: "Open / waiting / resolved" },
      { id: "complete-svc", name: "Complete", hint: "Case complete" },
    ],
  },
] as const;

function loadSettings(): PanelSettings {
  try {
    const raw = localStorage.getItem(SETTINGS_KEY);
    if (raw) return { enabled: true, sound: false, startingPage: "welcome", ...JSON.parse(raw) };
  } catch {
    /* keep defaults */
  }
  return { enabled: true, sound: false, startingPage: "welcome" };
}

export function SettingsFirstPage() {
  const [settings, setSettings] = useState<PanelSettings>(loadSettings);
  const matchedFamily =
    FAMILIES.find((g) => g.pages.some((p) => p.id === settings.startingPage))?.family ||
    FAMILIES[0].family;
  const [family, setFamily] = useState<string>(matchedFamily);
  const [openId, setOpenId] = useState("");

  const active = useMemo(
    () => FAMILIES.find((g) => g.family === family) ?? FAMILIES[0],
    [family],
  );

  const commit = (next: PanelSettings) => {
    setSettings(next);
    localStorage.setItem(SETTINGS_KEY, JSON.stringify(next));
  };

  return (
    <div className="firstpage-shell">
      <AppRail />
      <section className="settings-firstpage">
        <div className="settings-card">
          <div className="settings-row">
            <div>
              <div className="settings-title">Enable panel</div>
              <div className="settings-hint">Panel will redirect when disabled</div>
            </div>
            <Toggle
              on={settings.enabled}
              onClick={() => commit({ ...settings, enabled: !settings.enabled })}
            />
          </div>
        </div>

        <div className="settings-card">
          <div className="settings-row">
            <div>
              <div className="settings-title">Notification sounds</div>
              <div className="settings-hint">Sounds will play when notifications are received</div>
            </div>
            <Toggle on={settings.sound} onClick={() => commit({ ...settings, sound: !settings.sound })} />
          </div>
        </div>

        <div className="settings-card settings-card-pages">
          <div className="settings-row settings-initial-head">
            <div>
              <div className="settings-title">Initial page</div>
              <div className="settings-hint">First page after visiting the panel</div>
            </div>
            <div className="settings-family-tabs">
              {FAMILIES.map((group) => (
                <button
                  key={group.family}
                  type="button"
                  className={`settings-family ${group.family === family ? "on" : ""}`}
                  onClick={() => setFamily(group.family)}
                >
                  {group.family}
                </button>
              ))}
            </div>
          </div>

          {active.pages.map((page) => {
            const selected = settings.startingPage === page.id;
            const expanded = openId === page.id;
            return (
              <div key={page.id} className={`settings-page ${selected ? "on" : ""}`}>
                <button
                  type="button"
                  className="settings-page-hit"
                  onClick={() => {
                    setOpenId(expanded ? "" : page.id);
                    commit({ ...settings, startingPage: page.id });
                  }}
                >
                  <span>
                    <span className="settings-title">{page.name}</span>
                    <span className="settings-hint">{page.hint}</span>
                  </span>
                  <span className={`settings-chevron ${expanded ? "open" : ""}`}>▾</span>
                </button>
              </div>
            );
          })}
        </div>
      </section>
    </div>
  );
}

function Toggle({ on, onClick }: { on: boolean; onClick: () => void }) {
  return (
    <button type="button" className={`settings-toggle ${on ? "on" : ""}`} onClick={onClick} aria-pressed={on}>
      <span />
    </button>
  );
}
