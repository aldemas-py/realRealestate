<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$db = getDB();

$stats = $db->query("SELECT (SELECT COUNT(*) FROM office_spaces) as total_spaces, (SELECT COUNT(*) FROM office_spaces WHERE status = 'available') as available_spaces, (SELECT COUNT(*) FROM office_spaces WHERE status = 'occupied') as occupied_spaces, (SELECT COUNT(*) FROM users WHERE role_id = 2 AND status = 'active') as active_customers, (SELECT COUNT(*) FROM users WHERE status = 'pending') as pending_users, (SELECT COUNT(*) FROM visit_requests WHERE status = 'pending') as pending_visits, (SELECT COUNT(*) FROM leases WHERE status = 'active') as active_leases, (SELECT COUNT(*) FROM leases WHERE status IN ('expiring','deposit_pending')) as attention_leases, (SELECT COUNT(*) FROM payments WHERE status = 'overdue') as overdue_payments, (SELECT COUNT(*) FROM payments WHERE status = 'paid') as total_paid, (SELECT COUNT(*) FROM testimonials WHERE status = 'pending') as pending_testimonials, (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid') as total_revenue")->fetch();

$pageTitle = 'Admin Dashboard - Zahara Co-Working Space';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
    <aside class="admin-sidebar"><?php require __DIR__ . '/sidebar.php'; ?></aside>
    <main class="admin-main">
        <div class="admin-header">
            <h1>Dashboard</h1>
            <div><span style="color: var(--text-light); font-size: 0.9rem;"><?= date('l, F j, Y') ?></span><a
                    href="/work_folder/realRealestate/public/logout.php" class="btn btn-ghost btn-sm">Logout</a></div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Spaces</h3>
                    <div class="stat-value"><?= $stats['total_spaces'] ?></div>
                    <div class="stat-label"><?= $stats['available_spaces'] ?> available &middot;
                        <?= $stats['occupied_spaces'] ?> occupied</div>
                </div>
                <div class="stat-card">
                    <h3>Active Leases</h3>
                    <div class="stat-value"><?= $stats['active_leases'] ?></div>
                    <div class="stat-label"><?= $stats['attention_leases'] ?> need attention</div>
                </div>
                <div class="stat-card">
                    <h3>Active Customers</h3>
                    <div class="stat-value"><?= $stats['active_customers'] ?></div>
                    <div class="stat-label"><?= $stats['pending_users'] ?> pending approval</div>
                </div>
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <div class="stat-value">KES <?= number_format($stats['total_revenue']) ?></div>
                    <div class="stat-label">From <?= $stats['total_paid'] ?> payments</div>
                </div>
                <div class="stat-card">
                    <h3>Pending Visits</h3>
                    <div class="stat-value" style="color: var(--warning);"><?= $stats['pending_visits'] ?></div>
                    <div class="stat-label">Requires review</div>
                </div>
                <div class="stat-card">
                    <h3>Overdue Payments</h3>
                    <div class="stat-value" style="color: var(--danger);"><?= $stats['overdue_payments'] ?></div>
                    <div class="stat-label">Compliance alert</div>
                </div>
                <div class="stat-card">
                    <h3>Pending Reviews</h3>
                    <div class="stat-value" style="color: var(--info);"><?= $stats['pending_testimonials'] ?></div>
                    <div class="stat-label">Awaiting moderation</div>
                </div>
                <div class="stat-card">
                    <h3>Compliance Status</h3>
                    <div class="stat-value" style="color: var(--success);">&#10003;</div>
                    <div class="stat-label">Policy Engine Active</div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    <div
                        style="background: var(--bg-white); border-radius: var(--radius-md); padding: 24px; border: 1px solid var(--border);">
                        <h3 style="margin-bottom: 16px;">Recent Visit Requests</h3>
                        <?php $recentVisits = $db->query("SELECT vr.*, u.full_name, os.name as space_name FROM visit_requests vr JOIN users u ON vr.user_id = u.id JOIN office_spaces os ON vr.space_id = os.id ORDER BY vr.created_at DESC LIMIT 5")->fetchAll(); ?>
                        <?php if (empty($recentVisits)): ?><p style="color: var(--text-light);">No visit requests yet.
                        </p>
                        <?php else: ?><div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Space</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody><?php foreach ($recentVisits as $v): ?><tr>
                                        <td><?= htmlspecialchars($v['full_name']) ?></td>
                                        <td><?= htmlspecialchars($v['space_name']) ?></td>
                                        <td><?= date('d M', strtotime($v['preferred_date'])) ?>
                                        </td>
                                        <td><span
                                                class="status-badge status-<?= $v['status'] ?>"><?= ucfirst($v['status']) ?></span>
                                        </td>
                                    </tr><?php endforeach; ?></tbody>
                            </table>
                        </div><?php endif; ?>
                        <a href="/work_folder/realRealestate/admin/visit-requests/index.php"
                            class="btn btn-ghost btn-sm" style="margin-top: 12px;">View All
                            &rarr;</a>
                    </div>

                    <div
                        style="background: var(--bg-white); border-radius: var(--radius-md); padding: 24px; border: 1px solid var(--border);">
                        <h3 style="margin-bottom: 16px;">Recent Payments</h3>
                        <?php $recentPayments = $db->query("SELECT p.*, u.full_name, os.name as space_name FROM payments p JOIN users u ON p.user_id = u.id JOIN leases l ON p.lease_id = l.id JOIN office_spaces os ON l.space_id = os.id ORDER BY p.created_at DESC LIMIT 5")->fetchAll(); ?>
                        <?php if (empty($recentPayments)): ?><p style="color: var(--text-light);">No payments recorded
                            yet.</p>
                        <?php else: ?><div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Space</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody><?php foreach ($recentPayments as $p): ?><tr>
                                        <td><?= htmlspecialchars($p['full_name']) ?></td>
                                        <td><?= htmlspecialchars($p['space_name']) ?></td>
                                        <td>KES <?= number_format($p['amount']) ?></td>
                                        <td><span
                                                class="status-badge status-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span>
                                        </td>
                                    </tr><?php endforeach; ?></tbody>
                            </table>
                        </div><?php endif; ?>
                        <a href="/work_folder/realRealestate/admin/payments/index.php" class="btn btn-ghost btn-sm"
                            style="margin-top: 12px;">View All
                            &rarr;</a>
                    </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>