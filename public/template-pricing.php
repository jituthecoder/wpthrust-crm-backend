<?php
/**
 * Template Name: Custom Pricing Page
 * Description: Custom WPThrust Dedicated Pricing Page Template with Section Navigation.
 * 
 * Instructions:
 * 1. Upload this file as `template-pricing.php` inside your WordPress Child Theme directory:
 *    `wp-content/themes/your-child-theme-name/template-pricing.php`
 * 2. In WordPress Admin -> Pages -> Add New Page (e.g., "Pricing").
 * 3. On the right panel under Page Attributes -> Template, select "Custom Pricing Page".
 * 4. Publish the page!
 */

get_header(); 
?>

<!-- Embedded Custom CSS for Child Theme Template -->
<style>
/* ==========================================================================
   WPThrust Dedicated Section Pricing Page Stylesheet
   ========================================================================== */
:root {
    --primary-teal: #00a389;
    --primary-teal-hover: #008872;
    --primary-teal-light: #e6f6f3;
    --navy-dark: #0b132a;
    --navy-medium: #1e293b;
    --text-dark: #0f172a;
    --text-muted: #64748b;
    --bg-gradient: linear-gradient(135deg, #f0fdfa 0%, #f8fafc 40%, #eff6ff 100%);
    --card-shadow: 0 15px 45px rgba(0, 163, 137, 0.15), 0 4px 12px rgba(15, 23, 42, 0.04);
    --card-shadow-popular: 0 20px 55px rgba(0, 163, 137, 0.22), 0 6px 16px rgba(15, 23, 42, 0.08);
    --border-radius: 20px;
    --font-family: 'Outfit', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

html {
    scroll-behavior: smooth;
    scroll-padding-top: 100px;
}

.wpthrust-pricing-wrapper {
    font-family: var(--font-family);
    background: var(--bg-gradient);
    color: var(--text-dark);
    line-height: 1.6;
    padding-bottom: 60px;
    -webkit-font-smoothing: antialiased;
}

.wpthrust-pricing-wrapper a {
    text-decoration: none;
    color: inherit;
}

/* Page Hero Section */
.hero-section {
    text-align: center;
    padding: 60px 20px 30px 20px;
    max-width: 900px;
    margin: 0 auto;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #e6f6f3;
    color: var(--primary-teal);
    border: 1px solid rgba(0, 163, 137, 0.25);
    padding: 6px 18px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 18px;
}

.hero-title {
    font-size: 42px;
    font-weight: 800;
    color: var(--navy-dark);
    letter-spacing: -0.8px;
    line-height: 1.2;
    margin-bottom: 16px;
}

.hero-title span {
    color: var(--primary-teal);
}

.hero-subtitle {
    font-size: 18px;
    color: var(--text-muted);
    max-width: 680px;
    margin: 0 auto 35px auto;
}

/* Sticky Quick-Jump Anchor Bar */
.jump-nav-wrapper {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 40px;
    padding: 0 15px;
}

.jump-pill {
    background: #ffffff;
    color: var(--navy-dark);
    border: 1px solid #cbd5e1;
    padding: 10px 22px;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 700;
    transition: all 0.25s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.03);
}

.jump-pill:hover {
    background: var(--navy-dark);
    color: #ffffff;
    border-color: var(--navy-dark);
    box-shadow: 0 8px 18px rgba(11, 19, 42, 0.2);
    transform: translateY(-2px);
}

/* Service Sections */
.pricing-container {
    max-width: 1140px;
    margin: 0 auto 40px auto;
    padding: 0 20px;
}

.service-section {
    margin-bottom: 90px;
}

.section-header {
    text-align: center;
    max-width: 700px;
    margin: 0 auto 40px auto;
}

.section-badge {
    display: inline-block;
    background: #e6f6f3;
    color: var(--primary-teal);
    font-size: 13px;
    font-weight: 700;
    padding: 4px 14px;
    border-radius: 50px;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.section-title {
    font-size: 32px;
    font-weight: 800;
    color: var(--navy-dark);
    margin-bottom: 10px;
    letter-spacing: -0.5px;
}

.section-desc {
    font-size: 16px;
    color: var(--text-muted);
    line-height: 1.5;
}

/* Flexbox Pricing Grid with Sleek Fixed Max-Width */
.pricing-grid {
    display: flex;
    justify-content: center;
    align-items: stretch;
    flex-wrap: wrap;
    gap: 30px;
    max-width: 900px;
    margin: 0 auto;
}

/* Compact Sleek Card Width */
.pricing-card {
    background: #ffffff;
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    border: 1px solid rgba(0, 163, 137, 0.12);
    padding: 42px 32px 32px 32px;
    text-align: center;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.3s ease;
    width: 100%;
    max-width: 410px; /* Perfect proportion - stops cards from stretching too wide */
    flex: 1 1 360px;
}

.pricing-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--card-shadow-popular);
}

.popular-ribbon {
    position: absolute;
    top: 22px;
    right: -34px;
    transform: rotate(45deg);
    background: #ef4444;
    color: #ffffff;
    font-size: 11px;
    font-weight: 800;
    padding: 4px 38px;
    text-transform: uppercase;
    letter-spacing: 1px;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.card-title {
    font-size: 26px;
    font-weight: 800;
    color: var(--navy-dark);
    margin-bottom: 8px;
}

.card-subtitle {
    font-size: 14px;
    color: var(--text-muted);
    min-height: 42px;
    margin-bottom: 24px;
    line-height: 1.4;
}

.price-box {
    margin-bottom: 28px;
}

.price-number {
    font-size: 54px;
    font-weight: 800;
    color: var(--navy-dark);
    line-height: 1;
    display: inline-flex;
    align-items: flex-start;
}

.price-symbol {
    font-size: 32px;
    font-weight: 700;
    vertical-align: top;
    margin-left: 2px;
    margin-top: 4px;
}

.price-fee-label {
    font-size: 16px;
    font-weight: 700;
    color: var(--primary-teal);
    margin-top: 6px;
}

.features-list {
    list-style: none;
    text-align: left;
    margin-bottom: 32px;
    padding: 0;
}

.features-list li {
    font-size: 15px;
    color: #334155;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.check-icon-circle {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid var(--navy-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: var(--navy-dark);
    font-size: 12px;
    font-weight: 800;
}

.btn-choose {
    background: var(--primary-teal);
    color: #ffffff !important;
    font-size: 17px;
    font-weight: 700;
    padding: 15px 28px;
    border-radius: 10px;
    width: 100%;
    display: inline-block;
    transition: all 0.25s ease;
    box-shadow: 0 8px 18px rgba(0, 163, 137, 0.3);
    margin-bottom: 16px;
    border: none;
    cursor: pointer;
    text-align: center;
}

.btn-choose:hover {
    background: var(--primary-teal-hover);
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(0, 163, 137, 0.4);
    color: #ffffff !important;
}

.card-guarantee {
    font-size: 12px;
    font-weight: 800;
    color: var(--primary-teal);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

/* FAQ Section */
.faq-section {
    max-width: 850px;
    margin: 60px auto 40px auto;
    padding: 0 20px;
}

.faq-title {
    text-align: center;
    font-size: 32px;
    font-weight: 800;
    color: var(--navy-dark);
    margin-bottom: 40px;
}

.faq-grid {
    display: grid;
    gap: 18px;
}

.faq-item {
    background: #ffffff;
    border-radius: 12px;
    padding: 22px 26px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}

.faq-q {
    font-size: 17px;
    font-weight: 700;
    color: var(--navy-dark);
    margin-bottom: 8px;
}

.faq-a {
    font-size: 14px;
    color: var(--text-muted);
    line-height: 1.6;
}

/* Responsive */
@media (max-width: 768px) {
    .hero-title { font-size: 32px; }
    .section-title { font-size: 26px; }
    .pricing-card { padding: 35px 24px; }
}
</style>

<div class="wpthrust-pricing-wrapper">

    <!-- Page Hero Section -->
    <section class="hero-section">
        <div class="hero-badge">
            <span>⚡ Transparent Pricing • 90+ Speed Guarantee</span>
        </div>
        <h1 class="hero-title">
            Speed Optimization & <span>Content Services</span>
        </h1>
        <p class="hero-subtitle">
            Guaranteed 90+ Google PageSpeed Insights result with 100% money-back guarantee. Explore our specialized services below.
        </p>

        <!-- Sticky Quick-Jump Anchor Bar (SEO & UX Optimized) -->
        <div class="jump-nav-wrapper">
            <a href="#wordpress" class="jump-pill">🔵 WordPress Speed</a>
            <a href="#shopify" class="jump-pill">🟢 Shopify Speed</a>
            <a href="#php" class="jump-pill">🐘 PHP & Custom Code</a>
            <a href="#other-cms" class="jump-pill">⚡ Other CMS</a>
            <a href="#content-writing" class="jump-pill">✍️ Content Writing</a>
        </div>
    </section>

    <!-- Main Content Container with Separate Sections -->
    <main class="pricing-container">

        <!-- SECTION 1: WORDPRESS SPEED OPTIMIZATION -->
        <section id="wordpress" class="service-section">
            <div class="section-header">
                <div class="section-badge">🔵 WordPress Performance</div>
                <h2 class="section-title">WordPress Speed Optimization</h2>
                <p class="section-desc">Transform slow WordPress sites into lightning-fast, high-converting assets with guaranteed 90+ PageSpeed scores.</p>
            </div>

            <div class="pricing-grid">
                <!-- Basic Speedup Card -->
                <div class="pricing-card">
                    <div>
                        <h3 class="card-title">Basic Speedup</h3>
                        <p class="card-subtitle">Essential speed improvements for blogs, portfolios and small websites.</p>
                        <div class="price-box">
                            <span class="price-number">99<span class="price-symbol">$</span></span>
                            <div class="price-fee-label">One Time Fee</div>
                        </div>
                        <ul class="features-list">
                            <li><span class="check-icon-circle">✓</span> 80+ Google Desktop Speed Test</li>
                            <li><span class="check-icon-circle">✓</span> 80+ Google Mobile Speed Test</li>
                            <li><span class="check-icon-circle">✓</span> Minimum B Grade on GTmetrix</li>
                            <li><span class="check-icon-circle">✓</span> Upto 3 secs Load time on GTmetrix</li>
                            <li><span class="check-icon-circle">✓</span> Same design and functionality</li>
                            <li><span class="check-icon-circle">✓</span> Premium Plugin For Life Time</li>
                        </ul>
                    </div>
                    <div>
                        <a href="https://wpthrust.in/#schedule" class="btn-choose">Choose Basic →</a>
                        <div class="card-guarantee">🔥 100% MONEY-BACK GUARANTEE</div>
                    </div>
                </div>

                <!-- Advanced Pro Card (POPULAR) -->
                <div class="pricing-card">
                    <div class="popular-ribbon">POPULAR</div>
                    <div>
                        <h3 class="card-title">Advanced Pro</h3>
                        <p class="card-subtitle">Complete performance optimization for business sites and stores.</p>
                        <div class="price-box">
                            <span class="price-number">199<span class="price-symbol">$</span></span>
                            <div class="price-fee-label">One Time Fee</div>
                        </div>
                        <ul class="features-list">
                            <li><span class="check-icon-circle">✓</span> 90+ Google Desktop Speed Test</li>
                            <li><span class="check-icon-circle">✓</span> 90+ Google Mobile Speed Test</li>
                            <li><span class="check-icon-circle">✓</span> Minimum A Grade on GTmetrix</li>
                            <li><span class="check-icon-circle">✓</span> Upto 2 secs Load time on GTmetrix</li>
                            <li><span class="check-icon-circle">✓</span> Core Web Vitals Pass Guarantee</li>
                            <li><span class="check-icon-circle">✓</span> Database Cleanup & CDN Setup</li>
                        </ul>
                    </div>
                    <div>
                        <a href="https://wpthrust.in/#schedule" class="btn-choose">Choose Advanced →</a>
                        <div class="card-guarantee">🔥 90+ SPEED SCORE GUARANTEED</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 2: SHOPIFY SPEED OPTIMIZATION -->
        <section id="shopify" class="service-section">
            <div class="section-header">
                <div class="section-badge">🟢 E-Commerce Boost</div>
                <h2 class="section-title">Shopify Speed Optimization</h2>
                <p class="section-desc">Eliminate app bloat and slow liquid code to boost sales conversions and checkout speeds.</p>
            </div>

            <div class="pricing-grid">
                <!-- Shopify Starter -->
                <div class="pricing-card">
                    <div>
                        <h3 class="card-title">Shopify Starter</h3>
                        <p class="card-subtitle">Speed boost for growing Shopify stores with basic theme assets.</p>
                        <div class="price-box">
                            <span class="price-number">149<span class="price-symbol">$</span></span>
                            <div class="price-fee-label">One Time Fee</div>
                        </div>
                        <ul class="features-list">
                            <li><span class="check-icon-circle">✓</span> 80+ Shopify Mobile Speed Score</li>
                            <li><span class="check-icon-circle">✓</span> App Script Deferral & Audit</li>
                            <li><span class="check-icon-circle">✓</span> WebP Image Compression</li>
                            <li><span class="check-icon-circle">✓</span> Liquid Code Optimization</li>
                            <li><span class="check-icon-circle">✓</span> Zero layout breakage guarantee</li>
                        </ul>
                    </div>
                    <div>
                        <a href="https://wpthrust.in/#schedule" class="btn-choose">Choose Shopify Starter →</a>
                        <div class="card-guarantee">🔥 100% SATISFACTION GUARANTEED</div>
                    </div>
                </div>

                <!-- Shopify Pro Ultra -->
                <div class="pricing-card">
                    <div class="popular-ribbon">POPULAR</div>
                    <div>
                        <h3 class="card-title">Shopify Pro Ultra</h3>
                        <p class="card-subtitle">Maximum speed optimization for high-traffic E-Commerce Shopify stores.</p>
                        <div class="price-box">
                            <span class="price-number">279<span class="price-symbol">$</span></span>
                            <div class="price-fee-label">One Time Fee</div>
                        </div>
                        <ul class="features-list">
                            <li><span class="check-icon-circle">✓</span> 90+ Shopify Mobile Speed Guarantee</li>
                            <li><span class="check-icon-circle">✓</span> Instant Page Loading (InstantClick)</li>
                            <li><span class="check-icon-circle">✓</span> Lazy loading & Font Preloading</li>
                            <li><span class="check-icon-circle">✓</span> 3rd Party Tracking Delay Tuning</li>
                            <li><span class="check-icon-circle">✓</span> 30 Days Speed Monitoring Support</li>
                        </ul>
                    </div>
                    <div>
                        <a href="https://wpthrust.in/#schedule" class="btn-choose">Choose Shopify Pro →</a>
                        <div class="card-guarantee">🔥 90+ SPEED SCORE GUARANTEED</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 3: PHP & CUSTOM CODE SPEED -->
        <section id="php" class="service-section">
            <div class="section-header">
                <div class="section-badge">🐘 Custom Web Apps</div>
                <h2 class="section-title">PHP & Custom Code Speed Optimization</h2>
                <p class="section-desc">Server-level tuning, SQL indexing, and code optimization for custom PHP, CodeIgniter & Laravel apps.</p>
            </div>

            <div class="pricing-grid">
                <!-- Custom PHP Standard -->
                <div class="pricing-card">
                    <div>
                        <h3 class="card-title">PHP Standard</h3>
                        <p class="card-subtitle">Optimizing custom PHP scripts, CodeIgniter, and custom web apps.</p>
                        <div class="price-box">
                            <span class="price-number">199<span class="price-symbol">$</span></span>
                            <div class="price-fee-label">One Time Fee</div>
                        </div>
                        <ul class="features-list">
                            <li><span class="check-icon-circle">✓</span> Under 1.5s TTFB Server Tuning</li>
                            <li><span class="check-icon-circle">✓</span> MySQL Query Indexing & Cleanup</li>
                            <li><span class="check-icon-circle">✓</span> Gzip & Brotli Compression</li>
                            <li><span class="check-icon-circle">✓</span> OPcache & PHP-FPM Configuration</li>
                            <li><span class="check-icon-circle">✓</span> Asset Minification & Bundling</li>
                        </ul>
                    </div>
                    <div>
                        <a href="https://wpthrust.in/#schedule" class="btn-choose">Choose PHP Standard →</a>
                        <div class="card-guarantee">🔥 100% MONEY-BACK GUARANTEE</div>
                    </div>
                </div>

                <!-- PHP Enterprise -->
                <div class="pricing-card">
                    <div class="popular-ribbon">POPULAR</div>
                    <div>
                        <h3 class="card-title">PHP Enterprise</h3>
                        <p class="card-subtitle">High-scale optimization for Laravel, Symfony, and custom SAAS platforms.</p>
                        <div class="price-box">
                            <span class="price-number">399<span class="price-symbol">$</span></span>
                            <div class="price-fee-label">One Time Fee</div>
                        </div>
                        <ul class="features-list">
                            <li><span class="check-icon-circle">✓</span> Redis / Memcached Caching Setup</li>
                            <li><span class="check-icon-circle">✓</span> Nginx / Apache HTTP/2 & SSL Tuning</li>
                            <li><span class="check-icon-circle">✓</span> Heavy SQL Query Optimization</li>
                            <li><span class="check-icon-circle">✓</span> 95+ PageSpeed Score Guarantee</li>
                            <li><span class="check-icon-circle">✓</span> Load Balancer & CDN Acceleration</li>
                        </ul>
                    </div>
                    <div>
                        <a href="https://wpthrust.in/#schedule" class="btn-choose">Choose PHP Enterprise →</a>
                        <div class="card-guarantee">🔥 95+ SPEED SCORE GUARANTEED</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 4: OTHER CMS -->
        <section id="other-cms" class="service-section">
            <div class="section-header">
                <div class="section-badge">⚡ Magento & WooCommerce</div>
                <h2 class="section-title">Other CMS & Platform Optimization</h2>
                <p class="section-desc">Dedicated acceleration for Magento, WooCommerce, Webflow, and PrestaShop stores.</p>
            </div>

            <div class="pricing-grid">
                <div class="pricing-card">
                    <div class="popular-ribbon">POPULAR</div>
                    <div>
                        <h3 class="card-title">E-Commerce Turbo</h3>
                        <p class="card-subtitle">Performance tuning for WooCommerce, Magento, PrestaShop & Webflow.</p>
                        <div class="price-box">
                            <span class="price-number">249<span class="price-symbol">$</span></span>
                            <div class="price-fee-label">One Time Fee</div>
                        </div>
                        <ul class="features-list">
                            <li><span class="check-icon-circle">✓</span> 90+ Mobile & Desktop Score</li>
                            <li><span class="check-icon-circle">✓</span> Cart & Checkout Speed Acceleration</li>
                            <li><span class="check-icon-circle">✓</span> Fragment Caching & Session Tuning</li>
                            <li><span class="check-icon-circle">✓</span> Image Optimization & Lazyload</li>
                            <li><span class="check-icon-circle">✓</span> 100% Risk Free Money-Back Guarantee</li>
                        </ul>
                    </div>
                    <div>
                        <a href="https://wpthrust.in/#schedule" class="btn-choose">Choose E-Commerce →</a>
                        <div class="card-guarantee">🔥 90+ SPEED SCORE GUARANTEED</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 5: CONTENT WRITING & COPYWRITING -->
        <section id="content-writing" class="service-section">
            <div class="section-header">
                <div class="section-badge">✍️ SEO Copywriting</div>
                <h2 class="section-title">Content Writing & SEO Copywriting</h2>
                <p class="section-desc">High-ranking blog articles and conversion-focused copywriting crafted to boost sales and organic Google traffic.</p>
            </div>

            <div class="pricing-grid">
                <!-- Blog Content Writing -->
                <div class="pricing-card">
                    <div>
                        <h3 class="card-title">Blog & Articles</h3>
                        <p class="card-subtitle">SEO-optimized articles designed to rank high and drive organic traffic.</p>
                        <div class="price-box">
                            <span class="price-number">0.05<span class="price-symbol">$</span></span>
                            <div class="price-fee-label">Per Word</div>
                        </div>
                        <ul class="features-list">
                            <li><span class="check-icon-circle">✓</span> 100% Original & Plagiarism-Free</li>
                            <li><span class="check-icon-circle">✓</span> SurferSEO / Yoast Optimization</li>
                            <li><span class="check-icon-circle">✓</span> In-depth Topic Research</li>
                            <li><span class="check-icon-circle">✓</span> Royalty-Free Header Images</li>
                            <li><span class="check-icon-circle">✓</span> Free Unlimited Revisions</li>
                        </ul>
                    </div>
                    <div>
                        <a href="https://wpthrust.in/#schedule" class="btn-choose">Order Content →</a>
                        <div class="card-guarantee">🔥 FAST 48-HOUR DELIVERY</div>
                    </div>
                </div>

                <!-- Website Copywriting -->
                <div class="pricing-card">
                    <div class="popular-ribbon">POPULAR</div>
                    <div>
                        <h3 class="card-title">Website Copywriting</h3>
                        <p class="card-subtitle">High-converting landing page copy, headlines, and call-to-actions.</p>
                        <div class="price-box">
                            <span class="price-number">0.08<span class="price-symbol">$</span></span>
                            <div class="price-fee-label">Per Word</div>
                        </div>
                        <ul class="features-list">
                            <li><span class="check-icon-circle">✓</span> Conversion Rate Optimized (CRO)</li>
                            <li><span class="check-icon-circle">✓</span> Engaging Headlines & Subheadings</li>
                            <li><span class="check-icon-circle">✓</span> Competitor Research & Positioning</li>
                            <li><span class="check-icon-circle">✓</span> Formatted & Ready to Paste</li>
                            <li><span class="check-icon-circle">✓</span> Dedicated Copywriter Support</li>
                        </ul>
                    </div>
                    <div>
                        <a href="https://wpthrust.in/#schedule" class="btn-choose">Order Copywriting →</a>
                        <div class="card-guarantee">🔥 HIGH CONVERSION GUARANTEE</div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- FAQ Section -->
    <section class="faq-section">
        <h2 class="faq-title">Frequently Asked Questions</h2>
        <div class="faq-grid">
            <div class="faq-item">
                <div class="faq-q">1. What is your 90+ Speed Guarantee?</div>
                <div class="faq-a">We guarantee your website will achieve a 90+ score on Google PageSpeed Insights (Mobile & Desktop). If we fail to reach the agreed performance target, we provide a 100% money-back refund.</div>
            </div>

            <div class="faq-item">
                <div class="faq-q">2. Will optimization break my website design, fonts, or layout?</div>
                <div class="faq-a">No, absolutely not! We perform all speed optimizations carefully on a staging environment first. We test all interactive elements, checkout forms, and media layouts to ensure 100% functional and visual preservation.</div>
            </div>

            <div class="faq-item">
                <div class="faq-q">3. How long does the optimization process take?</div>
                <div class="faq-a">Most WordPress, Shopify, and custom PHP websites are fully optimized within 24 to 48 hours after login access is provided to our performance engineers.</div>
            </div>

            <div class="faq-item">
                <div class="faq-q">4. Do I need to buy expensive plugins or pay recurring monthly fees?</div>
                <div class="faq-a">No! All our speed optimization packages are a one-time fee. We configure lifetime premium caching and optimization tools so you never have to pay monthly subscriptions for speed plugins.</div>
            </div>

            <div class="faq-item">
                <div class="faq-q">5. Do you optimize custom PHP, Laravel, and E-Commerce stores like WooCommerce & Shopify?</div>
                <div class="faq-a">Yes! We specialize in custom PHP, CodeIgniter, Laravel, Shopify, Magento, and WooCommerce stores. We optimize heavy database queries, liquid/PHP code execution, image compression, and server-level caching.</div>
            </div>

            <div class="faq-item">
                <div class="faq-q">6. What access details do you need to start optimization?</div>
                <div class="faq-a">We only require temporary Administrator access to your website dashboard (e.g., WordPress or Shopify admin) and cPanel / Hosting access if server-level caching or CDN setup is required.</div>
            </div>

            <div class="faq-item">
                <div class="faq-q">7. How does website speed affect my Google SEO rankings & sales?</div>
                <div class="faq-a">Google explicitly uses Core Web Vitals (LCP, INP, CLS) as an essential search ranking factor. Faster websites rank higher on search engines, reduce bounce rates, and significantly increase ecommerce conversion rates.</div>
            </div>

            <div class="faq-item">
                <div class="faq-q">8. What is included in your Content Writing & Copywriting services?</div>
                <div class="faq-a">Our content services include 100% original, plagiarism-free, SurferSEO-optimized blog posts and high-converting landing page copy written by experienced writers, including competitor research and formatting.</div>
            </div>

            <div class="faq-item">
                <div class="faq-q">9. What happens if my site speed slows down in the future after updates?</div>
                <div class="faq-a">All our packages include 30 days of complimentary post-optimization support and speed monitoring. If a new plugin or update slows down your site, our team will fine-tune it for free.</div>
            </div>

            <div class="faq-item">
                <div class="faq-q">10. Is my website data and customer information safe during optimization?</div>
                <div class="faq-a">Yes, 100%. We adhere to strict data protection standards and never touch or store private customer data. We always recommend creating a backup and updating credentials after completion.</div>
            </div>
        </div>
    </section>

</div>

<?php 
// Load WordPress Default Site Footer
get_footer(); 
?>
