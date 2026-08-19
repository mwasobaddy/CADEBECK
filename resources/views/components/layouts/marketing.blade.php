<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ $title ?? 'CADEBECK HR — UK HR & Payroll Management Software' }}</title>
    <meta name="description" content="{{ $metaDescription ?? 'CADEBECK HR is a comprehensive HR and payroll management system for UK businesses. Streamline employee management, automate payroll, and stay HMRC compliant.' }}" />

    <meta property="og:title" content="{{ $title ?? 'CADEBECK HR — UK HR & Payroll Management Software' }}" />
    <meta property="og:description" content="{{ $metaDescription ?? 'Streamline employee management, automate payroll, and stay HMRC compliant with CADEBECK HR.' }}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:image" content="{{ url('/og-image.png') }}" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $title ?? 'CADEBECK HR' }}" />
    <meta name="twitter:description" content="{{ $metaDescription ?? 'Streamline employee management, automate payroll, and stay HMRC compliant.' }}" />

    <link rel="canonical" href="{{ url()->current() }}" />
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .marketing-gradient {
            background: linear-gradient(135deg, #065f46 0%, #059669 50%, #10b981 100%);
        }
        .marketing-gradient-dark {
            background: linear-gradient(135deg, #052e20 0%, #065f46 50%, #047857 100%);
        }
        .hero-gradient {
            background: linear-gradient(135deg, #064e3b 0%, #065f46 30%, #059669 70%, #10b981 100%);
        }
        .text-gradient {
            background: linear-gradient(135deg, #059669, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .float-animation {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .testimonial-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .slide-in-left {
            animation: slideInLeft 0.8s ease-out;
        }
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .logo-scroll {
            animation: scrollLogos 30s linear infinite;
        }
        @keyframes scrollLogos {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .faq-content {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 0.3s ease;
        }
        .faq-content.open {
            grid-template-rows: 1fr;
        }
        .faq-content > div {
            overflow: hidden;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-white dark:bg-zinc-900 antialiased" x-data="{ mobileMenuOpen: false }" x-cloak>
    <div class="min-h-screen flex flex-col">
        <header class="fixed top-0 left-0 right-0 z-50 transition-all duration-300"
                x-data="{ scrolled: false }"
                x-init="scrolled = window.scrollY > 20"
                @scroll.window="scrolled = window.scrollY > 20"
                x-bind:class="scrolled ? 'bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md shadow-lg border-b border-gray-100 dark:border-zinc-800' : 'bg-transparent'">
            <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    <a href="/" class="flex items-center space-x-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-emerald-500 shadow-lg bg-white dark:bg-zinc-800">
                            <x-app-logo-icon class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">CADEBECK</span>
                            <span class="hidden sm:block text-xs text-gray-500 dark:text-gray-400 -mt-1">HR Management</span>
                        </div>
                    </a>

                    <div class="hidden lg:flex items-center space-x-8">
                        <a href="/features" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Features</a>
                        <a href="/pricing" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Pricing</a>
                        <a href="/about" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">About</a>
                        <a href="/contact" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Contact</a>
                    </div>

                    <div class="flex items-center space-x-4">
                        <a href="/login" class="hidden sm:inline-flex text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Sign In</a>
                        <a href="/contact" class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white marketing-gradient rounded-full hover:shadow-lg hover:shadow-emerald-500/25 transition-all duration-300">
                            Get a Free Demo
                        </a>
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-800">
                            <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            <svg x-show="mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <div x-cloak x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="lg:hidden pb-6 border-t border-gray-100 dark:border-zinc-800">
                    <div class="pt-4 space-y-3">
                        <a href="/features" class="block px-4 py-2 text-base font-medium text-gray-700 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800">Features</a>
                        <a href="/pricing" class="block px-4 py-2 text-base font-medium text-gray-700 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800">Pricing</a>
                        <a href="/about" class="block px-4 py-2 text-base font-medium text-gray-700 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800">About</a>
                        <a href="/contact" class="block px-4 py-2 text-base font-medium text-gray-700 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800">Contact</a>
                        <hr class="border-gray-100 dark:border-zinc-800">
                        <a href="/login" class="block px-4 py-2 text-base font-medium text-emerald-600 dark:text-emerald-400">Sign In</a>
                    </div>
                </div>
            </nav>
        </header>

        <main class="flex-1">
            {{ $slot }}
        </main>

        <footer class="bg-gray-900 dark:bg-black text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
                    <div class="lg:col-span-2">
                        <a href="/" class="flex items-center space-x-3 mb-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-emerald-500 bg-white/10">
                                <x-app-logo-icon class="h-6 w-6 text-emerald-400" />
                            </div>
                            <div>
                                <span class="text-lg font-bold text-white">CADEBECK</span>
                                <span class="block text-xs text-gray-400 -mt-1">HR Management</span>
                            </div>
                        </a>
                        <p class="text-gray-400 text-sm leading-relaxed max-w-sm">
                            Smart HR software that transforms the way you manage your staff. From hiring to payroll, we've got you covered.
                        </p>
                        <div class="flex space-x-4 mt-6">
                            <a href="https://www.linkedin.com/company/cadebeckhrms" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-gray-800 hover:bg-emerald-600 flex items-center justify-center transition-colors" aria-label="LinkedIn">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                            </a>
                            <a href="https://www.facebook.com/cadebeckhrms" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-gray-800 hover:bg-emerald-600 flex items-center justify-center transition-colors" aria-label="Facebook">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a href="https://www.instagram.com/cadebeckhrms/" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-gray-800 hover:bg-emerald-600 flex items-center justify-center transition-colors" aria-label="Instagram">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                            </a>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Product</h3>
                        <ul class="space-y-3">
                            <li><a href="/features" class="text-sm text-gray-400 hover:text-white transition-colors">HR Software</a></li>
                            <li><a href="/features" class="text-sm text-gray-400 hover:text-white transition-colors">Payroll</a></li>
                            <li><a href="/features" class="text-sm text-gray-400 hover:text-white transition-colors">Attendance</a></li>
                            <li><a href="/features" class="text-sm text-gray-400 hover:text-white transition-colors">Recruitment</a></li>
                            <li><a href="/pricing" class="text-sm text-gray-400 hover:text-white transition-colors">Pricing</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Company</h3>
                        <ul class="space-y-3">
                            <li><a href="/about" class="text-sm text-gray-400 hover:text-white transition-colors">About Us</a></li>
                            <li><a href="/contact" class="text-sm text-gray-400 hover:text-white transition-colors">Contact</a></li>
                        </ul>
                    </div>
                </div>

                <div class="mt-12 pt-8 border-t border-gray-800">
                    <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                        <p class="text-sm text-gray-500">&copy; {{ date('Y') }} CADEBECK HR. All rights reserved.</p>
                        <div class="flex space-x-6 text-sm text-gray-500">
                            <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                            <a href="#" class="hover:text-white transition-colors">Terms of Service</a>
                            <a href="#" class="hover:text-white transition-colors">Cookie Policy</a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
        // FAQ accordion
        document.querySelectorAll('.faq-question').forEach(button => {
            button.addEventListener('click', () => {
                const content = button.nextElementSibling;
                const icon = button.querySelector('.faq-icon');
                content.classList.toggle('open');
                icon.classList.toggle('rotate-180');
            });
        });
    </script>

    @if(session('message_sent'))
    <div class="fixed bottom-6 right-6 bg-emerald-600 text-white px-6 py-4 rounded-xl shadow-2xl z-50 fade-in" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
        <p class="font-medium">Thank you! We'll be in touch shortly.</p>
    </div>
    @endif

    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "Organization",
        "name": "CADEBECK HR",
        "url": "{{ url('/') }}",
        "logo": "{{ url('/favicon.svg') }}",
        "description": "HR and payroll management software for UK businesses. Streamline employee management, automate payroll, and stay HMRC compliant.",
        "sameAs": [
            "https://www.facebook.com/cadebeckhrms",
            "https://www.linkedin.com/company/cadebeckhrms",
            "https://www.instagram.com/cadebeckhrms/"
        ],
        "address": {
            "@@type": "PostalAddress",
            "streetAddress": "18 St Cross Street",
            "postalCode": "EC1 8UN",
            "addressLocality": "London",
            "addressCountry": "UK"
        }
    }
    </script>
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "SoftwareApplication",
        "name": "CADEBECK HR",
        "operatingSystem": "Web",
        "applicationCategory": "BusinessApplication",
        "description": "HR and payroll management software for UK businesses.",
        "offers": {
            "@@type": "Offer",
            "price": "3.00",
            "priceCurrency": "GBP",
            "priceValidUntil": "{{ date('Y-12-31') }}"
        }
    }
    </script>

    <!-- Tawk.to Chatbot -->
    <script>
        var Tawk_API = Tawk_API || {}, Tawk_LoadStart = new Date();
        (function() {
            var s1 = document.createElement("script"), s0 = document.getElementsByTagName("script")[0];
            s1.async = true;
            s1.src = 'https://embed.tawk.to/REPLACE_WITH_WIDGET_ID/1i5t0k8p6';
            s1.charset = 'UTF-8';
            s1.setAttribute('crossorigin', '*');
            s0.parentNode.insertBefore(s1, s0);
        })();
    </script>
</body>
</html>
