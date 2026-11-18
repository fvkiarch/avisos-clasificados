const currency = new Intl.NumberFormat('es-UY', { maximumFractionDigits: 2 });
const relative = new Intl.RelativeTimeFormat('es', { numeric: 'auto' });

export function formatAmount(amount, category) {
  if (amount == null) {
    return 'A convenir';
  }

  const suffix = category === 'Laboral' ? ' / mes' : '';

  return `$ ${currency.format(amount)}${suffix}`;
}

export function relativeTime(iso, now = Date.now()) {
  const seconds = Math.round((new Date(iso).getTime() - now) / 1000);
  const steps = [
    ['year', 31536000],
    ['month', 2592000],
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
  ];

  for (const [unit, size] of steps) {
    if (Math.abs(seconds) >= size) {
      return relative.format(Math.round(seconds / size), unit);
    }
  }

  return 'recién';
}

export function initials(name = '') {
  const letters = name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((word) => word[0]?.toUpperCase() ?? '')
    .join('');

  return letters || '?';
}

export function debounce(fn, ms) {
  let timer;

  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), ms);
  };
}
