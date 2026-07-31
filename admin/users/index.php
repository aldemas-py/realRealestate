<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $db->prepare("UPDATE users SET status='active', approval_date=NOW(), approved_by=? WHERE id=?")->execute([$_SESSION['user_id'], $id]);
    logAudit($_SESSION['user_id'], 'user.approve', 'user', $id);
    $_SESSION['success'] = 'User approved.';
    header('Location: /work_folder/realRealestate/admin/users/index.php');
    exit;
}
if (isset($_GET['suspend'])) {
    $id = (int)$_GET['suspend'];
    $db->prepare("UPDATE users SET status='suspended' WHERE id=?")->execute([$id]);
    logAudit($_SESSION['user_id'], 'user.suspend', 'user', $id);
    $_SESSION['success'] = 'User suspended.';
    header('Location: /work_folder/realRealestate/admin/users/index.php');
    exit;
}
if (isset($_GET['activate'])) {
    $id = (int)$_GET['activate'];
    $db->prepare("UPDATE users SET status='active' WHERE id=? AND role_id=2")->execute([$id]);
    $_SESSION['success'] = 'User reactivated.';
    header('Location: /work_folder/realRealestate/admin/users/index.php');
    exit;
}
$users = $db->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC")->fetchAll();
$pageTitle = 'Users - Admin';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-layout">
    <aside class="admin-sidebar"><?php require __DIR__ . '/../sidebar.php'; ?></aside>
    <main class="admin-main">
        <div class="admin-header">
            <h1>User Management</h1>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Approved</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= ucfirst($u['role_name']) ?></td>
                        <td><span class="status-badge status-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span>
                        </td>
                        <td><?= $u['approval_date'] ? date('d M Y', strtotime($u['approval_date'])) : '-' ?></td>
                        <td><?= $u['last_login'] ? date('d M Y', strtotime($u['last_login'])) : 'Never' ?></td>
                        <td><?php if ($u['status'] === 'pending' && $u['role_id'] == 2): ?><a
                                href="?approve=<?= $u['id'] ?>"
                                class="btn btn-sm btn-success">Approve</a><?php elseif ($u['status'] === 'active' && $u['role_id'] == 2): ?><a
                                href="?suspend=<?= $u['id'] ?>"
                                class="btn btn-sm btn-danger">Suspend</a><?php elseif ($u['status'] === 'suspended'): ?><a
                                href="?activate=<?= $u['id'] ?>"
                                class="btn btn-sm btn-success">Reactivate</a><?php else: ?><span
                                style="color: var(--text-light);">-</span><?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>