<?php
  $showLogo = $showLogo ?? true;
  $profileCustomer = auth()->check() ? auth()->user()->customer : null;
  $profileName = trim(($profileCustomer->first_name ?? 'สมาชิก') . ' ' . ($profileCustomer->last_name ?? ''));
  $profileInitial = mb_strtoupper(mb_substr($profileCustomer->first_name ?? 'ส', 0, 1));
  $profileColors = ['#2563eb', '#0f766e', '#c2410c', '#7c3aed', '#be123c', '#0369a1'];
  $profileColor = $profileColors[auth()->check() ? (crc32((string) auth()->id()) % count($profileColors)) : 0];
?>

<style>
  .team-header{background:linear-gradient(90deg,#0a1a4a,#12297d);color:#fff;display:flex;align-items:center;justify-content:space-between;padding:14px 32px;min-height:64px;position:relative;z-index:30;}
  .team-header.no-logo{justify-content:flex-end;}
  .team-logo{display:flex;align-items:center;gap:10px;color:#fff;font-weight:700;font-size:18px;text-decoration:none;}
  .team-logo img{width:36px;height:36px;object-fit:contain;mix-blend-mode:screen;}
  .profile-trigger{display:flex;align-items:center;gap:12px;background:none;border:0;color:#fff;font:500 13px inherit;cursor:pointer;padding:0;}
  .profile-avatar{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:18px;overflow:hidden;border:2px solid rgba(255,255,255,.7);}
  .profile-popup{position:absolute;right:4.5%;top:calc(100% + 9px);width:278px;background:#fff;color:#263247;border-radius:8px;box-shadow:0 12px 30px rgba(15,32,65,.18);border:1px solid #edf0f5;overflow:visible;}
  .profile-popup::before{content:'';position:absolute;right:19px;top:-8px;width:15px;height:15px;background:#fff;border-left:1px solid #edf0f5;border-top:1px solid #edf0f5;transform:rotate(45deg);}
  .profile-popup-head{position:relative;background:#fff;padding:21px 20px 18px;border-bottom:1px solid #edf0f5;z-index:1;}
  .profile-popup-head strong{display:block;font-size:16px;line-height:1.4;}
  .profile-popup-head span{display:block;color:#a6adba;font-size:12px;margin-top:2px;}
  .profile-popup-menu{position:relative;background:#fff;padding:7px 0;z-index:1;}
  .profile-popup-menu a,.profile-popup-menu button{display:block;width:100%;padding:11px 20px;border:0;background:none;text-align:left;color:#334155;font:500 13px inherit;text-decoration:none;cursor:pointer;}
  .profile-popup-menu a:hover,.profile-popup-menu button:hover{background:#f7f8fa;color:#f47b1f;}
  .profile-popup-menu .profile-logout{border-top:1px solid #edf0f5;color:#475569;padding-top:14px;padding-bottom:14px;}
  .profile-login{background:#f47b1f;border:0;border-radius:22px;color:#fff;padding:10px 22px;font:600 14px inherit;cursor:pointer;}
  [data-profile-menu][hidden]{display:none;}
  @media(max-width:640px){.team-header{padding:12px 16px;}.team-logo{font-size:16px;}.team-logo img{width:32px;height:32px;}.profile-name{display:none;}.profile-popup{right:16px;width:calc(100vw - 32px);}}
</style>

<header class="team-header <?php echo e($showLogo ? '' : 'no-logo'); ?>">

  <!-- LOGO SECTION -->
  <?php if($showLogo): ?>
  <a href="<?php echo e(url('/')); ?>" class="team-logo">
    <img src="<?php echo e(asset('images/logo.png')); ?>" alt="TEAM 3 Ticket Logo" class="w-7 h-7 object-contain" style="mix-blend-mode:screen;">
    <span class="font-bold text-base tracking-wide">TEAM <span class="text-[#F47B1F]">3</span> Ticket</span>
  </a>
  <?php endif; ?>

  <!-- PROFILE & DROPDOWN SECTION -->
  <div class="profile-area">

    <!-- Profile Trigger Button -->
    <?php if(auth()->guard()->check()): ?>
    <button type="button" class="profile-trigger" data-profile-toggle aria-expanded="false">
      <span class="profile-name"><?php echo e($profileName); ?></span>
      <span class="profile-avatar" style="background-color:<?php echo e($profileColor); ?>;"><?php echo e($profileInitial); ?></span>
    </button>
    <?php else: ?>
    <button class="profile-login" id="openLogin">เข้าสู่ระบบ</button>
    <?php endif; ?>

    <!-- Profile Dropdown Popup (Matching Mockup) -->
    <?php if(auth()->guard()->check()): ?>
    <div data-profile-menu hidden class="profile-popup">

      <!-- Top Arrow Indicator -->
      <div class="absolute -top-2 right-3 w-4 h-4 bg-white rotate-45 border-t border-l border-gray-100"></div>

      <!-- User Header Info -->
      <div class="profile-popup-head">
        <strong><?php echo e($profileName); ?></strong>
        <span>ข้อมูลส่วนตัว</span>
      </div>

      <!-- Menu Items -->
      <div class="profile-popup-menu">

        <a href="#" aria-disabled="true">
          ตั๋วของฉัน
        </a>

        <a href="<?php echo e(route('orders.history')); ?>">
          ประวัติการสั่งซื้อ
        </a>

        <a href="<?php echo e(route('profile.edit')); ?>">
          แก้ไขข้อมูลส่วนตัว
        </a>

        <!-- Logout Item -->
        <form method="POST" action="<?php echo e(route('auth.logout')); ?>">
          <?php echo csrf_field(); ?>
          <button type="submit" class="profile-logout">ออกจากระบบ</button>
        </form>

      </div>

    </div>
    <?php endif; ?>

  </div>

</header>

<?php if(auth()->guard()->check()): ?>
<script>
  (() => {
    const trigger = document.querySelector('[data-profile-toggle]');
    const menu = document.querySelector('[data-profile-menu]');
    if (!trigger || !menu) return;
    trigger.addEventListener('click', () => {
      const isOpen = !menu.hidden;
      menu.hidden = isOpen;
      trigger.setAttribute('aria-expanded', String(!isOpen));
    });
    document.addEventListener('click', (event) => {
      if (!event.target.closest('.profile-area')) {
        menu.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
      }
    });
  })();
</script>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/navbar.blade.php ENDPATH**/ ?>