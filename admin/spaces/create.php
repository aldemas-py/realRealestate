<?php

/**
 * ADMIN - CREATE SPACE
 */
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $shortDescription = trim($_POST['short_description'] ?? '');
    $spaceType = $_POST['space_type'] ?? 'private_office';
    $capacity = (int)($_POST['capacity'] ?? 1);
    $pricePerMonth = (float)($_POST['price_per_month'] ?? 0);
    $securityDeposit = (float)($_POST['security_deposit'] ?? 0);
    $addressLine1 = trim($_POST['address_line1'] ?? '');
    $city = trim($_POST['city'] ?? 'Nairobi');
    $country = trim($_POST['country'] ?? 'Kenya');
    $latitude = (float)($_POST['latitude'] ?? 0);
    $longitude = (float)($_POST['longitude'] ?? 0);
    $sizeSqft = (int)($_POST['size_sqft'] ?? 0);
    $amenities = $_POST['amenities'] ?? [];
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    // Auto-generate slug
    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', $name));
        $slug = trim($slug, '-');
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO office_spaces (name, slug, description, short_description, space_type, capacity, price_per_month, security_deposit, address_line1, city, country, latitude, longitude, size_sqft, amenities, is_featured, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $name,
            $slug,
            $description,
            $shortDescription,
            $spaceType,
            $capacity,
            $pricePerMonth,
            $securityDeposit,
            $addressLine1,
            $city,
            $country,
            $latitude,
            $longitude,
            $sizeSqft,
            json_encode($amenities),
            $isFeatured,
            $_SESSION['user_id']
        ]);

        logAudit($_SESSION['user_id'], 'space.create', 'office_space', $db->lastInsertId());
        $_SESSION['success'] = "Space '$name' created successfully!";
        header('Location: /work_folder/realRealestate/admin/spaces/index.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to create space. Slug may already exist.';
    }
}

$pageTitle = 'Create Space - Admin';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <aside class="admin-sidebar"><?php require __DIR__ . '/../sidebar.php'; ?></aside>
    <main class="admin-main">
        <div class="admin-header">
            <h1>Create New Space</h1>
            <a href="/work_folder/realRealestate/admin/spaces/index.php" class="btn btn-ghost">&larr; Back</a>
        </div>

        <form method="POST" action="" style="max-width: 800px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group" style="grid-column: 1/-1;">
                    <label for="name">Space Name *</label>
                    <input type="text" id="name" name="name" required placeholder="e.g., Westlands Executive Tower">
                </div>
                <div class="form-group" style="grid-column: 1/-1;">
                    <label for="slug">URL Slug (leave empty to auto-generate)</label>
                    <input type="text" id="slug" name="slug" placeholder="westlands-executive-tower">
                </div>
                <div class="form-group" style="grid-column: 1/-1;">
                    <label for="description">Full Description</label>
                    <textarea id="description" name="description" rows="5"
                        placeholder="Detailed description of the space..."></textarea>
                </div>
                <div class="form-group" style="grid-column: 1/-1;">
                    <label for="short_description">Short Description (max 300 chars)</label>
                    <input type="text" id="short_description" name="short_description" maxlength="300"
                        placeholder="Brief highlight...">
                </div>
                <div class="form-group">
                    <label for="space_type">Space Type *</label>
                    <select id="space_type" name="space_type" required>
                        <option value="private_office">Private Office</option>
                        <option value="open_desk">Open Desk / Coworking</option>
                        <option value="meeting_room">Meeting Room</option>
                        <option value="virtual_office">Virtual Office</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="capacity">Capacity (people) *</label>
                    <input type="number" id="capacity" name="capacity" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label for="price_per_month">Price per Month (KES) *</label>
                    <input type="number" id="price_per_month" name="price_per_month" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="security_deposit">Security Deposit (KES)</label>
                    <input type="number" id="security_deposit" name="security_deposit" min="0" step="0.01" value="0">
                </div>
                <div class="form-group">
                    <label for="address_line1">Address *</label>
                    <input type="text" id="address_line1" name="address_line1" required
                        placeholder="Woodvale Grove, Westlands">
                </div>
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="Nairobi">
                </div>
                <div class="form-group">
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country" value="Kenya">
                </div>
                <div class="form-group">
                    <label for="latitude">Latitude</label>
                    <input type="number" id="latitude" name="latitude" step="0.0001" value="-1.2628">
                </div>
                <div class="form-group">
                    <label for="longitude">Longitude</label>
                    <input type="number" id="longitude" name="longitude" step="0.0001" value="36.8119">
                </div>
                <div class="form-group">
                    <label for="size_sqft">Size (sq ft)</label>
                    <input type="number" id="size_sqft" name="size_sqft" min="0" value="0">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_featured" value="1"> Featured Space
                    </label>
                </div>

                <div class="form-group">
                    <label>Amenities (hold Ctrl to select multiple)</label>
                    <select name="amenities[]" multiple style="height: 120px;">
                        <?php
                        $allAmenities = ['wifi', 'parking', 'coffee', 'meeting_room_access', '24_7_access', 'furnished', 'executive_lounge', 'air_conditioning', 'security', 'generator', 'printing', 'lockers', 'phone_booths', 'breakout_area', 'event_space', 'kitchen', 'video_conferencing', 'smartboard', 'projector', 'catering', 'sound_system', 'garden_access', 'natural_light', 'green_energy', 'wellness_room', 'bike_parking', 'mail_handling', 'phone_answering', 'business_address', 'director_services'];
                        foreach ($allAmenities as $a): ?>
                            <option value="<?= $a ?>"><?= ucwords(str_replace('_', ' ', $a)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Create Space</button>
        </form>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>