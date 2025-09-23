<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('partials.head')
        <style>
            .hero-gradient {
            background: linear-gradient(135deg, #065f46 0%, #16a34a 25%, #22c55e 50%, #86efac 75%, #bbf7d0 100%);
            }
            
            .hero-gradient-dark {
            background: linear-gradient(135deg, #052e20 0%, #08332a 25%, #0f392f 50%, #1a4b38 75%, #294d3e 100%);
            }
            
            .testimonial-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            }
            
            .testimonial-card-dark {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .company-logo {
            background: linear-gradient(135deg, #065f46, #16a34a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            }
            
            .company-logo-dark {
            background: linear-gradient(135deg, #bbf7d0, #86efac);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            }
            
            .floating-element {
            animation: float 6s ease-in-out infinite;
            }
            
            @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            }
            
            .slide-in {
            animation: slideIn 0.8s ease-out;
            }
            
            @keyframes slideIn {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
            }

            /* Dark mode grid pattern */
            .grid-pattern-dark {
            background-image: 
                linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 20px 20px;
            }
        </style>
    </head>
    <body class="h-full bg-gray-50 dark:bg-gray-900 antialiased">
        <div class="min-h-full flex">
            <!-- Left side - Form -->
            <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24 bg-white dark:bg-gray-900 transition-colors duration-200">
                <div class="mx-auto w-full max-w-sm lg:w-96">
                    <!-- Logo and Company Name -->
                    <div class="mb-8">
                        <div class="flex items-center space-x-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full border border-green-500 dark:border-green-700 dark:bg-gray-200/50 shadow-lg">
                                <x-app-logo-icon class="h-8 w-8 text-white" />
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold company-logo dark:company-logo-dark">{{ config('app.name', 'CADEBECK') }}</h1>
                                <p class="text-sm text-gray-500 dark:text-gray-400">HR Management System</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Content -->
                    <div class="slide-in">
                        {{ $slot }}
                    </div>

                    <!-- Footer -->
                    <div class="mt-8 text-center">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            © {{ date('Y') }} CADEBECK. All rights reserved.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right side - Hero Section -->
            <div class="hidden lg:block relative w-0 flex-1">
                <div class="absolute inset-0 hero-gradient dark:hero-gradient-dark transition-all duration-300">
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 opacity-10 dark:opacity-20">
                        <div class="w-full h-full grid-pattern-dark dark:block hidden"></div>
                        <svg class="w-full h-full dark:hidden" viewBox="0 0 100 100" preserveAspectRatio="none">
                            <defs>
                                <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                                    <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                                </pattern>
                            </defs>
                            <rect width="100%" height="100%" fill="url(#grid)" />
                        </svg>
                    </div>
                    
                    <!-- Floating Elements -->
                    <div class="absolute top-20 right-20 floating-element">
                        <div class="w-20 h-20 bg-white/20 dark:bg-white/10 rounded-full backdrop-blur-sm"></div>
                    </div>
                    <div class="absolute bottom-32 left-16 floating-element" style="animation-delay: -2s;">
                        <div class="w-16 h-16 bg-white/15 dark:bg-white/8 rounded-lg backdrop-blur-sm"></div>
                    </div>
                    <div class="absolute top-1/3 left-1/4 floating-element" style="animation-delay: -4s;">
                        <div class="w-12 h-12 bg-white/10 dark:bg-white/5 rounded-full backdrop-blur-sm"></div>
                    </div>

                    <!-- Main Content -->
                    <div class="relative h-full flex flex-col justify-center px-12">
                        <!-- Hero Text -->
                        <div class="mb-12">
                            <h2 class="text-4xl font-bold text-white dark:text-gray-100 mb-4 leading-tight">
                                Streamline Your HR
                                <span class="block text-green-200 dark:text-green-300">Management</span>
                            </h2>
                            <p class="text-xl text-green-100 dark:text-gray-300 leading-relaxed">
                                Empower your workforce with modern HR solutions that drive productivity and employee satisfaction.
                            </p>
                        </div>

                        <!-- Testimonial Carousel -->
                        <div class="testimonial-card dark:testimonial-card-dark rounded-2xl p-8 shadow-2xl transition-all duration-300 hidden lg:block" id="testimonialCarousel">
                            <div class="flex text-yellow-400 dark:text-yellow-300 mb-4">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                            </div>
                            
                            <div class="testimonial-content">
                                <blockquote class="text-white dark:text-gray-100 text-lg font-medium mb-4 leading-relaxed">
                                    "CADEBECK has revolutionized our HR processes. The intuitive interface and comprehensive features have made employee management effortless and efficient."
                                </blockquote>
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-white/20 dark:bg-white/10 rounded-full flex items-center justify-center mr-4">
                                        <span class="text-white dark:text-gray-100 font-semibold">SA</span>
                                    </div>
                                    <div>
                                        <div class="text-white dark:text-gray-100 font-semibold">Sarah Ahmed</div>
                                        <div class="text-green-200 dark:text-gray-300 text-sm">HR Director</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Navigation dots -->
                            <div class="flex justify-center mt-8 space-x-2">
                                <button class="w-2 h-2 rounded-full bg-white dark:bg-gray-300 transition-all duration-300"></button>
                                <button class="w-2 h-2 rounded-full bg-white/50 dark:bg-gray-300/50 transition-all duration-300"></button>
                                <button class="w-2 h-2 rounded-full bg-white/50 dark:bg-gray-300/50 transition-all duration-300"></button>
                            </div>
                        </div>

                        <!-- Feature highlights -->
                        <div class="mt-12 grid grid-cols-3 gap-6 text-center hidden">
                            <div class="text-white dark:text-gray-100">
                                <div class="text-2xl font-bold">99%</div>
                                <div class="text-green-200 dark:text-gray-300 text-sm">Uptime</div>
                            </div>
                            <div class="text-white dark:text-gray-100">
                                <div class="text-2xl font-bold">500+</div>
                                <div class="text-green-200 dark:text-gray-300 text-sm">Companies</div>
                            </div>
                            <div class="text-white dark:text-gray-100">
                                <div class="text-2xl font-bold">24/7</div>
                                <div class="text-green-200 dark:text-gray-300 text-sm">Support</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Simple testimonial carousel
            document.addEventListener('DOMContentLoaded', function() {
                const testimonials = [
                    {
                        quote: "CADEBECK has revolutionized our HR processes. The intuitive interface and comprehensive features have made employee management effortless and efficient.",
                        author: "Sarah Ahmed",
                        position: "HR Director",
                        initials: "SA"
                    },
                    {
                        quote: "The payroll management system is outstanding. It handles complex calculations seamlessly and ensures compliance with local regulations.",
                        author: "Michael Chen",
                        position: "Finance Manager", 
                        initials: "MC"
                    },
                    {
                        quote: "Employee self-service features have significantly reduced our administrative workload while improving employee satisfaction and engagement.",
                        author: "Fatima Hassan",
                        position: "Operations Lead",
                        initials: "FH"
                    }
                ];
                
                let currentIndex = 0;
                const content = document.querySelector('.testimonial-content');
                const dots = document.querySelectorAll('#testimonialCarousel button');
                
                function updateTestimonial(index) {
                    const testimonial = testimonials[index];
                    if (content) {
                        content.innerHTML = `
                            <blockquote class="text-white dark:text-gray-100 text-lg font-medium mb-4 leading-relaxed">
                                "${testimonial.quote}"
                            </blockquote>
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-white/20 dark:bg-white/10 rounded-full flex items-center justify-center mr-4">
                                    <span class="text-white dark:text-gray-100 font-semibold">${testimonial.initials}</span>
                                </div>
                                <div>
                                    <div class="text-white dark:text-gray-100 font-semibold">${testimonial.author}</div>
                                    <div class="text-green-200 dark:text-gray-300 text-sm">${testimonial.position}</div>
                                </div>
                            </div>
                        `;
                    }
                    
                    // Update dots
                    dots.forEach((dot, i) => {
                        dot.className = i === index 
                            ? 'w-2 h-2 rounded-full bg-white dark:bg-gray-300 transition-all duration-300'
                            : 'w-2 h-2 rounded-full bg-white/50 dark:bg-gray-300/50 transition-all duration-300';
                    });
                }
                
                // Auto-rotate testimonials
                setInterval(() => {
                    currentIndex = (currentIndex + 1) % testimonials.length;
                    updateTestimonial(currentIndex);
                }, 5000);
                
                // Click handlers for dots
                dots.forEach((dot, index) => {
                    dot.addEventListener('click', () => {
                        currentIndex = index;
                        updateTestimonial(currentIndex);
                    });
                });
            });
        </script>

        @fluxScripts
    </body>
</html>
