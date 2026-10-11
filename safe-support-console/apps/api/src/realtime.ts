import type { Server as HttpServer } from "node:http";
import { Server } from "socket.io";
import { db } from "./db/client.js";
import { hashToken } from "./lib/crypto.js";

export function createRealtime(httpServer: HttpServer, webOrigin: string): Server {
  const io = new Server(httpServer, {
    cors: {
      origin: webOrigin,
      credentials: true,
    },
  });

  io.use((socket, next) => {
    try {
      const role = socket.handshake.auth?.role as string | undefined;
      if (role === "agent") {
        const cookieHeader = socket.handshake.headers.cookie || "";
        const match = cookieHeader.match(/(?:^|;\s*)harbor_agent=([^;]+)/);
        const token = match?.[1] ? decodeURIComponent(match[1]) : undefined;
        if (!token) return next(new Error("Unauthorized"));
        const session = db
          .prepare("SELECT agent_id, expires_at FROM agent_sessions WHERE token_hash = ?")
          .get(hashToken(token)) as { agent_id: string; expires_at: string } | undefined;
        if (!session || new Date(session.expires_at) < new Date()) {
          return next(new Error("Unauthorized"));
        }
        socket.data.role = "agent";
        socket.data.agentId = session.agent_id;
        return next();
      }
      if (role === "customer") {
        const caseToken = socket.handshake.auth?.caseToken as string | undefined;
        if (!caseToken) return next(new Error("Unauthorized"));
        const supportCase = db
          .prepare("SELECT id FROM support_cases WHERE customer_token_hash = ?")
          .get(hashToken(caseToken)) as { id: string } | undefined;
        if (!supportCase) return next(new Error("Unauthorized"));
        socket.data.role = "customer";
        socket.data.caseId = supportCase.id;
        return next();
      }
      return next(new Error("Unauthorized"));
    } catch (err) {
      return next(err as Error);
    }
  });

  io.on("connection", (socket) => {
    if (socket.data.role === "agent") {
      socket.join("agents");
    }
    if (socket.data.role === "customer" && socket.data.caseId) {
      socket.join(`case:${socket.data.caseId}`);
    }
    socket.on("join_case", (caseId: string) => {
      if (socket.data.role !== "agent") return;
      if (typeof caseId === "string" && caseId.length < 64) {
        socket.join(`case:${caseId}`);
      }
    });
  });

  return io;
}
