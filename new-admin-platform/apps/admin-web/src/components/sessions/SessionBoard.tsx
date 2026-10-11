import type { Session } from '../../lib/types';
import { SessionCard } from './SessionCard';

export function SessionBoard({
  sessions,
  onView,
  onClose,
}: {
  sessions: Session[];
  onView: (token: string) => void;
  onClose: (token: string) => void;
}) {
  if (!sessions.length) {
    return <div className="session-board" />;
  }

  return (
    <div className="session-board">
      {sessions.map((session) => (
        <SessionCard key={session.token} session={session} onView={onView} onClose={onClose} />
      ))}
    </div>
  );
}
