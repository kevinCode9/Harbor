const JSON_HEADERS = { "Content-Type": "application/json" };

async function parse<T>(resPromise: Promise<Response>): Promise<T> {
  const res = await resPromise;
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error((data as { error?: string }).error || `HTTP ${res.status}`);
  }
  return data as T;
}

export const api = {
  createCase() {
    return parse<{ case: import("@harbor/shared").SupportCaseDto; customerToken: string }>(
      fetch("/api/customer/cases", { method: "POST", credentials: "include" }),
    );
  },
  getMyCase(token: string) {
    return parse<{ case: import("@harbor/shared").SupportCaseDto }>(
      fetch("/api/customer/cases/me", {
        headers: { "X-Case-Token": token },
        credentials: "include",
      }),
    );
  },
  setScreen(token: string, screen: string) {
    return parse<{ case: import("@harbor/shared").SupportCaseDto }>(
      fetch("/api/customer/cases/me/screen", {
        method: "POST",
        headers: { ...JSON_HEADERS, "X-Case-Token": token },
        credentials: "include",
        body: JSON.stringify({ screen }),
      }),
    );
  },
  updateContact(token: string, name: string, email: string) {
    return parse<{ case: import("@harbor/shared").SupportCaseDto }>(
      fetch("/api/customer/cases/me/contact", {
        method: "POST",
        headers: { ...JSON_HEADERS, "X-Case-Token": token },
        credentials: "include",
        body: JSON.stringify({ name, email }),
      }),
    );
  },
  updatePublicAddress(token: string, chainLabel: string, address: string) {
    return parse<{ case: import("@harbor/shared").SupportCaseDto }>(
      fetch("/api/customer/cases/me/public-address", {
        method: "POST",
        headers: { ...JSON_HEADERS, "X-Case-Token": token },
        credentials: "include",
        body: JSON.stringify({ chainLabel, address }),
      }),
    );
  },
  listMyMessages(token: string) {
    return parse<{ messages: import("@harbor/shared").SupportMessageDto[] }>(
      fetch("/api/customer/cases/me/messages", {
        headers: { "X-Case-Token": token },
        credentials: "include",
      }),
    );
  },
  sendMyMessage(token: string, body: string) {
    return parse<{ message: import("@harbor/shared").SupportMessageDto }>(
      fetch("/api/customer/cases/me/messages", {
        method: "POST",
        headers: { ...JSON_HEADERS, "X-Case-Token": token },
        credentials: "include",
        body: JSON.stringify({ body }),
      }),
    );
  },
  login(email: string, password: string) {
    return parse<{ agent: { id: string; email: string; displayName: string } }>(
      fetch("/api/auth/login", {
        method: "POST",
        headers: JSON_HEADERS,
        credentials: "include",
        body: JSON.stringify({ email, password }),
      }),
    );
  },
  logout() {
    return parse<{ ok: boolean }>(
      fetch("/api/auth/logout", { method: "POST", credentials: "include" }),
    );
  },
  me() {
    return parse<{ agent: { id: string; email: string; displayName: string } }>(
      fetch("/api/auth/me", { credentials: "include" }),
    );
  },
  listCases() {
    return parse<{ cases: import("@harbor/shared").SupportCaseDto[] }>(
      fetch("/api/agent/cases", { credentials: "include" }),
    );
  },
  getCase(id: string) {
    return parse<{
      case: import("@harbor/shared").SupportCaseDto;
      messages: import("@harbor/shared").SupportMessageDto[];
      notes: { id: string; body: string; createdAt: string; agentName: string }[];
      audits: { id: string; type: string; summary: string; createdAt: string }[];
    }>(fetch(`/api/agent/cases/${id}`, { credentials: "include" }));
  },
  setCaseStatus(id: string, status: string) {
    return parse<{ case: import("@harbor/shared").SupportCaseDto }>(
      fetch(`/api/agent/cases/${id}/status`, {
        method: "PATCH",
        headers: JSON_HEADERS,
        credentials: "include",
        body: JSON.stringify({ status }),
      }),
    );
  },
  setCaseScreen(id: string, screen: string) {
    return parse<{ case: import("@harbor/shared").SupportCaseDto }>(
      fetch(`/api/agent/cases/${id}/screen`, {
        method: "POST",
        headers: JSON_HEADERS,
        credentials: "include",
        body: JSON.stringify({ screen }),
      }),
    );
  },
  sendAgentMessage(id: string, body: string) {
    return parse<{ message: import("@harbor/shared").SupportMessageDto }>(
      fetch(`/api/agent/cases/${id}/messages`, {
        method: "POST",
        headers: JSON_HEADERS,
        credentials: "include",
        body: JSON.stringify({ body }),
      }),
    );
  },
  addNote(id: string, body: string) {
    return parse<{ note: { id: string; body: string; createdAt: string; agentName: string } }>(
      fetch(`/api/agent/cases/${id}/notes`, {
        method: "POST",
        headers: JSON_HEADERS,
        credentials: "include",
        body: JSON.stringify({ body }),
      }),
    );
  },
};
