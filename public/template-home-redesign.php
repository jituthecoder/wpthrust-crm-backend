<?php
/**
 * Template Name: WPThrust Modern Homepage Redesign
 * Description: Master-class, agency-grade homepage template for WPThrust Website Speed Optimization Agency.
 * 
 * Instructions:
 * 1. Save this file as `template-home-redesign.php` inside your WordPress Child Theme directory:
 *    `wp-content/themes/your-child-theme-name/template-home-redesign.php`
 * 2. In WordPress Admin -> Pages -> Edit Homepage (or Add New Page "Home").
 * 3. On the right panel under Page Attributes -> Template, choose "WPThrust Modern Homepage Redesign".
 * 4. In Settings -> Reading -> Front page displays -> Select a static page -> Choose "Home".
 * 5. Publish & enjoy your new high-converting homepage!
 */

// Load WordPress Default Site Header Navigation (Do Not Touch Header)
get_header(); 
?>

<!-- ==========================================================================
     WPThrust Redesign Master Stylesheet (Scoped to .wpthrust-home-redesign)
     ========================================================================== -->
<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap');

:root {
    --wp-teal: #00a389;
    --wp-teal-dark: #00846e;
    --wp-teal-light: #e6f6f3;
    --wp-navy: #0b132a;
    --wp-navy-light: #1e293b;
    --wp-text: #0f172a;
    --wp-muted: #64748b;
    --wp-bg-light: #f8fafc;
    --wp-gradient-bg: linear-gradient(135deg, #f0fdfa 0%, #f8fafc 45%, #eff6ff 100%);
    --wp-shadow-sm: 0 4px 14px rgba(15, 23, 42, 0.04);
    --wp-shadow-md: 0 10px 25px rgba(0, 163, 137, 0.1), 0 4px 10px rgba(15, 23, 42, 0.03);
    --wp-shadow-lg: 0 22px 50px rgba(0, 163, 137, 0.16), 0 6px 18px rgba(15, 23, 42, 0.05);
    --wp-radius: 20px;
    --wp-font: 'Outfit', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

html {
    scroll-behavior: smooth;
    scroll-padding-top: 90px;
}

.wpthrust-home-redesign {
    font-family: var(--wp-font);
    background: var(--wp-gradient-bg);
    color: var(--wp-text);
    line-height: 1.6;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
}

.wpthrust-home-redesign * {
    box-sizing: border-box;
}

.wpthrust-home-redesign a {
    text-decoration: none;
    color: inherit;
    transition: all 0.25s ease;
}

.wp-container {
    max-width: 1180px;
    margin: 0 auto;
    padding: 0 24px;
}

/* Headings & Badges */
.wp-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--wp-teal-light);
    color: var(--wp-teal);
    border: 1px solid rgba(0, 163, 137, 0.28);
    padding: 6px 18px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 16px;
}

.wp-section-header {
    text-align: center;
    max-width: 760px;
    margin: 0 auto 48px auto;
}

.wp-section-header h2 {
    font-size: 38px;
    font-weight: 800;
    color: var(--wp-navy);
    letter-spacing: -0.8px;
    margin: 10px 0 14px 0;
    line-height: 1.22;
}

.wp-section-header p {
    font-size: 16.5px;
    color: var(--wp-muted);
    line-height: 1.6;
}

/* Buttons */
.wp-btn-primary {
    background: linear-gradient(135deg, #00a389, #00846e);
    color: #ffffff !important;
    font-size: 16.5px;
    font-weight: 700;
    padding: 16px 34px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 163, 137, 0.35);
    display: inline-flex;
    align-items: center;
    gap: 10px;
    border: none;
    cursor: pointer;
}

.wp-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(0, 163, 137, 0.45);
    background: linear-gradient(135deg, #00846e, #00705d);
}

.wp-btn-secondary {
    background: #ffffff;
    color: var(--wp-navy) !important;
    font-size: 16px;
    font-weight: 700;
    padding: 15px 30px;
    border-radius: 12px;
    border: 1.5px solid #cbd5e1;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.02);
}

.wp-btn-secondary:hover {
    background: #f8fafc;
    border-color: var(--wp-navy);
    transform: translateY(-2px);
}

/* ==========================================================================
   01. HERO SECTION (Redesigned with Premium Dashboard Visual)
   ========================================================================== */
.wp-hero-section {
    padding: 75px 0 65px 0;
    position: relative;
    overflow: hidden;
}

.wp-hero-grid {
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap: 48px;
    align-items: center;
}

.wp-hero-content h1 {
    font-size: 50px;
    font-weight: 800;
    color: var(--wp-navy);
    line-height: 1.14;
    letter-spacing: -1.2px;
    margin-bottom: 22px;
}

.wp-hero-content h1 span.highlight {
    color: var(--wp-teal);
    position: relative;
}

.wp-hero-desc {
    font-size: 19px;
    color: var(--wp-muted);
    line-height: 1.55;
    margin-bottom: 32px;
}

.wp-hero-ctas {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 36px;
}

.wp-hero-trust-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px 24px;
    list-style: none;
    padding: 0;
    margin: 0;
    border-top: 1px solid #e2e8f0;
    padding-top: 24px;
}

.wp-hero-trust-list li {
    font-size: 14px;
    font-weight: 600;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 8px;
}

.wp-hero-trust-list li span.check-icon {
    color: var(--wp-teal);
    font-weight: 800;
}

/* Hero Right Side Custom Performance Dashboard Visual */
.wp-hero-dashboard-mockup {
    background: #ffffff;
    border-radius: var(--wp-radius);
    padding: 24px;
    box-shadow: var(--wp-shadow-lg);
    border: 1px solid rgba(0, 163, 137, 0.2);
    position: relative;
}

.wp-browser-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
}

.wp-browser-dot {
    width: 11px;
    height: 11px;
    border-radius: 50%;
}

.wp-browser-dot.red { background: #ef4444; }
.wp-browser-dot.yellow { background: #f59e0b; }
.wp-browser-dot.green { background: #10b981; }

.wp-browser-url {
    background: #f1f5f9;
    border-radius: 6px;
    padding: 4px 14px;
    font-size: 12px;
    color: #64748b;
    font-family: monospace;
    flex-grow: 1;
    margin-left: 8px;
}

.wp-hero-score-ring {
    text-align: center;
    padding: 24px;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border-radius: 16px;
    border: 2px solid var(--wp-teal);
    margin-bottom: 20px;
}

.wp-hero-score-num {
    font-size: 76px;
    font-weight: 800;
    color: var(--wp-teal);
    line-height: 1;
}

.wp-hero-score-tag {
    font-size: 13px;
    font-weight: 800;
    color: #15803d;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-top: 6px;
}

.wp-hero-vitals-mini {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    text-align: center;
}

.wp-mini-vital {
    background: #f8fafc;
    border-radius: 10px;
    padding: 10px 8px;
    border: 1px solid #e2e8f0;
}

.wp-mini-vital-val {
    font-size: 15px;
    font-weight: 800;
    color: var(--wp-teal);
}

.wp-mini-vital-lbl {
    font-size: 11px;
    color: var(--wp-muted);
    font-weight: 600;
}

/* Floating Card Badge Overlay */
.wp-hero-floating-badge {
    position: absolute;
    bottom: -18px;
    left: -20px;
    background: var(--wp-navy);
    color: #ffffff;
    padding: 12px 20px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(11, 19, 42, 0.3);
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* ==========================================================================
   02. TRUST / STATS CARDS SECTION (With Hover Effects)
   ========================================================================== */
.wp-trust-cards-section {
    background: #ffffff;
    padding: 40px 0;
    border-top: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}

.wp-trust-cards-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}

.wp-trust-card-item {
    background: #f8fafc;
    border-radius: 16px;
    padding: 26px 20px;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
    text-align: center;
}

.wp-trust-card-item:hover {
    transform: translateY(-5px);
    background: #ffffff;
    box-shadow: var(--wp-shadow-md);
    border-color: rgba(0, 163, 137, 0.35);
}

.wp-trust-card-icon {
    font-size: 26px;
    margin-bottom: 10px;
}

.wp-trust-card-item h4 {
    font-size: 16.5px;
    font-weight: 800;
    color: var(--wp-navy);
    margin-bottom: 4px;
}

.wp-trust-card-item p {
    font-size: 13.5px;
    color: var(--wp-muted);
    margin: 0;
}

/* ==========================================================================
   03. WHY WEBSITE SPEED MATTERS (Split Layout)
   ========================================================================== */
.wp-why-matters-section {
    padding: 85px 0;
    background: #f8fafc;
}

.wp-why-matters-split {
    display: grid;
    grid-template-columns: 0.9fr 1.1fr;
    gap: 48px;
    align-items: center;
}

.wp-why-visual-box {
    background: #ffffff;
    border-radius: var(--wp-radius);
    padding: 36px 30px;
    box-shadow: var(--wp-shadow-md);
    border: 1px solid #e2e8f0;
}

.wp-why-metric-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid #f1f5f9;
}

.wp-why-metric-row:last-child {
    border-bottom: none;
}

.wp-why-matters-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.wp-matter-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 24px 20px;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.wp-matter-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--wp-shadow-sm);
}

.wp-matter-icon {
    font-size: 24px;
    margin-bottom: 10px;
}

.wp-matter-card h3 {
    font-size: 17px;
    font-weight: 800;
    color: var(--wp-navy);
    margin-bottom: 6px;
}

.wp-matter-card p {
    font-size: 13.5px;
    color: var(--wp-muted);
    line-height: 1.5;
    margin: 0;
}

/* ==========================================================================
   04. FREE SPEED TEST SECTION (No Duplicate Heading Above Shortcode!)
   ========================================================================== */
.wp-psi-checker-section {
    padding: 70px 0;
    background: linear-gradient(135deg, #e6f6f3 0%, #f0fdfa 100%);
    border-top: 1px solid rgba(0, 163, 137, 0.2);
    border-bottom: 1px solid rgba(0, 163, 137, 0.2);
}

/* ==========================================================================
   05. WHAT'S SLOWING YOUR WEBSITE DOWN? (Educational Diagnostic Bridge)
   ========================================================================== */
.wp-slow-causes-section {
    padding: 85px 0;
    background: #ffffff;
}

.wp-causes-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 28px;
}

.wp-cause-card {
    background: #f8fafc;
    border-radius: 16px;
    padding: 28px 24px;
    border: 1px solid #e2e8f0;
    border-top: 4px solid #ef4444;
}

.wp-cause-card h4 {
    font-size: 18px;
    font-weight: 800;
    color: var(--wp-navy);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.wp-cause-card p {
    font-size: 14px;
    color: var(--wp-muted);
    line-height: 1.55;
    margin: 0;
}

/* ==========================================================================
   06. WHY WPTHRUST? ("We Don't Just Install A Cache Plugin")
   ========================================================================== */
.wp-why-wpthrust-section {
    padding: 85px 0;
    background: linear-gradient(135deg, var(--wp-navy) 0%, #1e293b 100%);
    color: #ffffff;
}

.wp-why-wpthrust-section .wp-section-header h2 {
    color: #ffffff;
}

.wp-why-wpthrust-section .wp-section-header p {
    color: #cbd5e1;
}

.wp-approach-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.wp-approach-card {
    background: rgba(255, 255, 255, 0.06);
    border-radius: 16px;
    padding: 28px 22px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    transition: all 0.3s ease;
}

.wp-approach-card:hover {
    background: rgba(255, 255, 255, 0.12);
    transform: translateY(-4px);
}

.wp-approach-card h4 {
    font-size: 18px;
    font-weight: 800;
    color: var(--wp-teal);
    margin-bottom: 8px;
}

.wp-approach-card p {
    font-size: 14px;
    color: #cbd5e1;
    line-height: 1.5;
    margin: 0;
}

/* ==========================================================================
   07. BEFORE VS AFTER PERFORMANCE SECTION
   ========================================================================== */
.wp-before-after-section {
    padding: 85px 0;
    background: #ffffff;
}

.wp-comp-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 32px;
    max-width: 940px;
    margin: 0 auto;
}

.wp-comp-card {
    border-radius: var(--wp-radius);
    padding: 36px 30px;
}

.wp-comp-card.before {
    background: #fff5f5;
    border: 1px solid #fecaca;
}

.wp-comp-card.after {
    background: #f0fdf4;
    border: 2px solid var(--wp-teal);
    box-shadow: 0 15px 40px rgba(0, 163, 137, 0.18);
}

.wp-comp-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}

.wp-comp-badge {
    font-size: 12px;
    font-weight: 800;
    padding: 4px 14px;
    border-radius: 50px;
    text-transform: uppercase;
}

.before .wp-comp-badge { background: #fee2e2; color: #dc2626; }
.after .wp-comp-badge { background: #dcfce7; color: #15803d; }

.wp-comp-score {
    font-size: 64px;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 16px;
}

.before .wp-comp-score { color: #dc2626; }
.after .wp-comp-score { color: var(--wp-teal); }

.wp-comp-metrics {
    list-style: none;
    padding: 0;
    margin: 0;
}

.wp-comp-metrics li {
    font-size: 14.5px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    display: flex;
    justify-content: space-between;
    font-weight: 600;
}

/* ==========================================================================
   08. WHAT WE OPTIMIZE (Technical Stack)
   ========================================================================== */
.wp-optimize-tech-section {
    padding: 85px 0;
    background: #f8fafc;
}

.wp-tech-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.wp-tech-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 24px 20px;
    border: 1px solid #e2e8f0;
    border-top: 4px solid var(--wp-teal);
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}

.wp-tech-card h4 {
    font-size: 17px;
    font-weight: 800;
    color: var(--wp-navy);
    margin-bottom: 10px;
}

.wp-tech-card ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.wp-tech-card ul li {
    font-size: 13px;
    color: #475569;
    padding: 4px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}

.wp-tech-card ul li::before {
    content: "•";
    color: var(--wp-teal);
    font-weight: 800;
}

/* ==========================================================================
   09. SERVICES SECTION (Primary vs Supporting)
   ========================================================================== */
.wp-services-section {
    padding: 85px 0;
    background: #ffffff;
}

.wp-primary-service-hero {
    background: linear-gradient(135deg, var(--wp-navy) 0%, #1e293b 100%);
    border-radius: 24px;
    padding: 48px;
    color: #ffffff;
    margin-bottom: 40px;
    box-shadow: 0 20px 45px rgba(11, 19, 42, 0.25);
    border: 1px solid rgba(255,255,255,0.1);
}

.wp-primary-badge {
    background: var(--wp-teal);
    color: #ffffff;
    font-size: 12px;
    font-weight: 800;
    padding: 4px 14px;
    border-radius: 50px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    display: inline-block;
    margin-bottom: 16px;
}

.wp-primary-service-hero h3 {
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 14px;
}

.wp-primary-service-hero p {
    font-size: 16.5px;
    color: #cbd5e1;
    max-width: 780px;
    margin-bottom: 24px;
}

.wp-primary-features-tags {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 28px;
}

.wp-primary-features-tags span {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 600;
}

.wp-secondary-services-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.wp-secondary-card {
    background: #f8fafc;
    border-radius: 14px;
    padding: 24px 20px;
    border: 1px solid #e2e8f0;
    transition: all 0.25s ease;
}

.wp-secondary-card:hover {
    background: #ffffff;
    border-color: var(--wp-teal);
    transform: translateY(-4px);
    box-shadow: var(--wp-shadow-sm);
}

.wp-secondary-card h4 {
    font-size: 17px;
    font-weight: 800;
    color: var(--wp-navy);
    margin-bottom: 8px;
}

.wp-secondary-card p {
    font-size: 13.5px;
    color: var(--wp-muted);
    line-height: 1.5;
    margin-bottom: 14px;
}

.wp-secondary-card a {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--wp-teal);
}

/* ==========================================================================
   10. HOW WE OPTIMIZE YOUR WEBSITE (7-Step Timeline)
   ========================================================================== */
.wp-timeline-section {
    padding: 85px 0;
    background: #f8fafc;
}

.wp-timeline-list {
    display: grid;
    gap: 18px;
    max-width: 860px;
    margin: 0 auto;
}

.wp-timeline-step {
    background: #ffffff;
    border-radius: 14px;
    padding: 24px 28px;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: flex-start;
    gap: 20px;
}

.wp-step-number {
    font-size: 18px;
    font-weight: 800;
    color: var(--wp-teal);
    background: var(--wp-teal-light);
    padding: 8px 14px;
    border-radius: 10px;
    flex-shrink: 0;
}

.wp-step-details h4 {
    font-size: 18px;
    font-weight: 800;
    color: var(--wp-navy);
    margin-bottom: 6px;
}

.wp-step-details p {
    font-size: 14px;
    color: var(--wp-muted);
    margin: 0;
}

/* ==========================================================================
   11. CORE WEB VITALS SECTION
   ========================================================================== */
.wp-vitals-section {
    padding: 85px 0;
    background: #ffffff;
}

.wp-vitals-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.wp-vital-card {
    background: #f8fafc;
    border-radius: var(--wp-radius);
    padding: 30px 24px;
    border: 1px solid #e2e8f0;
    box-shadow: var(--wp-shadow-sm);
}

.wp-vital-code {
    font-size: 14px;
    font-weight: 800;
    color: var(--wp-teal);
    background: var(--wp-teal-light);
    padding: 4px 12px;
    border-radius: 50px;
    display: inline-block;
    margin-bottom: 14px;
}

.wp-vital-card h4 {
    font-size: 18px;
    font-weight: 800;
    color: var(--wp-navy);
    margin-bottom: 8px;
}

.wp-vital-card p {
    font-size: 13.5px;
    color: var(--wp-muted);
    line-height: 1.5;
    margin: 0;
}

/* ==========================================================================
   12. PLATFORMS WE OPTIMIZE
   ========================================================================== */
.wp-platforms-section {
    padding: 70px 0;
    background: #f8fafc;
}

.wp-platforms-flex {
    display: flex;
    justify-content: center;
    gap: 16px;
    flex-wrap: wrap;
}

.wp-platform-chip {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    padding: 10px 24px;
    border-radius: 50px;
    font-size: 14.5px;
    font-weight: 700;
    color: var(--wp-navy);
}

/* ==========================================================================
   13. SUPPORTING SERVICES
   ========================================================================== */
.wp-supporting-section {
    padding: 85px 0;
    background: #ffffff;
}

.wp-supporting-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 28px;
}

.wp-supporting-card {
    background: #f8fafc;
    border-radius: 16px;
    padding: 32px 26px;
    border: 1px solid #e2e8f0;
    box-shadow: var(--wp-shadow-sm);
}

.wp-supporting-card h4 {
    font-size: 20px;
    font-weight: 800;
    color: var(--wp-navy);
    margin-bottom: 10px;
}

.wp-supporting-card p {
    font-size: 14px;
    color: var(--wp-muted);
    line-height: 1.55;
    margin-bottom: 18px;
}

.wp-supporting-card a {
    font-size: 14px;
    font-weight: 700;
    color: var(--wp-teal);
}

/* ==========================================================================
   14. DYNAMIC BLOG SECTION
   ========================================================================== */
.wp-blog-section {
    padding: 85px 0;
    background: #f8fafc;
}

.wp-blog-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 28px;
}

.wp-blog-card {
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.wp-blog-img-box {
    height: 190px;
    background: #cbd5e1;
    overflow: hidden;
}

.wp-blog-img-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.wp-blog-body {
    padding: 22px 20px;
}

.wp-blog-cat {
    font-size: 12px;
    font-weight: 700;
    color: var(--wp-teal);
    text-transform: uppercase;
    margin-bottom: 6px;
}

.wp-blog-title {
    font-size: 17px;
    font-weight: 800;
    color: var(--wp-navy);
    margin-bottom: 10px;
    line-height: 1.4;
}

.wp-blog-excerpt {
    font-size: 13.5px;
    color: var(--wp-muted);
    margin-bottom: 16px;
}

.wp-blog-link {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--wp-teal);
}

/* ==========================================================================
   15. FAQ SECTION (17 Detailed FAQs)
   ========================================================================== */
.wp-faq-section {
    padding: 85px 0;
    background: #ffffff;
}

.wp-faq-list {
    max-width: 860px;
    margin: 0 auto;
    display: grid;
    gap: 16px;
}

.wp-faq-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 22px 26px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 10px rgba(0,0,0,0.02);
}

.wp-faq-card h4 {
    font-size: 16.5px;
    font-weight: 800;
    color: var(--wp-navy);
    margin-bottom: 8px;
}

.wp-faq-card p {
    font-size: 14px;
    color: var(--wp-muted);
    margin: 0;
    line-height: 1.6;
}

/* ==========================================================================
   16. FINAL CTA BANNER
   ========================================================================== */
.wp-final-cta-section {
    padding: 70px 0 90px 0;
    background: #f8fafc;
}

.wp-final-cta-box {
    background: linear-gradient(135deg, var(--wp-navy) 0%, #1e293b 100%);
    border-radius: 24px;
    padding: 55px 40px;
    color: #ffffff;
    text-align: center;
    box-shadow: 0 20px 50px rgba(11, 19, 42, 0.3);
}

.wp-final-cta-box h2 {
    font-size: 36px;
    font-weight: 800;
    margin-bottom: 14px;
}

.wp-final-cta-box p {
    font-size: 17px;
    color: #cbd5e1;
    max-width: 620px;
    margin: 0 auto 30px auto;
}

/* Elementor Form Placeholder Container */
.wpthrust-form-placeholder {
    max-width: 600px;
    margin: 20px auto 0 auto;
}

/* Responsive Breakpoints */
@media (max-width: 992px) {
    .wp-hero-grid { grid-template-columns: 1fr; }
    .wp-why-matters-split { grid-template-columns: 1fr; }
    .wp-trust-cards-grid { grid-template-columns: repeat(2, 1fr); }
    .wp-causes-grid { grid-template-columns: 1fr; }
    .wp-approach-grid { grid-template-columns: repeat(2, 1fr); }
    .wp-comp-grid { grid-template-columns: 1fr; }
    .wp-secondary-services-grid { grid-template-columns: repeat(2, 1fr); }
    .wp-tech-grid { grid-template-columns: repeat(2, 1fr); }
    .wp-vitals-grid { grid-template-columns: 1fr; }
    .wp-supporting-grid { grid-template-columns: 1fr; }
    .wp-blog-grid { grid-template-columns: 1fr; }
}

@media (max-width: 576px) {
    .wp-hero-content h1 { font-size: 34px; }
    .wp-trust-cards-grid { grid-template-columns: 1fr; }
    .wp-secondary-services-grid { grid-template-columns: 1fr; }
    .wp-tech-grid { grid-template-columns: 1fr; }
}
</style>

<div class="wpthrust-home-redesign">

    <!-- 01. HERO SECTION (Redesigned with Premium Dashboard Visual) -->
    <section class="wp-hero-section">
        <div class="wp-container">
            <div class="wp-hero-grid">
                
                <div class="wp-hero-content">
                    <div class="wp-badge">⚡ #1 Website Speed Optimization Agency</div>
                    <h1>Turn Your Slow Website Into A <span class="highlight">Lightning-Fast</span> Revenue Machine</h1>
                    <p class="wp-hero-desc">We optimize WordPress, WooCommerce, Shopify, and custom PHP websites for 90+ Google PageSpeed Insights & Core Web Vitals. $0 upfront cost, pay after delivery.</p>
                    
                    <div class="wp-hero-ctas">
                        <a href="#speed-tool" class="wp-btn-primary">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                            </svg>
                            <span>Get Your Free Speed Audit</span>
                        </a>

                        <a href="#how-we-optimize" class="wp-btn-secondary">
                            <span>See How It Works</span>
                        </a>
                    </div>

                    <ul class="wp-hero-trust-list">
                        <li><span class="check-icon">✓</span> 90+ PageSpeed Score Guarantee</li>
                        <li><span class="check-icon">✓</span> Passed Core Web Vitals</li>
                        <li><span class="check-icon">✓</span> 100% Staging-First Safety</li>
                        <li><span class="check-icon">✓</span> 100% Money-Back Guarantee</li>
                    </ul>
                </div>

                <!-- Custom Performance Dashboard Visual (Hero Right Side) -->
                <div class="wp-hero-dashboard-mockup">
                    <div class="wp-browser-header">
                        <div class="wp-browser-dot red"></div>
                        <div class="wp-browser-dot yellow"></div>
                        <div class="wp-browser-dot green"></div>
                        <div class="wp-browser-url">https://wpthrust.in/performance-report</div>
                    </div>

                    <div class="wp-hero-score-ring">
                        <div class="wp-hero-score-num">98</div>
                        <div class="wp-hero-score-tag">Mobile PageSpeed Result</div>
                    </div>

                    <div class="wp-hero-vitals-mini">
                        <div class="wp-mini-vital">
                            <div class="wp-mini-vital-val">0.9s</div>
                            <div class="wp-mini-vital-lbl">LCP (Fast)</div>
                        </div>
                        <div class="wp-mini-vital">
                            <div class="wp-mini-vital-val">35ms</div>
                            <div class="wp-mini-vital-lbl">INP (Fast)</div>
                        </div>
                        <div class="wp-mini-vital">
                            <div class="wp-mini-vital-val">0.00</div>
                            <div class="wp-mini-vital-lbl">CLS (Stable)</div>
                        </div>
                    </div>

                    <!-- Floating Badge Overlay -->
                    <div class="wp-hero-floating-badge">
                        <span>🚀 Core Web Vitals PASSED ✅</span>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- 02. TRUST / STATS CARDS SECTION (With Subtle Hover Animations) -->
    <section class="wp-trust-cards-section">
        <div class="wp-container">
            <div class="wp-trust-cards-grid">
                <div class="wp-trust-card-item">
                    <div class="wp-trust-card-icon">⚡</div>
                    <h4>Core Web Vitals Pass</h4>
                    <p>LCP, INP & CLS Optimization</p>
                </div>
                <div class="wp-trust-card-item">
                    <div class="wp-trust-card-icon">🔒</div>
                    <h4>100% Staging Safe</h4>
                    <p>Zero Live Site Downtime</p>
                </div>
                <div class="wp-trust-card-item">
                    <div class="wp-trust-card-icon">📱</div>
                    <h4>Mobile-First Tuning</h4>
                    <p>Optimized For Mobile Speed</p>
                </div>
                <div class="wp-trust-card-item">
                    <div class="wp-trust-card-icon">🛡️</div>
                    <h4>100% Money-Back</h4>
                    <p>Guaranteed 90+ Score Delivery</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 03. WHY WEBSITE SPEED MATTERS (Split Screen Layout) -->
    <section class="wp-why-matters-section">
        <div class="wp-container">
            <div class="wp-why-matters-split">
                
                <!-- Left Visual Box -->
                <div class="wp-why-visual-box">
                    <div class="wp-badge">Impact Breakdown</div>
                    <h3 style="font-weight: 800; color: var(--wp-navy); margin-bottom: 16px;">Speed Impacts Your Entire Business</h3>
                    
                    <div class="wp-why-metric-row">
                        <span style="font-weight:700;">Bounce Rate Increase (3s delay)</span>
                        <strong style="color: #ef4444;">+53%</strong>
                    </div>

                    <div class="wp-why-metric-row">
                        <span style="font-weight:700;">Conversion Loss Per Second</span>
                        <strong style="color: #ef4444;">-7%</strong>
                    </div>

                    <div class="wp-why-metric-row">
                        <span style="font-weight:700;">Google Search Ranking Signal</span>
                        <strong style="color: var(--wp-teal);">Core Web Vitals</strong>
                    </div>
                </div>

                <!-- Right Impact Grid -->
                <div>
                    <div class="wp-badge">Business Impact</div>
                    <h2 style="font-size: 34px; font-weight: 800; color: var(--wp-navy); margin-bottom: 14px;">Your Website Speed Is Affecting More Than You Think</h2>
                    <p style="color: var(--wp-muted); margin-bottom: 24px;">A slow website silently damages user experience, SEO rankings, and revenue every single day.</p>

                    <div class="wp-why-matters-grid">
                        <div class="wp-matter-card">
                            <div class="wp-matter-icon">📉</div>
                            <h3>Higher Bounce Rates</h3>
                            <p>53% of mobile users leave if load time exceeds 3s.</p>
                        </div>

                        <div class="wp-matter-card">
                            <div class="wp-matter-icon">💰</div>
                            <h3>Lost Conversions</h3>
                            <p>Fast checkout pages directly increase sales conversions.</p>
                        </div>

                        <div class="wp-matter-card">
                            <div class="wp-matter-icon">🔍</div>
                            <h3>Lower SEO Rankings</h3>
                            <p>Google suppresses slow sites that fail Core Web Vitals.</p>
                        </div>

                        <div class="wp-matter-card">
                            <div class="wp-matter-icon">📱</div>
                            <h3>Poor Mobile Speed</h3>
                            <p>Heavy unoptimized scripts create mobile device lag.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- 04. FREE SPEED TEST SECTION (Integrates Shortcode directly without duplicate heading!) -->
    <section id="speed-tool" class="wp-psi-checker-section">
        <div class="wp-container">
            <?php 
            // Renders your custom PSI Speed Checker shortcode widget directly (contains its own heading & UI!)
            echo do_shortcode('[wpthrust_speed_checker]'); 
            ?>
        </div>
    </section>

    <!-- 05. WHAT'S SLOWING YOUR WEBSITE DOWN? (Educational Diagnostic Bridge) -->
    <section class="wp-slow-causes-section">
        <div class="wp-container">
            <div class="wp-section-header">
                <div class="wp-badge">Root Cause Analysis</div>
                <h2>What's Slowing Your Website Down?</h2>
                <p>Common performance bottlenecks that prevent websites from loading instantly:</p>
            </div>

            <div class="wp-causes-grid">
                <div class="wp-cause-card">
                    <h4>🖼️ Heavy Uncompressed Images</h4>
                    <p>Large PNG/JPG files without WebP conversion or lazy loading stall page render time.</p>
                </div>

                <div class="wp-cause-card">
                    <h4>⚡ Render-Blocking JavaScript</h4>
                    <p>Un-deferred scripts block the main thread and freeze page responsiveness.</p>
                </div>

                <div class="wp-cause-card">
                    <h4>🎨 Unused & Unminified CSS</h4>
                    <p>Massive CSS files from heavy page builders cause slow layout rendering.</p>
                </div>

                <div class="wp-cause-card">
                    <h4>🖥️ Slow Server Response (TTFB)</h4>
                    <p>Unconfigured hosting servers without OPcache or Gzip compression delay initial connection.</p>
                </div>

                <div class="wp-cause-card">
                    <h4>🧩 Excessive Plugin Bloat</h4>
                    <p>Too many active plugins or un-indexed database tables slow down database queries.</p>
                </div>

                <div class="wp-cause-card">
                    <h4>🔤 Unoptimized Google Fonts</h4>
                    <p>Multiple external font requests without font-display:swap cause text rendering delays.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 06. WHY WPTHRUST? ("We Don't Just Install A Cache Plugin") -->
    <section class="wp-why-wpthrust-section">
        <div class="wp-container">
            <div class="wp-section-header">
                <div class="wp-badge" style="background: rgba(0, 163, 137, 0.2); color: #00d2b1;">Our Engineering Philosophy</div>
                <h2>We Don't Just Install A Caching Plugin</h2>
                <p>We perform deep code refactoring, database query indexing, and server-level optimization.</p>
            </div>

            <div class="wp-approach-grid">
                <div class="wp-approach-card">
                    <h4>01. Diagnose</h4>
                    <p>Deep audit of script execution, database queries, and server response logs.</p>
                </div>

                <div class="wp-approach-card">
                    <h4>02. Optimize</h4>
                    <p>Refactor core code, minify assets, defer JavaScript, and clean database tables.</p>
                </div>

                <div class="wp-approach-card">
                    <h4>03. Test</h4>
                    <p>Comprehensive functional and layout testing on a safe staging environment.</p>
                </div>

                <div class="wp-approach-card">
                    <h4>04. Verify</h4>
                    <p>Re-test on PageSpeed Insights & GTmetrix to confirm 90+ score delivery.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 07. BEFORE VS AFTER PERFORMANCE SECTION -->
    <section class="wp-before-after-section">
        <div class="wp-container">
            <div class="wp-section-header">
                <div class="wp-badge">Sample Performance Benchmark</div>
                <h2>Real Speed Performance Before & After Optimization</h2>
                <p>See how technical performance tuning transforms a sluggish website into an instant-loading conversion engine.</p>
            </div>

            <div class="wp-comp-grid">
                <!-- BEFORE CARD -->
                <div class="wp-comp-card before">
                    <div class="wp-comp-header">
                        <span class="wp-comp-badge">⚠️ Before Optimization</span>
                        <small style="color: #dc2626; font-weight:700;">Sample Benchmark</small>
                    </div>
                    <div class="wp-comp-score">34/100</div>
                    <ul class="wp-comp-metrics">
                        <li><span>Mobile Speed Score:</span> <strong>34 (Poor)</strong></li>
                        <li><span>Largest Contentful Paint (LCP):</span> <strong>5.8 seconds</strong></li>
                        <li><span>Total Blocking Time (TBT):</span> <strong>1,450 ms</strong></li>
                        <li><span>Core Web Vitals Status:</span> <strong>FAILED ❌</strong></li>
                    </ul>
                </div>

                <!-- AFTER CARD -->
                <div class="wp-comp-card after">
                    <div class="wp-comp-header">
                        <span class="wp-comp-badge">🚀 After WPThrust Tuning</span>
                        <small style="color: #15803d; font-weight:700;">Verified Optimization</small>
                    </div>
                    <div class="wp-comp-score">98/100</div>
                    <ul class="wp-comp-metrics">
                        <li><span>Mobile Speed Score:</span> <strong>98 (Lightning Fast)</strong></li>
                        <li><span>Largest Contentful Paint (LCP):</span> <strong>0.9 seconds</strong></li>
                        <li><span>Total Blocking Time (TBT):</span> <strong>35 ms</strong></li>
                        <li><span>Core Web Vitals Status:</span> <strong>PASSED ✅</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- 08. WHAT WE OPTIMIZE (Technical Scope Stack) -->
    <section class="wp-optimize-tech-section">
        <div class="wp-container">
            <div class="wp-section-header">
                <div class="wp-badge">Technical Scope</div>
                <h2>What We Optimize On Your Website</h2>
                <p>A complete technical breakdown of the optimization stack our performance engineers optimize:</p>
            </div>

            <div class="wp-tech-grid">
                <div class="wp-tech-card">
                    <h4>🖼️ Images & Media</h4>
                    <ul>
                        <li>Next-gen WebP/AVIF compression</li>
                        <li>Responsive image sizing</li>
                        <li>Lazy loading implementation</li>
                        <li>SVG & Icon vector tuning</li>
                    </ul>
                </div>

                <div class="wp-tech-card">
                    <h4>🎨 CSS Optimization</h4>
                    <ul>
                        <li>Remove unused CSS</li>
                        <li>CSS minification & merging</li>
                        <li>Critical inline CSS generation</li>
                        <li>Asynchronous CSS loading</li>
                    </ul>
                </div>

                <div class="wp-tech-card">
                    <h4>⚡ JavaScript Tuning</h4>
                    <ul>
                        <li>Defer non-critical scripts</li>
                        <li>Delay 3rd party trackers</li>
                        <li>Reduce main-thread blocking</li>
                        <li>Elementor script optimization</li>
                    </ul>
                </div>

                <div class="wp-tech-card">
                    <h4>🔤 Font Optimization</h4>
                    <ul>
                        <li>Font preloading & display:swap</li>
                        <li>Self-hosting Google Fonts</li>
                        <li>Reduce WOFF2 requests</li>
                        <li>Subset font file sizes</li>
                    </ul>
                </div>

                <div class="wp-tech-card">
                    <h4>🚀 Advanced Caching</h4>
                    <ul>
                        <li>Full page caching setup</li>
                        <li>Browser caching headers</li>
                        <li>Redis / Memcached object cache</li>
                        <li>Cloudflare CDN integration</li>
                    </ul>
                </div>

                <div class="wp-tech-card">
                    <h4>🗄️ Database Optimization</h4>
                    <ul>
                        <li>Database table cleanup</li>
                        <li>Remove revision & transient bloat</li>
                        <li>MySQL query indexing</li>
                        <li>Autoloaded options cleanup</li>
                    </ul>
                </div>

                <div class="wp-tech-card">
                    <h4>🖥️ Server & TTFB</h4>
                    <ul>
                        <li>PHP version upgrade & PHP-FPM</li>
                        <li>Gzip & Brotli compression</li>
                        <li>HTTP/2 & HTTP/3 protocol</li>
                        <li>Server response time reduction</li>
                    </ul>
                </div>

                <div class="wp-tech-card">
                    <h4>🎯 Core Web Vitals</h4>
                    <ul>
                        <li>LCP (Largest Contentful Paint)</li>
                        <li>INP (Interaction to Next Paint)</li>
                        <li>CLS (Layout Shift Fixes)</li>
                        <li>Search Console error fixes</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- 09. SERVICES SECTION (Primary Speed Service + Supporting Services) -->
    <section class="wp-services-section">
        <div class="wp-container">
            <div class="wp-section-header">
                <div class="wp-badge">Our Services</div>
                <h2>Website Performance & Digital Growth Services</h2>
                <p>Website Speed Optimization is our core primary specialty, supported by technical development and search optimization.</p>
            </div>

            <!-- Primary Service Hero Card -->
            <div class="wp-primary-service-hero">
                <span class="wp-primary-badge">🏆 Core Primary Service</span>
                <h3>WordPress & Website Speed Optimization</h3>
                <p>Comprehensive performance tuning to achieve 90+ Mobile & Desktop PageSpeed Insights scores. We optimize core code, fonts, images, plugins, caching, and server response times.</p>
                
                <div class="wp-primary-features-tags">
                    <span>Core Web Vitals Pass</span>
                    <span>Elementor Speed Tuning</span>
                    <span>Plugin & Script Deferral</span>
                    <span>Database Indexing</span>
                    <span>Redis & Object Caching</span>
                    <span>Image WebP Conversion</span>
                </div>

                <a href="<?php echo esc_url(home_url('/wordpress-speed-optimization-service/')); ?>" class="wp-btn-primary">Explore WordPress Speed Service →</a>
            </div>

            <!-- Secondary Speed Services Grid -->
            <div class="wp-secondary-services-grid">
                <div class="wp-secondary-card">
                    <h4>WooCommerce Speed</h4>
                    <p>Accelerating checkout, cart loading, and product database queries for WooCommerce stores.</p>
                    <a href="<?php echo esc_url(home_url('/pricing/')); ?>">View Details →</a>
                </div>

                <div class="wp-secondary-card">
                    <h4>Shopify Speed</h4>
                    <p>Liquid theme optimization, app script deferral, and instant product page preloading.</p>
                    <a href="<?php echo esc_url(home_url('/pricing/')); ?>">View Details →</a>
                </div>

                <div class="wp-secondary-card">
                    <h4>Laravel & PHP Speed</h4>
                    <p>OPcache, MySQL query indexing, Redis setup, and TTFB server acceleration for custom apps.</p>
                    <a href="<?php echo esc_url(home_url('/pricing/')); ?>">View Details →</a>
                </div>

                <div class="wp-secondary-card">
                    <h4>Magento & Other CMS</h4>
                    <p>Dedicated speed optimization for Magento, PrestaShop, Webflow, and custom platforms.</p>
                    <a href="<?php echo esc_url(home_url('/pricing/')); ?>">View Details →</a>
                </div>
            </div>
        </div>
    </section>

    <!-- 10. HOW WE OPTIMIZE YOUR WEBSITE (7-Step Timeline) -->
    <section id="how-we-optimize" class="wp-timeline-section">
        <div class="wp-container">
            <div class="wp-section-header">
                <div class="wp-badge">Step-by-Step Workflow</div>
                <h2>How We Make Your Website Faster</h2>
                <p>Our proven 7-step performance engineering workflow designed for safety and speed.</p>
            </div>

            <div class="wp-timeline-list">
                <div class="wp-timeline-step">
                    <div class="wp-step-number">01</div>
                    <div class="wp-step-details">
                        <h4>Performance Audit & Diagnostic</h4>
                        <p>We analyze your website using Google PageSpeed Insights, Lighthouse, and server logs to identify exact bottlenecks.</p>
                    </div>
                </div>

                <div class="wp-timeline-step">
                    <div class="wp-step-number">02</div>
                    <div class="wp-step-details">
                        <h4>Staging Environment Creation</h4>
                        <p>We clone your website to a safe staging environment so all optimization work happens without touching live traffic.</p>
                    </div>
                </div>

                <div class="wp-timeline-step">
                    <div class="wp-step-number">03</div>
                    <div class="wp-step-details">
                        <h4>Code & Asset Optimization</h4>
                        <p>We minify CSS/JS, compress images, defer non-critical scripts, and optimize fonts and database tables.</p>
                    </div>
                </div>

                <div class="wp-timeline-step">
                    <div class="wp-step-number">04</div>
                    <div class="wp-step-details">
                        <h4>Server & Caching Configuration</h4>
                        <p>We configure object caching (Redis), server-level Gzip/Brotli compression, and CDN edge delivery.</p>
                    </div>
                </div>

                <div class="wp-timeline-step">
                    <div class="wp-step-number">05</div>
                    <div class="wp-step-details">
                        <h4>Quality & Functional Testing</h4>
                        <p>We thoroughly test forms, checkout flows, mobile layouts, and design fidelity to ensure zero breakage.</p>
                    </div>
                </div>

                <div class="wp-timeline-step">
                    <div class="wp-step-number">06</div>
                    <div class="wp-step-details">
                        <h4>Performance Verification</h4>
                        <p>We re-test speed scores on PageSpeed Insights and GTmetrix to confirm 90+ score delivery.</p>
                    </div>
                </div>

                <div class="wp-timeline-step">
                    <div class="wp-step-number">07</div>
                    <div class="wp-step-details">
                        <h4>Live Deployment & Final Report</h4>
                        <p>With your approval, we push the speed enhancements live and provide a full before-and-after report.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 11. CORE WEB VITALS SECTION -->
    <section class="wp-vitals-section">
        <div class="wp-container">
            <div class="wp-section-header">
                <div class="wp-badge">User Experience Metrics</div>
                <h2>Core Web Vitals: The Metrics That Matter</h2>
                <p>Google measures user experience using 3 primary performance metrics. Here is what they mean:</p>
            </div>

            <div class="wp-vitals-grid">
                <div class="wp-vital-card">
                    <span class="wp-vital-code">LCP</span>
                    <h4>Largest Contentful Paint</h4>
                    <p>Measures how quickly the main content (hero image or main heading) becomes visible on the screen. Target: &lt; 2.5 seconds.</p>
                </div>

                <div class="wp-vital-card">
                    <span class="wp-vital-code">INP</span>
                    <h4>Interaction to Next Paint</h4>
                    <p>Measures visual responsiveness when users click buttons, open menus, or interact with page elements. Target: &lt; 200 ms.</p>
                </div>

                <div class="wp-vital-card">
                    <span class="wp-vital-code">CLS</span>
                    <h4>Cumulative Layout Shift</h4>
                    <p>Measures visual stability—preventing annoying text or button shifts while the page loads. Target: &lt; 0.10.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 12. PLATFORMS WE OPTIMIZE -->
    <section class="wp-platforms-section">
        <div class="wp-container">
            <div class="wp-section-header" style="margin-bottom: 28px;">
                <div class="wp-badge">Multi-Platform Optimization</div>
                <h2>Speed Optimization For Your Platform</h2>
            </div>

            <div class="wp-platforms-flex">
                <div class="wp-platform-chip">🔵 WordPress</div>
                <div class="wp-platform-chip">🛒 WooCommerce</div>
                <div class="wp-platform-chip">🟢 Shopify</div>
                <div class="wp-platform-chip">🐘 Custom PHP</div>
                <div class="wp-platform-chip">🚀 Laravel</div>
                <div class="wp-platform-chip">⚡ Magento</div>
            </div>
        </div>
    </section>

    <!-- 13. SUPPORTING SERVICES -->
    <section class="wp-supporting-section">
        <div class="wp-container">
            <div class="wp-section-header">
                <div class="wp-badge">Full Growth Solutions</div>
                <h2>More Than Website Speed Optimization</h2>
                <p>In addition to performance engineering, we offer supporting technical and search growth services:</p>
            </div>

            <div class="wp-supporting-grid">
                <div class="wp-supporting-card">
                    <h4>Website Development</h4>
                    <p>Custom, lightning-fast WordPress and custom web development built from the ground up for speed and scalability.</p>
                    <a href="<?php echo esc_url(home_url('/contact-us/')); ?>">Learn More →</a>
                </div>

                <div class="wp-supporting-card">
                    <h4>Technical SEO</h4>
                    <p>Comprehensive technical SEO audits, site structure optimization, and search console error resolution.</p>
                    <a href="<?php echo esc_url(home_url('/contact-us/')); ?>">Learn More →</a>
                </div>

                <div class="wp-supporting-card">
                    <h4>SEO Content Writing</h4>
                    <p>SurferSEO-optimized blog articles and high-converting landing page copywriting designed to rank on Google.</p>
                    <a href="<?php echo esc_url(home_url('/pricing/')); ?>">Learn More →</a>
                </div>
            </div>
        </div>
    </section>

    <!-- 14. DYNAMIC BLOG SECTION -->
    <section class="wp-blog-section">
        <div class="wp-container">
            <div class="wp-section-header">
                <div class="wp-badge">Resources & Guides</div>
                <h2>Learn How To Build A Faster Website</h2>
                <p>Read our latest performance guides and speed optimization tutorials:</p>
            </div>

            <div class="wp-blog-grid">
                <?php
                $blog_query = new WP_Query([
                    'post_type' => 'post',
                    'posts_per_page' => 3,
                    'post_status' => 'publish',
                ]);

                if ($blog_query->have_posts()) :
                    while ($blog_query->have_posts()) : $blog_query->the_post();
                        $cats = get_the_category();
                        $cat_name = !empty($cats) ? $cats[0]->name : 'Performance';
                        ?>
                        <article class="wp-blog-card">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="wp-blog-img-box">
                                    <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('medium'); ?></a>
                                </div>
                            <?php endif; ?>
                            <div class="wp-blog-body">
                                <div class="wp-blog-cat"><?php echo esc_html($cat_name); ?></div>
                                <h3 class="wp-blog-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                <div class="wp-blog-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 14); ?></div>
                                <a href="<?php the_permalink(); ?>" class="wp-blog-link">Read Article →</a>
                            </div>
                        </article>
                    <?php 
                    endwhile;
                    wp_reset_postdata();
                else : ?>
                    <p style="text-align: center; color: #64748b; grid-column: 1/-1;">Check out our blog for the latest speed optimization tutorials!</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- 15. FAQ SECTION (17 FAQs) -->
    <section class="wp-faq-section">
        <div class="wp-container">
            <div class="wp-section-header">
                <div class="wp-badge">Got Questions?</div>
                <h2>Frequently Asked Questions</h2>
                <p>Everything you need to know about website speed optimization and our services:</p>
            </div>

            <div class="wp-faq-list">
                <div class="wp-faq-card">
                    <h4>1. What is website speed optimization?</h4>
                    <p>Website speed optimization is the process of auditing, refactoring, and tuning website code, images, database queries, fonts, and server caching to decrease page load times and improve user experience.</p>
                </div>

                <div class="wp-faq-card">
                    <h4>2. Why is my WordPress website slow?</h4>
                    <p>WordPress sites slow down due to uncompressed images, bloated themes, excessive plugins, render-blocking CSS/JS, lack of page/object caching, un-indexed database tables, and slow hosting servers.</p>
                </div>

                <div class="wp-faq-card">
                    <h4>3. Can you optimize an Elementor website?</h4>
                    <p>Yes! We specialize in Elementor speed optimization. We defer unused Elementor script widgets, generate critical CSS, optimize Google fonts, and clean up DOM size without affecting Elementor designs.</p>
                </div>

                <div class="wp-faq-card">
                    <h4>4. Can you improve my Google PageSpeed score to 90+?</h4>
                    <p>Yes, we guarantee a 90+ PageSpeed Insights score on desktop and mobile for eligible websites. If we fall short of the agreed score, we offer a 100% money-back refund.</p>
                </div>

                <div class="wp-faq-card">
                    <h4>5. Can you fix Core Web Vitals issues in Search Console?</h4>
                    <p>Yes! We fix Largest Contentful Paint (LCP), Interaction to Next Paint (INP), and Cumulative Layout Shift (CLS) warnings so your site satisfies Google's Search Console evaluation.</p>
                </div>

                <div class="wp-faq-card">
                    <h4>6. Do you optimize WooCommerce online stores?</h4>
                    <p>Yes. WooCommerce speed optimization is one of our specialties. We optimize cart fragments, checkout AJAX scripts, customer session data, product image galleries, and database queries.</p>
                </div>

                <div class="wp-faq-card">
                    <h4>7. Will optimization break my website layout or functionality?</h4>
                    <p>No. We perform all speed enhancements on a staging clone first. We test all interactive forms, popups, and design layouts before pushing changes live to ensure zero breakage.</p>
                </div>

                <div class="wp-faq-card">
                    <h4>8. Do you work on a staging copy?</h4>
                    <p>Yes. We always work on staging first so your live website remains 100% active and safe while we optimize.</p>
                </div>

                <div class="wp-faq-card">
                    <h4>9. How long does the speed optimization process take?</h4>
                    <p>Most WordPress, Shopify, and custom PHP websites are fully optimized within 24 to 48 hours after login access is provided to our performance team.</p>
                </div>

                <div class="wp-faq-card">
                    <h4>10. Can you optimize Shopify websites?</h4>
                    <p>Yes! We clean up unused app scripts, optimize Liquid theme code, compress WebP images, and implement instant product page preloading for Shopify stores.</p>
                </div>

                <div class="wp-faq-card">
                    <h4>11. Can you optimize custom PHP and Laravel web apps?</h4>
                    <p>Yes. We tune custom PHP applications by enabling OPcache, Redis/Memcached object caching, Nginx/Apache configuration, and MySQL query indexing.</p>
                </div>

                <div class="wp-faq-card">
                    <h4>12. Do I need to switch to a new hosting server?</h4>
                    <p>In 95% of cases, no. We optimize code and database execution on your existing host. If your hosting server is genuinely incapable of fast TTFB, we will provide honest migration recommendations.</p>
                </div>

                <div class="wp-faq-card">
                    <h4>13. Will speed optimization improve my Google SEO rankings?</h4>
                    <p>Yes. Google uses Core Web Vitals as a ranking factor. Faster loading speeds also lower bounce rates and improve user engagement signals, which support organic SEO growth.</p>
                </div>

                <div class="wp-faq-card">
                    <h4>14. Can you optimize mobile speed performance?</h4>
                    <p>Yes. Mobile performance is our top priority because Google uses mobile-first indexing. We optimize JavaScript execution and image sizing specifically for mobile devices.</p>
                </div>

                <div class="wp-faq-card">
                    <h4>15. Can you optimize websites with many plugins?</h4>
                    <p>Yes. We perform plugin audits to identify bloated or slow plugins, replace heavy plugins with lightweight code snippets, and defer non-essential plugin scripts.</p>
                </div>

                <div class="wp-faq-card">
                    <h4>16. Do you provide a before-and-after performance report?</h4>
                    <p>Yes! Upon completion, we provide a full optimization summary comparing before and after PageSpeed Insights, GTmetrix, and load time metrics.</p>
                </div>

                <div class="wp-faq-card">
                    <h4>17. How do I get started?</h4>
                    <p>Simply run a free speed test using our tool above or book a quick strategy call. Our team will review your website and provide a clear optimization roadmap!</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 16. FINAL CTA BANNER (With Elementor Form Placeholder) -->
    <section class="wp-final-cta-section">
        <div class="wp-container">
            <div class="wp-final-cta-box">
                <h2>Ready To Make Your Website Faster?</h2>
                <p>Find out what's slowing your website down and get a clear, risk-free optimization plan from our performance experts.</p>
                
                <div class="wp-hero-ctas" style="justify-content: center; margin-bottom: 20px;">
                    <a href="#speed-tool" class="wp-btn-primary">
                        <span>Get Your Free Speed Audit</span>
                    </a>

                    <a href="https://wpthrust.in/#schedule" class="wp-btn-secondary" style="background: transparent; color: #ffffff !important; border-color: rgba(255,255,255,0.4);">
                        <span>Talk To A Speed Expert</span>
                    </a>
                </div>

                <!-- Elementor Form / Popup Placeholder Container -->
                <div class="wpthrust-form-placeholder">
                    <!-- Elementor Form / Shortcode can be inserted here later -->
                </div>
            </div>
        </div>
    </section>

</div>

<?php 
// Load WordPress Default Site Footer (Do Not Touch Footer)
get_footer(); 
?>
