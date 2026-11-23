-- =====================================================================
-- Mock data: ผู้ใช้ 5 คน + ประวัติการจอง/ชำระเงิน/คำสั่งซื้อ/ตั๋ว
-- ต้องรัน mock_concerts.sql ก่อน (ใช้คอนเสิร์ต C0026-C0038)
--
-- บัญชีสำหรับ login ทดสอบ (รหัสผ่านทุกบัญชี = Password123!)
--   อีเมล                        รหัสผ่าน         หมายเหตุ
--   mock.napat@example.com       Password123!     3 คำสั่งซื้อ (ชำระแล้ว)
--   mock.ploy@example.com        Password123!     3 คำสั่งซื้อ (ชำระแล้ว)
--   mock.kritsada@example.com    Password123!     2 คำสั่งซื้อ + 1 การจองรอชำระ
--   mock.nattaya@example.com     Password123!     2 คำสั่งซื้อ + 1 การจองที่ยกเลิก
--   mock.tanawat@example.com     Password123!     ไม่มีตั๋ว (รอชำระ 1 / ยกเลิก 1)
-- (รหัสผ่านคัดลอกมาจาก admin@concert.local ที่ DatabaseSeeder ตั้งไว้)
--
-- รันซ้ำได้: ส่วนแรกลบข้อมูลของผู้ใช้ mock.*@example.com แล้วสร้างใหม่
-- =====================================================================
USE concert_db;

-- ปิด Safe Update Mode ชั่วคราว (Workbench บล็อก DELETE/UPDATE ที่ไม่มี WHERE บน key)
SET SQL_SAFE_UPDATES = 0;

SET FOREIGN_KEY_CHECKS = 0;
UPDATE seat s
  JOIN booking_seat bs ON bs.seat_id = s.seat_id
  JOIN booking b ON b.booking_id = bs.booking_id
  JOIN app_user u ON u.user_id = b.user_id
  JOIN concert c ON c.concert_id = b.concert_id
   SET s.seat_booking_status = 'AVAILABLE'
 WHERE u.email LIKE 'mock.%@example.com' AND c.status <> 'SOLD_OUT';
DELETE t FROM ticket t JOIN user_order o ON o.order_id = t.order_id JOIN booking b ON b.booking_id = o.booking_id
  JOIN app_user u ON u.user_id = b.user_id WHERE u.email LIKE 'mock.%@example.com';
DELETE o FROM user_order o JOIN booking b ON b.booking_id = o.booking_id
  JOIN app_user u ON u.user_id = b.user_id WHERE u.email LIKE 'mock.%@example.com';
DELETE p FROM payment p JOIN booking b ON b.booking_id = p.booking_id
  JOIN app_user u ON u.user_id = b.user_id WHERE u.email LIKE 'mock.%@example.com';
DELETE bs FROM booking_seat bs JOIN booking b ON b.booking_id = bs.booking_id
  JOIN app_user u ON u.user_id = b.user_id WHERE u.email LIKE 'mock.%@example.com';
DELETE b FROM booking b JOIN app_user u ON u.user_id = b.user_id WHERE u.email LIKE 'mock.%@example.com';
DELETE FROM app_user WHERE email LIKE 'mock.%@example.com';
SET FOREIGN_KEY_CHECKS = 1;

SET @pw = (SELECT password_hash FROM (SELECT password_hash FROM app_user WHERE email = 'admin@concert.local' LIMIT 1) t);

INSERT INTO app_user (email, password_hash, role, account_status, username, first_name, last_name, phone) VALUES
('mock.napat@example.com', @pw, 'USER', 1, 'mock_napat', 'Napat', 'Kittisak', '0891000001'),
('mock.ploy@example.com', @pw, 'USER', 1, 'mock_ploy', 'Ploypailin', 'Chaiyasit', '0891000002'),
('mock.kritsada@example.com', @pw, 'USER', 1, 'mock_kritsada', 'Kritsada', 'Wongsawat', '0891000003'),
('mock.nattaya@example.com', @pw, 'USER', 1, 'mock_nattaya', 'Nattaya', 'Srisuk', '0891000004'),
('mock.tanawat@example.com', @pw, 'USER', 1, 'mock_tanawat', 'Tanawat', 'Boonmee', '0891000005');

-- (1) mock.napat : C0026 VIP x2 = 9000 [CONFIRMED]
SET @u = (SELECT user_id FROM app_user WHERE email = 'mock.napat@example.com');
SET @c = (SELECT concert_id FROM concert WHERE concert_code = 'C0026');
INSERT INTO booking (user_id, concert_id, booking_date, total_amount, status, expiry_time, booking_code) VALUES (@u, @c, '2026-09-22 10:15:00', 9000, 'CONFIRMED', '2026-09-22 10:30:00', 'BKG-C0026-260922-0001');
SET @b = LAST_INSERT_ID();
INSERT INTO booking_seat (booking_id, seat_id, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot) SELECT @b, s.seat_id, z.name, CONCAT(s.`row`, s.`number`), z.price FROM seat s JOIN zone z ON z.zone_id = s.zone_id WHERE z.concert_id = @c AND z.name = 'VIP' AND (s.`row`, s.`number`) IN (('A',1),('A',2));
UPDATE seat s JOIN booking_seat bs ON bs.seat_id = s.seat_id SET s.seat_booking_status = 'SOLD' WHERE bs.booking_id = @b;
INSERT INTO payment (booking_id, amount, method, status, created_at, provider_transaction_id) VALUES (@b, 9000, 'CREDIT_CARD', 'COMPLETED', '2026-09-22 10:18:00', 'ch_mock_bkg_c0026_260922_0001');
SET @p = LAST_INSERT_ID();
INSERT INTO user_order (booking_id, payment_id, order_date, order_code, concert_name_snapshot, artist_snapshot, venue_snapshot, event_datetime_snapshot, organization_name_snapshot, user_fiestname_snapshot, user_lastname_snapshot, user_phone_snapshot) SELECT @b, @p, '2026-09-22 10:18:00', 'ORD-C0026-260922-0001', c.name, c.artist, c.venue, c.event_datetime, o.name, u.first_name, u.last_name, u.phone FROM concert c JOIN organization o ON o.organization_id = c.organization_id JOIN app_user u ON u.user_id = @u WHERE c.concert_id = @c;
SET @o = LAST_INSERT_ID();
INSERT INTO ticket (order_id, zone_id, seat_id, qr_code, is_used, ticket_code, quantity, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot, created_at) SELECT @o, s.zone_id, s.seat_id, SHA2(CONCAT(UUID(), s.seat_id), 256), 0, CONCAT('TKT-', 'C0026', '-', @b, '-', s.seat_id), 1, bs.zone_name_snapshot, bs.seat_label_snapshot, bs.unit_price_snapshot, '2026-09-22 10:18:00' FROM booking_seat bs JOIN seat s ON s.seat_id = bs.seat_id WHERE bs.booking_id = @b;

-- (2) mock.napat : C0038 Standing Front x3 = 4800 [CONFIRMED]
SET @u = (SELECT user_id FROM app_user WHERE email = 'mock.napat@example.com');
SET @c = (SELECT concert_id FROM concert WHERE concert_code = 'C0038');
INSERT INTO booking (user_id, concert_id, booking_date, total_amount, status, expiry_time, booking_code) VALUES (@u, @c, '2026-09-15 14:30:00', 4800, 'CONFIRMED', '2026-09-15 14:45:00', 'BKG-C0038-260915-0001');
SET @b = LAST_INSERT_ID();
INSERT INTO payment (booking_id, amount, method, status, created_at, provider_transaction_id) VALUES (@b, 4800, 'PROMPTPAY', 'COMPLETED', '2026-09-15 14:33:00', 'ch_mock_bkg_c0038_260915_0001');
SET @p = LAST_INSERT_ID();
INSERT INTO user_order (booking_id, payment_id, order_date, order_code, concert_name_snapshot, artist_snapshot, venue_snapshot, event_datetime_snapshot, organization_name_snapshot, user_fiestname_snapshot, user_lastname_snapshot, user_phone_snapshot) SELECT @b, @p, '2026-09-15 14:33:00', 'ORD-C0038-260915-0001', c.name, c.artist, c.venue, c.event_datetime, o.name, u.first_name, u.last_name, u.phone FROM concert c JOIN organization o ON o.organization_id = c.organization_id JOIN app_user u ON u.user_id = @u WHERE c.concert_id = @c;
SET @o = LAST_INSERT_ID();
INSERT INTO ticket (order_id, zone_id, seat_id, qr_code, is_used, ticket_code, quantity, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot, created_at) SELECT @o, z.zone_id, NULL, SHA2(CONCAT(UUID(), @o, 1), 256), 0, CONCAT('TKT-', 'C0038', '-', @b, '-S1'), 1, z.name, NULL, z.price, '2026-09-15 14:33:00' FROM zone z WHERE z.concert_id = @c AND z.name = 'Standing Front';
INSERT INTO ticket (order_id, zone_id, seat_id, qr_code, is_used, ticket_code, quantity, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot, created_at) SELECT @o, z.zone_id, NULL, SHA2(CONCAT(UUID(), @o, 2), 256), 0, CONCAT('TKT-', 'C0038', '-', @b, '-S2'), 1, z.name, NULL, z.price, '2026-09-15 14:33:00' FROM zone z WHERE z.concert_id = @c AND z.name = 'Standing Front';
INSERT INTO ticket (order_id, zone_id, seat_id, qr_code, is_used, ticket_code, quantity, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot, created_at) SELECT @o, z.zone_id, NULL, SHA2(CONCAT(UUID(), @o, 3), 256), 0, CONCAT('TKT-', 'C0038', '-', @b, '-S3'), 1, z.name, NULL, z.price, '2026-09-15 14:33:00' FROM zone z WHERE z.concert_id = @c AND z.name = 'Standing Front';

-- (3) mock.napat : C0036 Table x2 = 5600 [CONFIRMED]
SET @u = (SELECT user_id FROM app_user WHERE email = 'mock.napat@example.com');
SET @c = (SELECT concert_id FROM concert WHERE concert_code = 'C0036');
INSERT INTO booking (user_id, concert_id, booking_date, total_amount, status, expiry_time, booking_code) VALUES (@u, @c, '2026-09-03 12:05:00', 5600, 'CONFIRMED', '2026-09-03 12:20:00', 'BKG-C0036-260903-0001');
SET @b = LAST_INSERT_ID();
INSERT INTO booking_seat (booking_id, seat_id, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot) SELECT @b, s.seat_id, z.name, CONCAT(s.`row`, s.`number`), z.price FROM seat s JOIN zone z ON z.zone_id = s.zone_id WHERE z.concert_id = @c AND z.name = 'Table' AND (s.`row`, s.`number`) IN (('A',1),('A',2));
UPDATE seat s JOIN booking_seat bs ON bs.seat_id = s.seat_id SET s.seat_booking_status = 'SOLD' WHERE bs.booking_id = @b;
INSERT INTO payment (booking_id, amount, method, status, created_at, provider_transaction_id) VALUES (@b, 5600, 'CREDIT_CARD', 'COMPLETED', '2026-09-03 12:08:00', 'ch_mock_bkg_c0036_260903_0001');
SET @p = LAST_INSERT_ID();
INSERT INTO user_order (booking_id, payment_id, order_date, order_code, concert_name_snapshot, artist_snapshot, venue_snapshot, event_datetime_snapshot, organization_name_snapshot, user_fiestname_snapshot, user_lastname_snapshot, user_phone_snapshot) SELECT @b, @p, '2026-09-03 12:08:00', 'ORD-C0036-260903-0001', c.name, c.artist, c.venue, c.event_datetime, o.name, u.first_name, u.last_name, u.phone FROM concert c JOIN organization o ON o.organization_id = c.organization_id JOIN app_user u ON u.user_id = @u WHERE c.concert_id = @c;
SET @o = LAST_INSERT_ID();
INSERT INTO ticket (order_id, zone_id, seat_id, qr_code, is_used, ticket_code, quantity, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot, created_at) SELECT @o, s.zone_id, s.seat_id, SHA2(CONCAT(UUID(), s.seat_id), 256), 0, CONCAT('TKT-', 'C0036', '-', @b, '-', s.seat_id), 1, bs.zone_name_snapshot, bs.seat_label_snapshot, bs.unit_price_snapshot, '2026-09-03 12:08:00' FROM booking_seat bs JOIN seat s ON s.seat_id = bs.seat_id WHERE bs.booking_id = @b;

-- (4) mock.ploy : C0027 Gold x4 = 14000 [CONFIRMED]
SET @u = (SELECT user_id FROM app_user WHERE email = 'mock.ploy@example.com');
SET @c = (SELECT concert_id FROM concert WHERE concert_code = 'C0027');
INSERT INTO booking (user_id, concert_id, booking_date, total_amount, status, expiry_time, booking_code) VALUES (@u, @c, '2026-09-27 19:40:00', 14000, 'CONFIRMED', '2026-09-27 19:55:00', 'BKG-C0027-260927-0001');
SET @b = LAST_INSERT_ID();
INSERT INTO booking_seat (booking_id, seat_id, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot) SELECT @b, s.seat_id, z.name, CONCAT(s.`row`, s.`number`), z.price FROM seat s JOIN zone z ON z.zone_id = s.zone_id WHERE z.concert_id = @c AND z.name = 'Gold' AND (s.`row`, s.`number`) IN (('B',3),('B',4),('B',5),('B',6));
UPDATE seat s JOIN booking_seat bs ON bs.seat_id = s.seat_id SET s.seat_booking_status = 'SOLD' WHERE bs.booking_id = @b;
INSERT INTO payment (booking_id, amount, method, status, created_at, provider_transaction_id) VALUES (@b, 14000, 'CREDIT_CARD', 'COMPLETED', '2026-09-27 19:43:00', 'ch_mock_bkg_c0027_260927_0001');
SET @p = LAST_INSERT_ID();
INSERT INTO user_order (booking_id, payment_id, order_date, order_code, concert_name_snapshot, artist_snapshot, venue_snapshot, event_datetime_snapshot, organization_name_snapshot, user_fiestname_snapshot, user_lastname_snapshot, user_phone_snapshot) SELECT @b, @p, '2026-09-27 19:43:00', 'ORD-C0027-260927-0001', c.name, c.artist, c.venue, c.event_datetime, o.name, u.first_name, u.last_name, u.phone FROM concert c JOIN organization o ON o.organization_id = c.organization_id JOIN app_user u ON u.user_id = @u WHERE c.concert_id = @c;
SET @o = LAST_INSERT_ID();
INSERT INTO ticket (order_id, zone_id, seat_id, qr_code, is_used, ticket_code, quantity, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot, created_at) SELECT @o, s.zone_id, s.seat_id, SHA2(CONCAT(UUID(), s.seat_id), 256), 0, CONCAT('TKT-', 'C0027', '-', @b, '-', s.seat_id), 1, bs.zone_name_snapshot, bs.seat_label_snapshot, bs.unit_price_snapshot, '2026-09-27 19:43:00' FROM booking_seat bs JOIN seat s ON s.seat_id = bs.seat_id WHERE bs.booking_id = @b;

-- (5) mock.ploy : C0030 Table Seat x2 = 5000 [CONFIRMED]
SET @u = (SELECT user_id FROM app_user WHERE email = 'mock.ploy@example.com');
SET @c = (SELECT concert_id FROM concert WHERE concert_code = 'C0030');
INSERT INTO booking (user_id, concert_id, booking_date, total_amount, status, expiry_time, booking_code) VALUES (@u, @c, '2026-10-03 09:20:00', 5000, 'CONFIRMED', '2026-10-03 09:35:00', 'BKG-C0030-261003-0001');
SET @b = LAST_INSERT_ID();
INSERT INTO booking_seat (booking_id, seat_id, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot) SELECT @b, s.seat_id, z.name, CONCAT(s.`row`, s.`number`), z.price FROM seat s JOIN zone z ON z.zone_id = s.zone_id WHERE z.concert_id = @c AND z.name = 'Table Seat' AND (s.`row`, s.`number`) IN (('A',1),('A',2));
UPDATE seat s JOIN booking_seat bs ON bs.seat_id = s.seat_id SET s.seat_booking_status = 'SOLD' WHERE bs.booking_id = @b;
INSERT INTO payment (booking_id, amount, method, status, created_at, provider_transaction_id) VALUES (@b, 5000, 'PROMPTPAY', 'COMPLETED', '2026-10-03 09:23:00', 'ch_mock_bkg_c0030_261003_0001');
SET @p = LAST_INSERT_ID();
INSERT INTO user_order (booking_id, payment_id, order_date, order_code, concert_name_snapshot, artist_snapshot, venue_snapshot, event_datetime_snapshot, organization_name_snapshot, user_fiestname_snapshot, user_lastname_snapshot, user_phone_snapshot) SELECT @b, @p, '2026-10-03 09:23:00', 'ORD-C0030-261003-0001', c.name, c.artist, c.venue, c.event_datetime, o.name, u.first_name, u.last_name, u.phone FROM concert c JOIN organization o ON o.organization_id = c.organization_id JOIN app_user u ON u.user_id = @u WHERE c.concert_id = @c;
SET @o = LAST_INSERT_ID();
INSERT INTO ticket (order_id, zone_id, seat_id, qr_code, is_used, ticket_code, quantity, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot, created_at) SELECT @o, s.zone_id, s.seat_id, SHA2(CONCAT(UUID(), s.seat_id), 256), 0, CONCAT('TKT-', 'C0030', '-', @b, '-', s.seat_id), 1, bs.zone_name_snapshot, bs.seat_label_snapshot, bs.unit_price_snapshot, '2026-10-03 09:23:00' FROM booking_seat bs JOIN seat s ON s.seat_id = bs.seat_id WHERE bs.booking_id = @b;

-- (6) mock.ploy : C0031 Front Stage x2 = 5000 [CONFIRMED]
SET @u = (SELECT user_id FROM app_user WHERE email = 'mock.ploy@example.com');
SET @c = (SELECT concert_id FROM concert WHERE concert_code = 'C0031');
INSERT INTO booking (user_id, concert_id, booking_date, total_amount, status, expiry_time, booking_code) VALUES (@u, @c, '2026-10-06 21:10:00', 5000, 'CONFIRMED', '2026-10-06 21:25:00', 'BKG-C0031-261006-0001');
SET @b = LAST_INSERT_ID();
INSERT INTO payment (booking_id, amount, method, status, created_at, provider_transaction_id) VALUES (@b, 5000, 'CREDIT_CARD', 'COMPLETED', '2026-10-06 21:13:00', 'ch_mock_bkg_c0031_261006_0001');
SET @p = LAST_INSERT_ID();
INSERT INTO user_order (booking_id, payment_id, order_date, order_code, concert_name_snapshot, artist_snapshot, venue_snapshot, event_datetime_snapshot, organization_name_snapshot, user_fiestname_snapshot, user_lastname_snapshot, user_phone_snapshot) SELECT @b, @p, '2026-10-06 21:13:00', 'ORD-C0031-261006-0001', c.name, c.artist, c.venue, c.event_datetime, o.name, u.first_name, u.last_name, u.phone FROM concert c JOIN organization o ON o.organization_id = c.organization_id JOIN app_user u ON u.user_id = @u WHERE c.concert_id = @c;
SET @o = LAST_INSERT_ID();
INSERT INTO ticket (order_id, zone_id, seat_id, qr_code, is_used, ticket_code, quantity, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot, created_at) SELECT @o, z.zone_id, NULL, SHA2(CONCAT(UUID(), @o, 1), 256), 0, CONCAT('TKT-', 'C0031', '-', @b, '-S1'), 1, z.name, NULL, z.price, '2026-10-06 21:13:00' FROM zone z WHERE z.concert_id = @c AND z.name = 'Front Stage';
INSERT INTO ticket (order_id, zone_id, seat_id, qr_code, is_used, ticket_code, quantity, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot, created_at) SELECT @o, z.zone_id, NULL, SHA2(CONCAT(UUID(), @o, 2), 256), 0, CONCAT('TKT-', 'C0031', '-', @b, '-S2'), 1, z.name, NULL, z.price, '2026-10-06 21:13:00' FROM zone z WHERE z.concert_id = @c AND z.name = 'Front Stage';

-- (7) mock.kritsada : C0029 VIP x2 = 9600 [CONFIRMED]
SET @u = (SELECT user_id FROM app_user WHERE email = 'mock.kritsada@example.com');
SET @c = (SELECT concert_id FROM concert WHERE concert_code = 'C0029');
INSERT INTO booking (user_id, concert_id, booking_date, total_amount, status, expiry_time, booking_code) VALUES (@u, @c, '2026-10-02 10:02:00', 9600, 'CONFIRMED', '2026-10-02 10:17:00', 'BKG-C0029-261002-0001');
SET @b = LAST_INSERT_ID();
INSERT INTO booking_seat (booking_id, seat_id, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot) SELECT @b, s.seat_id, z.name, CONCAT(s.`row`, s.`number`), z.price FROM seat s JOIN zone z ON z.zone_id = s.zone_id WHERE z.concert_id = @c AND z.name = 'VIP' AND (s.`row`, s.`number`) IN (('B',1),('B',2));
UPDATE seat s JOIN booking_seat bs ON bs.seat_id = s.seat_id SET s.seat_booking_status = 'SOLD' WHERE bs.booking_id = @b;
INSERT INTO payment (booking_id, amount, method, status, created_at, provider_transaction_id) VALUES (@b, 9600, 'CREDIT_CARD', 'COMPLETED', '2026-10-02 10:05:00', 'ch_mock_bkg_c0029_261002_0001');
SET @p = LAST_INSERT_ID();
INSERT INTO user_order (booking_id, payment_id, order_date, order_code, concert_name_snapshot, artist_snapshot, venue_snapshot, event_datetime_snapshot, organization_name_snapshot, user_fiestname_snapshot, user_lastname_snapshot, user_phone_snapshot) SELECT @b, @p, '2026-10-02 10:05:00', 'ORD-C0029-261002-0001', c.name, c.artist, c.venue, c.event_datetime, o.name, u.first_name, u.last_name, u.phone FROM concert c JOIN organization o ON o.organization_id = c.organization_id JOIN app_user u ON u.user_id = @u WHERE c.concert_id = @c;
SET @o = LAST_INSERT_ID();
INSERT INTO ticket (order_id, zone_id, seat_id, qr_code, is_used, ticket_code, quantity, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot, created_at) SELECT @o, s.zone_id, s.seat_id, SHA2(CONCAT(UUID(), s.seat_id), 256), 0, CONCAT('TKT-', 'C0029', '-', @b, '-', s.seat_id), 1, bs.zone_name_snapshot, bs.seat_label_snapshot, bs.unit_price_snapshot, '2026-10-02 10:05:00' FROM booking_seat bs JOIN seat s ON s.seat_id = bs.seat_id WHERE bs.booking_id = @b;

-- (8) mock.kritsada : C0037 Standing x4 = 6000 [CONFIRMED]
SET @u = (SELECT user_id FROM app_user WHERE email = 'mock.kritsada@example.com');
SET @c = (SELECT concert_id FROM concert WHERE concert_code = 'C0037');
INSERT INTO booking (user_id, concert_id, booking_date, total_amount, status, expiry_time, booking_code) VALUES (@u, @c, '2026-09-06 11:45:00', 6000, 'CONFIRMED', '2026-09-06 12:00:00', 'BKG-C0037-260906-0001');
SET @b = LAST_INSERT_ID();
INSERT INTO payment (booking_id, amount, method, status, created_at, provider_transaction_id) VALUES (@b, 6000, 'PROMPTPAY', 'COMPLETED', '2026-09-06 11:48:00', 'ch_mock_bkg_c0037_260906_0001');
SET @p = LAST_INSERT_ID();
INSERT INTO user_order (booking_id, payment_id, order_date, order_code, concert_name_snapshot, artist_snapshot, venue_snapshot, event_datetime_snapshot, organization_name_snapshot, user_fiestname_snapshot, user_lastname_snapshot, user_phone_snapshot) SELECT @b, @p, '2026-09-06 11:48:00', 'ORD-C0037-260906-0001', c.name, c.artist, c.venue, c.event_datetime, o.name, u.first_name, u.last_name, u.phone FROM concert c JOIN organization o ON o.organization_id = c.organization_id JOIN app_user u ON u.user_id = @u WHERE c.concert_id = @c;
SET @o = LAST_INSERT_ID();
INSERT INTO ticket (order_id, zone_id, seat_id, qr_code, is_used, ticket_code, quantity, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot, created_at) SELECT @o, z.zone_id, NULL, SHA2(CONCAT(UUID(), @o, 1), 256), 0, CONCAT('TKT-', 'C0037', '-', @b, '-S1'), 1, z.name, NULL, z.price, '2026-09-06 11:48:00' FROM zone z WHERE z.concert_id = @c AND z.name = 'Standing';
INSERT INTO ticket (order_id, zone_id, seat_id, qr_code, is_used, ticket_code, quantity, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot, created_at) SELECT @o, z.zone_id, NULL, SHA2(CONCAT(UUID(), @o, 2), 256), 0, CONCAT('TKT-', 'C0037', '-', @b, '-S2'), 1, z.name, NULL, z.price, '2026-09-06 11:48:00' FROM zone z WHERE z.concert_id = @c AND z.name = 'Standing';
INSERT INTO ticket (order_id, zone_id, seat_id, qr_code, is_used, ticket_code, quantity, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot, created_at) SELECT @o, z.zone_id, NULL, SHA2(CONCAT(UUID(), @o, 3), 256), 0, CONCAT('TKT-', 'C0037', '-', @b, '-S3'), 1, z.name, NULL, z.price, '2026-09-06 11:48:00' FROM zone z WHERE z.concert_id = @c AND z.name = 'Standing';
INSERT INTO ticket (order_id, zone_id, seat_id, qr_code, is_used, ticket_code, quantity, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot, created_at) SELECT @o, z.zone_id, NULL, SHA2(CONCAT(UUID(), @o, 4), 256), 0, CONCAT('TKT-', 'C0037', '-', @b, '-S4'), 1, z.name, NULL, z.price, '2026-09-06 11:48:00' FROM zone z WHERE z.concert_id = @c AND z.name = 'Standing';

-- (9) mock.kritsada : C0028 Orchestra x2 = 4000 [PENDING_PAYMENT]
SET @u = (SELECT user_id FROM app_user WHERE email = 'mock.kritsada@example.com');
SET @c = (SELECT concert_id FROM concert WHERE concert_code = 'C0028');
INSERT INTO booking (user_id, concert_id, booking_date, total_amount, status, expiry_time, booking_code) VALUES (@u, @c, NOW(), 4000, 'PENDING_PAYMENT', DATE_ADD(NOW(), INTERVAL 15 MINUTE), 'BKG-C0028-261010-0001');
SET @b = LAST_INSERT_ID();
INSERT INTO booking_seat (booking_id, seat_id, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot) SELECT @b, s.seat_id, z.name, CONCAT(s.`row`, s.`number`), z.price FROM seat s JOIN zone z ON z.zone_id = s.zone_id WHERE z.concert_id = @c AND z.name = 'Orchestra' AND (s.`row`, s.`number`) IN (('C',5),('C',6));
UPDATE seat s JOIN booking_seat bs ON bs.seat_id = s.seat_id SET s.seat_booking_status = 'LOCKED' WHERE bs.booking_id = @b;
INSERT INTO payment (booking_id, amount, method, status, created_at, provider_transaction_id) VALUES (@b, 4000, 'CREDIT_CARD', 'PENDING', NOW(), NULL);

-- (10) mock.nattaya : C0026 Zone A x4 = 12000 [CONFIRMED]
SET @u = (SELECT user_id FROM app_user WHERE email = 'mock.nattaya@example.com');
SET @c = (SELECT concert_id FROM concert WHERE concert_code = 'C0026');
INSERT INTO booking (user_id, concert_id, booking_date, total_amount, status, expiry_time, booking_code) VALUES (@u, @c, '2026-09-21 13:25:00', 12000, 'CONFIRMED', '2026-09-21 13:40:00', 'BKG-C0026-260921-0001');
SET @b = LAST_INSERT_ID();
INSERT INTO booking_seat (booking_id, seat_id, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot) SELECT @b, s.seat_id, z.name, CONCAT(s.`row`, s.`number`), z.price FROM seat s JOIN zone z ON z.zone_id = s.zone_id WHERE z.concert_id = @c AND z.name = 'Zone A' AND (s.`row`, s.`number`) IN (('D',1),('D',2),('D',3),('D',4));
UPDATE seat s JOIN booking_seat bs ON bs.seat_id = s.seat_id SET s.seat_booking_status = 'SOLD' WHERE bs.booking_id = @b;
INSERT INTO payment (booking_id, amount, method, status, created_at, provider_transaction_id) VALUES (@b, 12000, 'CREDIT_CARD', 'COMPLETED', '2026-09-21 13:28:00', 'ch_mock_bkg_c0026_260921_0001');
SET @p = LAST_INSERT_ID();
INSERT INTO user_order (booking_id, payment_id, order_date, order_code, concert_name_snapshot, artist_snapshot, venue_snapshot, event_datetime_snapshot, organization_name_snapshot, user_fiestname_snapshot, user_lastname_snapshot, user_phone_snapshot) SELECT @b, @p, '2026-09-21 13:28:00', 'ORD-C0026-260921-0001', c.name, c.artist, c.venue, c.event_datetime, o.name, u.first_name, u.last_name, u.phone FROM concert c JOIN organization o ON o.organization_id = c.organization_id JOIN app_user u ON u.user_id = @u WHERE c.concert_id = @c;
SET @o = LAST_INSERT_ID();
INSERT INTO ticket (order_id, zone_id, seat_id, qr_code, is_used, ticket_code, quantity, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot, created_at) SELECT @o, s.zone_id, s.seat_id, SHA2(CONCAT(UUID(), s.seat_id), 256), 0, CONCAT('TKT-', 'C0026', '-', @b, '-', s.seat_id), 1, bs.zone_name_snapshot, bs.seat_label_snapshot, bs.unit_price_snapshot, '2026-09-21 13:28:00' FROM booking_seat bs JOIN seat s ON s.seat_id = bs.seat_id WHERE bs.booking_id = @b;

-- (11) mock.nattaya : C0038 Beach VIP x2 = 6000 [CONFIRMED]
SET @u = (SELECT user_id FROM app_user WHERE email = 'mock.nattaya@example.com');
SET @c = (SELECT concert_id FROM concert WHERE concert_code = 'C0038');
INSERT INTO booking (user_id, concert_id, booking_date, total_amount, status, expiry_time, booking_code) VALUES (@u, @c, '2026-09-12 16:50:00', 6000, 'CONFIRMED', '2026-09-12 17:05:00', 'BKG-C0038-260912-0001');
SET @b = LAST_INSERT_ID();
INSERT INTO booking_seat (booking_id, seat_id, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot) SELECT @b, s.seat_id, z.name, CONCAT(s.`row`, s.`number`), z.price FROM seat s JOIN zone z ON z.zone_id = s.zone_id WHERE z.concert_id = @c AND z.name = 'Beach VIP' AND (s.`row`, s.`number`) IN (('C',1),('C',2));
UPDATE seat s JOIN booking_seat bs ON bs.seat_id = s.seat_id SET s.seat_booking_status = 'SOLD' WHERE bs.booking_id = @b;
INSERT INTO payment (booking_id, amount, method, status, created_at, provider_transaction_id) VALUES (@b, 6000, 'PROMPTPAY', 'COMPLETED', '2026-09-12 16:53:00', 'ch_mock_bkg_c0038_260912_0001');
SET @p = LAST_INSERT_ID();
INSERT INTO user_order (booking_id, payment_id, order_date, order_code, concert_name_snapshot, artist_snapshot, venue_snapshot, event_datetime_snapshot, organization_name_snapshot, user_fiestname_snapshot, user_lastname_snapshot, user_phone_snapshot) SELECT @b, @p, '2026-09-12 16:53:00', 'ORD-C0038-260912-0001', c.name, c.artist, c.venue, c.event_datetime, o.name, u.first_name, u.last_name, u.phone FROM concert c JOIN organization o ON o.organization_id = c.organization_id JOIN app_user u ON u.user_id = @u WHERE c.concert_id = @c;
SET @o = LAST_INSERT_ID();
INSERT INTO ticket (order_id, zone_id, seat_id, qr_code, is_used, ticket_code, quantity, zone_name_snapshot, seat_label_snapshot, unit_price_snapshot, created_at) SELECT @o, s.zone_id, s.seat_id, SHA2(CONCAT(UUID(), s.seat_id), 256), 0, CONCAT('TKT-', 'C0038', '-', @b, '-', s.seat_id), 1, bs.zone_name_snapshot, bs.seat_label_snapshot, bs.unit_price_snapshot, '2026-09-12 16:53:00' FROM booking_seat bs JOIN seat s ON s.seat_id = bs.seat_id WHERE bs.booking_id = @b;

-- (12) mock.nattaya : C0027 Standing B x2 = 2400 [CANCELLED]
SET @u = (SELECT user_id FROM app_user WHERE email = 'mock.nattaya@example.com');
SET @c = (SELECT concert_id FROM concert WHERE concert_code = 'C0027');
INSERT INTO booking (user_id, concert_id, booking_date, total_amount, status, expiry_time, booking_code) VALUES (@u, @c, '2026-09-30 20:15:00', 2400, 'CANCELLED', '2026-09-30 20:30:00', 'BKG-C0027-260930-0001');
SET @b = LAST_INSERT_ID();
INSERT INTO payment (booking_id, amount, method, status, created_at, provider_transaction_id) VALUES (@b, 2400, 'CREDIT_CARD', 'FAILED', '2026-09-30 20:18:00', NULL);

-- (13) mock.tanawat : C0031 General x2 = 3000 [CANCELLED]
SET @u = (SELECT user_id FROM app_user WHERE email = 'mock.tanawat@example.com');
SET @c = (SELECT concert_id FROM concert WHERE concert_code = 'C0031');
INSERT INTO booking (user_id, concert_id, booking_date, total_amount, status, expiry_time, booking_code) VALUES (@u, @c, '2026-10-07 18:05:00', 3000, 'CANCELLED', '2026-10-07 18:20:00', 'BKG-C0031-261007-0001');
SET @b = LAST_INSERT_ID();
INSERT INTO payment (booking_id, amount, method, status, created_at, provider_transaction_id) VALUES (@b, 3000, 'PROMPTPAY', 'FAILED', '2026-10-07 18:08:00', NULL);

-- (14) mock.tanawat : C0029 Standing x1 = 2200 [PENDING_PAYMENT]
SET @u = (SELECT user_id FROM app_user WHERE email = 'mock.tanawat@example.com');
SET @c = (SELECT concert_id FROM concert WHERE concert_code = 'C0029');
INSERT INTO booking (user_id, concert_id, booking_date, total_amount, status, expiry_time, booking_code) VALUES (@u, @c, NOW(), 2200, 'PENDING_PAYMENT', DATE_ADD(NOW(), INTERVAL 15 MINUTE), 'BKG-C0029-261010-0001');
SET @b = LAST_INSERT_ID();
INSERT INTO payment (booking_id, amount, method, status, created_at, provider_transaction_id) VALUES (@b, 2200, 'CREDIT_CARD', 'PENDING', NOW(), NULL);

-- ตรวจผล: สรุปต่อผู้ใช้
SELECT u.email, COUNT(DISTINCT b.booking_id) AS bookings,
       COUNT(DISTINCT o.order_id) AS orders, COUNT(t.ticket_id) AS tickets
FROM app_user u
LEFT JOIN booking b ON b.user_id = u.user_id
LEFT JOIN user_order o ON o.booking_id = b.booking_id
LEFT JOIN ticket t ON t.order_id = o.order_id
WHERE u.email LIKE 'mock.%@example.com' GROUP BY u.user_id ORDER BY u.email;

SET SQL_SAFE_UPDATES = 1;
