<?php
$pageTitle = 'Testimonials - Zahara Co-Working Space';
require_once __DIR__ . '/../includes/header.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_testimonial'])) {
    requireAuth();
    $content = trim($_POST['content'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    $spaceId = !empty($_POST['space_id']) ? (int)$_POST['space_id'] : null;
    if (empty($content) || $rating < 1 || $rating > 5) {
        $_SESSION['error'] = 'Please provide your review content and a rating (1-5).';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO testimonials (user_id, space_id, content, rating, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$_SESSION['user_id'], $spaceId, $content, $rating]);
            logAudit($_SESSION['user_id'], 'testimonial.submit', 'testimonial', $db->lastInsertId());
            $_SESSION['success'] = 'Thank you for your review! It will be published after admin approval.';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to submit review. Please try again.';
        }
    }
    header('Location: /work_folder/realRealestate/public/testimonials.php');
    exit;
}

$testimonials = $db->query("SELECT t.*, u.full_name, os.name as space_name FROM testimonials t JOIN users u ON t.user_id = u.id LEFT JOIN office_spaces os ON t.space_id = os.id WHERE t.status = 'approved' ORDER BY t.is_featured DESC, t.created_at DESC LIMIT 50")->fetchAll();
$spaces = $db->query("SELECT id, name FROM office_spaces WHERE status = 'available' ORDER BY name")->fetchAll();
?>
<section class="section" style="padding: 40px 0;">
    <div class="container">
        <h1 class="section-title">What Our Clients Say</h1>
        <p class="section-subtitle">Real reviews from businesses and professionals who work at Zahara Co-Working Space.
        </p>
        <?php if (empty($testimonials)): ?>
            <div style="text-align: center; padding: 60px; color: var(--text-light);">
                <p style="font-size: 1.1rem;">No reviews yet. Be the first to share your experience!</p>
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
                                </div><?php if ($t['space_name']): ?><small>Works at:
                                        <?= htmlspecialchars($t['space_name']) ?></small><?php endif; ?>
                            </div>
                            <p class="testimonial-content">"<?= htmlspecialchars($t['content']) ?>"</p>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (isLoggedIn()): ?>
                    <div
                        style="max-width: 600px; margin: 50px auto 0; padding: 30px; background: var(--bg-white); border-radius: var(--radius-md); border: 1px solid var(--border);">
                        <h3 style="margin-bottom: 16px;">Share Your Experience</h3>
                        <form method="POST" action="">
                            <div class="form-group"><label>Rating</label>
                                <div style="display: flex; gap: 4px; font-size: 1.5rem;">
                                    <?php for ($i = 5; $i >= 1; $i--): ?><label style="cursor: pointer;"><input type="radio"
                                                name="rating" value="<?= $i ?>" style="display: none;"
                                                onchange="this.closest('div').querySelectorAll('label').forEach((l,j)=>l.style.color=j<5-this.value?'var(--accent)':'var(--border)')"><span
                                                style="color: var(--border); transition: var(--transition);">&#9733;</span></label><?php endfor; ?>
                                </div>
                                <div class="form-group"><label for="space_id">Related Space (optional)</label><select
                                        id="space_id" name="space_id">
                                        <option value="">General review</option><?php foreach ($spaces as $s): ?><option
                                                value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select></div>
                                <div class="form-group"><label for="content">Your Review</label><textarea id="content"
                                        name="content" placeholder="Tell us about your experience..." required
                                        minlength="10"></textarea></div>
                                <button type="submit" name="submit_testimonial" class="btn btn-primary">Submit Review</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div
                        style="text-align: center; margin-top: 40px; padding: 30px; background: var(--bg-white); border-radius: var(--radius-md); border: 1px solid var(--border);">
                        <p style="color: var(--text-mid);">Want to share your experience? <a
                                href="/work_folder/realRealestate/public/login.php">Sign in</a> to leave a review.</p>
                    </div>
                <?php endif; ?>
            </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>