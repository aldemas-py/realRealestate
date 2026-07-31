<?php

/**
 * Zahara Co-Working Space - Database Setup & Seed
 * Visit: http://127.0.0.1/work_folder/realRealestate/setup.php
 */
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'realestate_db';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<!DOCTYPE html><html><head><title>Zahara Co-Working Space Setup</title>';
echo '<style>body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;}
.success{color:green;background:#e8f5e9;padding:8px 12px;border-radius:4px;margin:4px 0;}
.error{color:#c62828;background:#ffebee;padding:8px 12px;border-radius:4px;margin:4px 0;}
.info{color:#1565c0;background:#e3f2fd;padding:8px 12px;border-radius:4px;margin:4px 0;}
h2{color:#0D47A1;}a{display:inline-block;margin-top:20px;padding:10px 20px;background:#1565C0;color:white;text-decoration:none;border-radius:4px;}</style></head><body>';
echo '<h1>Zahara Co-Working Space - Setup</h1>';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db`");
    echo '<p class="success">Database ready: ' . $db . '</p>';

    $existing = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo '<p class="info">Existing tables: ' . implode(', ', $existing) . '</p>';

    // Run schema.sql if tables are empty
    if (!in_array('roles', $existing)) {
        $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
        $queries = explode(';', $schema);
        foreach ($queries as $q) {
            $q = trim($q);
            if (!empty($q) && stripos($q, 'CREATE DATABASE') === false && stripos($q, 'USE ') === false) {
                try {
                    $pdo->exec($q);
                } catch (PDOException $e) {
                    echo '<p class="error">Schema: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
            }
        }
        echo '<p class="success">Schema applied successfully</p>';
        $existing = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    }

    // Ensure admin user
    $admin = $pdo->query("SELECT id, email, role_id FROM users WHERE role_id = 1 OR email = 'info@zaharacowork.com' LIMIT 1")->fetch();
    if (!$admin) {
        $hash = password_hash('Admin@123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (role_id, full_name, email, phone, password_hash, status, approval_date) VALUES (1, 'System Admin', 'info@zaharacowork.com', '+254-724-161342', ?, 'active', NOW())");
        $stmt->execute([$hash]);
        $admin = $pdo->query("SELECT id, email, role_id FROM users WHERE email = 'info@zaharacowork.com' LIMIT 1")->fetch();
        echo '<p class="success">Created admin user</p>';
    } else {
        $hash = password_hash('Admin@123', PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $admin['id']]);
        echo '<p class="info">Reset password for existing admin (ID: ' . $admin['id'] . ')</p>';
    }
    $uid = (int)$admin['id'];
    echo '<p class="info">Admin: info@zaharacowork.com / Password: Admin@123 (ID: ' . $uid . ')</p>';

    // Fix FK references
    $pdo->exec("UPDATE office_spaces SET created_by = $uid WHERE created_by NOT IN (SELECT id FROM users) OR created_by IS NULL");

    // Seed office spaces
    $sc = (int)$pdo->query("SELECT COUNT(*) FROM office_spaces")->fetchColumn();
    if ($sc === 0) {
        $spaces = [
            ['Zahara Executive Suite', 'zahara-executive-suite', 'Premium private office at Krishna Centre, 2nd Floor, Westlands. Features floor-to-ceiling windows, premium furnishings, and access to executive lounge. Ideal for established businesses seeking a prestigious Westlands address.', 'Premium private office at Krishna Centre', 'private_office', 8, 35000, 17500, -1.2645, 36.8115, 350, '["wifi","parking","coffee","meeting_room_access","24_7_access","furnished","executive_lounge","air_conditioning","security","generator"]', '{"mon-fri":"7:00-21:00","sat":"9:00-17:00"}', 1],
            ['Zahara Open Hub', 'zahara-open-hub', 'Vibrant open-plan coworking space designed for creatives, freelancers, and startups. Hot-desking with high-speed WiFi, breakout zones, and networking events at Krishna Centre, Westlands.', 'Vibrant open-plan coworking in Westlands', 'open_desk', 25, 8000, 4000, -1.2648, 36.8118, 900, '["wifi","coffee","printing","lockers","phone_booths","breakout_area","event_space","kitchen"]', '{"mon-fri":"6:00-22:00","sat-sun":"8:00-18:00"}', 1],
            ['Zahara Boardroom', 'zahara-boardroom', 'Professional boardroom at Krishna Centre equipped with 4K video conferencing, smartboard, and presentation system. Perfect for team meetings, client pitches, and workshops up to 14 people.', 'Fully equipped boardroom for up to 14', 'meeting_room', 14, 15000, 7500, -1.2642, 36.8112, 250, '["wifi","video_conferencing","smartboard","projector","catering","sound_system","air_conditioning"]', '{"mon-fri":"8:00-19:00"}', 1],
            ['Zahara Green Office', 'zahara-green-office', 'Eco-friendly private office at Krishna Centre with natural lighting, living walls, and ergonomic workstations. Features rooftop access and wellness area for mindful work.', 'Eco-friendly office with rooftop access', 'private_office', 6, 25000, 12500, -1.2650, 36.8120, 300, '["wifi","natural_light","wellness_room","rooftop_access","green_energy","bike_parking","organic_coffee"]', '{"mon-fri":"7:00-20:00","sat":"8:00-16:00"}', 0],
            ['Zahara Business Presence', 'zahara-business-presence', 'Establish your business at Krishna Centre, Westlands with a prestigious address, mail handling, and 2 hours monthly meeting room access. Perfect for remote businesses needing a professional Nairobi presence.', 'Prestigious business address at Krishna Centre', 'virtual_office', 1, 3000, 1500, -1.2640, 36.8110, 0, '["mail_handling","business_address","meeting_room_access","phone_answering"]', '{"mon-fri":"9:00-17:00"}', 1],
            ['Zahara Standard Virtual', 'zahara-standard-virtual', 'Standard virtual office package at Krishna Centre, Westlands. Includes business address, mail handling, phone answering, and 5 hours monthly meeting room access.', 'Standard virtual package with meeting access', 'virtual_office', 1, 5000, 2500, -1.2643, 36.8113, 0, '["mail_handling","business_address","phone_answering","meeting_room_access","director_services"]', '{"mon-fri":"9:00-17:00"}', 0],
            ['Zahara Virtual Office Plus', 'zahara-virtual-office-plus', 'Premium virtual office at Krishna Centre. Includes everything in Standard plus 15 hours meeting room access, dedicated phone line, and company registration support.', 'Premium virtual with 15hr meeting access', 'virtual_office', 1, 10000, 5000, -1.2646, 36.8116, 0, '["mail_handling","business_address","phone_answering","meeting_room_access","director_services","company_registration"]', '{"mon-fri":"9:00-17:00"}', 1],
        ];
        $stmt = $pdo->prepare("INSERT INTO office_spaces (name,slug,description,short_description,space_type,capacity,price_per_month,security_deposit,latitude,longitude,size_sqft,amenities,business_hours,is_featured,created_by,city,state,country,address_line1,currency,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Nairobi','Nairobi County','Kenya','Krishna Centre, 2nd Floor, Westlands','KES','available')");
        $count = 0;
        foreach ($spaces as $s) {
            $stmt->execute([$s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $s[9], $s[10], $s[11], $s[12], $s[13], $uid]);
            $count++;
        }
        echo '<p class="success">Seeded ' . $count . ' office spaces (including virtual packages)</p>';
    } else {
        echo '<p class="info">office_spaces has ' . $sc . ' rows - updating location to Krishna Centre</p>';
        $pdo->exec("UPDATE office_spaces SET address_line1='Krishna Centre, 2nd Floor, Westlands', city='Nairobi', country='Kenya' WHERE address_line1 LIKE '%Westlands%' OR address_line1 LIKE '%Pine%' OR address_line1 LIKE '%Market%' OR address_line1 LIKE '%Green%'");
    }

    // Seed space_images
    $ic = (int)$pdo->query("SELECT COUNT(*) FROM space_images")->fetchColumn();
    if ($ic === 0) {
        $spaceIds = $pdo->query("SELECT id FROM office_spaces")->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $pdo->prepare("INSERT INTO space_images (space_id, image_url, is_primary, sort_order, alt_text) VALUES (?, 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800', 1, 1, 'Zahara Co-Working Space')");
        foreach ($spaceIds as $sid) {
            $stmt->execute([$sid]);
        }
        echo '<p class="success">Seeded ' . count($spaceIds) . ' space images</p>';
    }

    // Seed testimonials
    $tc = (int)$pdo->query("SELECT COUNT(*) FROM testimonials")->fetchColumn();
    if ($tc === 0) {
        $tests = [
            ['Zahara Co-Working transformed our business. The Krishna Centre location is perfect, and the team is incredibly supportive. A true replacement for the traditional workplace!', 5, 1],
            ['The Open Hub is amazing for our growing team. Flexible terms, great amenities, and an unbeatable Westlands location near restaurants and banks.', 4, 1],
            ['Excellent virtual office package. We get a prestigious Krishna Centre address, mail handling, and meeting room access - all at an affordable price.', 5, 1],
            ['We started with a virtual office and upgraded to a private suite as we grew. Fair pricing, transparent terms, and a professional environment.', 4, 0],
            ['The boardroom is perfect for client meetings. State-of-the-art video conferencing and a very professional setting at Krishna Centre.', 5, 0],
        ];
        $stmt = $pdo->prepare("INSERT INTO testimonials (user_id, content, rating, status, is_featured) VALUES (?, ?, ?, 'approved', ?)");
        foreach ($tests as $t) {
            $stmt->execute([$uid, $t[0], $t[1], $t[2]]);
        }
        echo '<p class="success">Seeded ' . count($tests) . ' testimonials</p>';
    }

    // Seed policy_rules
    $pc = (int)$pdo->query("SELECT COUNT(*) FROM policy_rules")->fetchColumn();
    if ($pc === 0) {
        $pdo->exec("INSERT INTO policy_rules (policy_key, policy_name, description, rule_type, default_value) VALUES
            ('max_images_per_space','Max Images Per Space','Maximum images allowed per office space listing','validation','10'),
            ('max_pending_visits','Max Pending Visit Requests','Maximum concurrent pending visit requests per user','validation','3'),
            ('lease_min_duration_days','Minimum Lease Duration','Minimum lease duration in days','validation','30'),
            ('lease_max_duration_months','Maximum Lease Duration','Maximum lease duration in months','validation','24'),
            ('payment_grace_period_days','Payment Grace Period','Days after due date before marked overdue','computation','5'),
            ('late_fee_percentage','Late Fee Percentage','Percentage of rent charged as late fee','computation','5'),
            ('deposit_percentage','Deposit Percentage','Security deposit as percentage of monthly rent','computation','50'),
            ('visit_request_expiry_days','Visit Request Expiry Days','Days after which pending visit request auto-cancels','workflow','7'),
            ('lease_expiry_reminder_days','Lease Expiry Reminder Days','Days before lease expiry to send reminder','notification','30'),
            ('rent_increase_notice_days','Rent Increase Notice Days','Days of notice required before rent increase','notification','30')");
        echo '<p class="success">Seeded 10 policy rules (Policy-as-Code engine)</p>';
    }

    // Summary
    echo '<h2>System Summary</h2><ul>';
    foreach ($pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) as $t) {
        $c = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "<li>$t <span style='color:#666;'>($c rows)</span></li>";
    }
    echo '</ul>';
    echo '<p><strong>Admin Login:</strong> info@zaharacowork.com / Password: Admin@123</p>';
    echo '<a href="index.php">&#127968; Homepage</a> &nbsp; <a href="admin/login.php">&#128274; Admin Login</a> &nbsp; <a href="admin/policies.php">&#9878; Policy Engine</a>';
} catch (PDOException $e) {
    echo '<p class="error">' . htmlspecialchars($e->getMessage()) . '</p>';
}
echo '</body></html>';