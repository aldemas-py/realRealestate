<?php

/**
 * ADMIN - PAYMENT & RENT MANAGEMENT
 * Policy-as-Code: Track payments, detect overdue, apply late fees
 */
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$db = getDB();

// Record a payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    $leaseId = (int)$_POST['lease_id'];
    $userId = (int)$_POST['user_id'];
    $paymentType = $_POST['payment_type'];
    $amount = (float)$_POST['amount'];
    $dueDate = $_POST['due_date'];
    $paidDate = $_POST['paid_date'] ?? date('Y-m-d');
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $transactionRef = trim($_POST['transaction_reference'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    try {
        $stmt = $db->prepare("
            INSERT INTO payments (lease_id, user_id, payment_type, amount, due_date, paid_date, status, payment_method, transaction_reference, notes, recorded_by)
            VALUES (?, ?, ?, ?, ?, ?, 'paid', ?, ?, ?, ?)
        ");
        $stmt->execute([$leaseId, $userId, $paymentType, $amount, $dueDate, $paidDate, $paymentMethod, $transactionRef, $notes, $_SESSION['user_id']]);

        // If deposit payment, update lease
        if ($paymentType === 'deposit') {
            $db->prepare("UPDATE leases SET deposit_paid = TRUE, status = IF(status='deposit_pending', 'active', status) WHERE id = ?")->execute([$leaseId]);
        }

        logAudit($_SESSION['user_id'], 'payment.record', 'payment', $db->lastInsertId());
        $_SESSION['success'] = 'Payment recorded successfully.';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to record payment. ' . ($e->getCode() == 23000 ? 'Duplicate payment entry.' : $e->getMessage());
    }
    header('Location: /work_folder/realRealestate/admin/payments/index.php');
    exit;
}

// Filters
$statusFilter = $_GET['status'] ?? '';
$leaseFilter = (int)($_GET['lease_id'] ?? 0);

$where = "WHERE 1=1";
$params = [];
if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'paid', 'overdue', 'partially_paid', 'refunded'])) {
    $where .= " AND p.status = ?";
    $params[] = $statusFilter;
}
if ($leaseFilter > 0) {
    $where .= " AND p.lease_id = ?";
    $params[] = $leaseFilter;
}

$payments = $db->prepare("
    SELECT p.*, u.full_name, os.name as space_name, l.rent_amount as lease_rent
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    JOIN leases l ON p.lease_id = l.id 
    JOIN office_spaces os ON l.space_id = os.id 
    $where 
    ORDER BY p.due_date DESC 
    LIMIT 100
");
$payments->execute($params);
$payments = $payments->fetchAll();

// Active leases for payment form
$activeLeases = $db->query("
    SELECT l.id, l.rent_amount, l.deposit_amount, l.deposit_paid, l.payment_due_day, 
           u.full_name, u.id as user_id, os.name as space_name
    FROM leases l 
    JOIN users u ON l.user_id = u.id 
    JOIN office_spaces os ON l.space_id = os.id 
    WHERE l.status IN ('active', 'deposit_pending') 
    ORDER BY u.full_name
")->fetchAll();

$pageTitle = 'Payments - Admin';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <aside class="admin-sidebar"><?php require __DIR__ . '/../sidebar.php'; ?></aside>
    <main class="admin-main">
        <div class="admin-header">
            <h1>Rent Management & Payments</h1>
            <?php if (!empty($activeLeases)): ?>
                <button class="btn btn-primary" onclick="document.getElementById('paymentModal').style.display='block'">+
                    Record Payment</button>
            <?php endif; ?>
        </div>

        <!-- Stats -->
        <?php
        $pmtStats = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status='overdue' THEN 1 ELSE 0 END) as overdue_count,
                SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending_count,
                COALESCE(SUM(CASE WHEN status='paid' THEN amount ELSE 0 END), 0) as total_collected
            FROM payments
        ")->fetch();
        ?>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;">
            <div class="stat-card">
                <h3>Total Payments</h3>
                <div class="stat-value"><?= $pmtStats['total'] ?></div>
                <div class="stat-card">
                    <h3>Collected</h3>
                    <div class="stat-value" style="color: var(--success);">KES
                        <?= number_format($pmtStats['total_collected']) ?></div>
                    <div class="stat-card">
                        <h3>Overdue</h3>
                        <div class="stat-value" style="color: var(--danger);"><?= $pmtStats['overdue_count'] ?></div>
                        <div class="stat-card">
                            <h3>Pending</h3>
                            <div class="stat-value" style="color: var(--warning);"><?= $pmtStats['pending_count'] ?>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" action="" class="filters-bar" style="margin-bottom: 20px;">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending
                                </option>
                                <option value="overdue" <?= $statusFilter === 'overdue' ? 'selected' : '' ?>>Overdue
                                </option>
                                <option value="refunded" <?= $statusFilter === 'refunded' ? 'selected' : '' ?>>Refunded
                                </option>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                            <a href="/work_folder/realRealestate/admin/payments/index.php"
                                class="btn btn-ghost btn-sm">Clear</a>
                        </form>

                        <?php if (empty($payments)): ?>
                            <p style="color: var(--text-light); padding: 40px; text-align: center;">No payments recorded
                                yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Space</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Due Date</th>
                                            <th>Paid Date</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                            <th>Overdue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $p): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($p['full_name']) ?></strong></td>
                                                <td><?= htmlspecialchars($p['space_name']) ?></td>
                                                <td><?= ucfirst($p['payment_type']) ?></td>
                                                <td>KES <?= number_format($p['amount']) ?></td>
                                                <td><?= date('d M Y', strtotime($p['due_date'])) ?></td>
                                                <td><?= $p['paid_date'] ? date('d M Y', strtotime($p['paid_date'])) : '-' ?>
                                                </td>
                                                <td><?= ucwords(str_replace('_', ' ', $p['payment_method'] ?? '-')) ?></td>
                                                <td><span
                                                        class="status-badge status-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span>
                                                </td>
                                                <td><?= $p['overdue_days'] > 0 ? $p['overdue_days'] . ' days' : '-' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
    </main>
</div>

<!-- Payment Modal -->
<div id="paymentModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div
        style="background: white; border-radius: var(--radius-lg); padding: 30px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
        <h3 style="margin-bottom: 20px;">Record Payment</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label>Active Lease *</label>
                <select name="lease_id" required onchange="updatePaymentForm(this)">
                    <option value="">-- Select Lease --</option>
                    <?php foreach ($activeLeases as $al): ?>
                        <option value="<?= $al['id'] ?>" data-user="<?= $al['user_id'] ?>"
                            data-rent="<?= $al['rent_amount'] ?>" data-deposit="<?= $al['deposit_amount'] ?>"
                            data-due-day="<?= $al['payment_due_day'] ?>">
                            <?= htmlspecialchars($al['full_name']) ?> - <?= htmlspecialchars($al['space_name']) ?> (KES
                            <?= number_format($al['rent_amount']) ?>/mo)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="user_id" id="pmt_user_id">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Payment Type *</label>
                    <select name="payment_type" required>
                        <option value="rent">Monthly Rent</option>
                        <option value="deposit">Security Deposit</option>
                        <option value="late_fee">Late Fee</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (KES) *</label>
                    <input type="number" name="amount" id="pmt_amount" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Due Date *</label>
                    <input type="date" name="due_date" required>
                </div>
                <div class="form-group">
                    <label>Paid Date</label>
                    <input type="date" name="paid_date" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="mobile_money">Mobile Money (M-Pesa)</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Transaction Ref</label>
                    <input type="text" name="transaction_reference" placeholder="Optional ref number">
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" placeholder="Optional notes about this payment"></textarea>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="submit" name="record_payment" class="btn btn-primary">Record Payment</button>
                    <button type="button" class="btn btn-ghost"
                        onclick="document.getElementById('paymentModal').style.display='none'">Cancel</button>
                </div>
        </form>
    </div>

    <script>
        function updatePaymentForm(select) {
            var opt = select.options[select.selectedIndex];
            if (opt.value) {
                document.getElementById('pmt_user_id').value = opt.dataset.user;
                document.getElementById('pmt_amount').value = opt.dataset.rent;
            }
        }
    </script>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>