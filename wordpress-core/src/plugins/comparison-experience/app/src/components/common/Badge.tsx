interface BadgeProps {
  type?: 'basic' | 'promoted' | string;
  text: string;
  tooltip?: string;
}

export function Badge({ type = 'basic', text, tooltip }: BadgeProps) {
  const baseClasses = 'badge';
  const typeClasses = type === 'promoted' ? 'badge-promoted' : 'badge-basic';

  const badge = (
    <span className={`${baseClasses} ${typeClasses}`}>
      {text}
    </span>
  );

  if (tooltip) {
    return (
      <span className="relative group" title={tooltip}>
        {badge}
      </span>
    );
  }

  return badge;
}
