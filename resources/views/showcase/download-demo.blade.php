<x-layouts.marketing title="Download Free Demo — CADEBECK HR" metaDescription="Download a free demo version of CADEBECK HR and see how our HR and payroll software can transform your UK business.">
    <section class="pt-32 pb-24 min-h-screen bg-white dark:bg-zinc-900 flex items-center">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="fade-in">
                    <h1 class="text-5xl font-extrabold text-gray-900 dark:text-white mb-4">Try CADEBECK HR Free</h1>
                    <p class="text-xl text-gray-600 dark:text-gray-400 mb-8">Download our demo version and explore all the features at your own pace. No commitment required.</p>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center text-gray-700 dark:text-gray-300">
                            <svg class="w-6 h-6 text-emerald-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Full access to HR features
                        </li>
                        <li class="flex items-center text-gray-700 dark:text-gray-300">
                            <svg class="w-6 h-6 text-emerald-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Sample payroll data included
                        </li>
                        <li class="flex items-center text-gray-700 dark:text-gray-300">
                            <svg class="w-6 h-6 text-emerald-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            14-day fully functional trial
                        </li>
                        <li class="flex items-center text-gray-700 dark:text-gray-300">
                            <svg class="w-6 h-6 text-emerald-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            No credit card required
                        </li>
                    </ul>
                </div>
                <div class="bg-gray-50 dark:bg-zinc-800 rounded-3xl p-8 shadow-lg fade-in" style="animation-delay: 0.2s;">
                    <form action="/download-demo" method="POST" class="space-y-5">
                        @csrf
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Download Now</h2>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-6">Fill in your details to get the download link.</p>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name *</label>
                            <input type="text" name="name" required class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="John Doe">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address *</label>
                            <input type="email" name="email" required class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="john@company.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Company Name *</label>
                            <input type="text" name="company" required class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="Your Company Ltd">
                        </div>
                        <button type="submit" class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition-colors shadow-lg shadow-emerald-500/25">
                            Download Free Demo
                        </button>
                        <p class="text-xs text-gray-500 dark:text-gray-400 text-center">We'll send the download link to your email. No spam, ever.</p>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-layouts.marketing>
