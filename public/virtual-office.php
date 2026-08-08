<?php

/**
 * VIRTUAL OFFICE PROMOTIONAL PAGE - Zahara Co-Working Space
 * Replicates the official Zahara Virtual Office promotional flyer.
 */
$pageTitle = 'Virtual Office Solutions - Zahara Co-Working Space';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ============ HERO / FLYER HEADER ============ -->
<section class="vo-hero">
    <div class="container">
        <div class="vo-hero-grid">
            <div class="vo-hero-content">

                <div class="vo-brand">
                    <img src="/work_folder/realRealestate/images/logo.png" alt="Zahara Co-Working Space logo"
                        class="vo-brand-logo" loading="lazy">
                    <span class="hero-brand-text">Zahara Co-Working Space</span>
                </div>
                <h1>Give Your Business a <span>Professional Presence</span></h1>
                <p class="vo-subtitle">Looking to establish your business without the cost of renting a physical
                    office? Our <strong>Virtual Office Solutions</strong> are designed for entrepreneurs, startups,
                    freelancers, and growing businesses that want to build credibility while keeping costs low.</p>
                <div class="vo-check-list">
                    <span>&#9989; Official Business Address</span>
                    <span>&#9989; Mail Handling Services</span>
                    <span>&#9989; Meeting Room Access</span>
                    <span>&#9989; Dedicated Virtual Receptionist <em>(Premium Package)</em></span>
                </div>
                <div class="hero-actions">
                    <a href="#vo-pricing" class="btn btn-primary btn-lg">Choose Your Package</a>
                    <a href="/work_folder/realRealestate/public/contact.php" class="btn btn-outline btn-lg"
                        style="border-color: rgba(255,255,255,0.4); color: white;">Contact Us</a>
                </div>
            </div>
            <div class="vo-hero-visual">
                <div class="vo-hero-card">
                    <img src="/work_folder/realRealestate/images/logo.png" alt="Zahara Co-Working Space logo"
                        class="vo-hero-card-logo" loading="lazy">
                    <h3>Why Go Virtual?</h3>
                    <p>Build a credible business image with a prime Westlands address — without the cost of a physical
                        office.</p>
                    <ul class="offering-list">
                        <li>&#10003; No rent or setup costs</li>
                        <li>&#10003; Prime business address</li>
                        <li>&#10003; Flexible & scalable packages</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ PRICE / BENEFITS ============ -->
<section class="section" id="vo-pricing">
    <div class="container">
        <h2 class="section-title">Choose the Package That Fits Your Business</h2>
        <p class="section-subtitle">Flexible virtual office solutions to grow your professional image.</p>

        <div class="vo-benefits">
            <div class="vo-benefit">
                <div style="font-size: 2rem; margin-bottom: 8px;">&#9989;</div>
                <h4>Official Business Address</h4>
                <p>Use a prime Westlands address on your documents and website.</p>
            </div>
            <div class="vo-benefit">
                <div style="font-size: 2rem; margin-bottom: 8px;">&#128231;</div>
                <h4>Mail Handling</h4>
                <p>We receive, sort, and notify you of all your business mail.</p>
            </div>
            <div class="vo-benefit">
                <div style="font-size: 2rem; margin-bottom: 8px;">&#128204;</div>
                <h4>Meeting Room Access</h4>
                <p>Book professional meeting rooms when you need to meet clients.</p>
            </div>
            <div class="vo-benefit">
                <div style="font-size: 2rem; margin-bottom: 8px;">&#128222;</div>
                <h4>Virtual Receptionist</h4>
                <p>A dedicated receptionist answers your calls professionally.</p>
            </div>
        </div>

        <!-- Pricing Tiers -->
        <div class="vo-pricing">
            <!-- Tier 1 -->
            <div class="vo-card">
                <div class="vo-card-number">1</div>
                <div class="vo-card-icon">&#128205;</div>
                <h3>Business Presence</h3>
                <div class="vo-price">KSh 3,000<small>/month</small></div>
                <ul class="vo-features">
                    <li>&#10003; Official business address</li>
                    <li>&#10003; Mail handling services</li>
                </ul>
                <a href="/work_folder/realRealestate/public/contact.php" class="btn btn-outline">Get Started</a>
            </div>
            <!-- Tier 2 -->
            <div class="vo-card vo-card-popular">
                <div class="vo-card-badge">Most Popular</div>
                <div class="vo-card-number">2</div>
                <div class="vo-card-icon">&#128188;</div>
                <h3>Standard Virtual Office</h3>
                <div class="vo-price">KSh 5,000<small>/month</small></div>
                <ul class="vo-features">
                    <li>&#10003; Everything in Business Presence</li>
                    <li>&#10003; Package handling</li>
                    <li>&#10003; Co-working access</li>
                </ul>
                <a href="/work_folder/realRealestate/public/contact.php" class="btn btn-primary">Get Started</a>
            </div>
            <!-- Tier 3 -->
            <div class="vo-card">
                <div class="vo-card-number">3</div>
                <div class="vo-card-icon">&#128222;</div>
                <h3>Virtual Office Plus</h3>
                <div class="vo-price">KSh 10,000<small>/month</small></div>
                <ul class="vo-features">
                    <li>&#10003; Everything in Standard plan</li>
                    <li>&#10003; Dedicated office space</li>
                    <li>&#10003; Exclusive boardroom access</li>
                </ul>
                <a href="/work_folder/realRealestate/public/contact.php" class="btn btn-outline">Get Started</a>
            </div>
        </div>
    </div>
</section>

<!-- ============ CTA / CONTACT ============ -->
<section class="vo-cta">
    <div class="container">
        <h2>CONTACT US TODAY!</h2>
        <p>Start building your professional image today with Zahara Co-Working Space.</p>
        <div class="vo-contact">
            <div class="vo-contact-item"><span>&#128205;</span> Krishna Centre, 2nd Floor, Westlands</div>
            <div class="vo-contact-item"><span>&#128222;</span> 0708 854 435 | 0724 161 342</div>
            <div class="vo-contact-item"><span>&#9993;</span> zaharacoworking24@gmail.com</div>
        </div>
        <div class="vo-tagline">Your Business. Our Space. Your Success.</div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

