import { Link, Navigate, Route, Routes } from "react-router-dom";
import { PortalPage } from "./pages/PortalPage";
import { AgentPage } from "./pages/AgentPage";
import { SettingsFirstPage } from "./pages/SettingsFirstPage";

export function App() {
  return (
    <Routes>
      <Route path="/" element={<SettingsFirstPage />} />
      <Route path="/portal" element={<PortalPage />} />
      <Route path="/agent" element={<AgentPage />} />
      <Route
        path="*"
        element={
          <div className="shell">
            <p>Page not found.</p>
            <Link to="/">Back to settings</Link>
            <Navigate to="/" replace />
          </div>
        }
      />
    </Routes>
  );
}
