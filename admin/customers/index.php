<?php

/**
 * ADMIN - CUSTOMERS MANAGEMENT
 */
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$db = getDB();

if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $db->prepare("UPDATE users SET status='active', approval_date=NOW(), approved_by=? WHERE id=?")->execute([$_SESSION['user_id'], $id]);
    logAudit($_SESSION['user_id'], 'user.approve', 'user', $id);
    $_SESSION['success'] = 'Customer approved.';
    header('Location: /work_folder/realRealestate/admin/customers/index.php');
    exit;
}

if (isset($_GET['suspend'])) {
    $id = (int)$_GET['suspend'];
    $db->prepare("UPDATE users SET status='suspended' WHERE id=? AND role_id=2")->execute([$id]);
    logAudit($_SESSION['user_id'], 'user.suspend', 'user', $id);
    $_SESSION['success'] = 'Customer suspended.';
    header('Location: /work_folder/realRealestate/admin/customers/index.php');
    exit;
}

$customers = $db->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM leases WHERE user_id = u.id) as leases_count,
           (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE user_id = u.id AND status = 'paid') as total_paid
    FROM users u WHERE u.role_id = 2 
    ORDER BY u.created_at DESC
")->fetchAll();

$pageTitle = 'Customers - Admin';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <aside class="admin-sidebar"><?php require __DIR__ . '/../sidebar.php'; ?></aside>
    <main class="admin-main">
        <div class="admin-header">
            <h1>Manage Customers</h1>
        </div>
        <?php if (empty($customers)): ?>
            <p style="color: var(--text-light); padding: 40px; text-align: center;">No customers registered yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Leases</th>
                            <th>Total Paid</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $c): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($c['full_name']) ?></strong></td>
                                <td><?= htmlspecialchars($c['email']) ?></td>
                                <td><?= htmlspecialchars($c['phone'] ?? '-') ?></td>
                                <td><span class="status-badge status-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span>
                                </td>
                                <td><?= $c['leases_count'] ?></td>
                                <td>KES <?= number_format($c['total_paid']) ?></td>
                                <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                                <td>
                                    <?php if ($c['status'] === 'pending'): ?>
                                        <a href="?approve=<?= $c['id'] ?>" class="btn btn-sm btn-success">Approve</a>
                                    <?php elseif ($c['status'] === 'active'): ?>
                                        <a href="?suspend=<?= $c['id'] ?>" class="btn btn-sm btn-danger"
                                            data-confirm="Suspend this customer?">Suspend</a>
                                    <?php endif; ?>
                                    <a href="/work_folder/realRealestate/admin/leases/index.php?user_id=<?= $c['id'] ?>"
                                        class="btn btn-sm btn-ghost">Leases</a>
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