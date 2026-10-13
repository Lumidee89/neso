import { router } from '@inertiajs/react';

import AdminLayout from '@/Layouts/AdminLayout';

function StatusBadge({ status }) {
  const colors = {
    pending: 'bg-status-warning/10 text-status-warning',
    otp_verification: 'bg-status-warning/10 text-status-warning',
    processing: 'bg-status-warning/10 text-status-warning',
    completed: 'bg-status-good/10 text-status-good',
    failed: 'bg-status-critical/10 text-status-critical',
  };

  return (
    <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium capitalize ${colors[status]}`}>
      {status.replace('_', ' ')}
    </span>
  );
}

export default function WithdrawalsIndex({ withdrawals }) {
  const approve = (id) => router.post(`/admin/withdrawals/${id}/approve`, {}, { preserveScroll: true });
  const reject = (id) => router.post(`/admin/withdrawals/${id}/reject`, {}, { preserveScroll: true });

  return (
    <AdminLayout>
      <h1 className="mb-6 text-2xl font-bold text-ink-primary dark:text-white">Withdrawals</h1>

      <div className="overflow-hidden rounded-2xl border border-black/5 bg-surface dark:border-white/10 dark:bg-[#1a1a19]">
        <table className="w-full text-sm">
          <thead className="bg-page text-left text-ink-muted dark:bg-white/5">
            <tr>
              <th className="px-4 py-3 font-medium">User</th>
              <th className="px-4 py-3 font-medium">Amount</th>
              <th className="px-4 py-3 font-medium">Destination</th>
              <th className="px-4 py-3 font-medium">Reference</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-black/5 dark:divide-white/10">
            {withdrawals.map((withdrawal) => (
              <tr key={withdrawal.id}>
                <td className="px-4 py-3">
                  <div className="font-medium text-ink-primary dark:text-white">
                    {withdrawal.user.first_name} {withdrawal.user.last_name}
                  </div>
                  <div className="text-ink-secondary dark:text-[#c3c2b7]">{withdrawal.user.email}</div>
                </td>
                <td className="px-4 py-3 text-ink-primary dark:text-white">
                  ₦{Number(withdrawal.amount).toLocaleString()}
                </td>
                <td className="px-4 py-3 text-ink-secondary dark:text-[#c3c2b7]">
                  <div>{withdrawal.destination_bank}</div>
                  <div>{withdrawal.destination_account_number} · {withdrawal.destination_account_name}</div>
                </td>
                <td className="px-4 py-3 text-ink-secondary dark:text-[#c3c2b7]">{withdrawal.reference}</td>
                <td className="px-4 py-3">
                  <StatusBadge status={withdrawal.status} />
                </td>
                <td className="px-4 py-3">
                  {withdrawal.status === 'pending' ? (
                    <div className="flex gap-2">
                      <button
                        onClick={() => approve(withdrawal.id)}
                        className="rounded-lg bg-status-good px-2 py-1 text-xs font-medium text-white transition hover:opacity-90"
                      >
                        Approve
                      </button>
                      <button
                        onClick={() => reject(withdrawal.id)}
                        className="rounded-lg bg-status-critical px-2 py-1 text-xs font-medium text-white transition hover:opacity-90"
                      >
                        Reject
                      </button>
                    </div>
                  ) : (
                    <span className="text-ink-muted">—</span>
                  )}
                </td>
              </tr>
            ))}
            {withdrawals.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-ink-muted">
                  No withdrawal requests yet.
                </td>
              </tr>
            ) : null}
          </tbody>
        </table>
      </div>

      <p className="mt-4 text-xs text-ink-muted">
        Approving debits the user's available balance immediately. Rejecting leaves their balance untouched.
      </p>
    </AdminLayout>
  );
}
