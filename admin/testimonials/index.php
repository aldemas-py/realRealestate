<?php

/**
 * ADMIN - TESTIMONIAL MODERATION
 */
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

$testimonials = $db->query("
    SELECT t.*, u.full_name, u.email, os.name as space_name
    FROM testimonials t 
    JOIN users u ON t.user_id = u.id 
    LEFT JOIN office_spaces os ON t.space_id = os.id 
    ORDER BY t.status ASC, t.created_at DESC
")->fetchAll();

$pageTitle = 'Testimonials - Admin';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <aside class="admin-sidebar"><?php require __DIR__ . '/../sidebar.php'; ?></aside>
    <main class="admin-main">
        <div class="admin-header">
            <h1>Testimonial Moderation</h1>
        </div>

        <?php if (empty($testimonials)): ?>
            <p style="color: var(--text-light); padding: 40px; text-align: center;">No testimonials yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Author</th>
                            <th>Space</th>
                            <th>Rating</th>
                            <th>Content</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($testimonials as $t): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($t['full_name']) ?></strong></td>
                                <td><?= htmlspecialchars($t['space_name'] ?? 'General') ?></td>
                                <td><span
                                        style="color: #f59e0b;"><?= str_repeat('★', $t['rating']) . str_repeat('☆', 5 - $t['rating']) ?></span>
                                </td>
                                <td><small>"<?= htmlspecialchars(substr($t['content'], 0, 100)) ?><?= strlen($t['content']) > 100 ? '...' : '' ?>"</small>
                                </td>
                                <td><span class="status-badge status-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span>
                                </td>
                                <td><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                                <td>
                                    <div style="display: flex; gap: 4px;">
                                        <?php if ($t['status'] === 'pending'): ?>
                                            <a href="?approve=<?= $t['id'] ?>" class="btn btn-sm btn-success">Approve</a>
                                            <a href="?reject=<?= $t['id'] ?>" class="btn btn-sm btn-danger">Reject</a>
                                        <?php endif; ?>
                                        <a href="?feature=<?= $t['id'] ?>"
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