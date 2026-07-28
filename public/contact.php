<?php
$pageTitle = 'Contact Us - FlexiSpace';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section" style="padding: 40px 0;">
    <div class="container">
        <h1 class="section-title">Contact Us</h1>
        <p class="section-subtitle">Get in touch with us. We'd love to hear from you.</p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; max-width: 900px; margin: 0 auto;">
            <div>
                <div
                    style="background: var(--bg-white); border-radius: var(--radius-md); padding: 30px; border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 16px;">Send us a Message</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Send
                            Message</button>
                    </form>
                </div>
                <div>
                    <div
                        style="background: var(--bg-white); border-radius: var(--radius-md); padding: 30px; border: 1px solid var(--border);">
                        <h3 style="margin-bottom: 16px;">Our Office</h3>
                        <div style="margin-bottom: 20px;">
                            <p style="font-weight: 600;">Address</p>
                            <p style="color: var(--text-mid);">Woodvale Grove, Westlands<br>Nairobi, Kenya</p>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <p style="font-weight: 600;">Phone</p>
                            <p style="color: var(--text-mid);"><a href="tel:+254700000000">+254 700 000 000</a></p>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <p style="font-weight: 600;">Email</p>
                            <p style="color: var(--text-mid);"><a
                                    href="mailto:info@flexispace.co.ke">info@flexispace.co.ke</a></p>
                        </div>
                        <div>
                            <p style="font-weight: 600;">Business Hours</p>
                            <p style="color: var(--text-mid);">Mon - Fri: 8:00 AM - 6:00 PM<br>Sat: 9:00 AM - 3:00 PM
                            </p>
                        </div>

                        <div class="map-container" id="contactMap" style="height: 250px; margin-top: 24px;"></div>
                    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof initSingleSpaceMap === 'function') {
            initSingleSpaceMap('contactMap', {
                lat: -1.2628,
                lng: 36.8119,
                name: 'FlexiSpace - Westlands',
                address: 'Woodvale Grove, Westlands, Nairobi'
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>