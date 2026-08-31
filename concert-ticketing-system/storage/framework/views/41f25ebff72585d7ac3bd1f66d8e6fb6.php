<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TEAM 3 Ticket</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --navy-900:#0a1a4a;
    --navy-800:#0e2166;
    --navy-700:#12297d;
    --orange:#f7941d;
    --orange-dark:#e8820a;
    --bg:#f4f5f8;
    --gray-100:#f1f2f5;
    --gray-300:#dfe1e7;
    --gray-500:#8b8fa3;
    --gray-700:#4b4f63;
    --text:#1c2138;
    --card-blue:#e4e7f5;
    --card-yellow:#fbf3d9;
    --card-lav:#e6e2f7;
    --card-pink:#fbe3ee;
    --card-mint:#dff2ea;
  }
  *{box-sizing:border-box;margin:0;padding:0;}
  body{
    font-family:'Noto Sans Thai',sans-serif;
    background:var(--bg);
    color:var(--text);
  }

  /* Header */
  header.site{
    background:linear-gradient(90deg,var(--navy-900),var(--navy-700));
    display:flex;align-items:center;justify-content:space-between;
    padding:14px 32px;
  }
  .logo{display:flex;align-items:center;gap:10px;color:#fff;font-weight:800;font-size:18px;letter-spacing:.3px;}
  .logo-badge{
    width:30px;height:30px;border-radius:8px;
    background:var(--orange);
    display:flex;align-items:center;justify-content:center;
    font-size:16px;
  }
  .btn-orange{
    background:var(--orange);color:#fff;border:none;
    padding:10px 22px;border-radius:22px;font-weight:600;
    font-family:inherit;font-size:14px;cursor:pointer;
    transition:background .15s ease, transform .1s ease;
  }
  .btn-orange:hover{background:var(--orange-dark);transform:translateY(-1px);}

  /* Search */
  .search-wrap{max-width:1100px;margin:22px auto 0;padding:0 32px;}
  .search-bar{
    display:flex;align-items:center;gap:10px;
    background:#fff;border:1px solid var(--gray-300);
    border-radius:24px;padding:10px 18px;
    color:var(--gray-500);font-size:14px;
  }

  /* Hero */
  .hero-wrap{max-width:1100px;margin:18px auto 0;padding:0 32px;}
  .hero{
    position:relative;border-radius:14px;overflow:hidden;
    background:
      radial-gradient(circle at 80% 30%, rgba(255,255,255,.08), transparent 55%),
      linear-gradient(135deg,#0a1547 0%,#122a7d 55%,#1c3ea8 100%);
    padding:44px 48px;min-height:190px;
    display:flex;flex-direction:column;justify-content:center;
    color:#fff;
  }
  .hero-badge{
    display:inline-block;background:var(--orange);
    font-size:11px;font-weight:700;padding:4px 12px;
    border-radius:12px;margin-bottom:14px;width:fit-content;
    letter-spacing:.5px;
  }
  .hero h1{font-size:26px;font-weight:800;margin-bottom:10px;}
  .hero p{font-size:13px;color:#c7cdec;max-width:620px;line-height:1.6;}
  .hero-arrow{
    position:absolute;top:50%;transform:translateY(-50%);
    width:34px;height:34px;border-radius:50%;
    background:rgba(255,255,255,.12);color:#fff;
    display:flex;align-items:center;justify-content:center;
    cursor:pointer;font-size:16px;border:none;
  }
  .hero-arrow.left{left:16px;}
  .hero-arrow.right{right:16px;}
  .dots{display:flex;justify-content:center;gap:6px;margin:14px 0 26px;}
  .dots span{width:6px;height:6px;border-radius:50%;background:var(--gray-300);}
  .dots span.active{background:var(--navy-800);width:16px;border-radius:4px;}

  /* Recommend section */
  .section{max-width:1100px;margin:0 auto;padding:0 32px 40px;}
  .section-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;}
  .section-title{
    font-size:16px;font-weight:700;
    border-left:4px solid var(--orange);padding-left:10px;
  }
  .show-all{color:var(--navy-700);font-size:13px;font-weight:600;text-decoration:none;}

  .grid{
    display:grid;grid-template-columns:repeat(6,1fr);gap:16px;
  }
  .card{
    background:#fff;border-radius:10px;overflow:hidden;
    border:1px solid var(--gray-300);
    display:flex;flex-direction:column;
  }
  .card-img{
    height:110px;position:relative;
    display:flex;align-items:flex-end;padding:10px;
    color:#1c2138;font-weight:700;font-size:12px;
  }
  .c1{background:var(--card-blue);} .c2{background:var(--card-yellow);}
  .c3{background:var(--card-lav);} .c4{background:var(--card-pink);}
  .c5{background:var(--card-mint);} .c6{background:var(--card-blue);}
  .tag{
    position:absolute;top:8px;left:8px;
    background:var(--orange);color:#fff;font-size:9px;font-weight:700;
    padding:3px 8px;border-radius:10px;
  }
  .card-body{padding:10px 12px 12px;display:flex;flex-direction:column;gap:6px;flex:1;}
  .card-body .title{font-size:12px;font-weight:700;line-height:1.4;min-height:32px;}
  .card-body .date{font-size:11px;color:var(--gray-500);}
  .card-btn{
    margin-top:auto;text-align:center;font-size:12px;font-weight:600;
    padding:7px;border-radius:16px;border:none;cursor:pointer;
    font-family:inherit;
  }
  .card-btn.buy{background:var(--navy-800);color:#fff;}
  .card-btn.soon{background:var(--gray-100);color:var(--gray-500);}

  /* Footer */
  footer.site{
    background:var(--navy-900);color:#c7cdec;
    padding:36px 32px 20px;margin-top:10px;
  }
  .footer-inner{
    max-width:1100px;margin:0 auto;
    display:flex;justify-content:space-between;flex-wrap:wrap;gap:20px;
  }
  .footer-logo{display:flex;align-items:center;gap:10px;color:#fff;font-weight:800;font-size:16px;margin-bottom:10px;}
  .footer-inner p{font-size:12px;line-height:1.8;color:#9aa2c6;max-width:280px;}
  .footer-contact p{font-size:12px;line-height:2;color:#c7cdec;}
  .footer-bottom{
    max-width:1100px;margin:24px auto 0;padding-top:16px;
    border-top:1px solid rgba(255,255,255,.1);
    display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;
    font-size:11px;color:#7d84a8;
  }
  .footer-bottom a{color:#7d84a8;text-decoration:none;margin-left:14px;}

  /* Modal */
  .overlay{
    position:fixed;inset:0;background:rgba(10,16,50,.55);
    display:none;align-items:center;justify-content:center;
    z-index:100;padding:20px;
    backdrop-filter:blur(2px);
  }
  .overlay.open{display:flex;}
  .modal{
    background:#fff;border-radius:14px;width:100%;max-width:380px;
    padding:28px 26px 24px;position:relative;
    box-shadow:0 20px 60px rgba(10,16,50,.35);
    animation:pop .18s ease;
  }
  @keyframes pop{from{opacity:0;transform:translateY(8px) scale(.98);}to{opacity:1;transform:none;}}
  .modal-close{
    position:absolute;top:14px;right:16px;background:none;border:none;
    font-size:16px;color:var(--gray-500);cursor:pointer;
  }
  .modal h2{
    text-align:center;color:var(--navy-800);font-size:19px;font-weight:800;margin-bottom:4px;
  }
  .modal .sub{text-align:center;font-size:12px;color:var(--gray-500);margin-bottom:20px;}
  .field{margin-bottom:14px;}
  .field label{display:block;font-size:12px;font-weight:600;color:var(--gray-700);margin-bottom:6px;}
  .field label .req{color:#e8503c;}
  .field input{
    width:100%;padding:10px 12px;border:1px solid var(--gray-300);
    border-radius:8px;font-size:13px;font-family:inherit;color:var(--text);
    outline:none;transition:border-color .15s;
  }
  .field input:focus{border-color:var(--navy-700);}
  .field input::placeholder{color:#b7bad0;}
  .row-2{display:flex;gap:10px;}
  .row-2 .field{flex:1;}
  .options-row{
    display:flex;align-items:center;justify-content:space-between;
    font-size:12px;margin-bottom:18px;color:var(--gray-700);
  }
  .options-row label{display:flex;align-items:center;gap:6px;cursor:pointer;}
  .options-row a{color:var(--orange-dark);text-decoration:none;font-weight:600;}
  .modal .btn-orange{width:100%;padding:11px;border-radius:24px;font-size:14px;}
  .switch-text{text-align:center;font-size:12px;color:var(--gray-700);margin-top:16px;}
  .switch-text a{color:var(--orange-dark);font-weight:700;text-decoration:none;}
  .phone-field{
    display:flex;align-items:stretch;border:1px solid var(--gray-300);border-radius:8px;overflow:hidden;
  }
  .phone-prefix{
    background:var(--gray-100);display:flex;align-items:center;padding:0 10px;font-size:13px;color:var(--gray-700);border-right:1px solid var(--gray-300);
  }
  .phone-field input{border:none;border-radius:0;}
  .phone-field input:focus{border:none;}

  @media(max-width:960px){
    .grid{grid-template-columns:repeat(3,1fr);}
  }
  @media(max-width:640px){
    header.site{padding:12px 16px;}
    .search-wrap,.hero-wrap,.section{padding:0 16px;}
    .grid{grid-template-columns:repeat(2,1fr);}
    .hero{padding:28px 22px;}
    .hero h1{font-size:19px;}
    .footer-inner{flex-direction:column;}
  }
</style>
</head>
<body>

<header class="site">
  <div class="logo"><span class="logo-badge">🎫</span> TEAM 3 Ticket</div>
  <button class="btn-orange" id="openLogin">เข้าสู่ระบบ</button>
</header>

<div class="search-wrap">
  <div class="search-bar">🔍 ค้นหาคอนเสิร์ต หรือ ศิลปิน</div>
</div>

<div class="hero-wrap">
  <div class="hero">
    <span class="hero-badge">HIGHLIGHT LIVE คอนเสิร์ต</span>
    <h1>MIDNIGHT WAVE LIVE : DIVE TO BLUE</h1>
    <p>เตรียมดำดิ่งสู่ค่ำคืนแห่งเสียงเพลง กับโปรดักชันสุดยิ่งใหญ่ 2 วันเต็ม ที่จะพาคุณสัมผัสประสบการณ์ดนตรีที่ไม่มีวันลืม</p>
    <button class="hero-arrow left">‹</button>
    <button class="hero-arrow right">›</button>
  </div>
  <div class="dots"><span class="active"></span><span></span><span></span></div>
</div>

<div class="section">
  <div class="section-head">
    <div class="section-title">Recommend Concerts</div>
    <a href="#" class="show-all">Show All</a>
  </div>
  <div class="grid" id="cardGrid"></div>
</div>

<footer class="site">
  <div class="footer-inner">
    <div>
      <div class="footer-logo">🎫 TEAM 3 Ticket</div>
      <p>แพลตฟอร์มจำหน่ายบัตรคอนเสิร์ตและกิจกรรมดนตรีชั้นนำ ครบ จบ ในที่เดียว</p>
    </div>
    <div class="footer-contact">
      <p>📍 193 ถนนพหลโยธิน จตุจักร กรุงเทพฯ 10900</p>
      <p>📞 096-878-0707 (จันทร์-ศุกร์ 09:00-18:00)</p>
      <p>✉️ support@team3ticket.com</p>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© 2026 TEAM 3 Ticket. All rights reserved.</span>
    <span><a href="#">เงื่อนไขการใช้งาน</a><a href="#">นโยบายความเป็นส่วนตัว</a><a href="#">ติดต่อเรา</a></span>
  </div>
</footer>

<!-- Login Modal -->
<div class="overlay" id="loginOverlay">
  <div class="modal">
    <button class="modal-close" data-close>✕</button>
    <h2>เข้าสู่ระบบ</h2>
    <p class="sub">ยินดีต้อนรับกลับมาอีกครั้ง</p>
    <form id="loginForm">
      <div class="field">
        <label>อีเมล</label>
        <input type="email" placeholder="example@email.com" required>
      </div>
      <div class="field">
        <label>รหัสผ่าน</label>
        <input type="password" placeholder="••••••••" required>
      </div>
      <div class="options-row">
        <label><input type="checkbox" style="width:auto;"> จดจำฉัน</label>
        <a href="#">ลืมรหัสผ่าน?</a>
      </div>
      <button type="submit" class="btn-orange">เข้าสู่ระบบ</button>
    </form>
    <p class="switch-text">ยังไม่มีบัญชี? <a href="#" id="toRegister">สมัครสมาชิก</a></p>
  </div>
</div>

<!-- Register Modal -->
<div class="overlay" id="registerOverlay">
  <div class="modal">
    <button class="modal-close" data-close>✕</button>
    <h2>สมัครสมาชิก</h2>
    <p class="sub">เป็นสมาชิกอยู่แล้ว? <a href="#" id="toLoginFromSub" style="color:var(--orange-dark);font-weight:700;text-decoration:none;">เข้าสู่ระบบ</a></p>
    <form id="registerForm">
      <div class="field">
        <label>อีเมล <span class="req">*</span></label>
        <input type="email" placeholder="example@email.com" required>
      </div>
      <div class="field">
        <label>รหัสผ่าน <span class="req">*</span></label>
        <input type="password" placeholder="••••••••" required>
      </div>
      <div class="field">
        <label>ยืนยันรหัสผ่าน <span class="req">*</span></label>
        <input type="password" placeholder="••••••••" required>
      </div>
      <div class="row-2">
        <div class="field">
          <label>ชื่อ <span class="req">*</span></label>
          <input type="text" placeholder="กรุณากรอกชื่อจริงตามบัตรประชาชน" required>
        </div>
        <div class="field">
          <label>นามสกุล <span class="req">*</span></label>
          <input type="text" placeholder="กรุณากรอกนามสกุลตามบัตรประชาชน" required>
        </div>
      </div>
      <div class="field">
        <label>เบอร์โทรศัพท์ <span class="req">*</span></label>
        <div class="phone-field">
          <span class="phone-prefix">🇹🇭 +66</span>
          <input type="tel" placeholder="0XXXXXXXX" required>
        </div>
      </div>
      <button type="submit" class="btn-orange" style="margin-top:6px;">สมัครสมาชิก</button>
    </form>
  </div>
</div>

<script>
  // ----- Populate concert cards -----
  const concerts = [
    {cls:'c1', tag:'CONCERT', title:'U-KNOW PROJECT 28 - SCENERY in BANGKOK', date:'23 AUGUST 2026', status:'soon'},
    {cls:'c2', tag:'HIGHLIGHT LIVE', title:'DEAR 18 : MIDNIGHT WAVE LIVE - DIVE TO BLUE in BANGKOK', date:'24-25 JULY 2026', status:'buy'},
    {cls:'c3', tag:'ON CONCERT', title:'BUS LIGHT AS ONE CONCERT', date:'4-26 JULY 2026', status:'buy'},
    {cls:'c4', tag:'FAN MEETING', title:'LINGORM Thailand FAN MEETING TOUR...', date:'4TH JULY / 15 JULY 2026', status:'buy'},
    {cls:'c5', tag:'LIVE CONCERT', title:'Shades: Before Next Semesters', date:'17 JULY 2026', status:'buy'},
    {cls:'c6', tag:'ON CONCERT', title:'BUS LIGHT AS ONE CONCERT', date:'14-26 JULY 2026', status:'soon'},
  ];
  const grid = document.getElementById('cardGrid');
  function renderCards(){
    grid.innerHTML='';
    // two rows, same set repeated to match reference layout
    const all=[...concerts, ...concerts];
    all.forEach(c=>{
      const btn = c.status==='buy'
        ? `<button class="card-btn buy">Buy Now</button>`
        : `<button class="card-btn soon">Coming Soon</button>`;
      grid.innerHTML += `
        <div class="card">
          <div class="card-img ${c.cls}"><span class="tag">${c.tag}</span></div>
          <div class="card-body">
            <div class="title">${c.title}</div>
            <div class="date">${c.date}</div>
            ${btn}
          </div>
        </div>`;
    });
  }
  renderCards();

  // ----- Modal logic -----
  const loginOverlay = document.getElementById('loginOverlay');
  const registerOverlay = document.getElementById('registerOverlay');

  function openModal(el){ el.classList.add('open'); }
  function closeModal(el){ el.classList.remove('open'); }
  function closeAll(){ closeModal(loginOverlay); closeModal(registerOverlay); }

  document.getElementById('openLogin').addEventListener('click', ()=> openModal(loginOverlay));
  document.getElementById('toRegister').addEventListener('click', (e)=>{
    e.preventDefault(); closeModal(loginOverlay); openModal(registerOverlay);
  });
  document.getElementById('toLoginFromSub').addEventListener('click', (e)=>{
    e.preventDefault(); closeModal(registerOverlay); openModal(loginOverlay);
  });

  document.querySelectorAll('[data-close]').forEach(btn=>{
    btn.addEventListener('click', closeAll);
  });
  [loginOverlay, registerOverlay].forEach(ov=>{
    ov.addEventListener('click', (e)=>{ if(e.target === ov) closeAll(); });
  });
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeAll(); });

  document.getElementById('loginForm').addEventListener('submit', e=>{
    e.preventDefault();
    alert('เข้าสู่ระบบสำเร็จ (ตัวอย่าง)');
    closeAll();
  });
  document.getElementById('registerForm').addEventListener('submit', e=>{
    e.preventDefault();
    alert('สมัครสมาชิกสำเร็จ (ตัวอย่าง)');
    closeAll();
  });
</script>

</body>
</html>
<?php /**PATH /var/www/html/resources/views/home.blade.php ENDPATH**/ ?>