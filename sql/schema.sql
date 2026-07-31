-- ============================================================
-- REAL ESTATE OFFICE SPACE RENTAL SYSTEM
-- Policy-as-Code Schema with Compliance Enforcement
-- Location: Westlands, Nairobi, Kenya
-- ============================================================
CREATE DATABASE IF NOT EXISTS realestate_db;
USE realestate_db;
-- ============================================================
-- USERS & AUTH (RBAC Core)
-- ============================================================
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_level INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO roles (role_name, role_level)
VALUES ('admin', 100),
    ('customer', 10),
    ('visitor', 0);
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL DEFAULT 3,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('pending', 'active', 'suspended', 'inactive') NOT NULL DEFAULT 'pending',
    approval_date DATETIME NULL,
    approved_by INT NULL,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE
    SET NULL,
        CONSTRAINT chk_email_format CHECK (
            email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'
        )
);
-- ============================================================
-- AUDIT LOG (Compliance Trail)
-- ============================================================
CREATE TABLE audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    compliance_status ENUM('pass', 'fail', 'pending') DEFAULT 'pass',
    policy_violation TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_entity (entity_type, entity_id),
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_action (action)
);
-- ============================================================
-- OFFICE SPACES (Westlands, Nairobi)
-- ============================================================
CREATE TABLE office_spaces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    short_description VARCHAR(300),
    space_type ENUM(
        'private_office',
        'open_desk',
        'meeting_room',
        'virtual_office'
    ) NOT NULL,
    capacity INT NOT NULL CHECK (capacity > 0),
    price_per_month DECIMAL(10, 2) NOT NULL CHECK (price_per_month >= 0),
    security_deposit DECIMAL(10, 2) NOT NULL DEFAULT 0 CHECK (security_deposit >= 0),
    currency VARCHAR(3) DEFAULT 'KES',
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL DEFAULT 'Nairobi',
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Kenya',
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    size_sqft INT,
    amenities JSON,
    business_hours JSON,
    status ENUM(
        'available',
        'occupied',
        'maintenance',
        'unavailable'
    ) NOT NULL DEFAULT 'available',
    is_featured BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_status (status),
    INDEX idx_type (space_type),
    INDEX idx_location (latitude, longitude)
);
-- ============================================================
-- SPACE IMAGES
-- ============================================================
CREATE TABLE space_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    space_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    alt_text VARCHAR(200),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (space_id) REFERENCES office_spaces(id) ON DELETE CASCADE,
    INDEX idx_space_images (space_id)
);
-- ============================================================
-- VISIT REQUESTS (State Machine)
-- ============================================================
CREATE TABLE visit_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    space_id INT NOT NULL,
    preferred_date DATE NOT NULL,
    preferred_time TIME NOT NULL,
    alternate_date DATE,
    alternate_time TIME,
    notes TEXT,
    status ENUM(
        'pending',
        'approved',
        'completed',
        'rejected',
        'cancelled',
        'lease_created'
    ) NOT NULL DEFAULT 'pending',
    admin_notes TEXT,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (space_id) REFERENCES office_spaces(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE
    SET NULL,
        INDEX idx_visit_status (status),
        INDEX idx_visit_user (user_id),
        CONSTRAINT chk_one_pending_per_space UNIQUE (user_id, space_id, status(10))
);
-- ============================================================
-- LEASES (Rental Agreements - Compliance Core)
-- ============================================================
CREATE TABLE leases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    space_id INT NOT NULL,
    visit_request_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    rent_amount DECIMAL(10, 2) NOT NULL CHECK (rent_amount > 0),
    deposit_amount DECIMAL(10, 2) NOT NULL CHECK (deposit_amount >= 0),
    deposit_paid BOOLEAN DEFAULT FALSE,
    payment_due_day INT NOT NULL DEFAULT 1 CHECK (
        payment_due_day BETWEEN 1 AND 28
    ),
    lease_document TEXT,
    terms_agreed BOOLEAN DEFAULT FALSE,
    status ENUM(
        'draft',
        'deposit_pending',
        'active',
        'expiring',
        'expired',
        'terminated'
    ) NOT NULL DEFAULT 'draft',
    signed_by_customer BOOLEAN DEFAULT FALSE,
    signed_by_admin BOOLEAN DEFAULT FALSE,
    signed_at DATETIME NULL,
    terminated_at DATETIME NULL,
    termination_reason TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (space_id) REFERENCES office_spaces(id) ON DELETE CASCADE,
    FOREIGN KEY (visit_request_id) REFERENCES visit_requests(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT chk_dates CHECK (end_date > start_date),
    INDEX idx_lease_status (status),
    INDEX idx_lease_user (user_id),
    INDEX idx_lease_space (space_id)
);
-- ============================================================
-- PAYMENTS
-- ============================================================
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lease_id INT NOT NULL,
    user_id INT NOT NULL,
    payment_type ENUM('deposit', 'rent', 'late_fee', 'other') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL CHECK (amount > 0),
    currency VARCHAR(3) DEFAULT 'KES',
    due_date DATE NOT NULL,
    paid_date DATE NULL,
    status ENUM(
        'pending',
        'paid',
        'overdue',
        'partially_paid',
        'refunded'
    ) NOT NULL DEFAULT 'pending',
    payment_method ENUM(
        'cash',
        'bank_transfer',
        'credit_card',
        'mobile_money',
        'other'
    ) NULL,
    transaction_reference VARCHAR(100) NULL,
    receipt_url VARCHAR(500) NULL,
    notes TEXT,
    overdue_days INT DEFAULT 0,
    late_fee_applied DECIMAL(10, 2) DEFAULT 0,
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lease_id) REFERENCES leases(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT chk_unique_payment UNIQUE (lease_id, payment_type, due_date),
    INDEX idx_payment_status (status),
    INDEX idx_payment_due (due_date),
    INDEX idx_payment_lease (lease_id)
);
-- ============================================================
-- TESTIMONIALS
-- ============================================================
CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    space_id INT NULL,
    content TEXT NOT NULL,
    rating INT NOT NULL CHECK (
        rating BETWEEN 1 AND 5
    ),
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (space_id) REFERENCES office_spaces(id) ON DELETE
    SET NULL,
        FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE
    SET NULL,
        INDEX idx_testimonial_status (status),
        INDEX idx_testimonial_featured (is_featured)
);
-- ============================================================
-- NOTIFICATIONS
-- ============================================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'alert', 'success') DEFAULT 'info',
    policy_trigger VARCHAR(100) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notif_user (user_id),
    INDEX idx_notif_read (is_read),
    INDEX idx_notif_policy (policy_trigger)
);
-- ============================================================
-- POLICY ENGINE CONFIG (Policy as Code)
-- ============================================================
CREATE TABLE policy_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    policy_key VARCHAR(100) NOT NULL UNIQUE,
    policy_name VARCHAR(200) NOT NULL,
    description TEXT,
    rule_type ENUM(
        'validation',
        'workflow',
        'access_control',
        'notification',
        'computation'
    ) NOT NULL,
    default_value VARCHAR(500) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT INTO policy_rules (
        policy_key,
        policy_name,
        description,
        rule_type,
        default_value
    )
VALUES (
        'max_images_per_space',
        'Max Images Per Space',
        'Maximum number of images allowed per office space listing',
        'validation',
        '10'
    ),
    (
        'max_pending_visits',
        'Max Pending Visit Requests',
        'Maximum concurrent pending visit requests per user',
        'validation',
        '3'
    ),
    (
        'lease_min_duration_days',
        'Minimum Lease Duration',
        'Minimum lease duration in days',
        'validation',
        '30'
    ),
    (
        'lease_max_duration_months',
        'Maximum Lease Duration',
        'Maximum lease duration in months',
        'validation',
        '24'
    ),
    (
        'payment_grace_period_days',
        'Payment Grace Period',
        'Number of days after due date before marked overdue',
        'computation',
        '5'
    ),
    (
        'late_fee_percentage',
        'Late Fee Percentage',
        'Percentage of rent charged as late fee',
        'computation',
        '5'
    ),
    (
        'deposit_percentage',
        'Deposit Percentage',
        'Security deposit as percentage of monthly rent',
        'computation',
        '50'
    ),
    (
        'rent_increase_notice_days',
        'Rent Increase Notice',
        'Days of notice required before rent increase',
        'notification',
        '30'
    ),
    (
        'lease_expiry_reminder_days',
        'Lease Expiry Reminder',
        'Days before lease expiry to send reminder',
        'notification',
        '30'
    ),
    (
        'visit_request_expiry_days',
        'Visit Request Expiry',
        'Days after which pending visit request auto-cancels',
        'workflow',
        '7'
    ),
    (
        'require_lease_signing',
        'Require Lease Signing',
        'Both parties must sign lease before activation',
        'access_control',
        'true'
    );
-- ============================================================
-- COMPLIANCE REPORTS
-- ============================================================
CREATE TABLE compliance_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(100) NOT NULL,
    generated_by INT NOT NULL,
    parameters JSON,
    summary JSON,
    status ENUM('generating', 'ready', 'failed') DEFAULT 'generating',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE RESTRICT
);
-- ============================================================
-- TRIGGERS
-- ============================================================
DELIMITER // CREATE TRIGGER after_user_insert
AFTER
INSERT ON users FOR EACH ROW BEGIN
INSERT INTO audit_log (
        user_id,
        action,
        entity_type,
        entity_id,
        new_values,
        compliance_status
    )
VALUES (
        NEW.id,
        'user.register',
        'user',
        NEW.id,
        JSON_OBJECT(
            'email',
            NEW.email,
            'role_id',
            NEW.role_id,
            'status',
            NEW.status
        ),
        'pass'
    );
END // CREATE TRIGGER after_lease_active
AFTER
UPDATE ON leases FOR EACH ROW BEGIN IF NEW.status = 'active'
    AND OLD.status != 'active' THEN
UPDATE office_spaces
SET status = 'occupied'
WHERE id = NEW.space_id
    AND status = 'available';
INSERT INTO audit_log (
        user_id,
        action,
        entity_type,
        entity_id,
        old_values,
        new_values,
        compliance_status
    )
VALUES (
        NEW.created_by,
        'lease.activate',
        'lease',
        NEW.id,
        JSON_OBJECT('status', OLD.status),
        JSON_OBJECT('status', NEW.status, 'space_id', NEW.space_id),
        'pass'
    );
END IF;
IF NEW.status IN ('expired', 'terminated')
AND OLD.status NOT IN ('expired', 'terminated') THEN
UPDATE office_spaces
SET status = 'available'
WHERE id = NEW.space_id;
END IF;
END // DELIMITER;
-- ============================================================
-- SEED DATA: Default Admin
-- ============================================================
-- Password: Admin@123 (bcrypt hash)
INSERT INTO users (
        role_id,
        full_name,
        email,
        phone,
        password_hash,
        status,
        approval_date
    )
VALUES (
        1,
        'System Admin',
        'admin@realestate.co.ke',
        '+254-700-000000',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'active',
        NOW()
    );
-- ============================================================
-- SEED DATA: Office Spaces (Westlands, Nairobi)
-- ============================================================
INSERT INTO office_spaces (
        name,
        slug,
        description,
        short_description,
        space_type,
        capacity,
        price_per_month,
        security_deposit,
        address_line1,
        city,
        country,
        latitude,
        longitude,
        size_sqft,
        amenities,
        business_hours,
        status,
        is_featured,
        created_by
    )
VALUES (
        'Westlands Executive Tower',
        'westlands-executive-tower',
        'Premium executive office on the 10th floor with stunning Nairobi skyline views. Features modern furnishings, high-speed internet, and access to a shared executive lounge. Ideal for established businesses seeking a prestigious Westlands address.',
        'Premium executive office with Nairobi skyline views',
        'private_office',
        8,
        85000.00,
        42500.00,
        'Woodvale Grove, Westlands',
        'Nairobi',
        'Kenya',
        -1.2628,
        36.8119,
        320,
        '["wifi","parking","coffee","meeting_room_access","24_7_access","furnished","executive_lounge","air_conditioning","security","generator"]',
        '{"mon-fri":"8:00-20:00","sat":"9:00-17:00"}',
        'available',
        TRUE,
        1
    ),
    (
        'The Greenhouse Coworking',
        'the-greenhouse-coworking',
        'Vibrant open-plan coworking space designed for creative professionals, freelancers, and startups. Hot-desking with communal tables, breakout zones, and regular networking events. Located in the heart of Westlands.',
        'Vibrant coworking for creatives & startups',
        'open_desk',
        25,
        15000.00,
        7500.00,
        'Rhapta Road, Westlands',
        'Nairobi',
        'Kenya',
        -1.2650,
        36.8135,
        750,
        '["wifi","coffee","printing","lockers","phone_booths","breakout_area","event_space","kitchen","bike_parking"]',
        '{"mon-fri":"6:00-22:00","sat-sun":"8:00-18:00"}',
        'available',
        TRUE,
        1
    ),
    (
        'Boardroom Suite - Westlands',
        'boardroom-suite-westlands',
        'Professional meeting room fully equipped with 4K video conferencing, smartboard, and presentation system. Ideal for team meetings, client pitches, and workshops of up to 14 people.',
        'Fully equipped meeting room for up to 14 people',
        'meeting_room',
        14,
        35000.00,
        17500.00,
        'Mpaka Road, Westlands',
        'Nairobi',
        'Kenya',
        -1.2605,
        36.8100,
        180,
        '["wifi","video_conferencing","smartboard","projector","catering","sound_system","air_conditioning"]',
        '{"mon-fri":"7:00-19:00"}',
        'available',
        TRUE,
        1
    ),
    (
        'The Garden Office',
        'the-garden-office',
        'Serene ground-floor office with direct access to a landscaped garden courtyard. Features sustainable design, natural lighting, and a calming work environment. Perfect for wellness-focused businesses.',
        'Serene garden-access office with sustainable design',
        'private_office',
        6,
        55000.00,
        27500.00,
        'Waiyaki Way, Westlands',
        'Nairobi',
        'Kenya',
        -1.2590,
        36.8160,
        250,
        '["wifi","garden_access","parking","natural_light","green_energy","wellness_room","coffee_bar","generator"]',
        '{"mon-fri":"7:00-19:00","sat":"8:00-15:00"}',
        'available',
        FALSE,
        1
    ),
    (
        'Virtual Office - Westlands',
        'virtual-office-westlands',
        'Establish your business presence with a prestigious Westlands address. Includes mail handling, dedicated phone line, and monthly meeting room credits. Perfect for remote teams and growing startups.',
        'Prestigious Westlands business address',
        'virtual_office',
        1,
        5000.00,
        2500.00,
        'Woodvale Grove, Westlands',
        'Nairobi',
        'Kenya',
        -1.2630,
        36.8120,
        0,
        '["mail_handling","phone_answering","meeting_room_access","business_address","director_services"]',
        '{"mon-fri":"9:00-17:00"}',
        'available',
        FALSE,
        1
    );
-- Seed space images
INSERT INTO space_images (
        space_id,
        image_url,
        is_primary,
        sort_order,
        alt_text
    )
VALUES (
        1,
        'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800',
        TRUE,
        1,
        'Westlands Executive Tower - Main Office'
    ),
    (
        1,
        'https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=800',
        FALSE,
        2,
        'Westlands Executive Tower - Meeting Area'
    ),
    (
        2,
        'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800',
        TRUE,
        1,
        'The Greenhouse Coworking - Open Space'
    ),
    (
        2,
        'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=800',
        FALSE,
        2,
        'The Greenhouse Coworking - Breakout Zone'
    ),
    (
        3,
        'https://images.unsplash.com/photo-1517502884422-41eaead166d4?w=800',
        TRUE,
        1,
        'Boardroom Suite - Meeting Setup'
    ),
    (
        4,
        'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800',
        TRUE,
        1,
        'The Garden Office - Interior View'
    ),
    (
        5,
        'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800',
        TRUE,
        1,
        'Virtual Office - Reception Area'
    );
-- Testimonial seed (sample approved review)
INSERT INTO testimonials (
        user_id,
        space_id,
        content,
        rating,
        status,
        is_featured
    )
VALUES (
        1,
        1,
        'Excellent executive office space in Westlands! The facilities are top-notch and the location is perfect for our business operations. Highly recommend.',
        5,
        'approved',
        TRUE
    );