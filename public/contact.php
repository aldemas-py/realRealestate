<?php
$pageTitle = 'Contact Us - Zahara Co-Working Space';
require_once __DIR__ . '/../includes/header.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if (!empty($name) && !empty($email) && !empty($subject) && !empty($message)) {
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, policy_trigger) VALUES (1, ?, ?, 'info', 'contact_form')");
        $stmt->execute(["Contact: $subject - $name", "From: $email\n\n$message"]);
        $_SESSION['success'] = 'Thank you for your message! We will get back to you shortly.';
    } else {
        $_SESSION['error'] = 'Please fill in all fields.';
    }
    header('Location: /work_folder/realRealestate/public/contact.php');
    exit;
}
?>
<section class="section" style="padding: 40px 0;">
    <div class="container">
        <h1 class="section-title">Contact Us</h1>
        <p class="section-subtitle">Get in touch with us. Visit us at Krishna Centre, 2nd Floor, Westlands.</p>
<div class="contact-layout">
            <div class="contact-form-col">
                <div
                    style="background: var(--bg-white); border-radius: var(--radius-md); padding: 30px; border: 1px solid var(--border);">
                    <h3 style="margin-bottom: 16px;">Send us a Message</h3>
                    <?php if (isset($_SESSION['success'])): ?><div class="alert alert-success"><span
                            class="alert-icon">&#10003;</span>
                        <p><?= $_SESSION['success'] ?></p><button class="alert-close"
                            onclick="this.parentElement.remove()">&times;</button>
                    </div><?php unset($_SESSION['success']);
                            endif; ?>
                    <?php if (isset($_SESSION['error'])): ?><div class="alert alert-error"><span
                            class="alert-icon">&#10060;</span>
                        <p><?= $_SESSION['error'] ?></p><button class="alert-close"
                            onclick="this.parentElement.remove()">&times;</button>
                    </div><?php unset($_SESSION['error']);
                            endif; ?>
                    <form method="POST" action="">
                        <div class="form-group"><label for="name">Your Name</label><input type="text" id="name"
                                name="name" required></div>
                        <div class="form-group"><label for="email">Email</label><input type="email" id="email"
                                name="email" required></div>
                        <div class="form-group"><label for="subject">Subject</label><input type="text" id="subject"
                                name="subject" required></div>
                        <div class="form-group"><label for="message">Message</label><textarea id="message"
                                name="message" rows="5" required></textarea></div>
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
                            <p style="color: var(--text-mid);">Krishna Centre, 2nd Floor, Westlands<br>Nairobi, Kenya
                            </p>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <p style="font-weight: 600;">Phone</p>
                            <p style="color: var(--text-mid);"><a href="tel:+254724161342">0724 161 342</a></p>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <p style="font-weight: 600;">Email</p>
                            <p style="color: var(--text-mid);"><a
                                    href="mailto:info@zaharacowork.com">info@zaharacowork.com</a></p>
                        </div>
                        <div>
                            <p style="font-weight: 600;">Business Hours</p>
                            <p style="color: var(--text-mid);">Mon - Fri: 8:00 AM - 6:00 PM<br>Sat: 9:00 AM - 3:00 PM
                            </p>
                        </div>
<div class="map-container" id="contactMap" style="height: 250px; margin-top: 24px;"></div>
                    </div>
                </div>
            </div>
        </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof initSingleSpaceMap === 'function') {
        initSingleSpaceMap('contactMap', {
            lat: -1.2628,
            lng: 36.8119,
            name: 'Zahara Co-Working Space',
            address: 'Krishna Centre, 2nd Floor, Westlands, Nairobi'
        });
    }
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>