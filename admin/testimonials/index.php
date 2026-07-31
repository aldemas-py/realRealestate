<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $db->prepare("UPDATE testimonials SET status='approved', reviewed_by=?, reviewed_at=NOW() WHERE id=?")->execute([$_SESSION['user_id'], $id]);
    logAudit($_SESSION['user_id'], 'testimonial.approve', 'testimonial', $id);
    $_SESSION['success'] = 'Testimonial approved.';
    header('Location: /work_folder/realRealestate/admin/testimonials/index.php');
    exit;
}
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $db->prepare("UPDATE testimonials SET status='rejected', reviewed_by=?, reviewed_at=NOW() WHERE id=?")->execute([$_SESSION['user_id'], $id]);
    logAudit($_SESSION['user_id'], 'testimonial.reject', 'testimonial', $id);
    $_SESSION['success'] = 'Testimonial rejected.';
    header('Location: /work_folder/realRealestate/admin/testimonials/index.php');
    exit;
}
if (isset($_GET['feature'])) {
    $id = (int)$_GET['feature'];
    $db->prepare("UPDATE testimonials SET is_featured = NOT is_featured WHERE id=?")->execute([$id]);
    header('Location: /work_folder/realRealestate/admin/testimonials/index.php');
    exit;
}

$testimonials = $db->query("SELECT t.*, u.full_name, os.name as space_name FROM testimonials t JOIN users u ON t.user_id = u.id LEFT JOIN office_spaces os ON t.space_id = os.id ORDER BY t.status ASC, t.is_featured DESC, t.created_at DESC")->fetchAll();
$pageTitle = 'Testimonials - Admin';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-layout">
    <aside class="admin-sidebar"><?php require __DIR__ . '/../sidebar.php'; ?></aside>
    <main class="admin-main">
        <div class="admin-header">
            <h1>Testimonial Moderation</h1>
        </div>
        <?php if (empty($testimonials)): ?><p style="color: var(--text-light); text-align: center; padding: 40px;">No
            testimonials yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Space</th>
                        <th>Review</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($testimonials as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['full_name']) ?></td>
                        <td><?= htmlspecialchars($t['space_name'] ?? 'General') ?></td>
                        <td><small>"<?= htmlspecialchars(substr($t['content'], 0, 80)) ?><?= strlen($t['content']) > 80 ? '...' : '' ?>"</small>
                        </td>
                        <td><?= str_repeat('★', $t['rating']) ?></td>
                        <td><span class="status-badge status-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span>
                        </td>
                        <td><?= $t['is_featured'] ? '✓' : '-' ?></td>
                        <td>
                            <div style="display: flex; gap: 4px;"><?php if ($t['status'] === 'pending'): ?><a
                                    href="?approve=<?= $t['id'] ?>" class="btn btn-sm btn-success">Approve</a><a
                                    href="?reject=<?= $t['id'] ?>"
                                    class="btn btn-sm btn-danger">Reject</a><?php endif; ?><a
                                    href="?feature=<?= $t['id'] ?>"
                                    class="btn btn-sm btn-ghost"><?= $t['is_featured'] ? 'Unfeature' : 'Feature' ?></a>
                            </div>
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