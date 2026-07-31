<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$db = getDB();
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;
$total = $db->query("SELECT COUNT(*) FROM audit_log")->fetchColumn();
$logs = $db->prepare("SELECT a.*, u.full_name as user_name FROM audit_log a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset");
$logs->execute();
$logs = $logs->fetchAll();
$pageTitle = 'Audit Log - Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
    <aside class="admin-sidebar"><?php require __DIR__ . '/sidebar.php'; ?></aside>
    <main class="admin-main">
        <div class="admin-header">
            <h1>&#128214; Audit Log</h1><span style="font-size: 0.85rem; color: var(--text-light);">Compliance trail:
                All system actions are recorded</span>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>ID</th>
                        <th>Compliance</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $l): ?><tr>
                        <td style="white-space: nowrap;"><?= date('d M H:i', strtotime($l['created_at'])) ?></td>
                        <td><?= htmlspecialchars($l['user_name'] ?? 'System') ?></td>
                        <td><code><?= htmlspecialchars($l['action']) ?></code></td>
                        <td><?= htmlspecialchars($l['entity_type']) ?></td>
                        <td><?= $l['entity_id'] ?></td>
                        <td><span
                                class="status-badge status-<?= $l['compliance_status'] ?>"><?= ucfirst($l['compliance_status']) ?></span>
                        </td>
                        <td><small><?php $nv = json_decode($l['new_values'] ?? '{}', true);
                                        if ($nv): ?><?php foreach (array_slice($nv, 0, 3) as $k => $v): ?><?= htmlspecialchars($k) ?>=<?= htmlspecialchars(is_string($v) ? $v : json_encode($v)) ?>&nbsp;<?php endforeach;
                                                                                                                                                                                                                                                            endif; ?><?php if ($l['policy_violation']): ?><br><span
                                    style="color: var(--danger);">Violation:
                                    <?= htmlspecialchars($l['policy_violation']) ?></span><?php endif; ?></small></td>
                    </tr>
                    <?php endforeach; ?></tbody>
            </table>
        </div>
        <div style="margin-top: 16px; display: flex; justify-content: center; gap: 8px;">
            <?php for ($i = 1; $i <= max(1, ceil($total / $perPage)); $i++): ?><a href="?page=<?= $i ?>"
                class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-ghost' ?>"><?= $i ?></a><?php endfor; ?></div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>