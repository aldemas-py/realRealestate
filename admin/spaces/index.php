<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("SELECT name FROM office_spaces WHERE id = ?");
    $stmt->execute([$id]);
    $space = $stmt->fetch();
    if ($space) {
        $db->prepare("DELETE FROM office_spaces WHERE id = ?")->execute([$id]);
        logAudit($_SESSION['user_id'], 'space.delete', 'office_space', $id, $space);
        $_SESSION['success'] = 'Space deleted successfully.';
    }
    header('Location: /work_folder/realRealestate/admin/spaces/index.php');
    exit;
}

if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $newStatus = $_GET['toggle_status'];
    $allowed = ['available', 'occupied', 'maintenance', 'unavailable'];
    if (in_array($newStatus, $allowed)) {
        $db->prepare("UPDATE office_spaces SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
        logAudit($_SESSION['user_id'], 'space.status_change', 'office_space', $id, null, ['status' => $newStatus]);
        $_SESSION['success'] = "Space status updated to '$newStatus'.";
    }
    header('Location: /work_folder/realRealestate/admin/spaces/index.php');
    exit;
}

$spaces = $db->query("SELECT os.*, (SELECT image_url FROM space_images WHERE space_id = os.id AND is_primary = TRUE LIMIT 1) as primary_image, (SELECT COUNT(*) FROM leases WHERE space_id = os.id AND status IN ('active','deposit_pending')) as active_leases_count FROM office_spaces os ORDER BY os.created_at DESC")->fetchAll();

$pageTitle = 'Manage Spaces - Admin';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-layout">
    <aside class="admin-sidebar"><?php require __DIR__ . '/../sidebar.php'; ?></aside>
    <main class="admin-main">
        <div class="admin-header">
            <h1>Manage Office Spaces</h1><a href="/work_folder/realRealestate/admin/spaces/create.php"
                class="btn btn-primary">+ Add New Space</a>
        </div>
        <?php if (empty($spaces)): ?><div style="text-align: center; padding: 60px; color: var(--text-light);">
            <p style="font-size: 1.1rem;">No spaces created yet.</p><a
                href="/work_folder/realRealestate/admin/spaces/create.php" class="btn btn-primary"
                style="margin-top: 16px;">Create First Space</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Space</th>
                        <th>Type</th>
                        <th>Capacity</th>
                        <th>Price/Month</th>
                        <th>Status</th>
                        <th>Active Leases</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($spaces as $s): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['name']) ?></strong><br><small
                                style="color: var(--text-light);"><?= htmlspecialchars($s['address_line1']) ?>,
                                <?= htmlspecialchars($s['city']) ?></small></td>
                        <td><?= ucwords(str_replace('_', ' ', $s['space_type'])) ?></td>
                        <td><?= $s['capacity'] ?> seats</td>
                        <td>KES <?= number_format($s['price_per_month']) ?></td>
                        <td><span class="status-badge status-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span>
                        </td>
                        <td><?= $s['active_leases_count'] ?></td>
                        <td>
                            <div style="display: flex; gap: 4px; flex-wrap: wrap;"><a
                                    href="/work_folder/realRealestate/admin/spaces/edit.php?id=<?= $s['id'] ?>"
                                    class="btn btn-sm btn-outline">Edit</a><a
                                    href="/work_folder/realRealestate/public/space-detail.php?slug=<?= $s['slug'] ?>"
                                    class="btn btn-sm btn-ghost" target="_blank">View</a><a
                                    href="?toggle_status=<?= $s['status'] === 'available' ? 'maintenance' : 'available' ?>&id=<?= $s['id'] ?>"
                                    class="btn btn-sm btn-ghost">Toggle</a><a href="?delete=<?= $s['id'] ?>"
                                    class="btn btn-sm btn-danger"
                                    data-confirm="Delete this space? This cannot be undone.">Delete</a></div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>