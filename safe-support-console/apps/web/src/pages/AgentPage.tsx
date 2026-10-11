import { useEffect, useState, type FormEvent } from "react";
import { io, Socket } from "socket.io-client";
import { SAFE_SCREEN_LABELS, type SupportCaseDto, type SupportMessageDto } from "@harbor/shared";
import { api } from "../api";
import { AppRail } from "../components/AppRail";

type Note = { id: string; body: string; createdAt: string; agentName: string };
type Audit = { id: string; type: string; summary: string; createdAt: string };

const SERVICE_OPTIONS = [
  "Service A", "Service B", "Service C", "Service D", "Service E", "Service F",
  "Service G", "Service H", "Service I", "Service J", "Service K", "Service L",
  "Service M", "Service N", "Service O", "Service P", "Service Q",
];

const PAGE_GROUPS = {
  Example: [
    { id: "WELCOME", name: "Welcome", hint: "First visitor screen" },
    { id: "CASE_ID", name: "Support case", hint: "Support case number" },
    { id: "CONTACT", name: "Contact", hint: "Optional name and email" },
  ],
  Mail: [
    { id: "CONTACT", name: "Contact", hint: "Optional name and email" },
    { id: "STATUS", name: "Support status", hint: "Case status for the visitor" },
    { id: "WAITING", name: "Waiting", hint: "Waiting for an agent" },
  ],
  Service: [
    { id: "STATUS", name: "Support status", hint: "Open / waiting / resolved" },
    { id: "WAITING", name: "Waiting", hint: "Waiting for an agent" },
    { id: "SUCCESS", name: "Complete", hint: "Case complete" },
  ],
} as const;

export function AgentPage() {
  const [agent, setAgent] = useState<{ id: string; email: string; displayName: string } | null>(
    null,
  );
  const [email, setEmail] = useState("agent@harbor.local");
  const [password, setPassword] = useState("");
  const [cases, setCases] = useState<SupportCaseDto[]>([]);
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [detail, setDetail] = useState<SupportCaseDto | null>(null);
  const [messages, setMessages] = useState<SupportMessageDto[]>([]);
  const [notes, setNotes] = useState<Note[]>([]);
  const [audits, setAudits] = useState<Audit[]>([]);
  const [messageBody, setMessageBody] = useState("");
  const [noteBody, setNoteBody] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  const [screenFamily, setScreenFamily] = useState<"Example" | "Mail" | "Service">("Example");
  const [pickedScreen, setPickedScreen] = useState<string>("WELCOME");
  const [previewNote, setPreviewNote] = useState("");

  useEffect(() => {
    api
      .me()
      .then((r) => setAgent(r.agent))
      .catch(() => setAgent(null));
  }, []);

  useEffect(() => {
    if (!agent) return;
    api
      .listCases()
      .then((r) => setCases(r.cases))
      .catch((e) => setError(e instanceof Error ? e.message : "Failed to load cases"));
  }, [agent]);

  useEffect(() => {
    if (!agent || !selectedId) return;
    let alive = true;
    api
      .getCase(selectedId)
      .then((r) => {
        if (!alive) return;
        setDetail(r.case);
        setMessages(r.messages);
        setNotes(r.notes);
        setAudits(r.audits);
        setPickedScreen(r.case.currentScreen);
      })
      .catch((e) => setError(e instanceof Error ? e.message : "Failed to load case"));
    return () => {
      alive = false;
    };
  }, [agent, selectedId]);

  useEffect(() => {
    if (!agent) return;
    const socket: Socket = io("/", {
      path: "/socket.io",
      withCredentials: true,
      auth: { role: "agent" },
    });
    socket.on("case:created", (c: SupportCaseDto) => {
      setCases((prev) => [c, ...prev.filter((x) => x.id !== c.id)]);
    });
    socket.on("case:updated", (c: SupportCaseDto) => {
      setCases((prev) => {
        const rest = prev.filter((x) => x.id !== c.id);
        return [c, ...rest];
      });
      setDetail((d) => (d && d.id === c.id ? c : d));
    });
    socket.on("message:new", (m: SupportMessageDto) => {
      setMessages((prev) => {
        if (!selectedId || m.caseId !== selectedId) return prev;
        return prev.some((x) => x.id === m.id) ? prev : [...prev, m];
      });
    });
    return () => {
      socket.disconnect();
    };
  }, [agent, selectedId]);

  useEffect(() => {
    if (!agent || !selectedId) return;
    const socket: Socket = io("/", {
      path: "/socket.io",
      withCredentials: true,
      auth: { role: "agent" },
    });
    socket.emit("join_case", selectedId);
    return () => {
      socket.disconnect();
    };
  }, [agent, selectedId]);

  async function onLogin(e: FormEvent) {
    e.preventDefault();
    setBusy(true);
    setError("");
    try {
      const res = await api.login(email, password);
      setAgent(res.agent);
      setPassword("");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Login failed");
    } finally {
      setBusy(false);
    }
  }

  async function onLogout() {
    await api.logout();
    setAgent(null);
    setSelectedId(null);
    setDetail(null);
  }

  async function sendMessage(e: FormEvent) {
    e.preventDefault();
    if (!selectedId || !messageBody.trim()) return;
    setBusy(true);
    setError("");
    try {
      const res = await api.sendAgentMessage(selectedId, messageBody.trim());
      setMessages((prev) => [...prev, res.message]);
      setMessageBody("");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Send failed");
    } finally {
      setBusy(false);
    }
  }

  async function addNote(e: FormEvent) {
    e.preventDefault();
    if (!selectedId || !noteBody.trim()) return;
    setBusy(true);
    setError("");
    try {
      const res = await api.addNote(selectedId, noteBody.trim());
      setNotes((prev) => [res.note, ...prev]);
      setNoteBody("");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Note failed");
    } finally {
      setBusy(false);
    }
  }

  const livePage = detail ? SAFE_SCREEN_LABELS[detail.currentScreen] : "Example page";
  const liveIp = "127.0.0.1 (local)";
  const liveDevice = /Mobi|Android|iPhone/i.test(navigator.userAgent) ? "Mobile browser" : "Desktop browser";
  const liveUa = navigator.userAgent.slice(0, 72);

  return (
    <div className="firstpage-shell">
      <AppRail />
      <div className="panel-main">
        {!agent && (
          <form className="panel-login-bar" onSubmit={onLogin}>
            <span className="muted">Sign in to send screens</span>
            <input value={email} onChange={(e) => setEmail(e.target.value)} type="email" required />
            <input
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              type="password"
              required
              minLength={8}
              placeholder="Password"
            />
            <button disabled={busy} type="submit">
              Continue
            </button>
            {error && <span className="error" style={{ padding: "6px 10px" }}>{error}</span>}
          </form>
        )}

        {agent && (
          <div className="panel-top">
            <span className="pill">{detail ? "● Connected" : "● Idle"}</span>
            <select
              className="case-select"
              value={selectedId || ""}
              onChange={(e) => setSelectedId(e.target.value || null)}
            >
              <option value="">No case</option>
              {cases.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.caseNumber}
                </option>
              ))}
            </select>
            <button className="ghost" type="button" onClick={onLogout}>
              Log out
            </button>
          </div>
        )}

        {error && agent && <div className="error" style={{ marginBottom: "1rem" }}>{error}</div>}
        {previewNote && <div className="notice">{previewNote}</div>}

        <div className="panel-split">
          <section className="panel-live">
            <Field label="Page" value={livePage} />
            <Field label="IP" value={liveIp} />
            <Field label="Device" value={liveDevice} />
            <Field label="User agent" value={liveUa} />
            <Field label="Case ID" value={detail?.caseNumber || ""} />
            <Field label="Contact" value={detail?.contactName || ""} />
            <Field label="Email" value={detail?.contactEmail || ""} />
          </section>

          <aside className="panel-pages">
            <div className="service-box">
              <div className="service-title">Service options</div>
              <div className="service-grid">
                {SERVICE_OPTIONS.map((name) => (
                  <span key={name}>{name}</span>
                ))}
              </div>
            </div>

            <div className="settings-family-tabs" style={{ margin: "16px 0 8px" }}>
              {(["Example", "Mail", "Service"] as const).map((fam) => (
                <button
                  key={fam}
                  type="button"
                  className={`settings-family ${screenFamily === fam ? "on" : ""}`}
                  onClick={() => setScreenFamily(fam)}
                >
                  {fam}
                </button>
              ))}
            </div>

            {PAGE_GROUPS[screenFamily].map((page) => {
              const selected = pickedScreen === page.id;
              return (
                <div key={`${screenFamily}-${page.id}`} className={`page-item ${selected ? "on" : ""}`}>
                  <button
                    type="button"
                    className="hit"
                    onClick={() => {
                      setPickedScreen(page.id);
                      setPreviewNote("");
                    }}
                  >
                    {page.name}
                    <small>{page.hint}</small>
                  </button>
                  {selected && (
                    <div className="page-actions">
                      <button
                        type="button"
                        className="send"
                        disabled={busy || !detail}
                        onClick={async () => {
                          if (!detail) return;
                          const res = await api.setCaseScreen(detail.id, page.id as typeof detail.currentScreen);
                          setDetail(res.case);
                          setPreviewNote("");
                        }}
                      >
                        Send
                      </button>
                      <button
                        type="button"
                        className="preview"
                        onClick={() =>
                          setPreviewNote(`Preview only: ${page.name} (visitor not moved)`)
                        }
                      >
                        Preview
                      </button>
                    </div>
                  )}
                </div>
              );
            })}
          </aside>
        </div>
      </div>
    </div>
  );
}

function Field({ label, value }: { label: string; value: string }) {
  return (
    <div className="field-row">
      <span>{label}</span>
      <input readOnly value={value} />
    </div>
  );
}
