<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_policy'])) {
    $id = (int)$_POST['id'];
    $value = trim($_POST['default_value']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $stmt = $db->prepare("UPDATE policy_rules SET default_value=?, is_active=? WHERE id=?");
    $stmt->execute([$value, $isActive, $id]);
    logAudit($_SESSION['user_id'], 'policy.update', 'policy_rule', $id);
    $_SESSION['success'] = 'Policy rule updated.';
    header('Location: /work_folder/realRealestate/admin/policies.php');
    exit;
}

$policies = $db->query("SELECT * FROM policy_rules ORDER BY rule_type, policy_name")->fetchAll();
$pageTitle = 'Policy Engine - Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
    <aside class="admin-sidebar"><?php require __DIR__ . '/sidebar.php'; ?></aside>
    <main class="admin-main">
        <div class="admin-header">
            <h1>&#9878; Policy Engine</h1><span style="font-size: 0.85rem; color: var(--text-light);">Policy-as-Code:
                Business rules & compliance enforcement</span>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Policy</th>
                        <th>Key</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($policies as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['policy_name']) ?></strong><br><small
                                style="color: var(--text-light);"><?= htmlspecialchars($p['description']) ?></small>
                        </td>
                        <td><code><?= htmlspecialchars($p['policy_key']) ?></code></td>
                        <td><span class="status-badge"><?= ucfirst($p['rule_type']) ?></span></td>
                        <td>
                            <form method="POST" action="" style="display: flex; align-items: center; gap: 4px;">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <input type="text" name="default_value"
                                    value="<?= htmlspecialchars($p['default_value']) ?>"
                                    style="width: 80px; padding: 4px; font-size: 0.85rem;">
                                <label style="font-size: 0.8rem;"><input type="checkbox" name="is_active" value="1"
                                        <?= $p['is_active'] ? ' checked' : '' ?>> Active</label>
                                <button type="submit" name="update_policy" class="btn btn-sm btn-success">Save</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>