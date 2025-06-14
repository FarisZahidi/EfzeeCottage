-- Test data for EFZEE COTTAGE Database

-- Insert test users
INSERT INTO users (name, email, password, phone, role) VALUES
('Admin User', 'admin@efzeecottage.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456789', 'admin'),
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456781', 'guest'),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456782', 'guest');

-- Insert homestay details
INSERT INTO homestays (name, description, address, price_per_night, max_guests, bedrooms, bathrooms, status) VALUES
('EFZEE COTTAGE Main Unit', 'Spacious and modern homestay unit with garden view', 'Jalan Zabedah, Taman Zabedah, 83000 Batu Pahat, Johor', 250.00, 8, 3, 2, 'available'),
('EFZEE COTTAGE Studio', 'Cozy studio unit perfect for couples', 'Jalan Zabedah, Taman Zabedah, 83000 Batu Pahat, Johor', 150.00, 2, 1, 1, 'available');

-- Insert amenities
INSERT INTO amenities (name, icon) VALUES
('WiFi', 'fas fa-wifi'),
('Air Conditioning', 'fas fa-snowflake'),
('Kitchen', 'fas fa-utensils'),
('Parking', 'fas fa-parking'),
('TV', 'fas fa-tv'),
('Washing Machine', 'fas fa-tshirt');

-- Link amenities to homestays
INSERT INTO homestay_amenities (homestay_id, amenity_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6),
(2, 1), (2, 2), (2, 4), (2, 5);

-- Insert sample bookings
INSERT INTO bookings (user_id, homestay_id, check_in_date, check_out_date, total_price, guest_count, special_requests, status) VALUES
(2, 1, '2024-01-15', '2024-01-17', 500.00, 4, 'Early check-in if possible', 'confirmed'),
(3, 2, '2024-01-20', '2024-01-22', 300.00, 2, 'Late check-out requested', 'pending');

-- Insert sample payments
INSERT INTO payments (booking_id, amount, payment_method, status, transaction_id) VALUES
(1, 500.00, 'credit_card', 'completed', 'TRX123456789'),
(2, 300.00, 'bank_transfer', 'pending', 'TRX987654321');

-- Insert sample reviews
INSERT INTO reviews (booking_id, rating, comment) VALUES
(1, 5, 'Excellent stay! Very clean and comfortable. Will definitely come back.'),
(2, 4, 'Great location and friendly staff. Kitchen could be better equipped.');