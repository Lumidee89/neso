import AdminLayout from '@/Layouts/AdminLayout';

export default function ContractsIndex({ contracts }) {
  return (
    <AdminLayout>
      <h1 className="mb-6 text-2xl font-bold text-ink-primary dark:text-white">Contracts</h1>

      <div className="overflow-hidden rounded-2xl border border-black/5 bg-surface dark:border-white/10 dark:bg-[#1a1a19]">
        <table className="w-full text-sm">
          <thead className="bg-page text-left text-ink-muted dark:bg-white/5">
            <tr>
              <th className="px-4 py-3 font-medium">Title</th>
              <th className="px-4 py-3 font-medium">Payer</th>
              <th className="px-4 py-3 font-medium">Vendor</th>
              <th className="px-4 py-3 font-medium">Amount</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3 font-medium">Escrow</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-black/5 dark:divide-white/10">
            {contracts.map((contract) => (
              <tr key={contract.id}>
                <td className="px-4 py-3 font-medium text-ink-primary dark:text-white">{contract.title}</td>
                <td className="px-4 py-3 text-ink-secondary dark:text-[#c3c2b7]">
                  {contract.payer.first_name} {contract.payer.last_name}
                </td>
                <td className="px-4 py-3 text-ink-secondary dark:text-[#c3c2b7]">
                  {contract.vendor.first_name} {contract.vendor.last_name}
                </td>
                <td className="px-4 py-3 text-ink-primary dark:text-white">₦{Number(contract.amount).toLocaleString()}</td>
                <td className="px-4 py-3 capitalize text-ink-secondary dark:text-[#c3c2b7]">{contract.status}</td>
                <td className="px-4 py-3 text-ink-secondary dark:text-[#c3c2b7]">
                  {contract.escrow_transaction ? (
                    <>
                      <div className="font-medium text-ink-primary dark:text-white">
                        ₦{Number(contract.escrow_transaction.amount).toLocaleString()} locked
                      </div>
                      <div className="text-xs">
                        {new Date(contract.escrow_transaction.locked_at).toLocaleDateString()}
                      </div>
                    </>
                  ) : (
                    <span className="text-ink-muted">—</span>
                  )}
                </td>
              </tr>
            ))}
            {contracts.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-ink-muted">
                  No contracts yet.
                </td>
              </tr>
            ) : null}
          </tbody>
        </table>
      </div>
    </AdminLayout>
  );
}
