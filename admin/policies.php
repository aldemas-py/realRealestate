<?php

/**
 * ADMIN - POLICY ENGINE
 * View and manage policy rules for compliance enforcement
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_policy'])) {
    $id = (int)$_POST['id'];
    $value = trim($_POST['value']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $stmt = $db->prepare("UPDATE policy_rules SET default_value=?, is_active=? WHERE id=?");
    $stmt->execute([$value, $isActive, $id]);
    logAudit($_SESSION['user_id'], 'policy.update', 'policy_rule', $id);
    $_SESSION['success'] = 'Policy rule updated.';
    header('Location: /work_folder/realRealestate/admin/policies.php');
    exit;
}

$policies = $db->query("SELECT * FROM policy_rules ORDER BY rule_type, policy_key")->fetchAll();

$pageTitle = 'Policy Engine - Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <aside class="admin-sidebar"><?php require __DIR__ . '/sidebar.php'; ?></aside>
    <main class="admin-main">
        <div class="admin-header">
            <h1>&#9878; Policy Engine</h1>
            <span class="status-badge" style="background: var(--success);">System Active</span>
        </div>

        <p style="color: var(--text-mid); margin-bottom: 20px;">
            All business rules are encoded as policies. Changes take effect immediately.
            Policy violations are automatically logged in the audit trail.
        </p>

        <?php if (empty($policies)): ?>
            <p style="color: var(--text-light); padding: 40px; text-align: center;">No policy rules configured.</p>
        <?php else: ?>
            <div style="display: grid; gap: 16px;">
                <?php
                $currentType = '';
                foreach ($policies as $p):
                    if ($currentType !== $p['rule_type']):
                        $currentType = $p['rule_type'];
                ?>
                        <h3
                            style="text-transform: capitalize; color: var(--primary); border-bottom: 2px solid var(--primary-light); padding-bottom: 4px; margin-top: 16px;">
                            <?= ucfirst($currentType) ?> Rules
                        </h3>
                    <?php endif; ?>
                    <div
                        style="background: var(--bg-white); border-radius: var(--radius-md); padding: 16px 20px; border: 1px solid var(--border); display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 16px; align-items: center;">
                        <div>
                            <strong><?= htmlspecialchars($p['policy_name']) ?></strong><br>
                            <small style="color: var(--text-light);">Key:
                                <code><?= htmlspecialchars($p['policy_key']) ?></code></small>
                            <?php if ($p['description']): ?>
                                <p style="font-size: 0.85rem; color: var(--text-mid); margin-top: 4px;">
                                    <?= htmlspecialchars($p['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <form method="POST" action="">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <input type="text" name="value" value="<?= htmlspecialchars($p['default_value']) ?>"
                                    style="width: 100%; padding: 6px 8px; font-size: 0.9rem;">
                        </div>
                        <div>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.85rem;">
                                <input type="checkbox" name="is_active" value="1" <?= $p['is_active'] ? 'checked' : '' ?>>
                                Active
                            </label>
                        </div>
                        <div>
                            <button type="submit" name="update_policy" class="btn btn-sm btn-primary">Update</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>