<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $__env->yieldContent('title', 'TEAM 3 Ticket'); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  /* ===== Variables ===== */
  :root{
    --navy:#0d1b4b;          /* sidebar / topbar dark */
    --navy-mid:#122070;
    --orange:#f7941d;
    --orange-dark:#e8820a;
    --bg:#eef0f5;            /* page background — light blue-gray */
    --white:#ffffff;
    --gray-border:#e2e4ec;
    --gray-label:#6b7280;
    --gray-placeholder:#b0b5c8;
    --gray-light:#f5f6fa;
    --text:#1c2138;
    --text-muted:#9097b0;
    --danger:#f25c5c;        /* logout red */
    --navy-btn:#1e3a8a;
  }
  *{box-sizing:border-box;margin:0;padding:0;}
  body{font-family:'Noto Sans Thai',sans-serif;background:var(--bg);color:var(--text);}

  /* ===== Shell ===== */
  .shell{display:flex;min-height:100vh;}

  /* ================================================================
     SIDEBAR
  ================================================================ */
  .sidebar{
    width:181px;flex-shrink:0;
    background:var(--navy);
    display:flex;flex-direction:column;
  }

  /* Logo */
  .sidebar-logo{
    display:flex;align-items:center;gap:8px;
    padding:18px 10px 14px;text-decoration:none;
    color:#fff;font-weight:800;font-size:13px;line-height:1.2;
    white-space:nowrap;
  }
  .sidebar-logo:hover{opacity:.9;}
  .logo-icon{
    width:34px;height:34px;flex-shrink:0;
    object-fit:contain;
    mix-blend-mode:screen;
  }
  .logo-text{font-size:12px;font-weight:800;color:#fff;line-height:1.3;}
  .logo-text span{color:var(--orange);}

  /* Nav links */
  .sidebar nav{margin-top:4px;flex:1;}
  .sidebar nav a,
  .sidebar .nav-logout{
    display:block;
    padding:12px 16px;
    font-size:12px;font-weight:500;
    color:#9ba8cc;
    text-decoration:none;
    border-left:3px solid transparent;
    transition:background .15s,color .15s,border-color .15s;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
    cursor:pointer;
  }
  .sidebar nav a:hover{background:rgba(255,255,255,.06);color:#fff;}
  .sidebar nav a.active{
    background:rgba(255,255,255,.09);
    color:#fff;font-weight:600;
    border-left-color:var(--orange);
  }

  /* Logout button */
  .sidebar .nav-logout{
    width:100%;text-align:left;
    background:none;border:none;border-left:3px solid transparent;
    font-family:inherit;
    color:var(--danger);
  }
  .sidebar .nav-logout:hover{
    background:rgba(242,92,92,.1);
    color:#ff8080;
    border-left-color:var(--danger);
  }

  /* ================================================================
     TOPBAR
  ================================================================ */
  .main{flex:1;display:flex;flex-direction:column;min-width:0;}

  header.topbar{display:none;}
  .user-chip{display:flex;align-items:center;gap:10px;color:#fff;font-size:13px;font-weight:500;}
  .avatar{
    width:34px;height:34px;border-radius:50%;
    background:#9097b0;               /* gray avatar like mockup */
    flex-shrink:0;
  }

  /* ================================================================
     MAIN CONTENT
  ================================================================ */
  main.content{padding:22px 28px 60px;background:#eef4fb;flex:1;}

  .page-title{
    font-size:15px;font-weight:700;color:var(--text);
    border-left:4px solid var(--orange);padding-left:10px;
    margin-bottom:20px;
  }

  .status-msg{
    margin:0 0 16px;padding:10px 16px;
    background:#e9f8ef;border:1px solid #b5e6c8;color:#166534;
    border-radius:8px;font-size:12px;
  }

  /* ================================================================
     GRID & CARDS (profile page)
  ================================================================ */
  .grid-2{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
    max-width:none;
  }

  .card{
    background:var(--white);
    border-radius:10px;
    border:1px solid var(--gray-border);
    padding:24px 24px 28px;
  }

  /* Card heading with orange bar */
  .card-title{
    display:flex;align-items:center;gap:8px;
    font-size:14px;font-weight:700;color:var(--text);
    margin-bottom:20px;
  }
  .card-title::before{
    content:'';display:inline-block;
    width:4px;height:16px;border-radius:2px;
    background:var(--orange);flex-shrink:0;
  }

  /* ================================================================
     FORM FIELDS
  ================================================================ */
  .field{margin-bottom:14px;}
  .field label{
    display:block;font-size:11px;font-weight:600;
    color:var(--gray-label);margin-bottom:5px;letter-spacing:.2px;
  }
  .field input{
    width:100%;padding:9px 11px;
    border:1px solid var(--gray-border);
    border-radius:6px;font-size:13px;
    font-family:inherit;color:var(--text);
    background:var(--white);
    outline:none;transition:border-color .15s,box-shadow .15s;
  }
  .field input:focus{
    border-color:#6b8cda;
    box-shadow:0 0 0 3px rgba(107,140,218,.12);
  }
  .field input::placeholder{color:var(--gray-placeholder);}
  .field input:disabled{
    background:var(--gray-light);color:#9ba8cc;cursor:not-allowed;
  }

  /* Two-column row */
  .row-2{display:flex;gap:12px;}
  .row-2 .field{flex:1;}

  /* Phone with flag prefix */
  .phone-field{
    display:flex;align-items:stretch;
    border:1px solid var(--gray-border);border-radius:6px;overflow:hidden;
  }
  .phone-field:focus-within{
    border-color:#6b8cda;
    box-shadow:0 0 0 3px rgba(107,140,218,.12);
  }
  .phone-prefix{
    background:var(--gray-light);
    display:flex;flex-direction:column;align-items:center;
    justify-content:center;gap:1px;
    padding:0 10px;min-width:52px;
    font-size:10px;font-weight:600;color:var(--gray-label);
    border-right:1px solid var(--gray-border);
    line-height:1.2;
  }
  .phone-prefix .flag{font-size:16px;}
  .phone-field input{border:none;border-radius:0;box-shadow:none !important;}
  .phone-field input:focus{border:none;box-shadow:none !important;}

  /* Error states */
  .is-error{border-color:#e8503c !important;}
  .phone-field.is-error{border-color:#e8503c !important;}
  .field-error{font-size:11px;color:#e8503c;margin-top:4px;}

  /* ================================================================
     BUTTONS
  ================================================================ */
  .actions{display:flex;justify-content:center;margin-top:22px;}

  .btn{
    display:inline-block;
    border:none;font-family:inherit;font-size:13px;font-weight:600;
    padding:10px 28px;border-radius:20px;cursor:pointer;
    transition:background .15s,transform .1s;
    white-space:nowrap;
  }
  .btn:hover{transform:translateY(-1px);}
  .btn-orange{background:var(--orange);color:#fff;}
  .btn-orange:hover{background:var(--orange-dark);}
  .btn-navy{background:var(--navy-btn);color:#fff;}
  .btn-navy:hover{background:#1a3170;}

  /* ================================================================
     RESPONSIVE
  ================================================================ */
  @media(max-width:900px){.grid-2{grid-template-columns:1fr;}}
  @media(max-width:640px){
    .sidebar{width:60px;}
    .sidebar-logo span,.sidebar nav a,.sidebar .nav-logout{font-size:0;padding:12px 0;text-align:center;}
    .logo-icon{margin:0 auto;}
  }

  /* ===== Extra CSS from page ===== */
  <?php echo $__env->yieldContent('styles'); ?>
</style>
</head>
<body>

<div class="shell">

  <!-- ===== Sidebar ===== -->
  <aside class="sidebar">
    <a href="/" class="sidebar-logo">
      <img src="<?php echo e(asset('images/logo.png')); ?>" alt="TEAM 3 Ticket" class="logo-icon">
      <div class="logo-text">TEAM <span>3</span> Ticket</div>
    </a>
    <nav>
      <a href="/" class="<?php echo e(request()->is('/') ? 'active' : ''); ?>">ตั๋วของฉัน</a>
      <a href="<?php echo e(route('orders.history')); ?>" class="<?php echo e(request()->routeIs('orders.*') ? 'active' : ''); ?>">ประวัติการสั่งซื้อ</a>
      <a href="<?php echo e(route('profile.edit')); ?>" class="<?php echo e(request()->routeIs('profile.*') ? 'active' : ''); ?>">แก้ไขข้อมูลส่วนตัว</a>
      <form method="POST" action="<?php echo e(route('auth.logout')); ?>" style="margin:0;">
        <?php echo csrf_field(); ?>
        <button type="submit" class="nav-logout">ออกจากระบบ</button>
      </form>
    </nav>
  </aside>

  <!-- ===== Main ===== -->
  <div class="main">
    <?php echo $__env->make('navbar', ['showLogo' => false], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="content">
      <?php if(session('status')): ?>
        <div class="status-msg"><?php echo e(session('status')); ?></div>
      <?php endif; ?>

      <?php echo $__env->yieldContent('content'); ?>
    </main>
  </div>

</div>

<?php echo $__env->yieldContent('scripts'); ?>

</body>
</html>

<?php /**PATH /var/www/html/resources/views/layouts/app.blade.php ENDPATH**/ ?>