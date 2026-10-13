import { useForm } from '@inertiajs/react';

function MailIcon() {
  return (
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className="h-4 w-4">
      <path d="M4 5h16a1 1 0 011 1v12a1 1 0 01-1 1H4a1 1 0 01-1-1V6a1 1 0 011-1z" />
      <path d="M3 6l9 7 9-7" />
    </svg>
  );
}

function LockIcon() {
  return (
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className="h-4 w-4">
      <rect x="5" y="11" width="14" height="9" rx="1.5" />
      <path d="M8 11V8a4 4 0 018 0v3" />
    </svg>
  );
}

export default function Login() {
  const { data, setData, post, processing, errors } = useForm({
    email: '',
    password: '',
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    post('/login');
  };

  return (
    <div className="min-h-screen bg-page dark:bg-[#0d0d0d] lg:flex">
      {/* Brand panel */}
      <div className="relative hidden overflow-hidden bg-linear-to-br from-brand-600 via-brand-500 to-brand-700 px-12 py-16 text-white lg:flex lg:w-1/2 lg:flex-col lg:justify-between">
        <div className="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/10 blur-3xl" />
        <div className="pointer-events-none absolute -bottom-32 -left-16 h-80 w-80 rounded-full bg-black/10 blur-3xl" />

        <a href="/" className="relative flex items-center gap-2">
          <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-white/15 text-sm font-bold">N</span>
          <span className="text-lg font-bold tracking-tight">NESO</span>
        </a>

        <div className="relative max-w-md">
          <h1 className="text-3xl font-bold leading-tight text-balance">
            Secure escrow, digital contracts, and wallet management — in one dashboard.
          </h1>
          <p className="mt-4 text-sm text-white/80">
            Verify KYC, monitor wallets and contracts, resolve disputes, and configure platform
            charges as an administrator.
          </p>
        </div>

        <p className="relative text-xs text-white/60">&copy; {new Date().getFullYear()} NESO. All rights reserved.</p>
      </div>

      {/* Form panel */}
      <div className="flex min-h-screen flex-1 items-center justify-center px-6 py-16">
        <form onSubmit={handleSubmit} className="w-full max-w-sm">
          <div className="mb-8 lg:hidden">
            <a href="/" className="inline-flex items-center gap-2">
              <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-500 text-sm font-bold text-white">N</span>
              <span className="text-lg font-bold tracking-tight text-ink-primary dark:text-white">NESO</span>
            </a>
          </div>

          <h2 className="text-2xl font-bold text-ink-primary dark:text-white">Admin sign in</h2>
          <p className="mt-1 text-sm text-ink-secondary dark:text-[#c3c2b7]">
            Enter your credentials to manage the platform.
          </p>

          {errors.email ? (
            <div className="mt-6 rounded-lg border border-status-critical/30 bg-status-critical/10 px-4 py-3 text-sm text-status-critical">
              {errors.email}
            </div>
          ) : null}

          <div className="mt-6">
            <label className="mb-1.5 block text-sm font-medium text-ink-primary dark:text-white">Email</label>
            <div className="relative">
              <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink-muted">
                <MailIcon />
              </span>
              <input
                type="email"
                value={data.email}
                onChange={(e) => setData('email', e.target.value)}
                autoComplete="username"
                placeholder="you@neso.test"
                className="w-full rounded-lg border border-black/10 bg-surface py-2.5 pl-10 pr-3 text-sm text-ink-primary shadow-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-200 dark:border-white/10 dark:bg-[#1a1a19] dark:text-white dark:focus:ring-brand-900"
              />
            </div>
          </div>

          <div className="mt-4">
            <label className="mb-1.5 block text-sm font-medium text-ink-primary dark:text-white">Password</label>
            <div className="relative">
              <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink-muted">
                <LockIcon />
              </span>
              <input
                type="password"
                value={data.password}
                onChange={(e) => setData('password', e.target.value)}
                autoComplete="current-password"
                placeholder="••••••••"
                className="w-full rounded-lg border border-black/10 bg-surface py-2.5 pl-10 pr-3 text-sm text-ink-primary shadow-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-200 dark:border-white/10 dark:bg-[#1a1a19] dark:text-white dark:focus:ring-brand-900"
              />
            </div>
          </div>

          <button
            type="submit"
            disabled={processing}
            className="mt-8 w-full rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600 disabled:opacity-60"
          >
            {processing ? 'Signing in…' : 'Sign in'}
          </button>

          <p className="mt-6 text-center text-xs text-ink-muted">
            Access is limited to NESO administrators and compliance staff.
          </p>
        </form>
      </div>
    </div>
  );
}
