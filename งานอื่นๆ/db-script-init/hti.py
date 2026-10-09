
import argparse
import re
import sys
from pathlib import Path

HEADER = """-- MySQL Script: concert_ticketing schema v3.3.0
-- Base: Workbench model + restored manual changes from v3.2.0
"""

# (ชื่อ patch, ข้อความเดิม, ข้อความใหม่)
PATCHES = [
    (
        "app_user: rename phone unique index",
        "UNIQUE INDEX `app_usercol_UNIQUE` (`u_phone` ASC) VISIBLE,",
        "UNIQUE INDEX `u_phone_UNIQUE` (`u_phone` ASC) VISIBLE,",
    ),
    (
        "booking: idx_booking_expiry",
        "  UNIQUE INDEX `b_booking_code_UNIQUE` (`b_booking_code` ASC) VISIBLE,\n",
        "  UNIQUE INDEX `b_booking_code_UNIQUE` (`b_booking_code` ASC) VISIBLE,\n"
        "  INDEX `idx_booking_expiry` (`b_status` ASC, `b_expiry_time` ASC) VISIBLE, -- [เพิ่ม] สำหรับ Background job\n",
    ),
    (
        "audit_log: composite indexes",
        "  INDEX `fk_audit_user` (`al_user_id` ASC) VISIBLE,\n",
        "  -- [แก้] แทน fk_audit_user เดิม: คอมโพสิตนี้ขึ้นต้นด้วย al_user_id จึงใช้เป็น index ของ FK ได้\n"
        "  INDEX `idx_audit_user_created` (`al_user_id` ASC, `al_created_at` ASC) VISIBLE,\n"
        "  -- [เพิ่ม] กรองตามประเภท log + ช่วงเวลา\n"
        "  INDEX `idx_audit_type_created` (`al_type` ASC, `al_created_at` ASC) VISIBLE,\n"
        "  -- [เพิ่ม] ดู log ล่าสุด / ค้นตามช่วงเวลาอย่างเดียว\n"
        "  INDEX `idx_audit_created` (`al_created_at` ASC) VISIBLE,\n",
    ),
    (
        "zone_seat_pattern: fk_pattern_zone ON DELETE CASCADE",
        "  CONSTRAINT `fk_pattern_zone`\n"
        "    FOREIGN KEY (`zsp_zone_id`)\n"
        "    REFERENCES `concert_ticketing`.`zone` (`z_zone_id`))",
        "  CONSTRAINT `fk_pattern_zone`\n"
        "    FOREIGN KEY (`zsp_zone_id`)\n"
        "    REFERENCES `concert_ticketing`.`zone` (`z_zone_id`)\n"
        "    ON DELETE CASCADE) -- [แก้] ลบ zone แล้วลบ pattern ตาม",
    ),
    (
        "seat: replace fk_seat_zone index with idx_zone_booking_status",
        "  INDEX `fk_seat_zone` (`s_zone_id` ASC) VISIBLE,\n",
        "  -- [ลบ] INDEX fk_seat_zone ซ้ำซ้อนเพราะ uq_seat_zone_row_number ครอบคลุม s_zone_id แล้ว\n"
        "  INDEX `idx_zone_booking_status` (`s_zone_id` ASC, `s_seat_booking_status` ASC) VISIBLE, -- [เพิ่ม] ค้นหาที่นั่งว่างในโซน\n",
    ),
    (
        "seat: fk_seat_zone ON DELETE CASCADE",
        "    REFERENCES `concert_ticketing`.`zone` (`z_zone_id`),\n"
        "  CONSTRAINT `fk_seat_zone_seat_pattern1`",
        "    REFERENCES `concert_ticketing`.`zone` (`z_zone_id`)\n"
        "    ON DELETE CASCADE, -- [แก้] ลบ zone แล้วลบที่นั่งตาม (ถ้ามี booking_seat/seat_lock_log/ticket อ้างถึง seat จะลบไม่ได้)\n"
        "  CONSTRAINT `fk_seat_zone_seat_pattern1`",
    ),
    (
        "seat: fk_seat_zone_seat_pattern1 ON DELETE SET NULL",
        "    REFERENCES `concert_ticketing`.`zone_seat_pattern` (`zsp_pattern_id`)\n"
        "    ON DELETE NO ACTION\n"
        "    ON UPDATE NO ACTION)",
        "    REFERENCES `concert_ticketing`.`zone_seat_pattern` (`zsp_pattern_id`)\n"
        "    ON DELETE SET NULL) -- [แก้] ลบ pattern แล้วเคลียร์ค่าที่ seat เป็น NULL",
    ),
    (
        "seat_lock_log: idx_lock_expiry",
        "  INDEX `fk_lock_seat` (`sll_seat_id` ASC) VISIBLE,\n",
        "  INDEX `fk_lock_seat` (`sll_seat_id` ASC) VISIBLE,\n"
        "  INDEX `idx_lock_expiry` (`sll_status` ASC, `sll_expired_at` ASC) VISIBLE, -- [เพิ่ม] สำหรับ Background job ตรวจสอบ Lock หมดเวลา\n",
    ),
    (
        "ticket: fk_ticket_seat_idx",
        "  INDEX `fk_ticket_zone` (`t_zone_id` ASC) VISIBLE,\n",
        "  INDEX `fk_ticket_zone` (`t_zone_id` ASC) VISIBLE,\n"
        "  INDEX `fk_ticket_seat_idx` (`t_seat_id` ASC) VISIBLE, -- [เพิ่ม] สำหรับ Join/Lookup FK\n",
    ),
]


def clean_header(s: str) -> str:
    """แทนที่ header ของ Workbench และลบ comment 'Schema ...' ที่ซ้ำ/ตกค้าง"""
    if s.startswith("-- MySQL Script: concert_ticketing schema v3.3.0"):
        return s  # ทำแล้ว
    # header เดิม: บรรทัด comment ขึ้นต้นไฟล์จนถึงบรรทัดว่างแรก
    s = re.sub(r"\A(?:--[^\n]*\n)+", HEADER, s, count=1)
    # ลบบล็อก "Schema xxx" ทั้งแบบมีเส้นคั่น
    s = re.sub(
        r"-- -{50,}\n-- Schema [^\n]*\n-- -{50,}\n\n?", "", s
    )
    return s


def apply_patches(s: str, verbose: bool = True):
    s = s.replace("\r\n", "\n")
    applied, skipped = [], []

    s = clean_header(s)

    for name, old, new in PATCHES:
        if new in s:
            skipped.append(name)
            continue
        n = s.count(old)
        if n != 1:
            raise SystemExit(
                f"[ERROR] patch '{name}': พบข้อความเดิม {n} ที่ (ต้องเป็น 1)\n"
                f"        โมเดลอาจเปลี่ยนจากที่สคริปต์คาดไว้ กรุณาตรวจสอบ"
            )
        s = s.replace(old, new)
        applied.append(name)

    s = s.replace("\n\n\n", "\n\n")  # ลดบรรทัดว่างซ้อน
    if verbose:
        for n in applied:
            print(f"  [applied] {n}")
        for n in skipped:
            print(f"  [skipped] {n} (มีอยู่แล้ว)")
    return s, applied, skipped


def sanity_check(s: str) -> list:
    """เช็กว่าของสำคัญของ v3.3.0 อยู่ครบ"""
    must_have = [
        "`idx_booking_expiry`", "`idx_audit_user_created`", "`idx_audit_type_created`",
        "`idx_audit_created`", "`idx_zone_booking_status`", "`idx_lock_expiry`",
        "`fk_ticket_seat_idx`", "`u_phone_UNIQUE`",
        "ON DELETE SET NULL", "ON DELETE CASCADE",
        "`zsp_row_alphabet_start`", "`zsp_seat_number_start`",
    ]
    must_not = ["`naming_template`", "`zsp_source_template_id`", "`app_usercol_UNIQUE`",
                "`zsp_row_start`", "`zsp_row_end`"]
    problems = [f"ขาด {t}" for t in must_have if t not in s]
    problems += [f"ไม่ควรมี {t}" for t in must_not if t in s]
    return problems


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("input", help="ไฟล์ SQL ที่ export จาก Workbench (เช่น koko.sql)")
    ap.add_argument("-o", "--output", help="ไฟล์ผลลัพธ์ (default: <input>_v3_3_0.sql)")
    ap.add_argument("--check", action="store_true", help="ตรวจอย่างเดียว ไม่เขียนไฟล์")
    args = ap.parse_args()

    src = Path(args.input)
    if not src.exists():
        sys.exit(f"[ERROR] ไม่พบไฟล์ {src}")

    print(f"อ่าน {src}")
    result, applied, skipped = apply_patches(src.read_text(encoding="utf-8"))

    problems = sanity_check(result)
    if problems:
        print("[WARN] ผลลัพธ์ไม่ตรงกับ v3.3.0:")
        for p in problems:
            print("   -", p)

    if args.check:
        print(f"\nตรวจแล้ว: จะ apply {len(applied)} patch, ข้าม {len(skipped)} patch")
        sys.exit(1 if problems else 0)

    out = Path(args.output) if args.output else src.with_name(src.stem + "_v3_3_0.sql")
    out.write_text(result, encoding="utf-8", newline="\n")
    print(f"\nเขียน {out} สำเร็จ (applied {len(applied)}, skipped {len(skipped)})")
    sys.exit(1 if problems else 0)


if __name__ == "__main__":
    main()