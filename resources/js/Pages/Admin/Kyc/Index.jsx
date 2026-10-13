import { router } from '@inertiajs/react';
import { useState } from 'react';

import AdminLayout from '@/Layouts/AdminLayout';

function StatusBadge({ status }) {
  const colors = {
    pending: 'bg-status-warning/10 text-status-warning',
    approved: 'bg-status-good/10 text-status-good',
    rejected: 'bg-status-critical/10 text-status-critical',
  };

  return (
    <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium capitalize ${colors[status]}`}>
      {status}
    </span>
  );
}

export default function KycIndex({ documents }) {
  const [rejectingId, setRejectingId] = useState(null);
  const [reason, setReason] = useState('');

  const approve = (id) => {
    router.post(`/admin/kyc/${id}/approve`, {}, { preserveScroll: true });
  };

  const submitReject = (id) => {
    router.post(
      `/admin/kyc/${id}/reject`,
      { rejection_reason: reason },
      {
        preserveScroll: true,
        onSuccess: () => {
          setRejectingId(null);
          setReason('');
        },
      },
    );
  };

  return (
    <AdminLayout>
      <h1 className="mb-6 text-2xl font-bold text-ink-primary dark:text-white">KYC Queue</h1>

      <div className="overflow-hidden rounded-2xl border border-black/5 bg-surface dark:border-white/10 dark:bg-[#1a1a19]">
        <table className="w-full text-sm">
          <thead className="bg-page text-left text-ink-muted dark:bg-white/5">
            <tr>
              <th className="px-4 py-3 font-medium">User</th>
              <th className="px-4 py-3 font-medium">Type</th>
              <th className="px-4 py-3 font-medium">Tier</th>
              <th className="px-4 py-3 font-medium">Document</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-black/5 dark:divide-white/10">
            {documents.map((doc) => (
              <tr key={doc.id}>
                <td className="px-4 py-3">
                  <div className="font-medium text-ink-primary dark:text-white">
                    {doc.user.first_name} {doc.user.last_name}
                  </div>
                  <div className="text-ink-secondary dark:text-[#c3c2b7]">{doc.user.email}</div>
                </td>
                <td className="px-4 py-3 text-ink-secondary dark:text-[#c3c2b7]">{doc.document_type}</td>
                <td className="px-4 py-3 text-ink-secondary dark:text-[#c3c2b7]">{doc.tier}</td>
                <td className="px-4 py-3">
                  {doc.file ? (
                    <a
                      href={`/admin/kyc/${doc.id}/document`}
                      target="_blank"
                      rel="noreferrer"
                      className="font-medium text-brand-600 hover:text-brand-700 dark:text-brand-300"
                    >
                      View
                    </a>
                  ) : (
                    <span className="text-ink-muted">—</span>
                  )}
                </td>
                <td className="px-4 py-3">
                  <StatusBadge status={doc.status} />
                </td>
                <td className="px-4 py-3">
                  {doc.status === 'pending' ? (
                    rejectingId === doc.id ? (
                      <div className="flex items-center gap-2">
                        <input
                          type="text"
                          value={reason}
                          onChange={(e) => setReason(e.target.value)}
                          placeholder="Reason"
                          className="rounded-lg border border-black/10 bg-surface px-2 py-1 text-xs text-ink-primary outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-200 dark:border-white/10 dark:bg-[#0d0d0d] dark:text-white dark:focus:ring-brand-900"
                        />
                        <button
                          onClick={() => submitReject(doc.id)}
                          className="rounded-lg bg-status-critical px-2 py-1 text-xs font-medium text-white transition hover:opacity-90"
                        >
                          Confirm
                        </button>
                        <button
                          onClick={() => setRejectingId(null)}
                          className="text-xs text-ink-muted hover:text-ink-primary dark:hover:text-white"
                        >
                          Cancel
                        </button>
                      </div>
                    ) : (
                      <div className="flex gap-2">
                        <button
                          onClick={() => approve(doc.id)}
                          className="rounded-lg bg-status-good px-2 py-1 text-xs font-medium text-white transition hover:opacity-90"
                        >
                          Approve
                        </button>
                        <button
                          onClick={() => setRejectingId(doc.id)}
                          className="rounded-lg bg-status-critical/10 px-2 py-1 text-xs font-medium text-status-critical transition hover:bg-status-critical/20"
                        >
                          Reject
                        </button>
                      </div>
                    )
                  ) : (
                    <span className="text-ink-muted">—</span>
                  )}
                </td>
              </tr>
            ))}
            {documents.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-ink-muted">
                  No KYC documents submitted yet.
                </td>
              </tr>
            ) : null}
          </tbody>
        </table>
      </div>
    </AdminLayout>
  );
}
