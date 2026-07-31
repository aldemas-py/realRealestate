<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth();
$db = getDB();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if (!empty($fullName)) {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$fullName, $phone, $user['id']]);
        logAudit($user['id'], 'profile.update', 'user', $user['id']);
        $_SESSION['success'] = 'Profile updated successfully.';
    }
    header('Location: /work_folder/realRealestate/public/profile.php');
    exit;
}

$leases = $db->prepare("SELECT l.*, os.name as space_name, os.address_line1, os.city FROM leases l JOIN office_spaces os ON l.space_id = os.id WHERE l.user_id = ? ORDER BY l.created_at DESC");
$leases->execute([$user['id']]);
$leases = $leases->fetchAll();

$payments = $db->prepare("SELECT p.*, os.name as space_name FROM payments p JOIN leases l ON p.lease_id = l.id JOIN office_spaces os ON l.space_id = os.id WHERE p.user_id = ? ORDER BY p.due_date DESC LIMIT 20");
$payments->execute([$user['id']]);
$payments = $payments->fetchAll();

$visits = $db->prepare("SELECT vr.*, os.name as space_name FROM visit_requests vr JOIN office_spaces os ON vr.space_id = os.id WHERE vr.user_id = ? ORDER BY vr.created_at DESC LIMIT 10");
$visits->execute([$user['id']]);
$visits = $visits->fetchAll();

$pageTitle = 'My Profile - Zahara Co-Working Space';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="profile-page">
    <div class="container">
        <div class="profile-header">
            <div class="profile-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
            <div class="profile-info">
                <h1><?= htmlspecialchars($user['full_name']) ?></h1>
                <p><?= htmlspecialchars($user['email']) ?> | <?= htmlspecialchars($user['phone'] ?? 'No phone') ?></p>
                <p style="font-size: 0.85rem;">Status: <span
                        class="status-badge status-<?= $user['status'] ?>"><?= ucfirst($user['status']) ?></span> |
                    Member since: <?= date('M Y', strtotime($user['created_at'])) ?></p>
            </div>

            <div class="profile-tabs">
                <button class="profile-tab active" data-tab="overview">Overview</button>
                <button class="profile-tab" data-tab="leases">My Leases</button>
                <button class="profile-tab" data-tab="payments">Payments</button>
                <button class="profile-tab" data-tab="visits">Visit Requests</button>
                <button class="profile-tab" data-tab="settings">Settings</button>
            </div>

            <div class="tab-content active" id="overview">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Active Leases</h3>
                        <div class="stat-value"><?= count(array_filter($leases, fn($l) => $l['status'] === 'active')) ?>
                        </div>
                        <div class="stat-card">
                            <h3>Pending Visits</h3>
                            <div class="stat-value">
                                <?= count(array_filter($visits, fn($v) => $v['status'] === 'pending')) ?></div>
                            <div class="stat-card">
                                <h3>Total Payments</h3>
                                <div class="stat-value"><?= count($payments) ?></div>
                                <div class="stat-card">
                                    <h3>Pending Payments</h3>
                                    <div class="stat-value" style="color: var(--danger);">
                                        <?= count(array_filter($payments, fn($p) => $p['status'] === 'pending' || $p['status'] === 'overdue')) ?>
                                    </div>
                                </div>
                                <?php if (!empty($leases)): ?><h3 style="margin: 24px 0 16px;">Recent Leases</h3>
                                    <div class="table-responsive">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Space</th>
                                                    <th>Status</th>
                                                    <th>Start Date</th>
                                                    <th>End Date</th>
                                                    <th>Rent</th>
                                                </tr>
                                            </thead>
                                            <tbody><?php foreach (array_slice($leases, 0, 5) as $l): ?><tr>
                                                        <td><?= htmlspecialchars($l['space_name']) ?></td>
                                                        <td><span
                                                                class="status-badge status-<?= $l['status'] ?>"><?= ucfirst(str_replace('_', ' ', $l['status'])) ?></span>
                                                        </td>
                                                        <td><?= date('d M Y', strtotime($l['start_date'])) ?></td>
                                                        <td><?= date('d M Y', strtotime($l['end_date'])) ?></td>
                                                        <td>KES <?= number_format($l['rent_amount']) ?>/mo</td>
                                                    </tr><?php endforeach; ?></tbody>
                                        </table>
                                    </div><?php endif; ?>
                            </div>

                            <div class="tab-content" id="leases">
                                <h3 style="margin-bottom: 16px;">All Leases</h3>
                                <?php if (empty($leases)): ?><p
                                        style="color: var(--text-light); padding: 40px 0; text-align: center;">No leases
                                        yet. Browse spaces and request a visit to get started.</p>
                                <?php else: ?><div class="table-responsive">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Space</th>
                                                    <th>Status</th>
                                                    <th>Period</th>
                                                    <th>Rent</th>
                                                    <th>Deposit</th>
                                                    <th>Signed</th>
                                                </tr>
                                            </thead>
                                            <tbody><?php foreach ($leases as $l): ?><tr>
                                                        <td><?= htmlspecialchars($l['space_name']) ?></td>
                                                        <td><span
                                                                class="status-badge status-<?= $l['status'] ?>"><?= ucfirst(str_replace('_', ' ', $l['status'])) ?></span>
                                                        </td>
                                                        <td><?= date('d M Y', strtotime($l['start_date'])) ?> -
                                                            <?= date('d M Y', strtotime($l['end_date'])) ?></td>
                                                        <td>KES <?= number_format($l['rent_amount']) ?></td>
                                                        <td>KES <?= number_format($l['deposit_amount']) ?>
                                                            <?= $l['deposit_paid'] ? '&#10003;' : '&#10007;' ?></td>
                                                        <td><?= $l['signed_by_customer'] ? '&#10003;' : '&#10007;' ?></td>
                                                    </tr><?php endforeach; ?></tbody>
                                        </table>
                                    </div><?php endif; ?>
                            </div>

                            <div class="tab-content" id="payments">
                                <h3 style="margin-bottom: 16px;">Payment History</h3>
                                <?php if (empty($payments)): ?><p
                                        style="color: var(--text-light); padding: 40px 0; text-align: center;">No payment
                                        records found.</p>
                                <?php else: ?><div class="table-responsive">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Space</th>
                                                    <th>Type</th>
                                                    <th>Amount</th>
                                                    <th>Due Date</th>
                                                    <th>Paid Date</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody><?php foreach ($payments as $p): ?><tr>
                                                        <td><?= htmlspecialchars($p['space_name']) ?></td>
                                                        <td><?= ucfirst($p['payment_type']) ?></td>
                                                        <td>KES <?= number_format($p['amount']) ?></td>
                                                        <td><?= date('d M Y', strtotime($p['due_date'])) ?></td>
                                                        <td><?= $p['paid_date'] ? date('d M Y', strtotime($p['paid_date'])) : '-' ?>
                                                        </td>
                                                        <td><span
                                                                class="status-badge status-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span>
                                                        </td>
                                                    </tr><?php endforeach; ?></tbody>
                                        </table>
                                    </div><?php endif; ?>
                            </div>

                            <div class="tab-content" id="visits">
                                <h3 style="margin-bottom: 16px;">Site Visit Requests</h3>
                                <?php if (empty($visits)): ?><p
                                        style="color: var(--text-light); padding: 40px 0; text-align: center;">No visit
                                        requests. <a href="/work_folder/realRealestate/public/spaces.php">Browse spaces</a>
                                        to request a visit.</p>
                                <?php else: ?><div class="table-responsive">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Space</th>
                                                    <th>Preferred Date</th>
                                                    <th>Time</th>
                                                    <th>Status</th>
                                                    <th>Admin Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody><?php foreach ($visits as $v): ?><tr>
                                                        <td><?= htmlspecialchars($v['space_name']) ?></td>
                                                        <td><?= date('d M Y', strtotime($v['preferred_date'])) ?></td>
                                                        <td><?= date('H:i', strtotime($v['preferred_time'])) ?></td>
                                                        <td><span
                                                                class="status-badge status-<?= $v['status'] ?>"><?= ucfirst($v['status']) ?></span>
                                                        </td>
                                                        <td><?= htmlspecialchars($v['admin_notes'] ?? '-') ?></td>
                                                    </tr><?php endforeach; ?></tbody>
                                        </table>
                                    </div><?php endif; ?>
                            </div>

                            <div class="tab-content" id="settings">
                                <div style="max-width: 500px;">
                                    <h3 style="margin-bottom: 16px;">Edit Profile</h3>
                                    <form method="POST" action="">
                                        <div class="form-group"><label for="full_name">Full Name</label><input
                                                type="text" id="full_name" name="full_name"
                                                value="<?= htmlspecialchars($user['full_name']) ?>" required></div>
                                        <div class="form-group"><label for="email">Email</label><input type="email"
                                                value="<?= htmlspecialchars($user['email']) ?>" disabled readonly
                                                style="background: var(--bg-light); cursor: not-allowed;"><small
                                                style="color: var(--text-light);">Email cannot be changed. Contact admin
                                                for assistance.</small></div>
                                        <div class="form-group"><label for="phone">Phone Number</label><input type="tel"
                                                id="phone" name="phone"
                                                value="<?= htmlspecialchars($user['phone'] ?? '') ?>"></div>
                                        <button type="submit" name="update_profile" class="btn btn-primary">Update
                                            Profile</button>
                                    </form>
                                </div>
                            </div>
</section>
<script>
    document.querySelectorAll('.profile-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.profile-tab').forEach(function(t) {
                t.classList.remove('active');
            });
            document.querySelectorAll('.tab-content').forEach(function(c) {
                c.classList.remove('active');
            });
            this.classList.add('active');
            document.getElementById(this.dataset.tab).classList.add('active');
        });
    });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>