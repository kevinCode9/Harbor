import type { Session } from '../../lib/types';
import { SupportConsole } from './SupportConsole';

export function SessionWorkspace({
  session,
  onSend,
  onPreview,
  onDemoFields,
}: {
  session: Session;
  onSend: (url: string, params?: Record<string, unknown>) => void;
  onPreview: (url: string, params?: Record<string, unknown>) => void;
  onDemoFields?: (fields: { location: string; time: string; name: string }) => void;
}) {
  return (
    <SupportConsole session={session} onSend={onSend} onPreview={onPreview} onDemoFields={onDemoFields} />
  );
}
