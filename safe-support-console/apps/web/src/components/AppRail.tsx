import { NavLink } from "react-router-dom";

export function AppRail() {
  return (
    <nav className="firstpage-rail">
      <NavLink to="/agent" className="firstpage-rail-btn">
        Panel
      </NavLink>
      <NavLink to="/" end className="firstpage-rail-btn">
        Settings
      </NavLink>
    </nav>
  );
}
