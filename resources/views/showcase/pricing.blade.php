<x-layouts.marketing title="Pricing — CADEBECK HR" metaDescription="Simple, transparent pricing for UK businesses. Starting from £3/employee/month. No hidden fees, fixed pricing.">
    <section class="pt-32 pb-24 bg-white dark:bg-zinc-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 fade-in">
                <h1 class="text-5xl font-extrabold text-gray-900 dark:text-white mb-4">Simple, Transparent Pricing</h1>
                <p class="text-xl text-gray-600 dark:text-gray-400">Choose the plan that fits your business. No hidden fees, ever.</p>
            </div>
            @include('showcase.partials.pricing-cards')
            <p class="text-center text-gray-500 dark:text-gray-400 mt-8 text-sm">All prices exclude VAT. Fixed pricing for the duration of your agreement.</p>
        </div>
    </section>

    <section class="py-24 bg-gray-50 dark:bg-zinc-800/50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white text-center mb-12">Why Thousands of UK Businesses Trust CADEBECK HR</h2>
            <div class="grid md:grid-cols-3 gap-8 text-center">
                <div class="bg-white dark:bg-zinc-800 rounded-2xl p-8 shadow-lg">
                    <div class="text-4xl font-bold text-emerald-600 dark:text-emerald-400 mb-2">1M+</div>
                    <p class="text-gray-600 dark:text-gray-400">Employees managed through our platform</p>
                </div>
                <div class="bg-white dark:bg-zinc-800 rounded-2xl p-8 shadow-lg">
                    <div class="text-4xl font-bold text-emerald-600 dark:text-emerald-400 mb-2">£4M+</div>
                    <p class="text-gray-600 dark:text-gray-400">Saved by businesses on solicitor fees yearly</p>
                </div>
                <div class="bg-white dark:bg-zinc-800 rounded-2xl p-8 shadow-lg">
                    <div class="text-4xl font-bold text-emerald-600 dark:text-emerald-400 mb-2">99.9%</div>
                    <p class="text-gray-600 dark:text-gray-400">Uptime guaranteed</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-24 bg-white dark:bg-zinc-900">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white text-center mb-12">Pricing FAQs</h2>
            <div class="space-y-4">
                <div class="border border-gray-200 dark:border-zinc-700 rounded-2xl overflow-hidden">
                    <button class="faq-question w-full flex items-center justify-between p-6 text-left bg-white dark:bg-zinc-800">
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">Can I change my plan later?</span>
                        <svg class="faq-icon w-5 h-5 text-gray-500 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="faq-content"><div><div class="px-6 pb-6 text-gray-600 dark:text-gray-400">Yes, you can upgrade or downgrade your plan at any time. Changes take effect from the next billing period.</div></div></div>
                </div>
                <div class="border border-gray-200 dark:border-zinc-700 rounded-2xl overflow-hidden">
                    <button class="faq-question w-full flex items-center justify-between p-6 text-left bg-white dark:bg-zinc-800">
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">Is there a setup fee?</span>
                        <svg class="faq-icon w-5 h-5 text-gray-500 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="faq-content"><div><div class="px-6 pb-6 text-gray-600 dark:text-gray-400">No setup fees. We offer free onboarding support to get you up and running quickly.</div></div></div>
                </div>
                <div class="border border-gray-200 dark:border-zinc-700 rounded-2xl overflow-hidden">
                    <button class="faq-question w-full flex items-center justify-between p-6 text-left bg-white dark:bg-zinc-800">
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">How does billing work?</span>
                        <svg class="faq-icon w-5 h-5 text-gray-500 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="faq-content"><div><div class="px-6 pb-6 text-gray-600 dark:text-gray-400">We bill monthly or annually based on your total number of employees. Annual billing comes with a 2-month discount. Your price is locked for the duration of your agreement.</div></div></div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.marketing>
