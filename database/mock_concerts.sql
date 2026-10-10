-- =====================================================================
-- Mock data: 13 คอนเสิร์ต (C0026 - C0038) พร้อมโซนนั่ง/โซนยืน และที่นั่ง
-- รันใน MySQL Workbench กับ schema ที่ใช้อยู่ (ใน docker คือ concert_db)
-- ต้องมี organization ที่ initials = 'GMM' และ 'BEC' อยู่แล้ว (มาจาก DatabaseSeeder)
-- รันซ้ำได้: ส่วนแรกจะลบของเดิม C0026-C0038 ก่อนแล้วสร้างใหม่
-- =====================================================================
USE concert_db;

-- ปิด Safe Update Mode ชั่วคราว (Workbench บล็อก DELETE/UPDATE ที่ไม่มี WHERE บน key)
SET SQL_SAFE_UPDATES = 0;

SET FOREIGN_KEY_CHECKS = 0;
DELETE s FROM seat s JOIN zone z ON z.zone_id = s.zone_id JOIN concert c ON c.concert_id = z.concert_id
 WHERE c.concert_code BETWEEN 'C0026' AND 'C0038';
DELETE z FROM zone z JOIN concert c ON c.concert_id = z.concert_id
 WHERE c.concert_code BETWEEN 'C0026' AND 'C0038';
DELETE FROM concert WHERE concert_code BETWEEN 'C0026' AND 'C0038';
SET FOREIGN_KEY_CHECKS = 1;

-- ตารางตัวเลข 1-20 ไว้สร้างที่นั่ง (แถว A.. / เลขที่นั่ง 1..)
DROP TABLE IF EXISTS tmp_nums;
CREATE TABLE tmp_nums (n INT PRIMARY KEY);
INSERT INTO tmp_nums (n) VALUES (1),(2),(3),(4),(5),(6),(7),(8),(9),(10),(11),(12),(13),(14),(15),(16),(17),(18),(19),(20);


-- ---------- C0026 : Neon Nights Live (ON_SALE) ----------
INSERT INTO concert (organization_id, name, artist, venue, address, event_datetime, description, poster_path, banner_path, seat_map_image_path, sale_start_datetime, sale_end_datetime, max_ticket_per_order, status, concert_code, is_deleted)
VALUES ((SELECT organization_id FROM organization WHERE initials = 'BEC' LIMIT 1), 'Neon Nights Live', 'Luna Park', 'Impact Arena, Muang Thong Thani', 'เมืองทองธานี นนทบุรี', '2026-11-08 19:00:00', 'คอนเสิร์ตป๊อปสุดอลังการพร้อมแสงสีเต็มเวที โชว์ครั้งแรกของ Luna Park ในรอบ 3 ปี', 'posters/neon-nights-live.jpg', 'banners/neon-nights-live.jpg', 'seat-maps/neon-nights-live.png', '2026-09-20 10:00:00', '2026-11-07 23:59:00', 6, 'ON_SALE', 'C0026', 0);
SET @c = LAST_INSERT_ID();
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'VIP', 'SEATED', 4500, 30, 3, 10, 1, '#F59E0B');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 3 AND s.n <= 10;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Zone A', 'SEATED', 3000, 48, 4, 12, 1, '#3B82F6');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 4 AND s.n <= 12;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Zone B', 'SEATED', 2000, 70, 5, 14, 1, '#8B5CF6');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 5 AND s.n <= 14;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, seat_start_number, color) VALUES (@c, 'Standing', 'STANDING', 1500, 800, 1, '#10B981');

-- ---------- C0027 : Rock the Stadium (ON_SALE) ----------
INSERT INTO concert (organization_id, name, artist, venue, address, event_datetime, description, poster_path, banner_path, seat_map_image_path, sale_start_datetime, sale_end_datetime, max_ticket_per_order, status, concert_code, is_deleted)
VALUES ((SELECT organization_id FROM organization WHERE initials = 'GMM' LIMIT 1), 'Rock the Stadium', 'Iron Valley', 'Rajamangala National Stadium', 'หัวหมาก บางกะปิ กรุงเทพฯ', '2026-11-22 18:00:00', 'มหกรรมร็อกกลางสนามกีฬา ปล่อยเพลงฮิตครบทุกยุคของ Iron Valley', 'posters/rock-the-stadium.jpg', 'banners/rock-the-stadium.jpg', 'seat-maps/rock-the-stadium.png', '2026-09-25 10:00:00', '2026-11-21 23:59:00', 8, 'ON_SALE', 'C0027', 0);
SET @c = LAST_INSERT_ID();
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Platinum', 'SEATED', 5000, 30, 3, 10, 1, '#F59E0B');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 3 AND s.n <= 10;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Gold', 'SEATED', 3500, 48, 4, 12, 1, '#EAB308');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 4 AND s.n <= 12;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Silver', 'SEATED', 2200, 70, 5, 14, 1, '#3B82F6');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 5 AND s.n <= 14;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, seat_start_number, color) VALUES (@c, 'Standing A', 'STANDING', 1800, 1500, 1, '#EF4444');
INSERT INTO zone (concert_id, name, zone_type, price, capacity, seat_start_number, color) VALUES (@c, 'Standing B', 'STANDING', 1200, 3000, 1, '#10B981');

-- ---------- C0028 : Acoustic Evening (ON_SALE) ----------
INSERT INTO concert (organization_id, name, artist, venue, address, event_datetime, description, poster_path, banner_path, seat_map_image_path, sale_start_datetime, sale_end_datetime, max_ticket_per_order, status, concert_code, is_deleted)
VALUES ((SELECT organization_id FROM organization WHERE initials = 'GMM' LIMIT 1), 'Acoustic Evening', 'Sunset Strings', 'Thailand Cultural Centre', 'ห้วยขวาง กรุงเทพฯ', '2026-11-15 19:30:00', 'ค่ำคืนอะคูสติกเบา ๆ ฟังสบาย ๆ กับเสียงกีตาร์และเสียงร้องใกล้ชิด', 'posters/acoustic-evening.jpg', 'banners/acoustic-evening.jpg', 'seat-maps/acoustic-evening.png', '2026-09-28 10:00:00', '2026-11-14 23:59:00', 4, 'ON_SALE', 'C0028', 0);
SET @c = LAST_INSERT_ID();
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Front Row', 'SEATED', 3000, 20, 2, 10, 1, '#F59E0B');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 2 AND s.n <= 10;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Orchestra', 'SEATED', 2000, 60, 5, 12, 1, '#3B82F6');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 5 AND s.n <= 12;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Balcony', 'SEATED', 1200, 56, 4, 14, 1, '#8B5CF6');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 4 AND s.n <= 14;

-- ---------- C0029 : K-Wave Fan Fest (ON_SALE) ----------
INSERT INTO concert (organization_id, name, artist, venue, address, event_datetime, description, poster_path, banner_path, seat_map_image_path, sale_start_datetime, sale_end_datetime, max_ticket_per_order, status, concert_code, is_deleted)
VALUES ((SELECT organization_id FROM organization WHERE initials = 'BEC' LIMIT 1), 'K-Wave Fan Fest', 'STELLAR 7', 'Thunder Dome, Muang Thong Thani', 'เมืองทองธานี นนทบุรี', '2026-12-06 17:00:00', 'แฟนมีตติ้งและคอนเสิร์ตเต็มรูปแบบของไอดอลกลุ่มใหม่มาแรง พร้อมกิจกรรมสุดพิเศษ', 'posters/k-wave-fan-fest.jpg', 'banners/k-wave-fan-fest.jpg', 'seat-maps/k-wave-fan-fest.png', '2026-10-01 10:00:00', '2026-12-05 23:59:00', 4, 'ON_SALE', 'C0029', 0);
SET @c = LAST_INSERT_ID();
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'VVIP', 'SEATED', 6500, 20, 2, 10, 1, '#EF4444');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 2 AND s.n <= 10;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'VIP', 'SEATED', 4800, 36, 3, 12, 1, '#F59E0B');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 3 AND s.n <= 12;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Zone A', 'SEATED', 3200, 70, 5, 14, 1, '#3B82F6');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 5 AND s.n <= 14;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, seat_start_number, color) VALUES (@c, 'Standing', 'STANDING', 2200, 1200, 1, '#10B981');

-- ---------- C0030 : Jazz Under the Stars (ON_SALE) ----------
INSERT INTO concert (organization_id, name, artist, venue, address, event_datetime, description, poster_path, banner_path, seat_map_image_path, sale_start_datetime, sale_end_datetime, max_ticket_per_order, status, concert_code, is_deleted)
VALUES ((SELECT organization_id FROM organization WHERE initials = 'GMM' LIMIT 1), 'Jazz Under the Stars', 'Blue Monsoon Quartet', 'Lumpini Park Amphitheatre', 'ปทุมวัน กรุงเทพฯ', '2026-11-29 18:30:00', 'แจ๊สกลางแจ้งใต้แสงดาว นั่งโต๊ะหรือปูเสื่อบนสนามหญ้าก็ได้', 'posters/jazz-under-the-stars.jpg', 'banners/jazz-under-the-stars.jpg', 'seat-maps/jazz-under-the-stars.png', '2026-10-02 10:00:00', '2026-11-28 23:59:00', 6, 'ON_SALE', 'C0030', 0);
SET @c = LAST_INSERT_ID();
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Table Seat', 'SEATED', 2500, 24, 3, 8, 1, '#F59E0B');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 3 AND s.n <= 8;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, seat_start_number, color) VALUES (@c, 'Lawn', 'STANDING', 900, 600, 1, '#10B981');

-- ---------- C0031 : EDM Countdown (ON_SALE) ----------
INSERT INTO concert (organization_id, name, artist, venue, address, event_datetime, description, poster_path, banner_path, seat_map_image_path, sale_start_datetime, sale_end_datetime, max_ticket_per_order, status, concert_code, is_deleted)
VALUES ((SELECT organization_id FROM organization WHERE initials = 'BEC' LIMIT 1), 'EDM Countdown', 'DJ Kairos', 'Show DC Hall', 'พระราม 9 กรุงเทพฯ', '2026-12-31 20:00:00', 'ปาร์ตี้เคาท์ดาวน์ส่งท้ายปี เสียงเบสหนักแน่นตลอดคืน', 'posters/edm-countdown.jpg', 'banners/edm-countdown.jpg', 'seat-maps/edm-countdown.png', '2026-10-05 10:00:00', '2026-12-30 23:59:00', 6, 'ON_SALE', 'C0031', 0);
SET @c = LAST_INSERT_ID();
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'VIP Deck', 'SEATED', 3500, 24, 2, 12, 1, '#F59E0B');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 2 AND s.n <= 12;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, seat_start_number, color) VALUES (@c, 'Front Stage', 'STANDING', 2500, 400, 1, '#EF4444');
INSERT INTO zone (concert_id, name, zone_type, price, capacity, seat_start_number, color) VALUES (@c, 'General', 'STANDING', 1500, 2000, 1, '#10B981');

-- ---------- C0032 : Indie Garden Fest (SCHEDULED) ----------
INSERT INTO concert (organization_id, name, artist, venue, address, event_datetime, description, poster_path, banner_path, seat_map_image_path, sale_start_datetime, sale_end_datetime, max_ticket_per_order, status, concert_code, is_deleted)
VALUES ((SELECT organization_id FROM organization WHERE initials = 'GMM' LIMIT 1), 'Indie Garden Fest', 'The Paper Kites TH', 'Siam Pavilion', 'ปทุมวัน กรุงเทพฯ', '2027-01-17 16:00:00', 'เทศกาลดนตรีอินดี้บรรยากาศสวน รวมวงน้องใหม่และศิลปินคุณภาพ', 'posters/indie-garden-fest.jpg', 'banners/indie-garden-fest.jpg', 'seat-maps/indie-garden-fest.png', '2026-11-01 10:00:00', '2027-01-16 23:59:00', 6, 'SCHEDULED', 'C0032', 0);
SET @c = LAST_INSERT_ID();
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Garden Seat', 'SEATED', 1800, 48, 4, 12, 1, '#3B82F6');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 4 AND s.n <= 12;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, seat_start_number, color) VALUES (@c, 'Standing', 'STANDING', 1000, 1000, 1, '#10B981');

-- ---------- C0033 : Luk Thung Grand Concert (SCHEDULED) ----------
INSERT INTO concert (organization_id, name, artist, venue, address, event_datetime, description, poster_path, banner_path, seat_map_image_path, sale_start_datetime, sale_end_datetime, max_ticket_per_order, status, concert_code, is_deleted)
VALUES ((SELECT organization_id FROM organization WHERE initials = 'GMM' LIMIT 1), 'Luk Thung Grand Concert', 'Pimpa Rungsri', 'Muang Thong Thani Hall', 'เมืองทองธานี นนทบุรี', '2027-01-30 18:00:00', 'คอนเสิร์ตลูกทุ่งยิ่งใหญ่ พร้อมวงดนตรีสดและหางเครื่องเต็มเวที', 'posters/luk-thung-grand-concert.jpg', 'banners/luk-thung-grand-concert.jpg', 'seat-maps/luk-thung-grand-concert.png', '2026-11-10 10:00:00', '2027-01-29 23:59:00', 8, 'SCHEDULED', 'C0033', 0);
SET @c = LAST_INSERT_ID();
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'VIP', 'SEATED', 2800, 36, 3, 12, 1, '#F59E0B');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 3 AND s.n <= 12;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Zone A', 'SEATED', 1800, 56, 4, 14, 1, '#3B82F6');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 4 AND s.n <= 14;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Zone B', 'SEATED', 1200, 80, 5, 16, 1, '#8B5CF6');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 5 AND s.n <= 16;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Zone C', 'SEATED', 800, 96, 6, 16, 1, '#10B981');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 6 AND s.n <= 16;

-- ---------- C0034 : Hip-Hop Takeover (SCHEDULED) ----------
INSERT INTO concert (organization_id, name, artist, venue, address, event_datetime, description, poster_path, banner_path, seat_map_image_path, sale_start_datetime, sale_end_datetime, max_ticket_per_order, status, concert_code, is_deleted)
VALUES ((SELECT organization_id FROM organization WHERE initials = 'BEC' LIMIT 1), 'Hip-Hop Takeover', 'Cipher Squad', 'Union Hall', 'บางกะปิ กรุงเทพฯ', '2027-02-14 19:00:00', 'ศึกแร็ปสุดมันส์ รวมศิลปินฮิปฮอปแถวหน้ามาปะทะกันบนเวทีเดียว', 'posters/hip-hop-takeover.jpg', 'banners/hip-hop-takeover.jpg', 'seat-maps/hip-hop-takeover.png', '2026-11-20 10:00:00', '2027-02-13 23:59:00', 4, 'SCHEDULED', 'C0034', 0);
SET @c = LAST_INSERT_ID();
INSERT INTO zone (concert_id, name, zone_type, price, capacity, seat_start_number, color) VALUES (@c, 'Pit', 'STANDING', 2000, 300, 1, '#EF4444');
INSERT INTO zone (concert_id, name, zone_type, price, capacity, seat_start_number, color) VALUES (@c, 'Standing', 'STANDING', 1300, 1200, 1, '#10B981');
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Balcony', 'SEATED', 1600, 36, 3, 12, 1, '#3B82F6');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 3 AND s.n <= 12;

-- ---------- C0035 : Symphony Pop Night (SCHEDULED) ----------
INSERT INTO concert (organization_id, name, artist, venue, address, event_datetime, description, poster_path, banner_path, seat_map_image_path, sale_start_datetime, sale_end_datetime, max_ticket_per_order, status, concert_code, is_deleted)
VALUES ((SELECT organization_id FROM organization WHERE initials = 'BEC' LIMIT 1), 'Symphony Pop Night', 'Bangkok Pops Orchestra', 'Royal Paragon Hall', 'สยาม กรุงเทพฯ', '2027-03-06 19:00:00', 'เพลงป๊อปยอดนิยมเรียบเรียงใหม่ด้วยวงออร์เคสตราเต็มวง', 'posters/symphony-pop-night.jpg', 'banners/symphony-pop-night.jpg', 'seat-maps/symphony-pop-night.png', '2026-12-01 10:00:00', '2027-03-05 23:59:00', 6, 'SCHEDULED', 'C0035', 0);
SET @c = LAST_INSERT_ID();
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Grand Circle', 'SEATED', 4000, 30, 3, 10, 1, '#F59E0B');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 3 AND s.n <= 10;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Stalls', 'SEATED', 2800, 70, 5, 14, 1, '#3B82F6');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 5 AND s.n <= 14;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Upper Circle', 'SEATED', 1500, 64, 4, 16, 1, '#8B5CF6');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 4 AND s.n <= 16;

-- ---------- C0036 : Midnight Soul (SOLD_OUT) ----------
INSERT INTO concert (organization_id, name, artist, venue, address, event_datetime, description, poster_path, banner_path, seat_map_image_path, sale_start_datetime, sale_end_datetime, max_ticket_per_order, status, concert_code, is_deleted)
VALUES ((SELECT organization_id FROM organization WHERE initials = 'GMM' LIMIT 1), 'Midnight Soul', 'Nara Velvet', 'Moonstar Studio', 'ทองหล่อ กรุงเทพฯ', '2026-10-24 20:00:00', 'โซลและอาร์แอนด์บีบรรยากาศอบอุ่นในห้องเล็กสุดพิเศษ บัตรหมดเกลี้ยง', 'posters/midnight-soul.jpg', 'banners/midnight-soul.jpg', 'seat-maps/midnight-soul.png', '2026-09-01 10:00:00', '2026-10-23 23:59:00', 4, 'SOLD_OUT', 'C0036', 0);
SET @c = LAST_INSERT_ID();
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Table', 'SEATED', 2800, 24, 3, 8, 1, '#F59E0B');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'SOLD', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 3 AND s.n <= 8;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, seat_start_number, color) VALUES (@c, 'Standing', 'STANDING', 1400, 250, 1, '#10B981');

-- ---------- C0037 : Throwback 90s Reunion (SOLD_OUT) ----------
INSERT INTO concert (organization_id, name, artist, venue, address, event_datetime, description, poster_path, banner_path, seat_map_image_path, sale_start_datetime, sale_end_datetime, max_ticket_per_order, status, concert_code, is_deleted)
VALUES ((SELECT organization_id FROM organization WHERE initials = 'BEC' LIMIT 1), 'Throwback 90s Reunion', 'The Retro Boys', 'Centralworld Live', 'ราชประสงค์ กรุงเทพฯ', '2026-10-31 19:00:00', 'รียูเนียนวงบอยแบนด์ในตำนานแห่งยุค 90 บัตรขายหมดภายในไม่กี่ชั่วโมง', 'posters/throwback-90s-reunion.jpg', 'banners/throwback-90s-reunion.jpg', 'seat-maps/throwback-90s-reunion.png', '2026-09-05 10:00:00', '2026-10-30 23:59:00', 6, 'SOLD_OUT', 'C0037', 0);
SET @c = LAST_INSERT_ID();
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'VIP', 'SEATED', 3600, 30, 3, 10, 1, '#F59E0B');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'SOLD', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 3 AND s.n <= 10;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Zone A', 'SEATED', 2400, 48, 4, 12, 1, '#3B82F6');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'SOLD', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 4 AND s.n <= 12;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, seat_start_number, color) VALUES (@c, 'Standing', 'STANDING', 1500, 500, 1, '#10B981');

-- ---------- C0038 : Summer Splash 2026 (ON_SALE) ----------
INSERT INTO concert (organization_id, name, artist, venue, address, event_datetime, description, poster_path, banner_path, seat_map_image_path, sale_start_datetime, sale_end_datetime, max_ticket_per_order, status, concert_code, is_deleted)
VALUES ((SELECT organization_id FROM organization WHERE initials = 'GMM' LIMIT 1), 'Summer Splash 2026', 'Coral Waves', 'Bangsaen Beach Stage', 'บางแสน ชลบุรี', '2026-10-17 17:00:00', 'เทศกาลดนตรีริมหาด ปิดท้ายฤดูร้อนด้วยศิลปินหลายวง', 'posters/summer-splash-2026.jpg', 'banners/summer-splash-2026.jpg', 'seat-maps/summer-splash-2026.png', '2026-09-10 10:00:00', '2026-10-16 23:59:00', 6, 'ON_SALE', 'C0038', 0);
SET @c = LAST_INSERT_ID();
INSERT INTO zone (concert_id, name, zone_type, price, capacity, total_rows, seats_per_row, seat_start_number, color) VALUES (@c, 'Beach VIP', 'SEATED', 3000, 30, 3, 10, 1, '#F59E0B');
SET @z = LAST_INSERT_ID();
INSERT INTO seat (zone_id, `row`, `number`, seat_booking_status, seat_status) SELECT @z, CHAR(64 + r.n), s.n, 'AVAILABLE', 'NORMAL' FROM tmp_nums r JOIN tmp_nums s WHERE r.n <= 3 AND s.n <= 10;
INSERT INTO zone (concert_id, name, zone_type, price, capacity, seat_start_number, color) VALUES (@c, 'Standing Front', 'STANDING', 1600, 500, 1, '#EF4444');
INSERT INTO zone (concert_id, name, zone_type, price, capacity, seat_start_number, color) VALUES (@c, 'Standing Back', 'STANDING', 900, 1500, 1, '#10B981');

DROP TABLE IF EXISTS tmp_nums;

-- ตรวจผล
SELECT c.concert_code, c.name, c.status, COUNT(z.zone_id) AS zones, SUM(z.zone_type='SEATED') AS seated, SUM(z.zone_type='STANDING') AS standing
FROM concert c LEFT JOIN zone z ON z.concert_id = c.concert_id
WHERE c.concert_code BETWEEN 'C0026' AND 'C0038' GROUP BY c.concert_id ORDER BY c.concert_code;

SET SQL_SAFE_UPDATES = 1;
