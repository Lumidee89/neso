import { router } from '@inertiajs/react';

import AdminLayout from '@/Layouts/AdminLayout';

function StatusBadge({ status }) {
  const colors = {
    verified: 'bg-status-good/10 text-status-good',
    suspended: 'bg-status-critical/10 text-status-critical',
    pending_verification: 'bg-status-warning/10 text-status-warning',
  };

  return (
    <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium capitalize ${colors[status]}`}>
      {status.replace('_', ' ')}
    </span>
  );
}

export default function WalletsIndex({ wallets }) {
  const freeze = (walletId) => router.post(`/admin/wallets/${walletId}/freeze`, {}, { preserveScroll: true });
  const unfreeze = (walletId) => router.post(`/admin/wallets/${walletId}/unfreeze`, {}, { preserveScroll: true });

  return (
    <AdminLayout>
      <h1 className="mb-6 text-2xl font-bold text-ink-primary dark:text-white">Wallets</h1>

      <div className="overflow-hidden rounded-2xl border border-black/5 bg-surface dark:border-white/10 dark:bg-[#1a1a19]">
        <table className="w-full text-sm">
          <thead className="bg-page text-left text-ink-muted dark:bg-white/5">
            <tr>
              <th className="px-4 py-3 font-medium">User</th>
              <th className="px-4 py-3 font-medium">Available</th>
              <th className="px-4 py-3 font-medium">Locked</th>
              <th className="px-4 py-3 font-medium">Pending</th>
              <th className="px-4 py-3 font-medium">Account status</th>
              <th className="px-4 py-3 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-black/5 dark:divide-white/10">
            {wallets.map((wallet) => (
              <tr key={wallet.id}>
                <td className="px-4 py-3">
                  <div className="font-medium text-ink-primary dark:text-white">
                    {wallet.user.first_name} {wallet.user.last_name}
                  </div>
                  <div className="text-ink-secondary dark:text-[#c3c2b7]">{wallet.user.email}</div>
                </td>
                <td className="px-4 py-3 text-ink-primary dark:text-white">₦{Number(wallet.available_balance).toLocaleString()}</td>
                <td className="px-4 py-3 text-ink-primary dark:text-white">₦{Number(wallet.locked_balance).toLocaleString()}</td>
                <td className="px-4 py-3 text-ink-primary dark:text-white">₦{Number(wallet.pending_balance).toLocaleString()}</td>
                <td className="px-4 py-3">
                  <StatusBadge status={wallet.user.status} />
                </td>
                <td className="px-4 py-3">
                  {wallet.user.status === 'suspended' ? (
                    <button
                      onClick={() => unfreeze(wallet.id)}
                      className="rounded-lg bg-status-good px-2 py-1 text-xs font-medium text-white transition hover:opacity-90"
                    >
                      Unfreeze
                    </button>
                  ) : (
                    <button
                      onClick={() => freeze(wallet.id)}
                      className="rounded-lg bg-status-critical px-2 py-1 text-xs font-medium text-white transition hover:opacity-90"
                    >
                      Freeze
                    </button>
                  )}
                </td>
              </tr>
            ))}
            {wallets.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-ink-muted">
                  No wallets created yet.
                </td>
              </tr>
            ) : null}
          </tbody>
        </table>
      </div>
    </AdminLayout>
  );
}
