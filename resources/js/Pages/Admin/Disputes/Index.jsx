import { router } from '@inertiajs/react';
import { useState } from 'react';

import AdminLayout from '@/Layouts/AdminLayout';

function StatusBadge({ status }) {
  const colors = {
    open: 'bg-status-serious/10 text-status-serious',
    under_review: 'bg-status-warning/10 text-status-warning',
    resolved_refund: 'bg-status-good/10 text-status-good',
    resolved_release: 'bg-status-good/10 text-status-good',
    closed: 'bg-black/5 text-ink-muted dark:bg-white/10',
  };

  return (
    <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium capitalize ${colors[status]}`}>
      {status.replace('_', ' ')}
    </span>
  );
}

export default function DisputesIndex({ disputes }) {
  const [resolvingId, setResolvingId] = useState(null);
  const [note, setNote] = useState('');

  const resolve = (id, status) => {
    router.post(
      `/admin/disputes/${id}/resolve`,
      { status, resolution_note: note },
      {
        preserveScroll: true,
        onSuccess: () => {
          setResolvingId(null);
          setNote('');
        },
      },
    );
  };

  return (
    <AdminLayout>
      <h1 className="mb-6 text-2xl font-bold text-ink-primary dark:text-white">Disputes</h1>

      <div className="overflow-hidden rounded-2xl border border-black/5 bg-surface dark:border-white/10 dark:bg-[#1a1a19]">
        <table className="w-full text-sm">
          <thead className="bg-page text-left text-ink-muted dark:bg-white/5">
            <tr>
              <th className="px-4 py-3 font-medium">Contract</th>
              <th className="px-4 py-3 font-medium">Raised by</th>
              <th className="px-4 py-3 font-medium">Reason</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-black/5 dark:divide-white/10">
            {disputes.map((dispute) => (
              <tr key={dispute.id}>
                <td className="px-4 py-3 font-medium text-ink-primary dark:text-white">{dispute.contract.title}</td>
                <td className="px-4 py-3 text-ink-secondary dark:text-[#c3c2b7]">
                  {dispute.raised_by.first_name} {dispute.raised_by.last_name}
                </td>
                <td className="max-w-xs truncate px-4 py-3 text-ink-secondary dark:text-[#c3c2b7]">{dispute.reason}</td>
                <td className="px-4 py-3">
                  <StatusBadge status={dispute.status} />
                </td>
                <td className="px-4 py-3">
                  {['open', 'under_review'].includes(dispute.status) ? (
                    resolvingId === dispute.id ? (
                      <div className="flex flex-col gap-2">
                        <input
                          type="text"
                          value={note}
                          onChange={(e) => setNote(e.target.value)}
                          placeholder="Resolution note"
                          className="rounded-lg border border-black/10 bg-surface px-2 py-1 text-xs text-ink-primary outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-200 dark:border-white/10 dark:bg-[#0d0d0d] dark:text-white dark:focus:ring-brand-900"
                        />
                        <div className="flex gap-2">
                          <button
                            onClick={() => resolve(dispute.id, 'resolved_refund')}
                            className="rounded-lg bg-status-warning px-2 py-1 text-xs font-medium text-white transition hover:opacity-90"
                          >
                            Refund payer
                          </button>
                          <button
                            onClick={() => resolve(dispute.id, 'resolved_release')}
                            className="rounded-lg bg-status-good px-2 py-1 text-xs font-medium text-white transition hover:opacity-90"
                          >
                            Release to vendor
                          </button>
                          <button onClick={() => setResolvingId(null)} className="text-xs text-ink-muted hover:text-ink-primary dark:hover:text-white">
                            Cancel
                          </button>
                        </div>
                      </div>
                    ) : (
                      <button
                        onClick={() => setResolvingId(dispute.id)}
                        className="rounded-lg bg-brand-500 px-2 py-1 text-xs font-medium text-white transition hover:bg-brand-600"
                      >
                        Resolve
                      </button>
                    )
                  ) : (
                    <span className="text-ink-muted">—</span>
                  )}
                </td>
              </tr>
            ))}
            {disputes.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-ink-muted">
                  No disputes raised yet.
                </td>
              </tr>
            ) : null}
          </tbody>
        </table>
      </div>

      <p className="mt-4 text-xs text-ink-muted">
        Resolving moves the locked escrow immediately — refund returns it to the payer's
        available balance, release credits the vendor's available balance.
      </p>
    </AdminLayout>
  );
}
