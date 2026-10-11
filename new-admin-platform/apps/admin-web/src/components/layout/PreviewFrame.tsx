import { useEffect } from 'react';
import { previewSrc } from '../../lib/preview';

export function PreviewFrame({
  url,
  params,
  onClose,
}: {
  url: string;
  params?: Record<string, unknown>;
  onClose: () => void;
}) {
  const src = previewSrc(url, params);

  useEffect(() => {
    const onKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') onClose();
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [onClose]);

  return (
    <div className="preview-overlay" role="dialog" aria-label="Page preview">
      <button type="button" className="preview-backdrop" aria-label="Close preview" onClick={onClose} />
      <div className="preview-stage">
        <button type="button" className="preview-close" onClick={onClose} aria-label="Close preview">
          ×
        </button>
        <iframe title="Visitor preview" src={src} />
      </div>
    </div>
  );
}
