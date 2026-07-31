<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_lease'])) {
    $userId = (int)$_POST['user_id'];
    $spaceId = (int)$_POST['space_id'];
    $visitRequestId = !empty($_POST['visit_request_id']) ? (int)$_POST['visit_request_id'] : null;
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $rentAmount = (float)$_POST['rent_amount'];
    $depositAmount = (float)$_POST['deposit_amount'];
    $paymentDueDay = (int)$_POST['payment_due_day'];
    $minDuration = (int)getPolicyValue('lease_min_duration_days', '30');
    if (strtotime($endDate) <= strtotime($startDate)) {
        $_SESSION['error'] = 'End date must be after start date.';
    } elseif ((strtotime($endDate) - strtotime($startDate)) / 86400 < $minDuration) {
        $_SESSION['error'] = "Minimum lease duration is $minDuration days.";
    } else {
        $stmt = $db->prepare("INSERT INTO leases (user_id, space_id, visit_request_id, start_date, end_date, rent_amount, deposit_amount, payment_due_day, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $spaceId, $visitRequestId, $startDate, $endDate, $rentAmount, $depositAmount, $paymentDueDay, $_SESSION['user_id']]);
        $leaseId = $db->lastInsertId();
        $db->prepare("INSERT INTO payments (lease_id, user_id, payment_type, amount, due_date, recorded_by) VALUES (?, ?, 'deposit', ?, ?, ?)")->execute([$leaseId, $userId, $depositAmount, $startDate, $_SESSION['user_id']]);
        if ($visitRequestId) {
            $db->prepare("UPDATE visit_requests SET status = 'lease_created' WHERE id = ?")->execute([$visitRequestId]);
        }
        logAudit($_SESSION['user_id'], 'lease.create', 'lease', $leaseId);
        $_SESSION['success'] = 'Lease created successfully!';
    }
    header("Location: /work_folder/realRealestate/admin/leases/index.php" . ($userId ? "?user_id=$userId" : ''));
    exit;
}

if (isset($_GET['activate']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $lease = $db->prepare("SELECT * FROM leases WHERE id = ?");
    $lease->execute([$id]);
    $lease = $lease->fetch();
    if ($lease && $lease['status'] === 'deposit_pending') {
        $db->prepare("UPDATE leases SET status='active', signed_by_customer=TRUE, signed_by_admin=TRUE, signed_at=NOW() WHERE id=?")->execute([$id]);
        $db->prepare("UPDATE office_spaces SET status='occupied' WHERE id=?")->execute([$lease['space_id']]);
        logAudit($_SESSION['user_id'], 'lease.activate', 'lease', $id);
        $_SESSION['success'] = 'Lease activated!';
    }
    header("Location: /work_folder/realRealestate/admin/leases/index.php");
    exit;
}

if (isset($_GET['terminate']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $reason = $_GET['reason'] ?? 'Admin termination';
    $lease = $db->prepare("SELECT * FROM leases WHERE id = ?");
    $lease->execute([$id]);
    $lease = $lease->fetch();
    if ($lease && in_array($lease['status'], ['active', 'expiring'])) {
        $db->prepare("UPDATE leases SET status='terminated', terminated_at=NOW(), termination_reason=? WHERE id=?")->execute([$reason, $id]);
        $db->prepare("UPDATE office_spaces SET status='available' WHERE id=?")->execute([$lease['space_id']]);
        logAudit($_SESSION['user_id'], 'lease.terminate', 'lease', $id);
        $_SESSION['success'] = 'Lease terminated.';
    }
    header("Location: /work_folder/realRealestate/admin/leases/index.php");
    exit;
}

$where = $userId ? "WHERE l.user_id = $userId" : '';
$leases = $db->query("SELECT l.*, u.full_name, u.email, os.name as space_name, os.slug FROM leases l JOIN users u ON l.user_id = u.id JOIN office_spaces os ON l.space_id = os.id $where ORDER BY l.created_at DESC")->fetchAll();
$customers = $db->query("SELECT id, full_name, email FROM users WHERE role_id = 2 AND status = 'active' ORDER BY full_name")->fetchAll();
$spaces = $db->query("SELECT id, name FROM office_spaces WHERE status = 'available' ORDER BY name")->fetchAll();
$completedVisits = $db->query("SELECT vr.id, vr.user_id, u.full_name, os.name as space_name FROM visit_requests vr JOIN users u ON vr.user_id = u.id JOIN office_spaces os ON vr.space_id = os.id WHERE vr.status = 'completed' ORDER BY vr.created_at DESC")->fetchAll();

$pageTitle = 'Leases - Admin';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-layout">
    <aside class="admin-sidebar"><?php require __DIR__ . '/../sidebar.php'; ?></aside>
    <main class="admin-main">
        <div class="admin-header">
            <h1>Lease Management</h1>
        </div>
        <div
            style="max-width: 700px; margin-bottom: 30px; background: var(--bg-white); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border);">
            <h3 style="margin-bottom: 16px;">Create New Lease</h3>
            <form method="POST" action="">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <select name="user_id" required>
                        <option value="">Select Customer *</option><?php foreach ($customers as $c): ?><option
                            value="<?= $c['id'] ?>" <?= $userId === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <select name="space_id" required>
                        <option value="">Select Space *</option><?php foreach ($spaces as $s): ?><option
                            value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?>
                    </select>
                    <select name="visit_request_id">
                        <option value="">Associated Visit (optional)</option><?php foreach ($completedVisits as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['full_name']) ?> -
                            <?= htmlspecialchars($v['space_name']) ?></option><?php endforeach; ?>
                    </select>
                    <input type="date" name="start_date" required placeholder="Start Date">
                    <input type="date" name="end_date" required placeholder="End Date">
                    <input type="number" name="rent_amount" step="0.01" min="1" required
                        placeholder="Rent Amount (KES)">
                    <input type="number" name="deposit_amount" step="0.01" min="0" placeholder="Deposit (KES)">
                    <input type="number" name="payment_due_day" min="1" max="28" value="1"
                        placeholder="Payment Due Day">
                    <button type="submit" name="create_lease" class="btn btn-primary" style="grid-column: 1/-1;">Create
                        Lease</button>
                </div>
            </form>
        </div>
        <?php if (empty($leases)): ?><p style="color: var(--text-light); text-align: center; padding: 40px;">No leases
            found.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Space</th>
                        <th>Period</th>
                        <th>Rent</th>
                        <th>Status</th>
                        <th>Signed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leases as $l): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($l['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($l['space_name']) ?></td>
                        <td><?= date('d M', strtotime($l['start_date'])) ?> -
                            <?= date('d M Y', strtotime($l['end_date'])) ?></td>
                        <td>KES <?= number_format($l['rent_amount']) ?></td>
                        <td><span
                                class="status-badge status-<?= $l['status'] ?>"><?= ucfirst(str_replace('_', ' ', $l['status'])) ?></span>
                        </td>
                        <td><?= $l['deposit_paid'] ? 'D: ✓' : 'D: ✗' ?> |
                            <?= $l['signed_by_customer'] ? 'C: ✓' : 'C: ✗' ?></td>
                        <td>
                            <div style="display: flex; gap: 4px;"><?php if ($l['status'] === 'deposit_pending'): ?><a
                                    href="?activate=1&id=<?= $l['id'] ?>" class="btn btn-sm btn-success"
                                    data-confirm="Activate this lease? This will mark the space as occupied.">Activate</a><?php endif; ?><?php if (in_array($l['status'], ['active', 'expiring'])): ?><a
                                    href="?terminate=1&id=<?= $l['id'] ?>&reason=Agreement+ended"
                                    class="btn btn-sm btn-danger"
                                    data-confirm="Terminate this lease?">Terminate</a><?php endif; ?></div>
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