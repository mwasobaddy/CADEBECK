# AI Prompt: Build CadebeckHR Showcase Website (cadebeckhr.com)

## Project Overview

Build a professional showcase/marketing website for **CadebeckHR** — an HR & Payroll management system targeting the UK market. The site is inspired by [BrightHR](https://www.brighthr.com/) in structure and UX. The site will be hosted on Hostinger.

## Tech Stack

- **Framework**: Next.js 14+ (App Router) — static export compatible OR Laravel (if preferred)
- **Styling**: Tailwind CSS v4
- **Animations**: Framer Motion (for carousels, reveal animations)
- **Chatbot**: Tawk.to or Tidio embed
- **SEO**: next-seo or built-in Next.js metadata API
- **Form handling**: React Hook Form + Resend or Formspree for demo/contact forms
- **Download demo**: Protected route or direct download link for a zip/demo package
- **Deployment**: Static export for Hostinger hosting

## Site Structure (Pages & Sections)

### 1. Homepage (`/`)

**Header:**
- Logo (CadebeckHR) on left
- Phone number (e.g., +44 ... ) on left or right
- Navigation links: Products, Pricing, Resources, About, Contact
- CTA button: "Get a Free Demo" (high contrast)
- Mobile: hamburger menu with same items

**Hero Section:**
- Headline: "HR, Payroll & Compliance Solutions for UK Businesses"
- Subheadline: "Streamline employee management, automate payroll, and stay compliant with UK employment law — all in one platform."
- CTA buttons: "Get a Free Demo" (primary) | "Download Demo Version" (secondary)
- Trust badges: "Trusted by X businesses" + rating badges
- Visual: Dashboard mockup screenshot or hero illustration of the app

**Product Showcase (Carousel/Slider):**
- 4 slides with icon + title + 4-5 bullet features + "Learn More" link:
  1. **HR Software** — Employee records, leave management, attendance tracking, document storage, performance management
  2. **Payroll** — PAYE calculations, NIC/Student loan deductions, payslip generation, HMRC compliance, payslip PDFs
  3. **Time & Attendance** — Clock in/out, shift scheduling, overtime tracking, real-time dashboard
  4. **Recruitment** — Job adverts, applicant tracking, onboarding workflows

**Why CadebeckHR (Value Props) — 5 cards:**
1. "Cut admin time in half"
2. "HMRC & Employment Law compliant"
3. "Staff get paid right — on time, every time"
4. "Let your team focus on real work, not red tape"
5. "24/7 expert support"

**Demo Booking Section:**
- Lead capture form: First Name, Last Name, Company Name, Business Email, Phone Number, Employee Count
- CTA: "Book Your Free Demo"
- Privacy notice link
- Side visual: App screenshot

**Testimonials Carousel:**
- 6-8 testimonials with: star rating, quote text, customer name, job title, company name, optional photo
- Auto-scroll + manual navigation arrows

**Social Proof (Logo Wall):**
- "Join X+ businesses that trust CadebeckHR"
- Scrolling row of client logos (placeholder/vector logos)

**Features Deep Dive (4-card section):**
- **Perks & Marketplace** — Employee discounts, business directory
- **eLearning** — HR & compliance training courses
- **Employee Wellbeing** — EAP, counselling, wellness programs
- **AI Chatbot** — Instant answers to HR questions

**Pricing Summary Section:**
- 3 pricing tiers (Core, Enhanced, Full) with feature lists
- "Starting from £X/employee/month"
- CTA: "View Full Pricing" → /pricing

**FAQ Section:**
- Accordion with 5-6 common questions (What is CadebeckHR? How much does it cost? Can I try before buying? Is it HMRC compliant? etc.)
- Schema.org FAQ structured data

**Footer:**
- Logo + short description
- Social links (Facebook, Twitter/X, LinkedIn, YouTube)
- App store badges (Apple App Store, Google Play Store)
- Columns: Product (HR Software, Payroll, Attendance, Recruitment, Pricing), Company (About, Blog, Careers, Contact), Resources (Help Centre, Guides, Webinars, FAQ), Legal (Privacy Policy, Terms of Service, Cookie Policy)
- Awards & certifications row
- Copyright: "© 2025 CadebeckHR. All rights reserved."
- Registration/company address in small text

### 2. Pricing Page (`/pricing`)

- Header (same as homepage)
- Hero: "Simple, transparent pricing" + employee count selector
- 3 pricing tiers in cards:
  - **Core HR** — Basic HR features
  - **Enhanced HR** — Core + 24/7 advice + document templates
  - **Full Suite** — Enhanced + Health & Safety + Wellbeing
- Each card: price/employee/month, feature checklist, CTA "Buy Online" or "Book a Demo"
- "Why trust CadebeckHR" section (stats, customer logos)
- FAQ accordion
- Footer

### 3. Features/Product Page (`/features` or `/hr-software`)

- Hero with product screenshot
- Feature grid (icon + title + description):
  - Staff Holiday/Leave Planner
  - Clocking In / Attendance
  - Sick Leave & Lateness
  - Shifts & Rotas
  - Performance Management
  - Timesheets
  - HR Document Storage
  - Overtime Tracker
  - Expense Tracker
  - Recruitment
  - Employee Recognition
- "Why choose CadebeckHR" section with 5 bullet points
- Testimonials
- CTA: Book a demo
- FAQ
- Footer

### 4. About Page (`/about`)

- Company story, mission, vision
- Team section (placeholder)
- Timeline / milestones
- Values
- CTA to join / contact

### 5. Contact Page (`/contact`)

- Contact form (name, email, phone, company, message)
- Email address, phone number, physical address
- Embedded Google Map
- Social links

### 6. Blog/Resources Page (`/resources` or `/blog`)

- Blog post cards with image, date, title, excerpt, "Read More"
- Categories filter
- Pagination
- Individual post page with sidebar

### 7. Download Demo Page (`/download-demo`)

- Short form (name, email, company) to access the demo
- Download button (link to a zip/installer package)
- Instructions for installation

## SEO Requirements (CRITICAL — do these first time, no corrections later)

### On-Page SEO
- **Semantic HTML5**: Proper `<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<aside>`, `<footer>` hierarchy
- **Heading hierarchy**: Exactly one `<h1>` per page, logical `<h2>` → `<h3>` → `<h4>` nesting, no skipping levels
- **Meta tags**: Unique `<title>` (50-60 chars) and `<meta name="description">` (150-160 chars) for EVERY page
- **Open Graph**: `og:title`, `og:description`, `og:image`, `og:url`, `og:type` on all pages
- **Twitter Cards**: `twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`
- **Canonical URLs**: `<link rel="canonical">` on every page to prevent duplicate content
- **Alt text**: Every `<img>` must have descriptive, keyword-rich `alt` attribute
- **Internal linking**: Links between related pages using descriptive anchor text (not "click here")
- **SEO-friendly URLs**: /hr-software, /pricing, /about, /contact, /blog/why-hr-software-matters (kebab-case, descriptive)

### Structured Data (JSON-LD)
- **Organization schema**: name, url, logo, contactPoint, sameAs (social profiles), address, foundingDate
- **SoftwareApplication schema**: applicationCategory=BusinessApplication, operatingSystem=Web, offers (pricing), aggregateRating (reviews)
- **FAQ schema**: On FAQ sections — question/answer pairs
- **Review schema**: On testimonials — reviewRating, author, itemReviewed
- **BreadcrumbList schema**: On inner pages
- **LocalBusiness schema**: If physical address in UK

### Technical SEO
- **XML Sitemap**: `/sitemap.xml` with all pages and lastmod dates
- **robots.txt**: Allow all crawlers, point to sitemap
- **Page speed**: Optimize images (WebP format, lazy loading), minify CSS/JS, use CDN, enable compression
- **Mobile responsiveness**: Fully responsive from 320px to 1920px+, touch-friendly navigation
- **Core Web Vitals**: LCP < 2.5s, FID < 100ms, CLS < 0.1
- **Font loading**: Use `font-display: swap` to prevent invisible text during load
- **Preconnect**: `<link rel="preconnect">` for Google Fonts, analytics, etc.
- **Progressive enhancement**: Site works without JavaScript (core content visible)

### Content SEO
- **Target keywords**: "HR software UK", "payroll system UK", "employee management system", "HMRC compliant payroll", "HRMS UK", "cloud HR software UK"
- **Blog content strategy**: Topics like "How to calculate PAYE in the UK 2025", "NIC deductions guide", "Employee leave entitlements under UK employment law"
- **Local SEO**: Include UK-specific content, address, phone with country code

## Design Guidelines

### Color Palette
- **Primary**: Deep professional blue (#1E3A5F) or green, based on existing app
- **Secondary**: Teal (#0D9488) or accent color from existing branding
- **Neutrals**: White, gray-50 through gray-900
- **CTA**: High-contrast (orange or bright green or purple)
- Keep consistent with CadebeckHR app branding

### Typography
- **Headings**: Inter or Plus Jakarta Sans (sans-serif, bold)
- **Body**: Inter (clean, readable at all sizes)
- Scale: text-xs through text-6xl with consistent line-height

### Component Patterns (copy BrightHR's approach)
- Cards with subtle shadow, rounded corners, hover lift effect
- Gradient CTAs with white text
- Clean divider lines between sections
- Lots of whitespace — don't crowd elements
- Icons in feature cards (use Lucide React or Heroicons)

## Chatbot Integration

- Embed **Tawk.to** or **Tidio** chat widget
- Pre-configure with: "Welcome to CadebeckHR! How can I help you today?"
- Auto-responders for common questions (pricing, demo booking, features)
- Chat icon: bottom-right, branded color
- Load asynchronously (defer) so it doesn't block page load

## Downloadable Demo

- The demo should be a zip file containing either:
  - A packaged installer or
  - A link/documentation guide
- Gate it behind a simple form (name + email) to capture leads
- After form submit, show download button + send email with link
- Track download count

## Performance Targets

- Lighthouse score: 90+ on all metrics (Performance, Accessibility, Best Practices, SEO)
- First Contentful Paint: < 1.5s
- Time to Interactive: < 3s
- Total page size: < 1MB (excluding images)
- All images in WebP format with srcset for responsive sizes

## Done List (verify before considering complete)

- [ ] All pages render correctly at mobile, tablet, desktop
- [ ] Lighthouse SEO score is 100
- [ ] Lighthouse Performance score is 90+
- [ ] Lighthouse Accessibility score is 90+
- [ ] XML sitemap generated and valid
- [ ] robots.txt present and correct
- [ ] All pages have unique meta titles and descriptions
- [ ] Open Graph tags present on all pages
- [ ] Structured data validated with Google Rich Results Test
- [ ] All images have alt text
- [ ] Proper `<h1>`-`<h6>` hierarchy on every page
- [ ] No broken internal links
- [ ] All forms submit correctly (demo booking, contact, download)
- [ ] Chatbot loads and responds
- [ ] Mobile menu works correctly
- [ ] All external links open in new tab with `rel="noopener noreferrer"`
- [ ] Privacy policy and terms of service pages exist
- [ ] 404 page exists and is styled
- [ ] Sitemap submitted to Google Search Console (post-launch)

## Implementation Order

1. Set up project, install dependencies, configure Tailwind + SEO
2. Build Layout (Header, Footer, SEO wrapper)
3. Build Homepage sections one by one (Hero → Carousel → Value Props → Demo Form → Testimonials → Logo Wall → Feature Cards → Pricing Summary → FAQ)
4. Build Pricing page
5. Build Features/Product page
6. Build About page
7. Build Contact page
8. Build Blog/Resources (if needed)
9. Build Download Demo page
10. Set up chatbot
11. Generate sitemap + robots.txt
12. Add all structured data
13. Performance optimization pass
14. Mobile responsiveness pass
15. SEO audit pass
