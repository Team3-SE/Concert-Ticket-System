/**
 * concert-search.js
 * ช่องค้นหาที่แสดงคอนเสิร์ตที่ใกล้เคียงขณะพิมพ์ (concerts/home, concerts/index)
 * ใช้ route concerts.suggest, ปุ่มลูกศร ↑ ↓ เลือก, Enter เปิดคอนเสิร์ต (หรือค้นหาทั้งหมดถ้ายังไม่ได้เลือก), Esc ปิด
 * คอนเสิร์ตที่สถานะไม่ใช่ ON_SALE (ยังไม่เปิดขาย/ขายหมด ฯลฯ) แสดงในรายการได้ แต่กดเข้าไปไม่ได้
 * Author : ธีรเมธ พลสาย
 * Created : 08/10/2569
 * Updated : 23/10/2569 67160292 ธีรเมธ พลสาย
 */

(() => {
  const form = document.querySelector('[data-concert-search]');
  if (!form) return;

  const input = form.querySelector('input[name="search"]');
  const box = document.getElementById(input.getAttribute('aria-controls'));
  const wrap = form.closest('.search-box');
  const endpoint = form.dataset.suggestUrl;

  const cache = new Map();
  let timer = null;
  let controller = null;
  let options = [];      // element ที่เลือกได้ (รายการคอนเสิร์ต + "ดูผลทั้งหมด")
  let active = -1;
  let current = '';

  const STATUS = {
    ON_SALE:   ['เปิดขาย', 'is-on-sale'],
    SCHEDULED: ['เร็ว ๆ นี้', ''],
    SOLD_OUT:  ['ขายหมด', 'is-closed'],
    CANCELLED: ['ยกเลิก', 'is-closed'],
    COMPLETED: ['จบแล้ว', ''],
  };

  /**
   * esc(value)
   * escape ตัวอักษรพิเศษของ HTML
   * param : value
   * return : string
   */
  const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));

  /**
   * highlight(text, terms)
   * ครอบคำที่ตรงกับคำค้นด้วย <mark>
   * param : text, terms
   * return : string HTML
   */
  const highlight = (text, terms) => {
    if (!text) return '';
    if (!terms.length) return esc(text);
    const pattern = terms.map((t) => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|');
    return String(text)
      .split(new RegExp(`(${pattern})`, 'gi'))
      .map((part, i) => (i % 2 ? `<mark>${esc(part)}</mark>` : esc(part)))
      .join('');
  };

  /**
   * normalize(value)
   * ตัดช่องว่างหัวท้าย และรวมช่องว่างที่ติดกันให้เหลือช่องเดียว
   * param : value
   * return : string
   */
  const normalize = (value) => value.trim().replace(/\s+/g, ' ');

  /**
   * open()
   * เปิดรายการแนะนำ
   * param : -
   * return : -
   */
  const open = () => {
    box.hidden = false;
    wrap.classList.add('is-open');
    input.setAttribute('aria-expanded', 'true');
  };

  /**
   * close()
   * ปิดรายการแนะนำ
   * param : -
   * return : -
   */
  const close = () => {
    box.hidden = true;
    wrap.classList.remove('is-open');
    input.setAttribute('aria-expanded', 'false');
    setActive(-1);
  };

  /**
   * setActive(index, scroll)
   * เลือกรายการที่ index (ใช้กับปุ่มลูกศรและเมาส์)
   * param : index, scroll
   * return : -
   */
  const setActive = (index, scroll = true) => {
    active = index;
    options.forEach((el, i) => el.classList.toggle('active', i === index));
    if (index >= 0 && options[index]) {
      input.setAttribute('aria-activedescendant', options[index].id);
      if (scroll) options[index].scrollIntoView({ block: 'nearest' });
    } else {
      input.removeAttribute('aria-activedescendant');
    }
  };

  /**
   * showMessage(text)
   * แสดงข้อความแทนรายการ (กำลังค้นหา / ไม่พบ / error)
   * param : text
   * return : -
   */
  const showMessage = (text) => {
    box.innerHTML = `<div class="suggest-empty">${text}</div>`;
    options = [];
    setActive(-1);
    open();
  };

  /**
   * render(query, results)
   * สร้างรายการคอนเสิร์ตที่ใกล้เคียงจากผลลัพธ์
   * param : query, results
   * return : -
   */
  const render = (query, results) => {
    const terms = query.split(' ').filter(Boolean);

    if (!results.length) {
      showMessage(`ไม่พบคอนเสิร์ตที่ใกล้เคียงกับ “${esc(query)}”`);
      return;
    }

    const items = results.map((r, i) => {
      const [label, cls] = STATUS[r.status] || ['เร็ว ๆ นี้', ''];
      const initial = esc((r.artist || r.name || '?').trim().charAt(0).toUpperCase());
      const thumb = r.poster
        ? `<img src="${esc(r.poster)}" alt="" loading="lazy" data-fallback="${initial}">`
        : initial;
      const meta = [r.artist, r.venue].filter(Boolean).map((v) => highlight(v, terms)).join(' · ');
      // กดได้เฉพาะคอนเสิร์ตที่เปิดขายแล้ว ที่เหลือแสดงเป็นรายการธรรมดา (ไม่ใช่ลิงก์)
      const canOpen = r.status === 'ON_SALE';
      const openTag = canOpen
        ? `<a class="suggest-item" id="suggest-${i}" role="option" href="${esc(r.url)}">`
        : '<div class="suggest-item is-disabled" aria-disabled="true">';
      const closeTag = canOpen ? '</a>' : '</div>';

      return `
        ${openTag}
          <span class="suggest-thumb">${thumb}</span>
          <span class="suggest-text">
            <span class="suggest-name">${highlight(r.name, terms)}</span>
            <span class="suggest-meta">${meta}</span>
          </span>
          <span class="suggest-side">
            ${r.date ? `<span class="suggest-date">${esc(r.date)}</span>` : ''}
            <span class="suggest-status ${cls}">${label}</span>
          </span>
        ${closeTag}`;
    }).join('');

    box.innerHTML = `
      <div class="suggest-label">คอนเสิร์ตที่ใกล้เคียง</div>
      ${items}
      <button type="button" class="suggest-all" id="suggest-all" role="option">
        ดูผลการค้นหาทั้งหมดสำหรับ “${esc(query)}”
      </button>`;

    // รูปโปสเตอร์โหลดไม่ได้ → แสดงตัวอักษรแรกแทน
    box.querySelectorAll('img[data-fallback]').forEach((img) => {
      img.addEventListener('error', () => { img.parentElement.textContent = img.dataset.fallback; }, { once: true });
    });

    options = [...box.querySelectorAll('a.suggest-item, .suggest-all')];
    setActive(-1);
    open();
  };

  /**
   * fetchSuggestions(query)
   * ดึงรายการแนะนำจาก server (มี cache และยกเลิกคำขอเก่า)
   * param : query
   * return : Promise ของ array ผลลัพธ์
   */
  const fetchSuggestions = async (query) => {
    if (cache.has(query)) return cache.get(query);
    if (controller) controller.abort();
    controller = new AbortController();
    const res = await fetch(`${endpoint}?q=${encodeURIComponent(query)}`, {
      headers: { Accept: 'application/json' },
      signal: controller.signal,
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    cache.set(query, data.results || []);
    return data.results || [];
  };

  /**
   * update()
   * ค้นหาตามคำที่พิมพ์แล้วแสดงผล
   * param : -
   * return : Promise
   */
  const update = async () => {
    const query = normalize(input.value);
    current = query;
    if (!query) { close(); return; }

    if (box.hidden && !cache.has(query)) showMessage('กำลังค้นหา…');

    try {
      const results = await fetchSuggestions(query);
      if (query === current) render(query, results);
    } catch (err) {
      if (err.name !== 'AbortError' && query === current) showMessage('ค้นหาไม่สำเร็จ ลองใหม่อีกครั้ง');
    }
  };

  /**
   * submitAll()
   * ปิดรายการแล้วค้นหาแบบเต็มหน้า (submit ฟอร์ม)
   * param : -
   * return : -
   */
  const submitAll = () => {
    close();
    form.submit();
  };

  input.addEventListener('input', () => {
    clearTimeout(timer);
    if (!normalize(input.value)) { current = ''; close(); return; }
    timer = setTimeout(update, 200);
  });

  input.addEventListener('focus', () => {
    if (normalize(input.value) && options.length) open();
  });

  input.addEventListener('keydown', (e) => {
    const count = options.length;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (box.hidden) { if (normalize(input.value)) update(); return; }
      if (count) setActive((active + 1) % count);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (!box.hidden && count) setActive((active - 1 + count) % count);
    } else if (e.key === 'Enter') {
      if (!box.hidden && active >= 0 && options[active]) {
        e.preventDefault();
        options[active].click();
      } else {
        close();   // ไม่ได้เลือกรายการ → ค้นหาทั้งหมดตามปกติ (submit ฟอร์ม)
      }
    } else if (e.key === 'Escape') {
      if (!box.hidden) { e.preventDefault(); close(); }
    }
  });

  box.addEventListener('mousemove', (e) => {
    const el = e.target.closest('a.suggest-item, .suggest-all');
    if (el) setActive(options.indexOf(el), false);
  });

  box.addEventListener('mousedown', (e) => e.preventDefault()); // คลิกแล้วช่องค้นหาไม่หลุดโฟกัส

  box.addEventListener('click', (e) => {
    if (e.target.closest('.suggest-all')) submitAll();
  });

  document.addEventListener('click', (e) => {
    if (!wrap.contains(e.target)) close();
  });
})();
