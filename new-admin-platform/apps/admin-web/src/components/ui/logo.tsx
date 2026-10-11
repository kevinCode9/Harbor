import './logo.css';

export default function Logo({
  size = 'md',
  label = 'Harbor',
}: {
  size?: 'md' | 'lg';
  label?: string;
}) {
  return <span className={`logo ${size === 'lg' ? 'logo-lg' : ''}`} role="img" aria-label={label} />;
}
