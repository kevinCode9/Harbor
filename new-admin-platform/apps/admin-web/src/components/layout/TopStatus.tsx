export function TopStatus({ count, enabled }: { count: number; enabled: boolean }) {
  const label = `${count} session${count === 1 ? '' : 's'}`;
  return (
    <div className="panel-top">
      <div className="panel-chip">{label}</div>
      <div className={`panel-chip ${enabled ? 'on' : ''}`}>{enabled ? 'Enabled' : 'Disabled'}</div>
    </div>
  );
}
