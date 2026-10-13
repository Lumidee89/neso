import { usePage } from '@inertiajs/react';

import AdminLayout from '@/Layouts/AdminLayout';

const TONES = {
  neutral: {
    icon: 'text-brand-600 bg-brand-50 dark:bg-brand-900/40 dark:text-brand-300',
  },
  good: {
    icon: 'text-status-good bg-status-good/10',
  },
  warning: {
    icon: 'text-status-warning bg-status-warning/10',
  },
  serious: {
    icon: 'text-status-serious bg-status-serious/10',
  },
  critical: {
    icon: 'text-status-critical bg-status-critical/10',
  },
};

function Icon({ name, className }) {
  const paths = {
    users: '<path d="M16 11a4 4 0 10-4-4M8 21v-2a4 4 0 014-4h1M16 21v-2a4 4 0 00-3-3.87M12 13a4 4 0 100-8 4 4 0 000 8z" />',
    clock: '<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 3" />',
    check: '<circle cx="12" cy="12" r="9" /><path d="M8.5 12.5l2.5 2.5 5-5" />',
    ban: '<circle cx="12" cy="12" r="9" /><path d="M6.5 6.5l11 11" />',
    alert: '<path d="M12 3l9 16H3l9-16z" /><path d="M12 10v4M12 17.5h.01" />',
    file: '<path d="M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z" /><path d="M14 3v5h5M9 13h6M9 17h6" />',
  };

  return (
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className={className} dangerouslySetInnerHTML={{ __html: paths[name] }} />
  );
}

function StatTile({ label, value, tone = 'neutral', icon }) {
  const toneClasses = TONES[tone].icon;

  return (
    <div className="rounded-2xl border border-black/5 bg-surface p-5 transition hover:shadow-md dark:border-white/10 dark:bg-[#1a1a19]">
      <div className="flex items-center gap-3">
        <span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ${toneClasses}`}>
          <Icon name={icon} className="h-4.5 w-4.5" />
        </span>
        <span className="text-sm text-ink-secondary dark:text-[#c3c2b7]">{label}</span>
      </div>
      <div className="mt-3 text-3xl font-bold text-ink-primary dark:text-white">{value}</div>
    </div>
  );
}

function BalanceTile({ label, value }) {
  return (
    <div className="rounded-2xl border border-black/5 bg-linear-to-br from-brand-500 to-brand-700 p-6 text-white shadow-sm">
      <div className="text-sm font-medium text-white/80">{label}</div>
      <div className="mt-2 text-3xl font-bold">
        ₦{value.toLocaleString()}
      </div>
    </div>
  );
}

export default function Dashboard({ stats }) {
  const { auth } = usePage().props;

  return (
    <AdminLayout>
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-ink-primary dark:text-white">
          Welcome back{auth?.user?.first_name ? `, ${auth.user.first_name}` : ''}
        </h1>
        <p className="mt-1 text-sm text-ink-secondary dark:text-[#c3c2b7]">
          Here's what's happening across the platform right now.
        </p>
      </div>

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatTile label="Total users" value={stats.users_total} tone="neutral" icon="users" />
        <StatTile label="Pending verification" value={stats.users_pending_verification} tone="warning" icon="clock" />
        <StatTile label="Verified users" value={stats.users_verified} tone="good" icon="check" />
        <StatTile label="Suspended users" value={stats.users_suspended} tone="critical" icon="ban" />
        <StatTile label="Pending KYC" value={stats.kyc_pending} tone="warning" icon="clock" />
        <StatTile label="Open disputes" value={stats.disputes_open} tone="serious" icon="alert" />
        <StatTile label="Total contracts" value={stats.contracts_total} tone="neutral" icon="file" />
      </div>

      <h2 className="mb-3 mt-10 text-lg font-semibold text-ink-primary dark:text-white">
        Wallet balances, platform-wide
      </h2>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <BalanceTile label="Available" value={stats.wallet_available_total} />
        <BalanceTile label="Locked in escrow" value={stats.wallet_locked_total} />
        <BalanceTile label="Pending" value={stats.wallet_pending_total} />
      </div>
    </AdminLayout>
  );
}
