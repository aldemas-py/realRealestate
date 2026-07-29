-- ============================================================
-- MIGRATION: Create missing tables for FlexiSpace
-- Run this via phpMyAdmin on realestate_db
-- ============================================================
-- VISIT REQUESTS
CREATE TABLE IF NOT EXISTS visit_requests (
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
) ENGINE = InnoDB;
-- LEASES
CREATE TABLE IF NOT EXISTS leases (
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
) ENGINE = InnoDB;
-- PAYMENTS
CREATE TABLE IF NOT EXISTS payments (
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
) ENGINE = InnoDB;
-- TESTIMONIALS
CREATE TABLE IF NOT EXISTS testimonials (
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
) ENGINE = InnoDB;
-- NOTIFICATIONS
CREATE TABLE IF NOT EXISTS notifications (
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
) ENGINE = InnoDB;
-- POLICY RULES
CREATE TABLE IF NOT EXISTS policy_rules (
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
) ENGINE = InnoDB;
-- COMPLIANCE REPORTS
CREATE TABLE IF NOT EXISTS compliance_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(100) NOT NULL,
    generated_by INT NOT NULL,
    parameters JSON,
    summary JSON,
    status ENUM('generating', 'ready', 'failed') DEFAULT 'generating',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE = InnoDB;
-- ============================================================
-- INSERT POLICY RULES
-- ============================================================
INSERT IGNORE INTO policy_rules (
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
    );
-- ============================================================
-- INSERT DUMMY TESTIMONIALS
-- ============================================================
INSERT INTO testimonials (user_id, content, rating, status, is_featured)
VALUES (
        1,
        'FlexiSpace transformed our business operations. The premium office space at Westlands gave us the professional image we needed to close major deals. Highly recommended!',
        5,
        'approved',
        TRUE
    ),
    (
        1,
        'The open desk area is perfect for our growing team. Flexible terms and great amenities including high-speed WiFi and meeting rooms.',
        4,
        'approved',
        TRUE
    ),
    (
        1,
        'Excellent location in the heart of Westlands. Walking distance to restaurants, banks, and public transport. The staff are incredibly helpful.',
        5,
        'approved',
        TRUE
    ),
    (
        1,
        'We started with a virtual office and upgraded to a private suite as we grew. FlexiSpace made the transition seamless. Fair pricing and transparent terms.',
        4,
        'approved',
        FALSE
    );