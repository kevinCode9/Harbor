import { useState } from 'react';

export function LoginPanel({
  adminKey,
  onKeyChange,
  onLogin,
}: {
  adminKey: string;
  onKeyChange: (value: string) => void;
  onLogin: () => void | Promise<boolean>;
}) {
  const [invalid, setInvalid] = useState(false);

  return (
    <div className="login login-sign-in">
      <form
        className="login-card"
        onSubmit={(e) => {
          e.preventDefault();
          void Promise.resolve(onLogin()).then((ok) => {
            if (ok === false) setInvalid(true);
          });
        }}
      >
        <h1>Sign in</h1>
        <label className={`login-key${invalid ? ' invalid' : ''}`}>
          <span className="login-lock" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
              <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
          </span>
          <input
            type="password"
            value={adminKey}
            onChange={(e) => {
              onKeyChange(e.target.value);
              if (!e.target.value.trim()) setInvalid(false);
            }}
            placeholder="Key"
            autoComplete="off"
            aria-label="Key"
          />
        </label>
        <button type="submit" className="login-continue">
          Continue
        </button>
      </form>
    </div>
  );
}
