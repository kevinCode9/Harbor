import http from "node:http";
import express from "express";
import cors from "cors";
import helmet from "helmet";
import cookieParser from "cookie-parser";
import rateLimit from "express-rate-limit";
import { authRouter } from "./routes/auth.js";
import { customerRouter } from "./routes/customer.js";
import { agentRouter } from "./routes/agent.js";
import { createRealtime } from "./realtime.js";
import "./db/migrate.js";

const port = Number(process.env.PORT || 4000);
const webOrigin = process.env.WEB_ORIGIN || "http://127.0.0.1:5173";

const app = express();
app.set("trust proxy", 1);
app.use(helmet());
app.use(
  cors({
    origin: webOrigin,
    credentials: true,
  }),
);
app.use(express.json({ limit: "32kb" }));
app.use(cookieParser());
app.use(
  rateLimit({
    windowMs: 60_000,
    max: 300,
    standardHeaders: true,
    legacyHeaders: false,
  }),
);

app.get("/health", (_req, res) => {
  res.json({ ok: true, service: "harbor-support-api" });
});

const server = http.createServer(app);
const io = createRealtime(server, webOrigin);

app.use("/api/auth", authRouter);
app.use("/api/customer", customerRouter(io));
app.use("/api/agent", agentRouter(io));

app.use(
  (
    err: unknown,
    _req: express.Request,
    res: express.Response,
    _next: express.NextFunction,
  ) => {
    console.error(err);
    res.status(500).json({ error: "Internal server error" });
  },
);

server.listen(port, "127.0.0.1", () => {
  console.log(`Harbor Support API listening on http://127.0.0.1:${port}`);
  console.log("Safe modes only: no seed/OTP/password collection endpoints.");
});
