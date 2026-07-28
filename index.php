<?php

/**
 * LANDING PAGE - FlexiSpace
 * Features: Hero, Featured Spaces, Map Preview, Testimonials
 */
$pageTitle = 'FlexiSpace - Premium Office Spaces in Westlands, Nairobi';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Fetch featured spaces
$featured = $db->query("
    SELECT os.*, 
           (SELECT image_url FROM space_images WHERE space_id = os.id AND is_primary = TRUE LIMIT 1) as primary_image
    FROM office_spaces os 
    WHERE os.is_featured = TRUE AND os.status = 'available' 
    ORDER BY os.price_per_month ASC 
    LIMIT 6
")->fetchAll();

// Fetch approved testimonials
$testimonials = $db->query("
    SELECT t.*, u.full_name 
    FROM testimonials t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.status = 'approved' AND t.is_featured = TRUE 
    ORDER BY t.created_at DESC 
    LIMIT 3
")->fetchAll();

// Stats
$stats = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM office_spaces WHERE status = 'available') as available_spaces,
        (SELECT COUNT(*) FROM leases WHERE status = 'active') as active_leases,
        (SELECT COUNT(*) FROM users WHERE role_id = 2 AND status = 'active') as happy_clients
")->fetch();
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-grid">
            <div class="hero-content">
                <h1>Your Premier Office Space in <span>Westlands, Nairobi</span></h1>
                <p>Discover flexible, premium office spaces tailored for your business. From executive suites to
                    collaborative coworking — find your perfect workspace in Nairobi's most vibrant business district.
                </p>
                <div class="hero-actions">
                    <a href="/work_folder/realRealestate/public/spaces.php" class="btn btn-secondary btn-lg">Browse
                        Spaces</a>
                    <a href="/work_folder/realRealestate/public/contact.php" class="btn btn-outline btn-lg"
                        style="border-color: rgba(255,255,255,0.4); color: white;">Schedule Tour</a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <h3><?= number_format($stats['available_spaces'] ?? 5) ?></h3>
                        <p>Available Spaces</p>
                    </div>
                    <div class="hero-stat">
                        <h3><?= number_format($stats['active_leases'] ?? 50) ?>+</h3>
                        <p>Active Leases</p>
                    </div>
                    <div class="hero-stat">
                        <h3><?= number_format($stats['happy_clients'] ?? 100) ?>+</h3>
                        <p>Happy Clients</p>
                    </div>
                </div>
                <div class="hero-image">
                    <div class="hero-map-placeholder">
                        <div class="map-icon">&#127758;</div>
                        <h3>Westlands Business District</h3>
                        <p>Woodvale Grove, Rhapta Road, Mpaka Road</p>
                        <a href="/work_folder/realRealestate/public/spaces.php" class="btn btn-secondary btn-sm">Explore
                            on Map &rarr;</a>
                    </div>
                </div>
</section>

<!-- Featured Spaces -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Featured Office Spaces</h2>
        <p class="section-subtitle">Handpicked premium spaces ready for immediate occupancy in prime Westlands
            locations.</p>

        <?php if (empty($featured)): ?>
            <div style="text-align: center; padding: 60px 20px; color: var(--text-light);">
                <p style="font-size: 1.1rem;">No featured spaces available at the moment. Check back soon!</p>
                <a href="/work_folder/realRealestate/public/spaces.php" class="btn btn-primary"
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
                            <span class="space-card-badge badge-featured">Featured</span>
                        </div>
                        <div class="space-card-body">
                            <h3><a
                                    href="/work_folder/realRealestate/public/space-detail.php?slug=<?= $space['slug'] ?>"><?= htmlspecialchars($space['name']) ?></a>
                            </h3>
                            <div class="space-meta">
                                <span>&#128205; <?= htmlspecialchars($space['city']) ?>,
                                    <?= htmlspecialchars($space['country']) ?></span>
                                <span>&#128101; <?= $space['capacity'] ?> seats</span>
                                <span>&#128196; <?= ucwords(str_replace('_', ' ', $space['space_type'])) ?></span>
                            </div>
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
                    <div style="text-align: center; margin-top: 32px;">
                        <a href="/work_folder/realRealestate/public/spaces.php" class="btn btn-outline btn-lg">View All Spaces
                            &rarr;</a>
                    </div>
                <?php endif; ?>
            </div>
</section>

<!-- How It Works -->
<section class="section" style="background: var(--bg-white);">
    <div class="container">
        <h2 class="section-title">How It Works</h2>
        <p class="section-subtitle">A simple, transparent process to get you into your ideal workspace.</p>
        <div
            style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 30px; margin-top: 40px;">
            <div style="text-align: center; padding: 30px;">
                <div
                    style="width: 60px; height: 60px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 1.5rem; font-weight: 700;">
                    1</div>
                <h3>Browse Spaces</h3>
                <p style="color: var(--text-mid); font-size: 0.9rem;">Explore our curated selection of office spaces
                    with detailed info and images.</p>
            </div>
            <div style="text-align: center; padding: 30px;">
                <div
                    style="width: 60px; height: 60px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 1.5rem; font-weight: 700;">
                    2</div>
                <h3>Request a Visit</h3>
                <p style="color: var(--text-mid); font-size: 0.9rem;">Schedule a site visit at your preferred date and
                    time to see the space.</p>
            </div>
            <div style="text-align: center; padding: 30px;">
                <div
                    style="width: 60px; height: 60px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 1.5rem; font-weight: 700;">
                    3</div>
                <h3>Sign Lease</h3>
                <p style="color: var(--text-mid); font-size: 0.9rem;">Like the space? Sign a lease agreement with
                    flexible terms.</p>
            </div>
            <div style="text-align: center; padding: 30px;">
                <div
                    style="width: 60px; height: 60px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 1.5rem; font-weight: 700;">
                    4</div>
                <h3>Move In</h3>
                <p style="color: var(--text-mid); font-size: 0.9rem;">Pay deposit, get set up, and start working from
                    your new space!</p>
            </div>
        </div>
</section>

<!-- Map Preview -->
<section class="map-section">
    <div class="container">
        <h2 class="section-title">Prime Location in Westlands</h2>
        <p class="section-subtitle">Our spaces are strategically located in Nairobi's premier business district.</p>
    </div>
    <div class="map-container" id="homeMap"
        style="max-width: var(--max-width); margin: 0 auto; border-radius: var(--radius-md);"></div>
</section>

<!-- Testimonials -->
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
                            <p class="testimonial-content">"<?= htmlspecialchars($t['content']) ?>"</p>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div style="text-align: center; margin-top: 32px;">
                    <a href="/work_folder/realRealestate/public/testimonials.php" class="btn btn-outline">Read All Reviews
                        &rarr;</a>
                </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php
        // Fetch all available spaces for map
        $mapSpaces = $db->query("
        SELECT id, name, slug, space_type, capacity, price_per_month, currency, 
               latitude as lat, longitude as lng, status,
               CONCAT(address_line1, ', ', city) as address
        FROM office_spaces WHERE status = 'available'
        LIMIT 50
    ")->fetchAll();
        ?>
        var spaces = <?= json_encode($mapSpaces) ?>;
        spaces.forEach(function(s) {
            s.url = '/work_folder/realRealestate/public/space-detail.php?slug=' + s.slug;
        });
        if (typeof initSpaceMap === 'function' && spaces.length > 0) {
            initSpaceMap('homeMap', spaces);
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>