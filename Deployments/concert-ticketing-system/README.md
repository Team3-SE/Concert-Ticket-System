# Concert Ticketing System

ระบบจองตั๋วคอนเสิร์ต — Team 3, Software Engineering, Burapha University

**Stack:** Laravel (PHP 8.2) · MySQL 8 · Redis 7 · NGINX · Docker

---

## สำหรับเพื่อนที่เพิ่งโคลน repo มา — ทำตามขั้นตอนนี้

### สิ่งที่ต้องติดตั้งก่อน

| ซอฟต์แวร์ | เวอร์ชันแนะนำ | ลิงก์ |
|---|---|---|
| Docker Desktop | 4.x ขึ้นไป | https://www.docker.com/products/docker-desktop |
| Git | ล่าสุด | https://git-scm.com |

> ไม่ต้องติดตั้ง PHP, Composer, MySQL, Redis บนเครื่อง — Docker จัดการให้ทั้งหมด

---

### ขั้นตอน Setup (ทำครั้งแรกครั้งเดียว)

**1. Clone repo (ถ้ายังไม่ได้ clone)**
```bash
git clone <repo-url>
cd concert-ticketing-system
```

**2. Copy ไฟล์ environment**
```bash
cp .env.example .env
```

**3. รัน Docker**
```bash
docker-compose up -d
```
> รอซักครู่ให้ MySQL และ Redis ขึ้นก่อน (healthcheck จะดูแลให้อัตโนมัติ)

**4. ติดตั้ง PHP dependencies**
```bash
docker exec concert_app composer install
```

**5. Generate App Key**
```bash
docker exec concert_app php artisan key:generate
```

**6. Run Migrations + Seeders**
```bash
docker exec concert_app php artisan migrate --seed
```

**7. Fix storage permissions**
```bash
docker exec concert_app php artisan storage:link
docker exec concert_app chmod -R 775 storage bootstrap/cache
```

**เสร็จแล้ว!** เปิดเบราว์เซอร์ไปที่ http://localhost:8080

---

### คำสั่งที่ใช้บ่อย

| สิ่งที่อยากทำ | คำสั่ง |
|---|---|
| เริ่ม containers | `docker-compose up -d` |
| หยุด containers | `docker-compose down` |
| ดู logs ทั้งหมด | `docker-compose logs -f` |
| ดู logs เฉพาะ app | `docker-compose logs -f app` |
| รัน artisan command | `docker exec concert_app php artisan <command>` |
| รัน migration ใหม่ | `docker exec concert_app php artisan migrate` |
| รัน Queue worker | `docker exec concert_app php artisan queue:work --queue=default` |
| เข้า shell container | `docker exec -it concert_app bash` |
| เข้า MySQL | `docker exec -it concert_db mysql -u concert_user -p concert_db` |
| เข้า Redis CLI | `docker exec -it concert_redis redis-cli` |
| Rebuild image (หลังแก้ Dockerfile) | `docker-compose up -d --build` |

---

### Port ที่ใช้งาน

| Service | Port (host) |
|---|---|
| Web (NGINX) | http://localhost:**8080** |
| MySQL | localhost:**3306** |
| Redis | localhost:**6379** |

> ถ้า port ชนกับของในเครื่อง แก้ได้ใน `.env` ที่ตัวแปร `NGINX_PORT`, `DB_PORT`, `REDIS_PORT`

---

### โครงสร้าง Docker

```
docker/
├── nginx/
│   └── default.conf    ← NGINX config (reverse proxy ไป PHP-FPM)
├── php/
│   ├── Dockerfile       ← PHP 8.2-FPM + extensions ที่ Laravel ต้องการ
│   └── php.ini          ← PHP settings (memory, upload size, opcache)
└── redis/
    └── redis.conf       ← Redis settings (AOF persistence, max memory)
```

---

### การแก้ปัญหาเบื้องต้น

**Q: `docker-compose up` แล้ว error port already in use**
```bash
# หา process ที่ใช้ port นั้น แล้วปิด หรือแก้ port ใน .env
```

**Q: `composer install` ช้ามาก**
> ปกติในครั้งแรก ครั้งถัดไปจะเร็วขึ้นเพราะ cache

**Q: `php artisan migrate` error ต่อ DB ไม่ได้**
```bash
# รอให้ container db healthy ก่อน (ประมาณ 30 วิหลัง docker-compose up)
docker-compose ps   # ดูว่า db status เป็น healthy หรือยัง
```

**Q: แก้โค้ดแล้วไม่เห็นการเปลี่ยนแปลง**
```bash
docker exec concert_app php artisan config:clear
docker exec concert_app php artisan cache:clear
docker exec concert_app php artisan view:clear
```



> ❌ ห้าม commit `.env` เด็ดขาด — `.env` อยู่ใน `.gitignore` แล้ว


