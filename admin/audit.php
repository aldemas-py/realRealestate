<?php

/**
 * ADMIN - AUDIT LOG
 * Complete compliance trail for all system actions
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

$actionFilter = $_GET['action'] ?? '';
$entityFilter = $_GET['entity'] ?? '';
$searchUser = trim($_GET['user'] ?? '');

$where = "WHERE 1=1";
$params = [];
if (!empty($actionFilter)) {
    $where .= " AND al.action LIKE ?";
    $params[] = "%$actionFilter%";
}
if (!empty($entityFilter)) {
    $where .= " AND al.entity_type = ?";
    $params[] = $entityFilter;
}
if (!empty($searchUser)) {
    $where .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$searchUser%";
    $params[] = "%$searchUser%";
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$countStmt = $db->prepare("SELECT COUNT(*) FROM audit_log al LEFT JOIN users u ON al.user_id = u.id $where");
$countStmt->execute($params);
$totalEntries = $countStmt->fetchColumn();
$totalPages = ceil($totalEntries / $perPage);

$logs = $db->prepare("
    SELECT al.*, u.full_name as user_name, u.email as user_email
    FROM audit_log al 
    LEFT JOIN users u ON al.user_id = u.id 
    $where 
    ORDER BY al.created_at DESC 
    LIMIT $perPage OFFSET $offset
");
$logs->execute($params);
$logs = $logs->fetchAll();

$entities = $db->query("SELECT DISTINCT entity_type FROM audit_log ORDER BY entity_type")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Audit Log - Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <aside class="admin-sidebar"><?php require __DIR__ . '/sidebar.php'; ?></aside>
    <main class="admin-main">
        <div class="admin-header">
            <h1>&#128214; Audit Log</h1>
            <span style="font-size: 0.9rem; color: var(--text-light);"><?= number_format($totalEntries) ?> total
                entries</span>
        </div>

        <p style="color: var(--text-mid); margin-bottom: 20px;">
            Complete compliance trail recording every action in the system.
            All state changes are captured with before/after snapshots for full accountability.
        </p>

        <!-- Filters -->
        <form method="GET" action="" class="filters-bar" style="margin-bottom: 20px;">
            <input type="text" name="user" placeholder="Search user..." value="<?= htmlspecialchars($searchUser) ?>">
            <input type="text" name="action" placeholder="Filter action..."
                value="<?= htmlspecialchars($actionFilter) ?>">
            <select name="entity">
                <option value="">All Entities</option>
                <?php foreach ($entities as $e): ?>
                    <option value="<?= $e ?>" <?= $entityFilter === $e ? 'selected' : '' ?>>
                        <?= ucfirst(str_replace('_', ' ', $e)) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
            <a href="/work_folder/realRealestate/admin/audit.php" class="btn btn-ghost btn-sm">Clear</a>
        </form>

        <?php if (empty($logs)): ?>
            <p style="color: var(--text-light); padding: 40px; text-align: center;">No audit entries found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Compliance</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td style="white-space: nowrap;"><?= date('d M Y H:i:s', strtotime($l['created_at'])) ?></td>
                                <td><small><?= htmlspecialchars($l['user_name'] ?? 'System') ?><br><?= htmlspecialchars($l['user_email'] ?? '') ?></small>
                                </td>
                                <td><code
                                        style="background: var(--bg-light); padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;"><?= htmlspecialchars($l['action']) ?></code>
                                </td>
                                <td><small><?= ucfirst(str_replace('_', ' ', $l['entity_type'])) ?>
                                        #<?= $l['entity_id'] ?></small></td>
                                <td>
                                    <span
                                        class="status-badge status-<?= $l['compliance_status'] === 'pass' ? 'available' : 'danger' ?>"
                                        style="font-size: 0.75rem;">
                                        <?= strtoupper($l['compliance_status']) ?>
                                    </span>
                                    <?php if ($l['policy_violation']): ?>
                                        <br><small
                                            style="color: var(--danger);"><?= htmlspecialchars($l['policy_violation']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($l['new_values']): ?>
                                        <button class="btn btn-sm btn-ghost"
                                            onclick="showAuditDetails(this, '<?= htmlspecialchars(addslashes($l['new_values'])) ?>', '<?= htmlspecialchars(addslashes($l['old_values'] ?? 'null')) ?>')">View</button>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>&<?= http_build_query($_GET) ?>">&laquo;
                            Prev</a><?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&<?= http_build_query($_GET) ?>"
                            class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?><a href="?page=<?= $page + 1 ?>&<?= http_build_query($_GET) ?>">Next
                            &raquo;</a><?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<script>
    function showAuditDetails(btn, newValues, oldValues) {
        var modal = document.createElement('div');
        modal.style.cssText =
            'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;display:flex;align-items:center;justify-content:center;';
        modal.onclick = function() {
            document.body.removeChild(modal);
        };

        var content = document.createElement('div');
        content.style.cssText =
            'background:white;border-radius:12px;padding:30px;max-width:600px;width:90%;max-height:80vh;overflow-y:auto;';
        content.onclick = function(e) {
            e.stopPropagation();
        };

        function formatJson(str) {
            try {
                return JSON.stringify(JSON.parse(str), null, 2);
            } catch (e) {
                return str || 'N/A';
            }
        }

        content.innerHTML = '<h3 style="margin-bottom:16px;">Audit Details</h3>' +
            '<div style="margin-bottom:16px;"><strong>New Values:</strong><pre style="background:#f5f5f5;padding:12px;border-radius:8px;overflow-x:auto;font-size:0.85rem;">' +
            formatJson(newValues) + '</pre></div>' +
            '<div><strong>Old Values:</strong><pre style="background:#f5f5f5;padding:12px;border-radius:8px;overflow-x:auto;font-size:0.85rem;">' +
            formatJson(oldValues) + '</pre></div>' +
            '<button class="btn btn-ghost btn-sm" style="margin-top:12px;" onclick="document.body.removeChild(this.closest(\'div\').parentElement.parentElement)">Close</button>';

        modal.appendChild(content);
        document.body.appendChild(modal);
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>