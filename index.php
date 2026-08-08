<?php

/**
 * LANDING PAGE - Zahara Co-Working Space
 * Hybrid Business Model: Virtual Office + Physical Co-Working Spaces
 */
$pageTitle = 'Zahara Co-Working Space - Virtual Office & Premium Workspaces in Westlands, Nairobi';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$featured = $db->query("SELECT os.*, (SELECT image_url FROM space_images WHERE space_id = os.id AND is_primary = TRUE LIMIT 1) as primary_image FROM office_spaces os WHERE os.is_featured = TRUE AND os.status = 'available' ORDER BY os.price_per_month ASC LIMIT 6")->fetchAll();
$testimonials = $db->query("SELECT t.*, u.full_name FROM testimonials t JOIN users u ON t.user_id = u.id WHERE t.status = 'approved' AND t.is_featured = TRUE ORDER BY t.created_at DESC LIMIT 3")->fetchAll();
$stats = $db->query("SELECT (SELECT COUNT(*) FROM office_spaces WHERE status = 'available') as available_spaces, (SELECT COUNT(*) FROM leases WHERE status = 'active') as active_leases, (SELECT COUNT(*) FROM users WHERE role_id = 2 AND status = 'active') as happy_clients")->fetch();
?>
<!-- ============ HERO SECTION (Flyer style) ============ -->
<section class="hero">
    <div class="container">
        <div class="hero-grid">
            <div class="hero-content">
                <div class="vo-brand">
                    <img src="/work_folder/realRealestate/images/logo.png" alt="Zahara Co-Working Space logo"
                        class="hero-logo">
                    <span class="hero-brand-text">Zahara Co-Working Space</span>
                </div>
                <h1>A Replacement of <span>Traditional Workplace</span></h1>
                <p>Give Your Business a Professional Presence — whether you need a fully furnished <strong>physical
                        workspace</strong> at Krishna Centre, Westlands, or a low-cost <strong>virtual office</strong>
                    with a premium business address, Zahara has a solution for your business.</p>
                <div class="hero-actions">
                    <a href="/work_folder/realRealestate/public/virtual-office.php" class="btn btn-secondary btn-lg">Virtual
                        Office</a>
                    <a href="/work_folder/realRealestate/public/spaces.php" class="btn btn-outline btn-lg"
                        style="border-color: rgba(255,255,255,0.4); color: white;">Physical Spaces</a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <h3><?= number_format($stats['available_spaces'] ?? 5) ?></h3>
                        <p>Available Spaces</p>
                    </div>
                    <div class="hero-stat">
                        <h3><?= number_format($stats['active_leases'] ?? 20) ?>+</h3>
                        <p>Active Clients</p>
                    </div>
                    <div class="hero-stat">
                        <h3><?= number_format($stats['happy_clients'] ?? 50) ?>+</h3>
                        <p>Happy Clients</p>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-map-placeholder">
                    <div class="map-icon">&#127758;</div>
                    <h3>Krishna Centre, 2nd Floor, Westlands</h3>
                    <p>Nairobi's Premier Business District</p>
                    <a href="/work_folder/realRealestate/public/contact.php" class="btn btn-secondary btn-sm">Schedule a
                        Visit &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ TWO OFFERINGS ============ -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Choose How You Want to Work</h2>
        <p class="section-subtitle">From a prime virtual business presence to a fully furnished physical workspace.</p>
        <div class="offering-grid">
            <div class="offering-card offering-virtual">
                <div class="offering-icon">&#128187;</div>
                <span class="offering-badge">From KSh 3,000/mo</span>
                <h3>Virtual Office</h3>
                <p>Establish your business presence without the cost of a physical office. Get a prime Westlands
                    address, mail handling, and meeting room access.</p>
                <ul class="offering-list">
                    <li>&#10003; Official Business Address</li>
                    <li>&#10003; Mail Handling Services</li>
                    <li>&#10003; Meeting Room Access</li>
                </ul>
                <a href="/work_folder/realRealestate/public/virtual-office.php" class="btn btn-primary">View Packages</a>
            </div>
            <div class="offering-card offering-physical">
                <div class="offering-icon">&#127970;</div>
                <span class="offering-badge">Physical Spaces</span>
                <h3>Co-Working Spaces</h3>
                <p>Work from fully furnished private offices, open desks, and boardrooms at Krishna Centre, Westlands.
                    High-speed Wi-Fi and a prime business location.</p>
                <ul class="offering-list">
                    <li>&#10003; Private Offices & Open Desks</li>
                    <li>&#10003; Boardrooms & Meeting Rooms</li>
                    <li>&#10003; High-Speed Wi-Fi</li>
                </ul>
                <a href="/work_folder/realRealestate/public/spaces.php" class="btn btn-primary">Browse Spaces</a>
            </div>
        </div>
    </div>
</section>

<!-- ============ HOW ZAHARA WORKS ============ -->
<section class="section" style="background: var(--bg-white);">
    <div class="container">
        <h2 class="section-title">How Zahara Works</h2>
        <p class="section-subtitle">Get your ideal workspace in four simple steps.</p>
        <div class="how-grid">
            <div class="how-step">
                <div class="how-number">1</div>
                <h3>Choose Your Solution</h3>
                <p>Pick a virtual office package or a physical co-working space that fits your needs and budget.</p>
            </div>
            <div class="how-step">
                <div class="how-number">2</div>
                <h3>Request / Sign Up</h3>
                <p>Create an account and request a visit for physical spaces, or sign up for a virtual office package
                    online.</p>
            </div>
            <div class="how-step">
                <div class="how-number">3</div>
                <h3>Approve & Activate</h3>
                <p>Admin approves your booking. For physical spaces, sign a flexible lease. For virtual, pick your
                    package.</p>
            </div>
            <div class="how-step">
                <div class="how-number">4</div>
                <h3>Launch & Grow</h3>
                <p>Start building your professional image at a prime Westlands address — online or in person.</p>
            </div>
        </div>
    </div>
</section>

<!-- ============ VIRTUAL OFFICE PACKAGES ============ -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Virtual Office Packages</h2>
        <p class="section-subtitle">Build credibility and keep costs low with our flexible virtual solutions.</p>
        <div class="vo-pricing">
            <div class="vo-card">
                <div class="vo-card-icon">&#128205;</div>
                <h3>Business Presence</h3>
                <div class="vo-price">KSh 3,000<small>/month</small></div>
                <ul class="vo-features">
                    <li>&#10003; Official business address</li>
                    <li>&#10003; Mail handling services</li>
                </ul>
                <a href="/work_folder/realRealestate/public/contact.php" class="btn btn-outline">Get Started</a>
            </div>
            <div class="vo-card vo-card-popular">
                <div class="vo-card-badge">Most Popular</div>
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
            <div class="vo-card">
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
        <div style="text-align: center; margin-top: 32px;">
            <a href="/work_folder/realRealestate/public/virtual-office.php" class="btn btn-outline btn-lg">View All Virtual
                Packages &rarr;</a>
        </div>
    </div>
</section>

<!-- ============ PHYSICAL SPACES ============ -->
<section class="section" style="background: var(--bg-white);">
    <div class="container">
        <h2 class="section-title">Our Physical Workspaces</h2>
        <p class="section-subtitle">Fully furnished, flexible co-working spaces at Krishna Centre, Westlands.</p>
        <?php if (empty($featured)): ?>
            <div style="text-align: center; padding: 60px 20px; color: var(--text-light);">
                <p style="font-size: 1.1rem;">No featured spaces available. Check back soon!</p><a
                    href="/work_folder/realRealestate/public/spaces.php" class="btn btn-primary"
                    style="margin-top: 16px;">View All Spaces</a>
            </div>
        <?php else: ?>
            <div class="spaces-grid">
                <?php foreach ($featured as $space): ?>
                    <div class="space-card">
                        <div class="space-card-image">
                            <img src="<?= htmlspecialchars($space['primary_image'] ?? 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=400') ?>"
                                alt="<?= htmlspecialchars($space['name']) ?>" loading="lazy">
                            <span class="space-card-badge badge-available">Available</span>
                        </div>
                        <div class="space-card-body">
                            <h3><a
                                    href="/work_folder/realRealestate/public/space-detail.php?slug=<?= $space['slug'] ?>"><?= htmlspecialchars($space['name']) ?></a>
                            </h3>
                            <div class="space-meta"><span>&#128205; <?= htmlspecialchars($space['city']) ?>,
                                    <?= htmlspecialchars($space['country']) ?></span><span>&#128101;
                                    <?= $space['capacity'] ?> seats</span></div>
                            <p class="space-description"><?= htmlspecialchars($space['short_description']) ?></p>
                            <div class="space-card-footer">
                                <div class="space-price">KES <?= number_format($space['price_per_month']) ?>
                                    <small>/month</small>
                                </div>
                                <a href="/work_folder/realRealestate/public/space-detail.php?slug=<?= $space['slug'] ?>"
                                    class="btn btn-primary btn-sm">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                    <div style="text-align: center; margin-top: 32px;"><a href="/work_folder/realRealestate/public/spaces.php"
                            class="btn btn-outline btn-lg">View All Spaces &rarr;</a></div>
                <?php endif; ?>
            </div>
</section>

<!-- ============ TESTIMONIALS ============ -->
<section class="section">
    <div class="container">
        <h2 class="section-title">What Our Clients Say</h2>
        <p class="section-subtitle">Trusted by businesses of all sizes in Nairobi.</p>
        <?php if (empty($testimonials)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-light);">
                <p>Testimonials coming soon. Be the first to leave a review!</p>
            </div>
        <?php else: ?>
            <div class="testimonials-grid">
                <?php foreach ($testimonials as $t): ?>
                    <div class="testimonial-card">
                        <div class="testimonial-header">
                            <div class="testimonial-avatar"><?= strtoupper(substr($t['full_name'], 0, 1)) ?></div>
                            <div class="testimonial-author">
                                <h4><?= htmlspecialchars($t['full_name']) ?></h4>
                                <div class="stars"><?= str_repeat('★', $t['rating']) . str_repeat('☆', 5 - $t['rating']) ?>
                                </div>
                            </div>
                        </div>
                        <p class="testimonial-content">"<?= htmlspecialchars($t['content']) ?>"</p>
                    </div>
                <?php endforeach; ?>
                </div>
                <div style="text-align: center; margin-top: 32px;"><a
                        href="/work_folder/realRealestate/public/testimonials.php" class="btn btn-outline">Read All
                        Reviews &rarr;</a></div>
            <?php endif; ?>
        </div>
</section>

<!-- ============ MAP ============ -->
<section class="map-section">
    <div class="container">
        <h2 class="section-title">Located at Krishna Centre, Westlands</h2>
        <p class="section-subtitle">Our spaces are at a prime business location in Nairobi's premier business district.
        </p>
    </div>
    <div class="map-container" id="homeMap"
        style="max-width: var(--max-width); margin: 0 auto; border-radius: var(--radius-md);"></div>
</section>

<!-- ============ CTA ============ -->
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var spaces =
            <?= json_encode($db->query("SELECT id, name, slug, latitude as lat, longitude as lng, CONCAT(address_line1, ', ', city) as address FROM office_spaces WHERE status = 'available' LIMIT 50")->fetchAll()) ?>;
        spaces.forEach(function(s) {
            s.url = '/work_folder/realRealestate/public/space-detail.php?slug=' + s.slug;
        });
        if (typeof initSpaceMap === 'function' && spaces.length > 0) {
            initSpaceMap('homeMap', spaces);
        }
    });
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

