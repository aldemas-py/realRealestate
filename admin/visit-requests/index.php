<?php

/**
 * ADMIN - VISIT REQUESTS MANAGEMENT
 */
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$db = getDB();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];
    $adminNotes = trim($_POST['admin_notes'] ?? '');
    $allowed = ['approved', 'completed', 'rejected', 'cancelled'];

    if (in_array($status, $allowed)) {
        $stmt = $db->prepare("UPDATE visit_requests SET status=?, admin_notes=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?");
        $stmt->execute([$status, $adminNotes, $_SESSION['user_id'], $id]);
        logAudit($_SESSION['user_id'], 'visit.update', 'visit_request', $id, null, ['status' => $status]);
        $_SESSION['success'] = "Visit request $status successfully.";
    }
    header('Location: /work_folder/realRealestate/admin/visit-requests/index.php');
    exit;
}

$requests = $db->query("
    SELECT vr.*, u.full_name, u.email, u.phone, os.name as space_name, os.slug
    FROM visit_requests vr 
    JOIN users u ON vr.user_id = u.id 
    JOIN office_spaces os ON vr.space_id = os.id 
    ORDER BY vr.created_at DESC
")->fetchAll();

$pageTitle = 'Visit Requests - Admin';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <aside class="admin-sidebar"><?php require __DIR__ . '/../sidebar.php'; ?></aside>
    <main class="admin-main">
        <div class="admin-header">
            <h1>Visit Requests</h1>
        </div>

        <?php if (empty($requests)): ?>
            <p style="color: var(--text-light); padding: 40px; text-align: center;">No visit requests yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Space</th>
                            <th>Preferred</th>
                            <th>Alternate</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($r['full_name']) ?></strong><br>
                                    <small style="color: var(--text-light);"><?= htmlspecialchars($r['email']) ?></small>
                                </td>
                                <td><a href="/work_folder/realRealestate/public/space-detail.php?slug=<?= $r['slug'] ?>"
                                        target="_blank"><?= htmlspecialchars($r['space_name']) ?></a></td>
                                <td><?= date('d M Y', strtotime($r['preferred_date'])) ?><br><small><?= date('H:i', strtotime($r['preferred_time'])) ?></small>
                                </td>
                                <td><?= $r['alternate_date'] ? date('d M Y', strtotime($r['alternate_date'])) : '-' ?></td>
                                <td><span class="status-badge status-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span>
                                </td>
                                <td><small><?= htmlspecialchars(substr($r['notes'] ?? '', 0, 50)) ?></small></td>
                                <td>
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <form method="POST" action="" style="display: flex; gap: 4px; flex-wrap: wrap;">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <input type="text" name="admin_notes" placeholder="Admin notes"
                                                style="width: 100%; padding: 4px; font-size: 0.8rem;">
                                            <button type="submit" name="update_status" value="approved"
                                                class="btn btn-sm btn-success">Approve</button>
                                            <button type="submit" name="update_status" value="rejected"
                                                class="btn btn-sm btn-danger">Reject</button>
                                        </form>
                                    <?php elseif ($r['status'] === 'approved'): ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="admin_notes"
                                                value="<?= htmlspecialchars($r['admin_notes'] ?? '') ?>">
                                            <button type="submit" name="update_status" value="completed"
                                                class="btn btn-sm btn-success">Mark Completed</button>
                                        </form>
                                    <?php else: ?>
                                        <span
                                            style="color: var(--text-light); font-size: 0.85rem;"><?= date('d M', strtotime($r['reviewed_at'])) ?></span>
                                    <?php endif; ?>
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