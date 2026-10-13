import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

import AdminLayout from '@/Layouts/AdminLayout';

const EMPTY_FORM = {
  name: '',
  type: 'flat',
  value: '',
  min_amount: '',
  max_amount: '',
  applies_to: 'all',
};

const inputClasses =
  'w-full rounded-lg border border-black/10 bg-surface px-2 py-1.5 text-sm text-ink-primary outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-200 dark:border-white/10 dark:bg-[#0d0d0d] dark:text-white dark:focus:ring-brand-900';

export default function ServiceChargesIndex({ charges }) {
  const [showForm, setShowForm] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const { data, setData, post, put, reset, processing, errors } = useForm(EMPTY_FORM);

  const startCreate = () => {
    reset();
    setEditingId(null);
    setShowForm(true);
  };

  const startEdit = (charge) => {
    setData({
      name: charge.name,
      type: charge.type,
      value: charge.value,
      min_amount: charge.min_amount ?? '',
      max_amount: charge.max_amount ?? '',
      applies_to: charge.applies_to,
    });
    setEditingId(charge.id);
    setShowForm(true);
  };

  const submit = (e) => {
    e.preventDefault();
    const onSuccess = () => {
      setShowForm(false);
      reset();
    };

    if (editingId) {
      put(`/admin/service-charges/${editingId}`, { onSuccess });
    } else {
      post('/admin/service-charges', { onSuccess });
    }
  };

  const toggle = (id) => router.post(`/admin/service-charges/${id}/toggle`, {}, { preserveScroll: true });

  return (
    <AdminLayout>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold text-ink-primary dark:text-white">Service Charges</h1>
        <button
          onClick={startCreate}
          className="rounded-lg bg-brand-500 px-3 py-2 text-sm font-medium text-white transition hover:bg-brand-600"
        >
          New charge
        </button>
      </div>

      {showForm ? (
        <form onSubmit={submit} className="mb-6 grid grid-cols-2 gap-4 rounded-2xl border border-black/5 bg-surface p-5 dark:border-white/10 dark:bg-[#1a1a19]">
          <div>
            <label className="mb-1 block text-xs font-medium text-ink-secondary dark:text-[#c3c2b7]">Name</label>
            <input
              value={data.name}
              onChange={(e) => setData('name', e.target.value)}
              className={inputClasses}
            />
            {errors.name ? <p className="mt-1 text-xs text-status-critical">{errors.name}</p> : null}
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-ink-secondary dark:text-[#c3c2b7]">Type</label>
            <select
              value={data.type}
              onChange={(e) => setData('type', e.target.value)}
              className={inputClasses}
            >
              <option value="flat">Flat</option>
              <option value="percentage">Percentage</option>
              <option value="tiered">Tiered</option>
            </select>
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-ink-secondary dark:text-[#c3c2b7]">Value</label>
            <input
              type="number"
              step="0.01"
              value={data.value}
              onChange={(e) => setData('value', e.target.value)}
              className={inputClasses}
            />
            {errors.value ? <p className="mt-1 text-xs text-status-critical">{errors.value}</p> : null}
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-ink-secondary dark:text-[#c3c2b7]">Applies to</label>
            <select
              value={data.applies_to}
              onChange={(e) => setData('applies_to', e.target.value)}
              className={inputClasses}
            >
              <option value="all">All</option>
              <option value="contract">Contract</option>
              <option value="withdrawal">Withdrawal</option>
              <option value="escrow_release">Escrow release</option>
            </select>
          </div>
          <div className="col-span-2 flex gap-2">
            <button
              type="submit"
              disabled={processing}
              className="rounded-lg bg-brand-500 px-3 py-2 text-sm font-medium text-white transition hover:bg-brand-600 disabled:opacity-60"
            >
              {editingId ? 'Update' : 'Create'}
            </button>
            <button
              type="button"
              onClick={() => setShowForm(false)}
              className="rounded-lg bg-black/5 px-3 py-2 text-sm font-medium text-ink-primary transition hover:bg-black/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/20"
            >
              Cancel
            </button>
          </div>
        </form>
      ) : null}

      <div className="overflow-hidden rounded-2xl border border-black/5 bg-surface dark:border-white/10 dark:bg-[#1a1a19]">
        <table className="w-full text-sm">
          <thead className="bg-page text-left text-ink-muted dark:bg-white/5">
            <tr>
              <th className="px-4 py-3 font-medium">Name</th>
              <th className="px-4 py-3 font-medium">Type</th>
              <th className="px-4 py-3 font-medium">Value</th>
              <th className="px-4 py-3 font-medium">Applies to</th>
              <th className="px-4 py-3 font-medium">Active</th>
              <th className="px-4 py-3 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-black/5 dark:divide-white/10">
            {charges.map((charge) => (
              <tr key={charge.id}>
                <td className="px-4 py-3 font-medium text-ink-primary dark:text-white">{charge.name}</td>
                <td className="px-4 py-3 capitalize text-ink-secondary dark:text-[#c3c2b7]">{charge.type}</td>
                <td className="px-4 py-3 text-ink-primary dark:text-white">
                  {charge.type === 'percentage' ? `${charge.value}%` : `₦${charge.value}`}
                </td>
                <td className="px-4 py-3 capitalize text-ink-secondary dark:text-[#c3c2b7]">{charge.applies_to.replace('_', ' ')}</td>
                <td className="px-4 py-3">
                  <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${charge.active ? 'bg-status-good/10 text-status-good' : 'bg-black/5 text-ink-muted dark:bg-white/10'}`}>
                    {charge.active ? 'Active' : 'Inactive'}
                  </span>
                </td>
                <td className="flex gap-3 px-4 py-3">
                  <button onClick={() => startEdit(charge)} className="text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-300">
                    Edit
                  </button>
                  <button onClick={() => toggle(charge.id)} className="text-xs font-medium text-ink-muted hover:text-ink-primary dark:hover:text-white">
                    {charge.active ? 'Deactivate' : 'Activate'}
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </AdminLayout>
  );
}
