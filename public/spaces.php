<?php

/**
 * BROWSE SPACES - With filters
 */
$pageTitle = 'Office Spaces - FlexiSpace | Westlands, Nairobi';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Filters
$type = $_GET['type'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$capacity = $_GET['capacity'] ?? '';
$search = trim($_GET['search'] ?? '');

$where = "WHERE os.status = 'available'";
$params = [];

if (!empty($type) && in_array($type, ['private_office', 'open_desk', 'meeting_room', 'virtual_office'])) {
    $where .= " AND os.space_type = ?";
    $params[] = $type;
}
if (!empty($minPrice)) {
    $where .= " AND os.price_per_month >= ?";
    $params[] = (float)$minPrice;
}
if (!empty($maxPrice)) {
    $where .= " AND os.price_per_month <= ?";
    $params[] = (float)$maxPrice;
}
if (!empty($capacity)) {
    $where .= " AND os.capacity >= ?";
    $params[] = (int)$capacity;
}
if (!empty($search)) {
    $where .= " AND (os.name LIKE ? OR os.description LIKE ? OR os.short_description LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM office_spaces os $where");
$countStmt->execute($params);
$totalSpaces = $countStmt->fetchColumn();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;
$totalPages = ceil($totalSpaces / $perPage);

// Fetch spaces
$stmt = $db->prepare("
    SELECT os.*, 
           (SELECT image_url FROM space_images WHERE space_id = os.id AND is_primary = TRUE LIMIT 1) as primary_image
    FROM office_spaces os 
    $where 
    ORDER BY os.is_featured DESC, os.created_at DESC 
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$spaces = $stmt->fetchAll();

// Get all available types for filter
$types = $db->query("SELECT DISTINCT space_type FROM office_spaces WHERE status = 'available'")->fetchAll(PDO::FETCH_COLUMN);
?>

<section class="section" style="padding: 40px 0;">
    <div class="container">
        <h1 style="font-size: 1.8rem; margin-bottom: 8px;">Available Office Spaces</h1>
        <p style="color: var(--text-mid); margin-bottom: 24px;"><?= $totalSpaces ?>
            space<?= $totalSpaces !== 1 ? 's' : '' ?> available in Westlands, Nairobi</p>

        <!-- Filters -->
        <form method="GET" action="" class="filters-bar">
            <input type="text" name="search" placeholder="Search spaces..." value="<?= htmlspecialchars($search) ?>">

            <select name="type">
                <option value="">All Types</option>
                <option value="private_office" <?= $type === 'private_office' ? 'selected' : '' ?>>Private Office
                </option>
                <option value="open_desk" <?= $type === 'open_desk' ? 'selected' : '' ?>>Coworking / Open Desk</option>
                <option value="meeting_room" <?= $type === 'meeting_room' ? 'selected' : '' ?>>Meeting Room</option>
                <option value="virtual_office" <?= $type === 'virtual_office' ? 'selected' : '' ?>>Virtual Office
                </option>
            </select>

            <select name="capacity">
                <option value="">Min Capacity</option>
                <option value="1" <?= $capacity === '1' ? 'selected' : '' ?>>1+ person</option>
                <option value="5" <?= $capacity === '5' ? 'selected' : '' ?>>5+ people</option>
                <option value="10" <?= $capacity === '10' ? 'selected' : '' ?>>10+ people</option>
                <option value="20" <?= $capacity === '20' ? 'selected' : '' ?>>20+ people</option>
            </select>

            <select name="max_price">
                <option value="">Max Budget</option>
                <option value="10000" <?= $maxPrice === '10000' ? 'selected' : '' ?>>Up to KES 10K</option>
                <option value="30000" <?= $maxPrice === '30000' ? 'selected' : '' ?>>Up to KES 30K</option>
                <option value="50000" <?= $maxPrice === '50000' ? 'selected' : '' ?>>Up to KES 50K</option>
                <option value="100000" <?= $maxPrice === '100000' ? 'selected' : '' ?>>Up to KES 100K</option>
            </select>

            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <?php if (!empty($_GET)): ?>
                <a href="/work_folder/realRealestate/public/spaces.php" class="btn btn-ghost btn-sm">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Spaces Grid -->
        <?php if (empty($spaces)): ?>
            <div style="text-align: center; padding: 80px 20px; color: var(--text-light);">
                <p style="font-size: 1.2rem; margin-bottom: 8px;">No spaces match your criteria</p>
                <p>Try adjusting your filters or <a href="/work_folder/realRealestate/public/spaces.php">view all
                        spaces</a>.</p>
            </div>
        <?php else: ?>
            <div class="spaces-grid">
                <?php foreach ($spaces as $space): ?>
                    <div class="space-card">
                        <div class="space-card-image">
                            <img src="<?= htmlspecialchars($space['primary_image'] ?? 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=400') ?>"
                                alt="<?= htmlspecialchars($space['name']) ?>" loading="lazy">
                            <span class="space-card-badge badge-available">Available</span>
                            <?php if ($space['is_featured']): ?>
                                <span class="space-card-badge badge-featured">Featured</span>
                            <?php endif; ?>
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
                                    class="btn btn-primary btn-sm">Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo;
                                    Previous</a>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?= $i ?>&<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                                    class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>&<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next
                                    &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
</section>

<section class="map-section">
    <div class="container">
        <h2 style="font-size: 1.4rem; margin-bottom: 20px;">All Spaces on Map</h2>
    </div>
    <div class="map-container" id="spacesMap"
        style="max-width: var(--max-width); margin: 0 auto; border-radius: var(--radius-md);"></div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php
        $mapSpaces = $db->query("
        SELECT id, name, slug, space_type, capacity, price_per_month, currency, 
               latitude as lat, longitude as lng, status,
               CONCAT(address_line1, ', ', city) as address
        FROM office_spaces WHERE status = 'available' LIMIT 50
    ")->fetchAll();
        ?>
        var spaces = <?= json_encode($mapSpaces) ?>;
        spaces.forEach(function(s) {
            s.url = '/work_folder/realRealestate/public/space-detail.php?slug=' + s.slug;
        });
        if (typeof initSpaceMap === 'function' && spaces.length > 0) {
            initSpaceMap('spacesMap', spaces);
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>