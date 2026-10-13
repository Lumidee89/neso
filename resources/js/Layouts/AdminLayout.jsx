import { Link, router, usePage } from '@inertiajs/react';

const NAV_ITEMS = [
  { href: '/admin/dashboard', label: 'Dashboard' },
  { href: '/admin/kyc', label: 'KYC' },
  { href: '/admin/contracts', label: 'Contracts' },
  { href: '/admin/wallets', label: 'Wallets' },
  { href: '/admin/withdrawals', label: 'Withdrawals' },
  { href: '/admin/disputes', label: 'Disputes' },
  { href: '/admin/charges', label: 'Charges' },
  { href: '/admin/service-charges', label: 'Service Charge Rules' },
];

export default function AdminLayout({ children }) {
  const { auth, flash } = usePage().props;
  const currentUrl = usePage().url;

  const handleLogout = (e) => {
    e.preventDefault();
    router.post('/logout');
  };

  const initials = auth?.user
    ? `${auth.user.first_name?.[0] ?? ''}${auth.user.last_name?.[0] ?? ''}`.toUpperCase()
    : '';

  return (
    <div className="flex min-h-screen bg-page dark:bg-[#0d0d0d]">
      <aside className="flex w-64 shrink-0 flex-col bg-ink-primary text-white">
        <a href="/" className="flex items-center gap-2 border-b border-white/10 px-6 py-5">
          <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-500 text-sm font-bold">N</span>
          <span className="text-lg font-bold tracking-tight">NESO Admin</span>
        </a>

        <nav className="flex-1 space-y-1 px-3 py-4">
          {NAV_ITEMS.map((item) => {
            const active = currentUrl?.startsWith(item.href);
            return (
              <Link
                key={item.href}
                href={item.href}
                className={`block rounded-lg px-3 py-2 text-sm font-medium transition ${
                  active
                    ? 'bg-brand-500 text-white'
                    : 'text-white/70 hover:bg-white/10 hover:text-white'
                }`}
              >
                {item.label}
              </Link>
            );
          })}
        </nav>

        <div className="border-t border-white/10 px-4 py-4">
          <div className="mb-3 flex items-center gap-2">
            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-semibold">
              {initials}
            </span>
            <span className="truncate text-sm text-white/80">{auth?.user?.email}</span>
          </div>
          <button
            onClick={handleLogout}
            className="w-full rounded-lg bg-white/10 px-3 py-2 text-left text-sm font-medium text-white transition hover:bg-white/20"
          >
            Log out
          </button>
        </div>
      </aside>

      <main className="flex-1 p-8">
        {flash?.success ? (
          <div className="mb-6 rounded-lg border border-status-good/30 bg-status-good/10 px-4 py-3 text-sm font-medium text-status-good">
            {flash.success}
          </div>
        ) : null}
        {children}
      </main>
    </div>
  );
}
