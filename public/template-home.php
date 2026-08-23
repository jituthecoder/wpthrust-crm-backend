<?php
/**
 * Template Name: Modern Homepage Template
 * Description: Luxury, high-converting homepage template for WPThrust speed optimization agency.
 * 
 * Instructions:
 * 1. Save this file as `template-home.php` inside your WordPress Child Theme directory:
 *    `wp-content/themes/your-child-theme-name/template-home.php`
 * 2. In WordPress Admin -> Pages -> Edit Homepage (or Add New Page "Home").
 * 3. On the right panel under Page Attributes -> Template, select "Modern Homepage Template".
 * 4. In Settings -> Reading -> Front page displays -> Select a static page -> Choose "Home".
 * 5. Publish!
 */

get_header(); 
?>

<!-- Embedded CSS for Modern WPThrust Homepage -->
<style>
/* ==========================================================================
   WPThrust Luxury Agency Design System Tokens
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
    --card-shadow: 0 20px 45px rgba(0, 163, 137, 0.12), 0 4px 14px rgba(15, 23, 42, 0.04);
    --card-shadow-hover: 0 25px 60px rgba(0, 163, 137, 0.2), 0 8px 20px rgba(15, 23, 42, 0.08);
    --border-radius: 20px;
    --font-family: 'Outfit', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

html {
    scroll-behavior: smooth;
}

.wpthrust-home-wrapper {
    font-family: var(--font-family);
    background: var(--bg-gradient);
    color: var(--text-dark);
    line-height: 1.6;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
}

.wpthrust-home-wrapper a {
    text-decoration: none;
    color: inherit;
}

.container-custom {
    max-width: 1140px;
    margin: 0 auto;
    padding: 0 20px;
}

/* ==========================================================================
   1. HERO SECTION (Clean Agency Hero - No PSI Tool Here)
   ========================================================================== */
.hero-wrapper {
    padding: 85px 20px 70px 20px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.hero-wrapper::before {
    content: '';
    position: absolute;
    top: -100px;
    left: 50%;
    transform: translateX(-50%);
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(0, 163, 137, 0.12) 0%, rgba(255, 255, 255, 0) 70%);
    pointer-events: none;
    z-index: 0;
}

.hero-content {
    position: relative;
    z-index: 1;
    max-width: 900px;
    margin: 0 auto;
}

.hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #ffffff;
    color: var(--primary-teal);
    border: 1px solid rgba(0, 163, 137, 0.3);
    padding: 8px 22px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 24px;
    box-shadow: 0 4px 15px rgba(0, 163, 137, 0.1);
}

.hero-heading {
    font-size: 52px;
    font-weight: 800;
    color: var(--navy-dark);
    letter-spacing: -1.2px;
    line-height: 1.15;
    margin-bottom: 20px;
}

.hero-heading span.teal-highlight {
    color: var(--primary-teal);
    position: relative;
}

.hero-heading span.teal-highlight::after {
    content: '';
    position: absolute;
    bottom: 2px;
    left: 0;
    right: 0;
    height: 8px;
    background: rgba(0, 163, 137, 0.18);
    border-radius: 4px;
    z-index: -1;
}

.hero-subtext {
    font-size: 19px;
    color: var(--text-muted);
    max-width: 720px;
    margin: 0 auto 36px auto;
    line-height: 1.55;
}

.hero-actions {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 48px;
}

.btn-hero-primary {
    background: linear-gradient(135deg, #00a389, #008872);
    color: #ffffff !important;
    font-size: 17px;
    font-weight: 700;
    padding: 16px 34px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 163, 137, 0.35);
    transition: all 0.25s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-hero-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 32px rgba(0, 163, 137, 0.45);
    background: linear-gradient(135deg, #008872, #007360);
}

.btn-hero-secondary {
    background: #ffffff;
    color: var(--navy-dark) !important;
    font-size: 16px;
    font-weight: 700;
    padding: 15px 30px;
    border-radius: 12px;
    border: 1.5px solid #cbd5e1;
    transition: all 0.25s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

.btn-hero-secondary:hover {
    background: #f8fafc;
    border-color: var(--navy-dark);
    transform: translateY(-2px);
}

/* Hero Stats Counter Bar */
.hero-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    background: #ffffff;
    padding: 26px 30px;
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    border: 1px solid rgba(226, 232, 240, 0.8);
    max-width: 960px;
    margin: 0 auto;
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 28px;
    font-weight: 800;
    color: var(--navy-dark);
    line-height: 1.1;
}

.stat-value span {
    color: var(--primary-teal);
}

.stat-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 4px;
}

/* ==========================================================================
   2. SPEED CHECKER SHORTCODE SECTION
   ========================================================================== */
.psi-shortcode-container {
    padding: 60px 0;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}

/* ==========================================================================
   3. BEFORE VS AFTER COMPARISON SECTION
   ========================================================================== */
.comparison-section {
    padding: 85px 0;
}

.section-title-center {
    text-align: center;
    max-width: 700px;
    margin: 0 auto 50px auto;
}

.section-badge-pill {
    display: inline-block;
    background: #e6f6f3;
    color: var(--primary-teal);
    font-size: 13px;
    font-weight: 700;
    padding: 5px 16px;
    border-radius: 50px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

.section-main-heading {
    font-size: 36px;
    font-weight: 800;
    color: var(--navy-dark);
    letter-spacing: -0.5px;
    margin-bottom: 12px;
}

.section-main-sub {
    font-size: 16px;
    color: var(--text-muted);
}

.comparison-card-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 32px;
    max-width: 920px;
    margin: 0 auto;
}

.comp-card {
    border-radius: var(--border-radius);
    padding: 36px 30px;
    position: relative;
    overflow: hidden;
}

.comp-card.before {
    background: #fff5f5;
    border: 1px solid #fecaca;
}

.comp-card.after {
    background: #f0fdf4;
    border: 2px solid var(--primary-teal);
    box-shadow: 0 15px 40px rgba(0, 163, 137, 0.18);
}

.comp-card-badge {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 4px 14px;
    border-radius: 50px;
    display: inline-block;
    margin-bottom: 18px;
}

.before .comp-card-badge { background: #fee2e2; color: #dc2626; }
.after .comp-card-badge { background: #dcfce7; color: #15803d; }

.comp-score-large {
    font-size: 64px;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 16px;
}

.before .comp-score-large { color: #dc2626; }
.after .comp-score-large { color: var(--primary-teal); }

.comp-metrics-list {
    list-style: none;
    padding: 0;
}

.comp-metrics-list li {
    font-size: 15px;
    font-weight: 600;
    padding: 10px 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    display: flex;
    justify-content: space-between;
}

/* ==========================================================================
   4. SERVICES SHOWCASE SECTION
   ========================================================================== */
.services-section {
    padding: 85px 0;
    background: #ffffff;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 30px;
}

.service-box {
    background: #f8fafc;
    border-radius: var(--border-radius);
    padding: 36px 28px;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.service-box:hover {
    background: #ffffff;
    transform: translateY(-6px);
    box-shadow: var(--card-shadow);
    border-color: rgba(0, 163, 137, 0.3);
}

.service-icon-box {
    width: 52px;
    height: 52px;
    background: var(--primary-teal-light);
    color: var(--primary-teal);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 20px;
}

.service-box-title {
    font-size: 22px;
    font-weight: 800;
    color: var(--navy-dark);
    margin-bottom: 10px;
}

.service-box-text {
    font-size: 14.5px;
    color: var(--text-muted);
    line-height: 1.55;
    margin-bottom: 20px;
}

.service-box-link {
    font-size: 15px;
    font-weight: 700;
    color: var(--primary-teal);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* ==========================================================================
   5. 4-STEP HOW IT WORKS PROCESS
   ========================================================================== */
.process-section {
    padding: 85px 0;
}

.process-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}

.process-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 30px 22px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    position: relative;
}

.process-step-num {
    font-size: 14px;
    font-weight: 800;
    color: var(--primary-teal);
    background: var(--primary-teal-light);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}

.process-card-title {
    font-size: 18px;
    font-weight: 800;
    color: var(--navy-dark);
    margin-bottom: 8px;
}

.process-card-text {
    font-size: 13.5px;
    color: var(--text-muted);
    line-height: 1.5;
}

/* ==========================================================================
   6. LEAD CAPTURE & CONSULTATION CTA SECTION
   ========================================================================== */
.cta-banner-wrapper {
    padding: 70px 0;
}

.cta-banner-box {
    background: linear-gradient(135deg, var(--navy-dark) 0%, #1e293b 100%);
    border-radius: 24px;
    padding: 55px 40px;
    color: #ffffff;
    text-align: center;
    box-shadow: 0 20px 50px rgba(11, 19, 42, 0.3);
    position: relative;
    overflow: hidden;
}

.cta-banner-box::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 250px;
    height: 250px;
    background: radial-gradient(circle, rgba(0, 163, 137, 0.3) 0%, transparent 70%);
    pointer-events: none;
}

.cta-title {
    font-size: 36px;
    font-weight: 800;
    margin-bottom: 14px;
    letter-spacing: -0.5px;
}

.cta-subtitle {
    font-size: 17px;
    color: #cbd5e1;
    max-width: 620px;
    margin: 0 auto 30px auto;
}

/* ==========================================================================
   7. REVIEWS & TESTIMONIALS
   ========================================================================== */
.testimonials-section {
    padding: 85px 0;
    background: #ffffff;
}

.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 28px;
}

.testi-card {
    background: #f8fafc;
    border-radius: 16px;
    padding: 28px 24px;
    border: 1px solid #e2e8f0;
}

.testi-stars {
    color: #f59e0b;
    font-size: 16px;
    margin-bottom: 14px;
}

.testi-text {
    font-size: 14.5px;
    color: #334155;
    line-height: 1.6;
    margin-bottom: 18px;
    font-style: italic;
}

.testi-author {
    font-size: 15px;
    font-weight: 700;
    color: var(--navy-dark);
}

.testi-role {
    font-size: 13px;
    color: var(--primary-teal);
    font-weight: 600;
}

/* Responsive */
@media (max-width: 992px) {
    .hero-heading { font-size: 40px; }
    .hero-stats-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }
    .comparison-card-grid { grid-template-columns: 1fr; }
    .process-grid { grid-template-columns: repeat(2, 1fr); }
    .testimonials-grid { grid-template-columns: 1fr; }
}

@media (max-width: 576px) {
    .hero-heading { font-size: 32px; }
    .hero-subtext { font-size: 16px; }
    .btn-hero-primary, .btn-hero-secondary { width: 100%; justify-content: center; }
    .process-grid { grid-template-columns: 1fr; }
}
</style>

<div class="wpthrust-home-wrapper">

    <!-- 1. HERO SECTION (Clean Agency Hero - No Speed Checker Here) -->
    <section class="hero-wrapper">
        <div class="hero-content">
            <div class="hero-pill">
                <span>⚡ #1 WordPress & E-Commerce Speed Agency</span>
            </div>

            <h1 class="hero-heading">
                Turn Slow Websites Into <span class="teal-highlight">Lightning-Fast</span> Revenue Machines
            </h1>

            <p class="hero-subtext">
                We optimize WordPress, Shopify, and custom PHP websites to <strong>90+ Google PageSpeed Insights</strong> scores. Guaranteed speed result or 100% money back.
            </p>

            <div class="hero-actions">
                <a href="#speed-tool" class="btn-hero-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                    <span>Test Your Site Speed</span>
                </a>

                <a href="https://wpthrust.in/#schedule" class="btn-hero-secondary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span>Schedule Free Call</span>
                </a>
            </div>

            <!-- Stats Bar -->
            <div class="hero-stats-grid">
                <div class="stat-item">
                    <div class="stat-value">90<span>+</span></div>
                    <div class="stat-label">PageSpeed Score</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">2,500<span>+</span></div>
                    <div class="stat-label">Sites Optimized</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">&lt; 1.2<span>s</span></div>
                    <div class="stat-label">Avg Load Time</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">100<span>%</span></div>
                    <div class="stat-label">Money-Back Guarantee</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. SPEED CHECKER SHORTCODE SECTION -->
    <section id="speed-tool" class="psi-shortcode-container">
        <div class="container-custom">
            <?php 
            // Renders your custom PSI Speed Checker shortcode widget
            echo do_shortcode('[wpthrust_speed_checker]'); 
            ?>
        </div>
    </section>

    <!-- 3. BEFORE VS AFTER COMPARISON SECTION -->
    <section class="comparison-section">
        <div class="container-custom">
            <div class="section-title-center">
                <div class="section-badge-pill">Proven Results</div>
                <h2 class="section-main-heading">Real Speed Performance Before & After</h2>
                <p class="section-main-sub">See how our performance engineering transforms slow loading sites into instant loading powerhouses.</p>
            </div>

            <div class="comparison-card-grid">
                <!-- BEFORE CARD -->
                <div class="comp-card before">
                    <span class="comp-card-badge">⚠️ Before Optimization</span>
                    <div class="comp-score-large">34/100</div>
                    <ul class="comp-metrics-list">
                        <li><span>Mobile Speed Score:</span> <strong>34 (Poor)</strong></li>
                        <li><span>Largest Contentful Paint (LCP):</span> <strong>5.8 seconds</strong></li>
                        <li><span>Total Blocking Time (TBT):</span> <strong>1,450 ms</strong></li>
                        <li><span>Core Web Vitals Status:</span> <strong>FAILED</strong></li>
                    </ul>
                </div>

                <!-- AFTER CARD -->
                <div class="comp-card after">
                    <span class="comp-card-badge">🚀 After WPThrust Tuning</span>
                    <div class="comp-score-large">98/100</div>
                    <ul class="comp-metrics-list">
                        <li><span>Mobile Speed Score:</span> <strong>98 (Lightning Fast)</strong></li>
                        <li><span>Largest Contentful Paint (LCP):</span> <strong>0.9 seconds</strong></li>
                        <li><span>Total Blocking Time (TBT):</span> <strong>40 ms</strong></li>
                        <li><span>Core Web Vitals Status:</span> <strong>PASSED ✅</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. SERVICES SHOWCASE SECTION -->
    <section class="services-section">
        <div class="container-custom">
            <div class="section-title-center">
                <div class="section-badge-pill">What We Do</div>
                <h2 class="section-main-heading">Specialized Speed & Content Services</h2>
                <p class="section-main-sub">End-to-end performance tuning and content creation tailored for your exact web platform.</p>
            </div>

            <div class="services-grid">
                <!-- Service 1: WordPress -->
                <div class="service-box">
                    <div>
                        <div class="service-icon-box">🔵</div>
                        <h3 class="service-box-title">WordPress Speed</h3>
                        <p class="service-box-text">Database query cleanup, asset minification, font preloading, and lifetime caching plugin configuration.</p>
                    </div>
                    <a href="pricing.html#wordpress" class="service-box-link">View WordPress Pricing →</a>
                </div>

                <!-- Service 2: Shopify -->
                <div class="service-box">
                    <div>
                        <div class="service-icon-box">🟢</div>
                        <h3 class="service-box-title">Shopify Acceleration</h3>
                        <p class="service-box-text">Eliminate app script bloat, optimize Liquid theme code, and enable instant product page preloading.</p>
                    </div>
                    <a href="pricing.html#shopify" class="service-box-link">View Shopify Pricing →</a>
                </div>

                <!-- Service 3: PHP & Custom -->
                <div class="service-box">
                    <div>
                        <div class="service-icon-box">🐘</div>
                        <h3 class="service-box-title">PHP & Custom Code</h3>
                        <p class="service-box-text">Server-level tuning (Nginx/Apache), OPcache, Redis caching, and SQL query indexing for custom web apps.</p>
                    </div>
                    <a href="pricing.html#php" class="service-box-link">View Custom PHP Pricing →</a>
                </div>

                <!-- Service 4: WooCommerce & Magento -->
                <div class="service-box">
                    <div>
                        <div class="service-icon-box">⚡</div>
                        <h3 class="service-box-title">E-Commerce Turbo</h3>
                        <p class="service-box-text">Cart & checkout speed optimization for WooCommerce, Magento, PrestaShop, and Webflow stores.</p>
                    </div>
                    <a href="pricing.html#other-cms" class="service-box-link">View E-Commerce Pricing →</a>
                </div>

                <!-- Service 5: Content Writing -->
                <div class="service-box">
                    <div>
                        <div class="service-icon-box">✍️</div>
                        <h3 class="service-box-title">SEO Content Writing</h3>
                        <p class="service-box-text">100% original, SurferSEO-optimized blog posts and high-converting landing page copywriting.</p>
                    </div>
                    <a href="pricing.html#content-writing" class="service-box-link">View Content Pricing →</a>
                </div>

                <!-- Service 6: Core Web Vitals Fix -->
                <div class="service-box">
                    <div>
                        <div class="service-icon-box">🎯</div>
                        <h3 class="service-box-title">Core Web Vitals Pass</h3>
                        <p class="service-box-text">Fix LCP, INP, and CLS layout shifts to pass Google's official Search Console performance audit.</p>
                    </div>
                    <a href="pricing.html" class="service-box-link">Learn More →</a>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. 4-STEP PROCESS SECTION -->
    <section class="process-section">
        <div class="container-custom">
            <div class="section-title-center">
                <div class="section-badge-pill">How It Works</div>
                <h2 class="section-main-heading">Our Guaranteed 4-Step Process</h2>
                <p class="section-main-sub">Simple, risk-free performance engineering with zero downtime or design breakage.</p>
            </div>

            <div class="process-grid">
                <div class="process-card">
                    <div class="process-step-num">1</div>
                    <h3 class="process-card-title">Staging Clone Setup</h3>
                    <p class="process-card-text">We create a safe staging copy of your site so your live store remains 100% untouched.</p>
                </div>

                <div class="process-card">
                    <div class="process-step-num">2</div>
                    <h3 class="process-card-title">Deep Optimization</h3>
                    <p class="process-card-text">Our engineers optimize scripts, database queries, images, fonts, and server caching.</p>
                </div>

                <div class="process-card">
                    <div class="process-step-num">3</div>
                    <h3 class="process-card-title">Verification & Audit</h3>
                    <p class="process-card-text">We run PageSpeed tests across mobile & desktop to confirm 90+ score delivery.</p>
                </div>

                <div class="process-card">
                    <div class="process-step-num">4</div>
                    <h3 class="process-card-title">Push to Live</h3>
                    <p class="process-card-text">Once you approve the speed result, we push changes live seamlessly without downtime!</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. LEAD CAPTURE / CONSULTATION BANNER CTA -->
    <section class="cta-banner-wrapper">
        <div class="container-custom">
            <div class="cta-banner-box">
                <h2 class="cta-title">Ready to Make Your Website 3x Faster?</h2>
                <p class="cta-subtitle">Get your free website performance analysis and custom speed roadmap today.</p>
                <div class="hero-actions" style="margin-bottom: 0;">
                    <a href="https://wpthrust.in/#schedule" class="btn-hero-primary">
                        <span>Schedule a Free Strategy Call</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- 7. REVIEWS & TESTIMONIALS -->
    <section class="testimonials-section">
        <div class="container-custom">
            <div class="section-title-center">
                <div class="section-badge-pill">Client Feedback</div>
                <h2 class="section-main-heading">What Our Clients Say</h2>
            </div>

            <div class="testimonials-grid">
                <div class="testi-card">
                    <div class="testi-stars">★★★★★</div>
                    <p class="testi-text">"WPThrust took our WooCommerce store from a 28 mobile score to 96! Sales increased by 35% in the first month alone."</p>
                    <div class="testi-author">David Miller</div>
                    <div class="testi-role">E-Commerce Store Owner</div>
                </div>

                <div class="testi-card">
                    <div class="testi-stars">★★★★★</div>
                    <p class="testi-text">"Super fast service! They optimized our custom PHP portal in under 24 hours with zero downtime or design glitches."</p>
                    <div class="testi-author">Sarah Jenkins</div>
                    <div class="testi-role">Marketing Director</div>
                </div>

                <div class="testi-card">
                    <div class="testi-stars">★★★★★</div>
                    <p class="testi-text">"Professional, fast, and transparent. The 90+ speed guarantee gave us complete confidence."</p>
                    <div class="testi-author">Rahul Verma</div>
                    <div class="testi-role">SaaS Founder</div>
                </div>
            </div>
        </div>
    </section>

</div>

<?php 
// Load WordPress Default Site Footer
get_footer(); 
?>
