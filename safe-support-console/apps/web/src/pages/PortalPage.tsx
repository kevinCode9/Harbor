import { useEffect, useMemo, useState, type FormEvent } from "react";
import { Link } from "react-router-dom";
import { io, Socket } from "socket.io-client";
import type { ClientScreen, SupportCaseDto, SupportMessageDto } from "@harbor/shared";
import { api } from "../api";

const TOKEN_KEY = "harbor_case_token";

export function PortalPage() {
  const [token, setToken] = useState(() => localStorage.getItem(TOKEN_KEY) || "");
  const [supportCase, setSupportCase] = useState<SupportCaseDto | null>(null);
  const [messages, setMessages] = useState<SupportMessageDto[]>([]);
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [chain, setChain] = useState("Bitcoin");
  const [address, setAddress] = useState("");
  const [messageBody, setMessageBody] = useState("");

  useEffect(() => {
    if (!token) return;
    let alive = true;
    (async () => {
      try {
        const [{ case: c }, msgs] = await Promise.all([
          api.getMyCase(token),
          api.listMyMessages(token),
        ]);
        if (!alive) return;
        setSupportCase(c);
        setMessages(msgs.messages);
        setName(c.contactName || "");
        setEmail(c.contactEmail || "");
        setAddress(c.publicAddress || "");
        setChain(c.publicAddressChain || "Bitcoin");
      } catch (e) {
        localStorage.removeItem(TOKEN_KEY);
        if (alive) {
          setToken("");
          setSupportCase(null);
          setError(e instanceof Error ? e.message : "Could not restore case");
        }
      }
    })();
    return () => {
      alive = false;
    };
  }, [token]);

  useEffect(() => {
    if (!token || !supportCase) return;
    const socket: Socket = io("/", {
      path: "/socket.io",
      auth: { role: "customer", caseToken: token },
    });
    socket.on("case:updated", (c: SupportCaseDto) => setSupportCase(c));
    socket.on("message:new", (m: SupportMessageDto) => {
      setMessages((prev) => (prev.some((x) => x.id === m.id) ? prev : [...prev, m]));
    });
    return () => {
      socket.disconnect();
    };
  }, [token, supportCase?.id]);

  const screen = supportCase?.currentScreen || "WELCOME";

  async function startCase() {
    setBusy(true);
    setError("");
    try {
      const res = await api.createCase();
      localStorage.setItem(TOKEN_KEY, res.customerToken);
      setToken(res.customerToken);
      setSupportCase(res.case);
      await api.setScreen(res.customerToken, "CASE_ID");
      const refreshed = await api.getMyCase(res.customerToken);
      setSupportCase(refreshed.case);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Failed to start case");
    } finally {
      setBusy(false);
    }
  }

  async function go(next: ClientScreen) {
    if (!token) return;
    setBusy(true);
    setError("");
    try {
      const res = await api.setScreen(token, next);
      setSupportCase(res.case);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Could not update screen");
    } finally {
      setBusy(false);
    }
  }

  async function saveContact(e: FormEvent) {
    e.preventDefault();
    if (!token) return;
    setBusy(true);
    setError("");
    try {
      const res = await api.updateContact(token, name, email);
      setSupportCase(res.case);
      await go("STATUS");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Contact update failed");
    } finally {
      setBusy(false);
    }
  }

  async function saveAddress(e: FormEvent) {
    e.preventDefault();
    if (!token) return;
    setBusy(true);
    setError("");
    try {
      const res = await api.updatePublicAddress(token, chain, address);
      setSupportCase(res.case);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Address rejected");
    } finally {
      setBusy(false);
    }
  }

  async function sendMessage(e: FormEvent) {
    e.preventDefault();
    if (!token || !messageBody.trim()) return;
    setBusy(true);
    setError("");
    try {
      const res = await api.sendMyMessage(token, messageBody.trim());
      setMessages((prev) => [...prev, res.message]);
      setMessageBody("");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Message rejected");
    } finally {
      setBusy(false);
    }
  }

  const title = useMemo(() => {
    switch (screen) {
      case "CASE_ID":
        return "Your support case";
      case "CONTACT":
        return "Optional contact details";
      case "STATUS":
        return "Case status";
      case "WAITING":
        return "An agent will be with you shortly";
      case "SUCCESS":
        return "You’re all set";
      default:
        return "Welcome to Harbor Support";
    }
  }, [screen]);

  return (
    <div className="shell">
      <div className="brand">
        <div className="brand-mark" aria-hidden />
        <div>
          <strong>Harbor Support</strong>
          <span>Secure customer assistance — no login secrets collected</span>
        </div>
      </div>

      <div className="card stack">
        <div className="row" style={{ justifyContent: "space-between" }}>
          <h1 style={{ margin: 0 }}>{title}</h1>
          {supportCase && <span className="pill">{supportCase.status.replaceAll("_", " ")}</span>}
        </div>

        <div className="notice">
          Harbor Support will never ask for passwords, SMS/authenticator codes, recovery phrases,
          seed phrases, or private keys. If someone asks for those, stop and report it.
        </div>

        {error && <div className="error">{error}</div>}

        {screen === "WELCOME" && (
          <div className="stack">
            <p className="muted">
              Start a support case to receive a case ID and chat with an agent about non-sensitive
              account or product questions.
            </p>
            <div className="row">
              <button disabled={busy} onClick={startCase}>
                Start support case
              </button>
              <Link className="muted" to="/">
                Settings →
              </Link>
              <Link className="muted" to="/agent">
                Agent console →
              </Link>
            </div>
          </div>
        )}

        {screen === "CASE_ID" && supportCase && (
          <div className="stack">
            <p className="muted">Save this case ID. You can return to this browser session later.</p>
            <div className="case-number">{supportCase.caseNumber}</div>
            <div className="row">
              <button disabled={busy} onClick={() => go("CONTACT")}>
                Continue
              </button>
              <button className="secondary" disabled={busy} onClick={() => go("STATUS")}>
                Skip to status
              </button>
            </div>
          </div>
        )}

        {screen === "CONTACT" && (
          <form className="stack" onSubmit={saveContact}>
            <p className="muted">Optional. Only share what you are comfortable providing.</p>
            <label>
              Name
              <input value={name} onChange={(e) => setName(e.target.value)} maxLength={120} />
            </label>
            <label>
              Email
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                maxLength={200}
              />
            </label>
            <div className="row">
              <button disabled={busy} type="submit">
                Save & continue
              </button>
              <button className="secondary" type="button" disabled={busy} onClick={() => go("STATUS")}>
                Skip
              </button>
            </div>
          </form>
        )}

        {(screen === "STATUS" || screen === "WAITING" || screen === "SUCCESS") && supportCase && (
          <div className="stack">
            <p>
              Case <strong>{supportCase.caseNumber}</strong> · screen{" "}
              <strong>{supportCase.currentScreen}</strong>
            </p>
            <p className="muted">
              Created {new Date(supportCase.createdAt).toLocaleString()} · Updated{" "}
              {new Date(supportCase.updatedAt).toLocaleString()}
            </p>

            <form className="stack" onSubmit={saveAddress}>
              <h2 style={{ margin: 0, fontSize: "1.15rem" }}>Public wallet address (optional)</h2>
              <p className="muted">
                Read-only reference only. Harbor cannot transfer assets. Never paste a seed phrase
                or private key.
              </p>
              <label>
                Chain label
                <input value={chain} onChange={(e) => setChain(e.target.value)} maxLength={40} />
              </label>
              <label>
                Public address
                <input value={address} onChange={(e) => setAddress(e.target.value)} maxLength={90} />
              </label>
              <button className="ghost" disabled={busy} type="submit">
                Save public address
              </button>
            </form>

            <div className="stack">
              <h2 style={{ margin: 0, fontSize: "1.15rem" }}>Messages</h2>
              {messages.length === 0 && <p className="muted">No messages yet.</p>}
              {messages.map((m) => (
                <div key={m.id} className={`msg ${m.authorRole === "AGENT" ? "agent" : ""}`}>
                  <strong>{m.authorRole === "AGENT" ? "Agent" : "You"}</strong>
                  <div>{m.body}</div>
                  <div className="muted" style={{ fontSize: "0.8rem" }}>
                    {new Date(m.createdAt).toLocaleString()}
                  </div>
                </div>
              ))}
              <form className="stack" onSubmit={sendMessage}>
                <label>
                  Message to support
                  <textarea
                    value={messageBody}
                    onChange={(e) => setMessageBody(e.target.value)}
                    maxLength={2000}
                    placeholder="Describe your issue. Do not include passwords or recovery phrases."
                  />
                </label>
                <button disabled={busy || !messageBody.trim()} type="submit">
                  Send message
                </button>
              </form>
            </div>

            <div className="row">
              {screen !== "WAITING" && (
                <button disabled={busy} onClick={() => go("WAITING")}>
                  I’m waiting for an agent
                </button>
              )}
              {screen !== "SUCCESS" && (
                <button className="secondary" disabled={busy} onClick={() => go("SUCCESS")}>
                  Mark complete
                </button>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
