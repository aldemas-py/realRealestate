<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    $leaseId = (int)$_POST['lease_id'];
    $paymentType = $_POST['payment_type'];
    $amount = (float)$_POST['amount'];
    $dueDate = $_POST['due_date'];
    $paidDate = $_POST['paid_date'] ?? date('Y-m-d');
    $method = $_POST['payment_method'] ?? 'cash';
    $ref = trim($_POST['transaction_reference'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $lease = $db->prepare("SELECT l.*, u.id as user_id FROM leases l JOIN users u ON l.user_id = u.id WHERE l.id = ?");
    $lease->execute([$leaseId]);
    $lease = $lease->fetch();
    if (!$lease) {
        $_SESSION['error'] = 'Invalid lease.';
    } else {
        $stmt = $db->prepare("INSERT INTO payments (lease_id, user_id, payment_type, amount, due_date, paid_date, status, payment_method, transaction_reference, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?, 'paid', ?, ?, ?, ?)");
        $stmt->execute([$leaseId, $lease['user_id'], $paymentType, $amount, $dueDate, $paidDate, $method, $ref, $notes, $_SESSION['user_id']]);
        if ($paymentType === 'deposit') {
            $db->prepare("UPDATE leases SET deposit_paid = TRUE, status = 'deposit_pending' WHERE id = ? AND deposit_paid = FALSE")->execute([$leaseId]);
        }
        logAudit($_SESSION['user_id'], 'payment.record', 'payment', $db->lastInsertId());
        $_SESSION['success'] = "Payment of KES " . number_format($amount) . " recorded successfully!";
    }
    header('Location: /work_folder/realRealestate/admin/payments/index.php');
    exit;
}

if (isset($_GET['mark_overdue']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $overdueDays = (int)$_GET['days'] ?? 1;
    $graceDays = (int)getPolicyValue('payment_grace_period_days', '5');
    $actualOverdue = max($overdueDays - $graceDays, 0);
    $lateFeePct = (float)getPolicyValue('late_fee_percentage', '5');
    $payment = $db->prepare("SELECT * FROM payments WHERE id = ?");
    $payment->execute([$id]);
    $payment = $payment->fetch();
    if ($payment) {
        $lateFee = $payment['amount'] * ($lateFeePct / 100);
        $db->prepare("UPDATE payments SET status='overdue', overdue_days=?, late_fee_applied=? WHERE id=?")->execute([$actualOverdue, $lateFee, $id]);
        logAudit($_SESSION['user_id'], 'payment.overdue', 'payment', $id);
        $_SESSION['success'] = "Payment marked overdue. Late fee: KES " . number_format($lateFee);
    }
    header('Location: /work_folder/realRealestate/admin/payments/index.php');
    exit;
}

$payments = $db->query("SELECT p.*, u.full_name as client_name, os.name as space_name FROM payments p JOIN users u ON p.user_id = u.id JOIN leases l ON p.lease_id = l.id JOIN office_spaces os ON l.space_id = os.id ORDER BY p.created_at DESC LIMIT 100")->fetchAll();
$leases = $db->query("SELECT l.id, u.full_name, os.name as space_name, l.rent_amount FROM leases l JOIN users u ON l.user_id = u.id JOIN office_spaces os ON l.space_id = os.id WHERE l.status IN ('active', 'deposit_pending')")->fetchAll();

$pageTitle = 'Payments - Admin';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-layout">
    <aside class="admin-sidebar"><?php require __DIR__ . '/../sidebar.php'; ?></aside>
    <main class="admin-main">
        <div class="admin-header">
            <h1>Rent & Payment Management</h1>
        </div>
        <div
            style="max-width: 700px; margin-bottom: 30px; background: var(--bg-white); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border);">
            <h3 style="margin-bottom: 16px;">Record Payment</h3>
            <form method="POST" action="">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <select name="lease_id" required>
                        <option value="">Select Lease *</option><?php foreach ($leases as $l): ?><option
                            value="<?= $l['id'] ?>"><?= htmlspecialchars($l['full_name']) ?> -
                            <?= htmlspecialchars($l['space_name']) ?> (KES <?= number_format($l['rent_amount']) ?>)
                        </option><?php endforeach; ?>
                    </select>
                    <select name="payment_type" required>
                        <option value="">Type *</option>
                        <option value="rent">Monthly Rent</option>
                        <option value="deposit">Security Deposit</option>
                        <option value="late_fee">Late Fee</option>
                        <option value="other">Other</option>
                    </select>
                    <input type="number" name="amount" step="0.01" min="1" required placeholder="Amount (KES)">
                    <input type="date" name="due_date" required value="<?= date('Y-m-d') ?>">
                    <input type="date" name="paid_date" value="<?= date('Y-m-d') ?>">
                    <select name="payment_method">
                        <option value="">Method</option>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="mobile_money">M-Pesa</option>
                        <option value="credit_card">Credit Card</option>
                    </select>
                    <input type="text" name="transaction_reference" placeholder="Transaction Ref">
                    <textarea name="notes" placeholder="Payment notes..." style="grid-column: 1/-1;"></textarea>
                    <button type="submit" name="record_payment" class="btn btn-primary"
                        style="grid-column: 1/-1;">Record Payment</button>
                </div>
            </form>
        </div>
        <?php if (empty($payments)): ?><p style="color: var(--text-light); text-align: center; padding: 40px;">No
            payments recorded yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Space</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Due</th>
                        <th>Paid</th>
                        <th>Status</th>
                        <th>Late Fee</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['client_name']) ?></td>
                        <td><?= htmlspecialchars($p['space_name']) ?></td>
                        <td><?= ucfirst($p['payment_type']) ?></td>
                        <td>KES <?= number_format($p['amount']) ?></td>
                        <td><?= date('d M', strtotime($p['due_date'])) ?></td>
                        <td><?= $p['paid_date'] ? date('d M', strtotime($p['paid_date'])) : '-' ?></td>
                        <td><span class="status-badge status-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span>
                        </td>
                        <td><?= $p['late_fee_applied'] > 0 ? 'KES ' . number_format($p['late_fee_applied']) : '-' ?>
                        </td>
                        <td><?php if ($p['status'] === 'pending'): ?><a
                                href="?mark_overdue=1&id=<?= $p['id'] ?>&days=10" class="btn btn-sm btn-warning">Mark
                                Overdue</a><?php else: ?><span
                                style="color: var(--text-light); font-size: 0.8rem;"><?= $p['transaction_reference'] ?? '-' ?></span><?php endif; ?>
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