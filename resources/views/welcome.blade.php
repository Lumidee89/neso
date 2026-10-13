<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NESO — Secure Escrow Payments for B2B Trade</title>
    <meta name="description" content="NESO is an escrow-based digital payment platform that secures transactions between buyers, suppliers, and vendors.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="bg-page text-ink-primary antialiased dark:bg-[#0d0d0d] dark:text-white">
    @php
        $authUser = auth()->user();
        $authInitials = $authUser
            ? strtoupper(substr($authUser->first_name, 0, 1) . substr($authUser->last_name, 0, 1))
            : null;
    @endphp

    {{-- Nav --}}
    <header class="sticky top-0 z-20 border-b border-black/5 bg-page/80 backdrop-blur dark:border-white/10 dark:bg-[#0d0d0d]/80">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="/" class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-500 text-sm font-bold text-white">N</span>
                <span class="text-lg font-bold tracking-tight">NESO</span>
            </a>
            <nav class="hidden items-center gap-8 text-sm font-medium text-ink-secondary md:flex dark:text-[#c3c2b7]">
                <a href="#features" class="hover:text-ink-primary dark:hover:text-white">Features</a>
                <a href="#how-it-works" class="hover:text-ink-primary dark:hover:text-white">How it works</a>
                <a href="#roles" class="hover:text-ink-primary dark:hover:text-white">Who it's for</a>
            </nav>
            @if ($authUser)
                <a href="/admin/dashboard" class="flex items-center gap-2 rounded-lg bg-brand-500 py-1.5 pl-2 pr-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20 text-xs font-bold">
                        {{ $authInitials }}
                    </span>
                    {{ $authUser->first_name }}
                </a>
            @else
                <a href="/login" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600">
                    Admin Login
                </a>
            @endif
        </div>
    </header>

    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-x-0 -top-24 -z-10 flex justify-center">
            <div class="h-96 w-[40rem] rounded-full bg-brand-200/40 blur-3xl dark:bg-brand-800/30"></div>
        </div>

        <div class="mx-auto grid max-w-6xl items-center gap-16 px-6 py-20 lg:grid-cols-2 lg:py-28">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 dark:border-brand-800 dark:bg-brand-900/40 dark:text-brand-200">
                    Escrow-based B2B payments
                </span>
                <h1 class="mt-5 text-4xl font-bold tracking-tight text-balance sm:text-5xl">
                    Get paid with confidence.
                    <span class="text-brand-500">Pay with protection.</span>
                </h1>
                <p class="mt-5 max-w-lg text-lg text-ink-secondary dark:text-[#c3c2b7]">
                    NESO holds funds in secure escrow until contractual obligations are met — splitting
                    payments automatically between suppliers and vendors, so every party in the trade
                    gets exactly what they're owed, exactly when they've earned it.
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-4">
                    <a href="{{ $authUser ? '/admin/dashboard' : '/login' }}" class="rounded-lg bg-brand-500 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600">
                        {{ $authUser ? 'Go to Dashboard' : 'Go to Admin Dashboard' }}
                    </a>
                    <a href="#how-it-works" class="text-sm font-semibold text-ink-primary hover:text-brand-600 dark:text-white">
                        See how it works →
                    </a>
                </div>
            </div>

            {{-- Decorative wallet mockup --}}
            <div class="relative mx-auto w-full max-w-sm">
                <div class="rounded-2xl border border-black/5 bg-surface p-6 shadow-xl dark:border-white/10 dark:bg-[#1a1a19]">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-ink-secondary dark:text-[#c3c2b7]">Vendor Wallet</span>
                        <span class="rounded-full bg-status-good/10 px-2 py-0.5 text-xs font-semibold text-status-good">Verified</span>
                    </div>
                    <div class="mt-6 space-y-4">
                        <div class="flex items-center justify-between border-b border-black/5 pb-4 dark:border-white/10">
                            <span class="text-sm text-ink-secondary dark:text-[#c3c2b7]">Available</span>
                            <span class="text-xl font-bold">₦500,000</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-black/5 pb-4 dark:border-white/10">
                            <span class="text-sm text-ink-secondary dark:text-[#c3c2b7]">Locked in escrow</span>
                            <span class="text-xl font-bold text-brand-500">₦3,200,000</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-ink-secondary dark:text-[#c3c2b7]">Pending</span>
                            <span class="text-xl font-bold">₦150,000</span>
                        </div>
                    </div>
                </div>
                <div class="absolute -right-6 -top-6 -z-10 h-full w-full rounded-2xl bg-brand-100 dark:bg-brand-900/40"></div>
            </div>
        </div>
    </section>

    {{-- Trust strip --}}
    <section class="border-y border-black/5 bg-surface dark:border-white/10 dark:bg-[#1a1a19]">
        <div class="mx-auto grid max-w-6xl grid-cols-1 gap-6 px-6 py-8 text-center sm:grid-cols-3">
            <div>
                <p class="text-sm font-semibold text-ink-primary dark:text-white">Escrow-secured transactions</p>
                <p class="text-xs text-ink-muted">Funds locked until delivery is confirmed</p>
            </div>
            <div>
                <p class="text-sm font-semibold text-ink-primary dark:text-white">Tiered KYC verification</p>
                <p class="text-xs text-ink-muted">Phone, NIN/BVN, and CAC business checks</p>
            </div>
            <div>
                <p class="text-sm font-semibold text-ink-primary dark:text-white">Built-in dispute resolution</p>
                <p class="text-xs text-ink-muted">Admin-reviewed refund or release</p>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="mx-auto max-w-6xl px-6 py-24">
        <div class="max-w-2xl">
            <h2 class="text-3xl font-bold tracking-tight">Everything a secure trade needs</h2>
            <p class="mt-3 text-ink-secondary dark:text-[#c3c2b7]">
                One platform for contracts, escrow, wallets, and compliance — so buyers and sellers
                can transact without having to trust each other blindly.
            </p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @php
                $features = [
                    [
                        'title' => 'Escrow protection',
                        'body' => 'Funds are locked in a secure wallet the moment a contract is paid, and released only when delivery is confirmed by both sides.',
                        'icon' => 'shield',
                    ],
                    [
                        'title' => 'Digital contracts',
                        'body' => 'Payers create contracts with clear terms, amounts, and delivery dates — vendors accept or reject before any money moves.',
                        'icon' => 'document',
                    ],
                    [
                        'title' => 'Automated payment split',
                        'body' => 'Vendors can allocate part of a contract to a supplier automatically, with the split calculated and enforced by the platform.',
                        'icon' => 'split',
                    ],
                    [
                        'title' => 'Wallet management',
                        'body' => 'Every user gets available, locked, and pending balances with a full transaction ledger and downloadable receipts.',
                        'icon' => 'wallet',
                    ],
                    [
                        'title' => 'KYC & compliance',
                        'body' => 'Three-tier verification — phone and email, then NIN/BVN, then full CAC business verification for higher limits.',
                        'icon' => 'badge',
                    ],
                    [
                        'title' => 'Dispute resolution',
                        'body' => 'If a delivery is rejected, a dispute opens automatically and an admin reviews the case to refund or release funds.',
                        'icon' => 'scale',
                    ],
                ];

                $icons = [
                    'shield' => '<path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z" /><path d="M9 12l2 2 4-4" />',
                    'document' => '<path d="M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z" /><path d="M14 3v5h5M9 13h6M9 17h6M9 9h2" />',
                    'split' => '<path d="M6 3v6c0 3 3 3 3 3h6c3 0 3-3 3-3V3M9 12v9M15 12v9" />',
                    'wallet' => '<path d="M3 7a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" /><path d="M16 12h3v3h-3a1.5 1.5 0 010-3z" />',
                    'badge' => '<path d="M12 2l2.4 1.4L17 3l.7 2.6L20 7l-1 2.5.9 2.5-2.1 1.6.2 2.6-2.6.6-1.2 2.2H9.8l-1.2-2.2-2.6-.6.2-2.6L4.1 12 5 9.5 4 7l2.3-1.4L7 3l2.6.4L12 2z" /><path d="M9.5 12l1.8 1.8L14.8 10" />',
                    'scale' => '<path d="M12 3v18M5 8l3.5-2L12 8M19 8l-3.5-2L12 8M4 8l-2 6a3 3 0 006 0l-2-6zM20 8l-2 6a3 3 0 006 0l-2-6z" />',
                ];
            @endphp

            @foreach ($features as $feature)
                <div class="rounded-2xl border border-black/5 bg-surface p-6 transition hover:shadow-md dark:border-white/10 dark:bg-[#1a1a19]">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-900/40 dark:text-brand-300">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            {!! $icons[$feature['icon']] !!}
                        </svg>
                    </div>
                    <h3 class="mt-4 font-semibold">{{ $feature['title'] }}</h3>
                    <p class="mt-2 text-sm text-ink-secondary dark:text-[#c3c2b7]">{{ $feature['body'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section id="how-it-works" class="border-t border-black/5 bg-surface dark:border-white/10 dark:bg-[#1a1a19]">
        <div class="mx-auto max-w-6xl px-6 py-24">
            <h2 class="text-3xl font-bold tracking-tight">How the escrow flow works</h2>

            <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['step' => '01', 'title' => 'Fund your wallet', 'body' => 'The payer deposits funds into their NESO wallet before creating a contract.'],
                    ['step' => '02', 'title' => 'Create & accept', 'body' => 'The payer creates a contract; the vendor reviews the terms and accepts.'],
                    ['step' => '03', 'title' => 'Money locked', 'body' => 'Payment moves into escrow — locked and untouchable until delivery is confirmed.'],
                    ['step' => '04', 'title' => 'Funds released', 'body' => 'Once both sides confirm delivery, funds unlock into the vendor\'s available balance.'],
                ] as $item)
                    <div>
                        <span class="text-3xl font-bold text-brand-200 dark:text-brand-800">{{ $item['step'] }}</span>
                        <h3 class="mt-3 font-semibold">{{ $item['title'] }}</h3>
                        <p class="mt-2 text-sm text-ink-secondary dark:text-[#c3c2b7]">{{ $item['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Roles --}}
    <section id="roles" class="mx-auto max-w-6xl px-6 py-24">
        <h2 class="text-3xl font-bold tracking-tight">Built for every side of the trade</h2>

        <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-3">
            @foreach ([
                ['role' => 'Payer', 'body' => 'Buyers who fund contracts, track deliveries, and confirm receipt before releasing payment.'],
                ['role' => 'Vendor', 'body' => 'Sellers who accept contracts, optionally allocate part of the payment to a supplier, and withdraw earnings.'],
                ['role' => 'Source', 'body' => 'Suppliers who receive approved payment allocations from vendors without ever seeing the full contract terms.'],
            ] as $item)
                <div class="rounded-2xl border border-black/5 bg-surface p-6 dark:border-white/10 dark:bg-[#1a1a19]">
                    <span class="inline-flex rounded-full bg-brand-500 px-3 py-1 text-xs font-semibold text-white">{{ $item['role'] }}</span>
                    <p class="mt-4 text-sm text-ink-secondary dark:text-[#c3c2b7]">{{ $item['body'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="border-t border-black/5 dark:border-white/10">
        <div class="mx-auto max-w-6xl px-6 py-20 text-center">
            <h2 class="text-3xl font-bold tracking-tight">Manage the platform</h2>
            <p class="mt-3 text-ink-secondary dark:text-[#c3c2b7]">
                Admins verify KYC, monitor wallets, resolve disputes, and configure charges from one dashboard.
            </p>
            <a href="{{ $authUser ? '/admin/dashboard' : '/login' }}" class="mt-8 inline-block rounded-lg bg-brand-500 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600">
                {{ $authUser ? 'Go to Dashboard' : 'Admin Login' }}
            </a>
        </div>
    </section>

    <footer class="border-t border-black/5 dark:border-white/10">
        <div class="mx-auto max-w-6xl px-6 py-8 text-center text-xs text-ink-muted">
            &copy; {{ date('Y') }} NESO. All rights reserved.
        </div>
    </footer>

</body>
</html>
