<?php

/**
 * SPACES LISTING PAGE
 */
$pageTitle = 'Our Spaces - Zahara Co-Working Space';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$typeFilter = $_GET['type'] ?? '';
$searchQuery = trim($_GET['q'] ?? '');

$where = "WHERE os.status = 'available'";
$params = [];
if (!empty($typeFilter) && in_array($typeFilter, ['private_office', 'open_desk', 'meeting_room', 'virtual_office'])) {
    $where .= " AND os.space_type = ?";
    $params[] = $typeFilter;
}
if (!empty($searchQuery)) {
    $where .= " AND (os.name LIKE ? OR os.description LIKE ? OR os.address_line1 LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$spaces = $db->prepare("
    SELECT os.*, (SELECT image_url FROM space_images WHERE space_id = os.id AND is_primary = TRUE LIMIT 1) as primary_image
    FROM office_spaces os $where
    ORDER BY os.is_featured DESC, os.price_per_month ASC
");
$spaces->execute($params);
$spaces = $spaces->fetchAll();
?>
<section class="section" style="padding: 40px 0;">
    <div class="container">
        <h1 class="section-title">Our Workspaces</h1>
        <p class="section-subtitle">Choose from private offices, open spaces, boardrooms, and virtual packages at
            Krishna Centre, Westlands.</p>

        <div class="filter-bar">
            <form method="GET" action=""
                style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; width: 100%;">
                <select name="type">
                    <option value="">All Types</option>
                    <option value="private_office" <?= $typeFilter === 'private_office' ? 'selected' : '' ?>>Private
                        Office</option>
                    <option value="open_desk" <?= $typeFilter === 'open_desk' ? 'selected' : '' ?>>Open Space</option>
                    <option value="meeting_room" <?= $typeFilter === 'meeting_room' ? 'selected' : '' ?>>Boardroom
                    </option>
                    <option value="virtual_office" <?= $typeFilter === 'virtual_office' ? 'selected' : '' ?>>Virtual
                        Office</option>
                </select>
                <input type="text" name="q" placeholder="Search spaces..."
                    value="<?= htmlspecialchars($searchQuery) ?>">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="/work_folder/realRealestate/public/spaces.php" class="btn btn-ghost btn-sm">Clear</a>
            </form>
        </div>

        <?php if (empty($spaces)): ?>
            <div style="text-align: center; padding: 60px; color: var(--text-light);">
                <p style="font-size: 1.1rem;">No spaces match your criteria.</p><a
                    href="/work_folder/realRealestate/public/spaces.php" class="btn btn-primary"
                    style="margin-top: 16px;">View All</a>
            </div>
        <?php else: ?>
            <div class="spaces-grid">
                <?php foreach ($spaces as $space): ?>
                    <div class="space-card">
                        <div class="space-card-image">
                            <img src="<?= htmlspecialchars($space['primary_image'] ?? 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=400') ?>"
                                alt="<?= htmlspecialchars($space['name']) ?>" loading="lazy">
                            <span class="space-card-badge badge-available">Available</span>
                            <?php if ($space['is_featured']): ?><span
                                    class="space-card-badge badge-featured">Featured</span><?php endif; ?>
                        </div>
                        <div class="space-card-body">
                            <h3><a
                                    href="/work_folder/realRealestate/public/space-detail.php?slug=<?= $space['slug'] ?>"><?= htmlspecialchars($space['name']) ?></a>
                            </h3>
                            <div class="space-meta"><span>&#128205; <?= htmlspecialchars($space['address_line1']) ?>,
                                    <?= htmlspecialchars($space['city']) ?></span><span>&#128101; <?= $space['capacity'] ?>
                                    seats</span><span>&#128196;
                                    <?= ucwords(str_replace('_', ' ', $space['space_type'])) ?></span></div>
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
                <?php endif; ?>
            </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>