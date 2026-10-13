import AdminLayout from '@/Layouts/AdminLayout';

export default function ChargesIndex({ charges, totalCollected }) {
  return (
    <AdminLayout>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold text-ink-primary dark:text-white">Charges</h1>
        <div className="rounded-2xl border border-black/5 bg-surface px-5 py-3 text-right dark:border-white/10 dark:bg-[#1a1a19]">
          <p className="text-xs font-medium text-ink-muted">Total collected</p>
          <p className="text-xl font-bold text-ink-primary dark:text-white">
            ₦{totalCollected.toLocaleString()}
          </p>
        </div>
      </div>

      <p className="mb-4 text-xs text-ink-muted">
        These are service charges collected from users — they belong to the platform, separate
        from any user's wallet balance.
      </p>

      <div className="overflow-hidden rounded-2xl border border-black/5 bg-surface dark:border-white/10 dark:bg-[#1a1a19]">
        <table className="w-full text-sm">
          <thead className="bg-page text-left text-ink-muted dark:bg-white/5">
            <tr>
              <th className="px-4 py-3 font-medium">Charge</th>
              <th className="px-4 py-3 font-medium">Paid by</th>
              <th className="px-4 py-3 font-medium">Contract</th>
              <th className="px-4 py-3 font-medium">Context</th>
              <th className="px-4 py-3 font-medium">Amount</th>
              <th className="px-4 py-3 font-medium">Date</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-black/5 dark:divide-white/10">
            {charges.map((charge) => (
              <tr key={charge.id}>
                <td className="px-4 py-3 font-medium text-ink-primary dark:text-white">
                  {charge.service_charge?.name ?? '—'}
                </td>
                <td className="px-4 py-3">
                  <div className="font-medium text-ink-primary dark:text-white">
                    {charge.user.first_name} {charge.user.last_name}
                  </div>
                  <div className="text-ink-secondary dark:text-[#c3c2b7]">{charge.user.email}</div>
                </td>
                <td className="px-4 py-3 text-ink-secondary dark:text-[#c3c2b7]">
                  {charge.contract?.title ?? '—'}
                </td>
                <td className="px-4 py-3 capitalize text-ink-secondary dark:text-[#c3c2b7]">
                  {charge.context.replace('_', ' ')}
                </td>
                <td className="px-4 py-3 text-ink-primary dark:text-white">
                  ₦{Number(charge.amount).toLocaleString()}
                </td>
                <td className="px-4 py-3 text-ink-secondary dark:text-[#c3c2b7]">
                  {new Date(charge.created_at).toLocaleDateString()}
                </td>
              </tr>
            ))}
            {charges.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-ink-muted">
                  No charges collected yet.
                </td>
              </tr>
            ) : null}
          </tbody>
        </table>
      </div>
    </AdminLayout>
  );
}
