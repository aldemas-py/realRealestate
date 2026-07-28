<?php

/**
 * ADMIN - LEASE MANAGEMENT
 */
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$db = getDB();

// Create lease from visit request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_lease'])) {
    $userId = (int)$_POST['user_id'];
    $spaceId = (int)$_POST['space_id'];
    $visitRequestId = (int)$_POST['visit_request_id'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $rentAmount = (float)$_POST['rent_amount'];
    $depositAmount = (float)$_POST['deposit_amount'];
    $paymentDueDay = (int)$_POST['payment_due_day'];

    try {
        $stmt = $db->prepare("
            INSERT INTO leases (user_id, space_id, visit_request_id, start_date, end_date, rent_amount, deposit_amount, payment_due_day, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)
        ");
        $stmt->execute([$userId, $spaceId, $visitRequestId, $startDate, $endDate, $rentAmount, $depositAmount, $paymentDueDay, $_SESSION['user_id']]);

        // Update visit request status
        $db->prepare("UPDATE visit_requests SET status='lease_created' WHERE id=?")->execute([$visitRequestId]);

        logAudit($_SESSION['user_id'], 'lease.create', 'lease', $db->lastInsertId());
        $_SESSION['success'] = 'Lease created successfully. Activate it once deposit is paid.';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to create lease: ' . $e->getMessage();
    }
    header('Location: /work_folder/realRealestate/admin/leases/index.php');
    exit;
}

// Update lease status
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    $transitions = [
        'activate' => 'active',
        'deposit_pending' => 'deposit_pending',
        'terminate' => 'terminated',
        'expire' => 'expired'
    ];

    if (isset($transitions[$action])) {
        $newStatus = $transitions[$action];
        $db->prepare("UPDATE leases SET status=? WHERE id=?")->execute([$newStatus, $id]);
        logAudit($_SESSION['user_id'], "lease.$action", 'lease', $id);
        $_SESSION['success'] = "Lease status updated to '$newStatus'.";
    }
    header('Location: /work_folder/realRealestate/admin/leases/index.php');
    exit;
}

$leases = $db->query("
    SELECT l.*, u.full_name, u.email, os.name as space_name, os.slug,
           (SELECT COUNT(*) FROM payments WHERE lease_id = l.id AND status = 'paid') as payments_made,
           (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE lease_id = l.id AND status = 'paid') as total_paid
    FROM leases l 
    JOIN users u ON l.user_id = u.id 
    JOIN office_spaces os ON l.space_id = os.id 
    ORDER BY l.created_at DESC
")->fetchAll();

// Get data for new lease form
$completedVisits = $db->query("
    SELECT vr.*, u.full_name, os.name as space_name, os.price_per_month, os.security_deposit
    FROM visit_requests vr 
    JOIN users u ON vr.user_id = u.id 
    JOIN office_spaces os ON vr.space_id = os.id 
    WHERE vr.status = 'completed' AND vr.id NOT IN (SELECT visit_request_id FROM leases)
    ORDER BY vr.created_at DESC
")->fetchAll();

$pageTitle = 'Leases - Admin';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <aside class="admin-sidebar"><?php require __DIR__ . '/../sidebar.php'; ?></aside>
    <main class="admin-main">
        <div class="admin-header">
            <h1>Lease Management</h1>
            <?php if (!empty($completedVisits)): ?>
                <button class="btn btn-primary"
                    onclick="document.getElementById('createLeaseModal').style.display='block'">+ New Lease</button>
            <?php endif; ?>
        </div>

        <?php if (empty($leases)): ?>
            <p style="color: var(--text-light); padding: 40px; text-align: center;">No leases created yet. Complete a visit
                request to create a lease.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Space</th>
                            <th>Period</th>
                            <th>Rent</th>
                            <th>Deposit</th>
                            <th>Paid</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leases as $l): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($l['full_name']) ?></strong><br><small><?= htmlspecialchars($l['email']) ?></small>
                                </td>
                                <td><a href="/work_folder/realRealestate/public/space-detail.php?slug=<?= $l['slug'] ?>"
                                        target="_blank"><?= htmlspecialchars($l['space_name']) ?></a></td>
                                <td><?= date('d M', strtotime($l['start_date'])) ?> -
                                    <?= date('d M Y', strtotime($l['end_date'])) ?></td>
                                <td>KES <?= number_format($l['rent_amount']) ?></td>
                                <td>KES <?= number_format($l['deposit_amount']) ?>
                                    <?= $l['deposit_paid'] ? '&#10003;' : '&#10007;' ?></td>
                                <td>KES <?= number_format($l['total_paid']) ?> (<?= $l['payments_made'] ?>x)</td>
                                <td><span
                                        class="status-badge status-<?= $l['status'] ?>"><?= ucfirst(str_replace('_', ' ', $l['status'])) ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                        <?php if ($l['status'] === 'draft'): ?>
                                            <a href="?action=deposit_pending&id=<?= $l['id'] ?>" class="btn btn-sm btn-primary">Mark
                                                Deposit Pending</a>
                                        <?php elseif ($l['status'] === 'deposit_pending'): ?>
                                            <a href="?action=activate&id=<?= $l['id'] ?>"
                                                class="btn btn-sm btn-success">Activate</a>
                                        <?php elseif ($l['status'] === 'active'): ?>
                                            <a href="/work_folder/realRealestate/admin/payments/index.php?lease_id=<?= $l['id'] ?>"
                                                class="btn btn-sm btn-ghost">Payments</a>
                                            <a href="?action=terminate&id=<?= $l['id'] ?>" class="btn btn-sm btn-danger"
                                                data-confirm="Terminate this lease?">Terminate</a>
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

<!-- Create Lease Modal -->
<div id="createLeaseModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div
        style="background: white; border-radius: var(--radius-lg); padding: 30px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
        <h3 style="margin-bottom: 20px;">Create New Lease</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label>Select Completed Visit Request</label>
                <select name="visit_request_id" required onchange="updateLeaseForm(this)">
                    <option value="">-- Select --</option>
                    <?php foreach ($completedVisits as $cv): ?>
                        <option value="<?= $cv['id'] ?>" data-user="<?= $cv['user_id'] ?>"
                            data-space="<?= $cv['space_id'] ?>" data-price="<?= $cv['price_per_month'] ?>"
                            data-deposit="<?= $cv['security_deposit'] ?>">
                            <?= htmlspecialchars($cv['full_name']) ?> - <?= htmlspecialchars($cv['space_name']) ?>
                            (<?= date('d M', strtotime($cv['preferred_date'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="user_id" id="lease_user_id">
            <input type="hidden" name="space_id" id="lease_space_id">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" required>
                </div>
                <div class="form-group">
                    <label>Rent Amount (KES)</label>
                    <input type="number" name="rent_amount" id="lease_rent" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Deposit Amount (KES)</label>
                    <input type="number" name="deposit_amount" id="lease_deposit" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Payment Due Day (1-28)</label>
                    <input type="number" name="payment_due_day" min="1" max="28" value="1" required>
                </div>
                <div style="display: flex; gap: 8px; margin-top: 16px;">
                    <button type="submit" name="create_lease" class="btn btn-primary">Create Lease</button>
                    <button type="button" class="btn btn-ghost"
                        onclick="document.getElementById('createLeaseModal').style.display='none'">Cancel</button>
                </div>
        </form>
    </div>

    <script>
        function updateLeaseForm(select) {
            var opt = select.options[select.selectedIndex];
            if (opt.value) {
                document.getElementById('lease_user_id').value = opt.dataset.user;
                document.getElementById('lease_space_id').value = opt.dataset.space;
                document.getElementById('lease_rent').value = opt.dataset.price;
                document.getElementById('lease_deposit').value = opt.dataset.deposit;
            }
        }
    </script>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>