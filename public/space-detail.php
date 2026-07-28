<?php

/**
 * SPACE DETAIL PAGE - With visit request form and map
 */
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$slug = $_GET['slug'] ?? '';

$stmt = $db->prepare("SELECT * FROM office_spaces WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$space = $stmt->fetch();

if (!$space) {
    $_SESSION['error'] = 'Space not found.';
    header('Location: /work_folder/realRealestate/public/spaces.php');
    exit;
}

// Get images
$images = $db->prepare("SELECT * FROM space_images WHERE space_id = ? ORDER BY is_primary DESC, sort_order ASC");
$images->execute([$space['id']]);
$images = $images->fetchAll();

// Handle visit request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_visit'])) {
    requireAuth();

    $preferredDate = $_POST['preferred_date'] ?? '';
    $preferredTime = $_POST['preferred_time'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];
    if (empty($preferredDate)) $errors[] = 'Preferred date is required.';
    if (empty($preferredTime)) $errors[] = 'Preferred time is required.';

    // Policy: Check max pending visits
    $maxPending = (int)getPolicyValue('max_pending_visits', '3');
    $pendingCount = $db->prepare("SELECT COUNT(*) FROM visit_requests WHERE user_id = ? AND status = 'pending'");
    $pendingCount->execute([$_SESSION['user_id']]);
    if ($pendingCount->fetchColumn() >= $maxPending) {
        $errors[] = "You can only have $maxPending pending visit requests at a time.";
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO visit_requests (user_id, space_id, preferred_date, preferred_time, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $space['id'], $preferredDate, $preferredTime, $notes]);

            logAudit(
                $_SESSION['user_id'],
                'visit.request',
                'visit_request',
                $db->lastInsertId(),
                null,
                ['space_id' => $space['id'], 'date' => $preferredDate],
                'pass'
            );

            $_SESSION['success'] = 'Visit request submitted! The admin will review and confirm your appointment.';
        } catch (Exception $e) {
            if ($e->getCode() == 23000) {
                $_SESSION['error'] = 'You already have a pending request for this space. Please wait for it to be reviewed.';
            } else {
                $_SESSION['error'] = 'Failed to submit request. Please try again.';
                error_log("Visit request error: " . $e->getMessage());
            }
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }

    header("Location: /work_folder/realRealestate/public/space-detail.php?slug=$slug");
    exit;
}

$pageTitle = $space['name'] . ' - FlexiSpace';
$amenities = json_decode($space['amenities'] ?? '[]', true) ?? [];
?>

<section class="space-detail-hero">
    <div class="container">
        <div class="space-detail-grid">
            <!-- Left: Gallery -->
            <div class="space-gallery">
                <div class="space-gallery-main">
                    <?php if (!empty($images)): ?>
                        <img src="<?= htmlspecialchars($images[0]['image_url']) ?>"
                            alt="<?= htmlspecialchars($images[0]['alt_text'] ?? $space['name']) ?>" id="mainImage">
                    <?php else: ?>
                        <div
                            style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: var(--bg-light); color: var(--text-light); font-size: 3rem;">
                            &#127970;</div>
                    <?php endif; ?>
                </div>
                <?php if (count($images) > 1): ?>
                    <div class="space-gallery-thumbs">
                        <?php foreach ($images as $img): ?>
                            <img src="<?= htmlspecialchars($img['image_url']) ?>"
                                alt="<?= htmlspecialchars($img['alt_text'] ?? '') ?>"
                                class="<?= $img['is_primary'] ? 'active' : '' ?>">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Info + Form -->
            <div>
                <div class="space-info">
                    <div class="space-meta">
                        <span>&#128205; <?= htmlspecialchars($space['address_line1']) ?>,
                            <?= htmlspecialchars($space['city']) ?></span>
                        <span>&#128101; Up to <?= $space['capacity'] ?> people</span>
                        <span>&#128196; <?= ucwords(str_replace('_', ' ', $space['space_type'])) ?></span>
                        <?php if ($space['size_sqft'] > 0): ?>
                            <span>&#128207; <?= $space['size_sqft'] ?> sq ft</span>
                        <?php endif; ?>
                    </div>
                    <h1><?= htmlspecialchars($space['name']) ?></h1>
                    <div class="space-price">KES <?= number_format($space['price_per_month']) ?> <small>/month</small>
                    </div>
                    <p style="color: var(--text-mid); font-size: 0.9rem;">
                        Security deposit: KES <?= number_format($space['security_deposit']) ?>
                    </p>

                    <!-- Amenities -->
                    <?php if (!empty($amenities)): ?>
                        <div class="space-amenities">
                            <?php foreach ($amenities as $amenity): ?>
                                <span class="amenity-tag"><?= ucwords(str_replace('_', ' ', $amenity)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="space-description-full">
                        <?= nl2br(htmlspecialchars($space['description'])) ?>
                    </div>

                    <!-- Request Visit Form -->
                    <div class="request-form">
                        <h3>Request a Site Visit</h3>
                        <p style="font-size: 0.85rem; color: var(--text-mid); margin-bottom: 16px;">
                            See the space in person. Admin will confirm your appointment.
                        </p>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="preferred_date">Preferred Date</label>
                                <input type="date" id="preferred_date" name="preferred_date"
                                    min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="preferred_time">Preferred Time</label>
                                <input type="time" id="preferred_time" name="preferred_time" required>
                            </div>
                            <div class="form-group">
                                <label for="notes">Additional Notes (optional)</label>
                                <textarea id="notes" name="notes"
                                    placeholder="Any special requirements or questions..."></textarea>
                            </div>
                            <button type="submit" name="request_visit" class="btn btn-primary" style="width: 100%; justify-content: center