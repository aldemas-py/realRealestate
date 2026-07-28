<?php

/**
 * ADMIN - USER MANAGEMENT
 */
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
    $db->prepare("UPDATE users SET status='active' WHERE id=?")->execute([$id]);
    logAudit($_SESSION['user_id'], 'user.activate', 'user', $id);
    $_SESSION['success'] = 'User activated.';
    header('Location: /work_folder/realRealestate/admin/users/index.php');
    exit;
}

$users = $db->query("
    SELECT u.*, r.role_name, r.role_level,
           (SELECT COUNT(*) FROM leases WHERE user_id = u.id) as leases_count
    FROM users u 
    JOIN roles r ON u.role_id = r.id
    ORDER BY u.created_at DESC
")->fetchAll();

$pageTitle = 'Users - Admin';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <aside class="admin-sidebar"><?php require __DIR__ . '/../sidebar.php'; ?></aside>
    <main class="admin-main">
        <div class="admin-header">
            <h1>User Management</h1>
        </div>

        <?php if (empty($users)): ?>
            <p style="color: var(--text-light); padding: 40px; text-align: center;">No users found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Leases</th>
                            <th>Last Login</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                                <td><span class="status-badge"
                                        style="background: <?= $u['role_level'] >= 100 ? 'var(--primary)' : 'var(--info)' ?>;"><?= ucfirst($u['role_name']) ?></span>
                                </td>
                                <td><span class="status-badge status-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span>
                                </td>
                                <td><?= $u['leases_count'] ?></td>
                                <td><?= $u['last_login'] ? date('d M Y', strtotime($u['last_login'])) : 'Never' ?></td>
                                <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <div style="display: flex; gap: 4px;">
                                        <?php if ($u['status'] === 'pending'): ?>
                                            <a href="?approve=<?= $u['id'] ?>" class="btn btn-sm btn-success">Approve</a>
                                        <?php endif; ?>
                                        <?php if ($u['status'] === 'active' && $u['role_level'] < 100): ?>
                                            <a href="?suspend=<?= $u['id'] ?>" class="btn btn-sm btn-danger"
                                                data-confirm="Suspend this user?">Suspend</a>
                                        <?php endif; ?>
                                        <?php if ($u['status'] === 'suspended'): ?>
                                            <a href="?activate=<?= $u['id'] ?>" class="btn btn-sm btn-primary">Activate</a>
                                        <?php endif; ?>
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