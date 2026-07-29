<?php

/**
 * FlexiSpace - Database Setup & Seed
 * Visit: http://127.0.0.1/work_folder/realRealestate/setup.php
 */
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'realestate_db';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<!DOCTYPE html><html><head><title>FlexiSpace Setup</title>';
echo '<style>body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;}
.success{color:green;background:#e8f5e9;padding:8px 12px;border-radius:4px;margin:4px 0;}
.error{color:#c62828;background:#ffebee;padding:8px 12px;border-radius:4px;margin:4px 0;}
.info{color:#1565c0;background:#e3f2fd;padding:8px 12px;border-radius:4px;margin:4px 0;}
h2{color:#1a237e;}a{display:inline-block;margin-top:20px;padding:10px 20px;background:#1a237e;color:white;text-decoration:none;border-radius:4px;}</style></head><body>';
echo '<h1>FlexiSpace - Database Setup</h1>';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db`");
    echo '<p class="success">Database ready: ' . $db . '</p>';

    // Show existing tables
    $existing = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo '<p class="info">Tables: ' . implode(', ', $existing) . '</p>';

    // === STEP 1: Admin User ===
    $admin = $pdo->query("SELECT id, email, role_id FROM users WHERE role_id = 1 OR email = 'admin@flexispace.co.ke' LIMIT 1")->fetch();
    if (!$admin) {
        $hash = password_hash('Admin@123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (role_id, full_name, email, phone, password_hash, status, approval_date) VALUES (1, 'System Admin', 'admin@flexispace.co.ke', '+254-700-000000', ?, 'active', NOW())");
        $stmt->execute([$hash]);
        $admin = $pdo->query("SELECT id, email, role_id FROM users WHERE email = 'admin@flexispace.co.ke' LIMIT 1")->fetch();
        echo '<p class="success">Created admin user</p>';
    } else {
        // Force reset password to ensure it works
        $hash = password_hash('Admin@123', PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $admin['id']]);
        echo '<p class="info">Reset password for existing admin (ID: ' . $admin['id'] . ')</p>';
    }
    $uid = (int)$admin['id'];
    echo '<p class="info">Admin: ' . $admin['email'] . ' / Password: Admin@123 (ID: ' . $uid . ')</p>';

    // === STEP 2: Fix FK references ===
    $pdo->exec("UPDATE office_spaces SET created_by = $uid WHERE created_by NOT IN (SELECT id FROM users) OR created_by IS NULL");

    // === STEP 3: Seed 5 office spaces ===
    $sc = (int)$pdo->query("SELECT COUNT(*) FROM office_spaces")->fetchColumn();
    if ($sc === 0) {
        $spaces = [
            ['Skyline Executive Suite', 'skyline-executive-suite', 'Premium corner office on the 15th floor with panoramic city views.', 'Premium corner office with city views', 'private_office', 8, 3500.00, 1750.00, -1.2650, 36.8090, 350, '["wifi","parking","coffee","meeting_room_access","24_7_access","furnished"]', '{"mon-fri":"8:00-20:00","sat":"9:00-17:00"}', 1],
            ['Creative Hub Open Space', 'creative-hub-open-space', 'Vibrant open-plan coworking for creatives and startups.', 'Open-plan coworking space', 'open_desk', 20, 500.00, 250.00, -1.2660, 36.8100, 800, '["wifi","coffee","printing","lockers","phone_booths","breakout_area"]', '{"mon-fri":"6:00-22:00","sat-sun":"8:00-18:00"}', 1],
            ['Boardroom A - Meeting Space', 'boardroom-a-meeting-space', 'Professional meeting room with 4K video conferencing.', 'Fully equipped meeting room', 'meeting_room', 12, 1200.00, 600.00, -1.2670, 36.8080, 200, '["wifi","video_conferencing","whiteboard","projector","catering"]', '{"mon-fri":"8:00-18:00"}', 1],
            ['The Greenhouse - Green Office', 'the-greenhouse-green-office', 'Eco-friendly office with living walls and rooftop garden.', 'Eco-friendly green office', 'private_office', 6, 2200.00, 1100.00, -1.2640, 36.8110, 280, '["wifi","green_energy","rooftop_garden","wellness_room","bike_storage","organic_coffee"]', '{"mon-fri":"7:00-19:00","sat":"8:00-15:00"}', 0],
            ['Virtual Office Premium', 'virtual-office-premium', 'Prestigious business address with mail handling.', 'Business address + mail handling', 'virtual_office', 1, 200.00, 100.00, -1.2680, 36.8070, 0, '["mail_handling","phone_answering","meeting_room_access","business_address"]', '{"mon-fri":"9:00-17:00"}', 0],
        ];
        $stmt = $pdo->prepare("INSERT INTO office_spaces (name,slug,description,short_description,space_type,capacity,price_per_month,security_deposit,latitude,longitude,size_sqft,amenities,business_hours,is_featured,created_by,city,state,country,address_line1,currency,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Nairobi','Nairobi County','Kenya','Westlands Business Park','KES','available')");
        $count = 0;
        foreach ($spaces as $s) {
            $stmt->execute([$s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $s[9], $s[10], $s[11], $s[12], $s[13], $uid]);
            $count++;
        }
        echo '<p class="success">Seeded ' . $count . ' office spaces</p>';
    } else {
        echo '<p class="info">office_spaces already has ' . $sc . ' rows</p>';
    }

    // === STEP 4: Seed space_images ===
    $ic = (int)$pdo->query("SELECT COUNT(*) FROM space_images")->fetchColumn();
    if ($ic === 0) {
        $spaceIds = $pdo->query("SELECT id FROM office_spaces")->fetchAll(PDO::FETCH_COLUMN);
        $img = 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800';
        $stmt = $pdo->prepare("INSERT INTO space_images (space_id, image_url, is_primary, sort_order, alt_text) VALUES (?, ?, 1, 1, 'FlexiSpace Office')");
        foreach ($spaceIds as $sid) {
            $stmt->execute([$sid]);
        }
        echo '<p class="success">Seeded ' . count($spaceIds) . ' space images</p>';
    }

    // === STEP 5: Seed testimonials ===
    $tc = (int)$pdo->query("SELECT COUNT(*) FROM testimonials")->fetchColumn();
    if ($tc === 0) {
        $tests = [
            ['FlexiSpace transformed our business. The premium office in Westlands gave us the professional image we needed. Highly recommended!', 5, 1],
            ['The open desk area is perfect for our growing team. Flexible terms and great amenities including high-speed WiFi.', 4, 1],
            ['Excellent location in the heart of Westlands. Walking distance to restaurants, banks, and public transport.', 5, 1],
            ['We started with a virtual office and upgraded as we grew. Fair pricing and transparent terms.', 4, 0],
        ];
        $stmt = $pdo->prepare("INSERT INTO testimonials (user_id, content, rating, status, is_featured) VALUES (?, ?, ?, 'approved', ?)");
        foreach ($tests as $t) {
            $stmt->execute([$uid, $t[0], $t[1], $t[2]]);
        }
        echo '<p class="success">Seeded 4 testimonials</p>';
    }

    // === STEP 6: Seed policy_rules ===
    $pc = (int)$pdo->query("SELECT COUNT(*) FROM policy_rules")->fetchColumn();
    if ($pc === 0) {
        $pdo->exec("INSERT INTO policy_rules (policy_key, policy_name, description, rule_type, default_value) VALUES
            ('max_images_per_space','Max Images','Max images per space','validation','10'),
            ('max_pending_visits','Max Pending Visits','Max concurrent pending visit requests','validation','3'),
            ('lease_min_duration_days','Min Lease Duration','Minimum lease duration','validation','30'),
            ('lease_max_duration_months','Max Lease Duration','Maximum lease duration','validation','24'),
            ('payment_grace_period_days','Payment Grace Period','Days before overdue','computation','5'),
            ('late_fee_percentage','Late Fee Percent','Late fee on overdue rent','computation','5'),
            ('deposit_percentage','Deposit Percent','Security deposit as percent of rent','computation','50'),
            ('visit_request_expiry_days','Visit Request Expiry','Days before auto-cancel','workflow','7'),
            ('lease_expiry_reminder_days','Lease Expiry Reminder','Days before lease expiry to remind','notification','30'),
            ('rent_increase_notice_days','Rent Increase Notice','Days notice for rent increase','notification','30')");
        echo '<p class="success">Seeded 10 policy rules</p>';
    }

    // === FINAL SUMMARY ===
    echo '<h2>Tables Summary</h2><ul>';
    foreach ($pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) as $t) {
        $c = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "<li>$t <span style='color:#666;'>($c rows)</span></li>";
    }
    echo '</ul>';
    echo '<p><strong>Login:</strong> admin@flexispace.co.ke / Admin@123</p>';
    echo '<a href="index.php">Homepage</a> &nbsp; <a href="admin/login.php">Admin Login</a>';
} catch (PDOException $e) {
    echo '<p class="error">' . htmlspecialchars($e->getMessage()) . '</p>';
}
echo '</body></html>';
