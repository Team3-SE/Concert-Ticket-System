<?php $__env->startSection('title', 'แก้ไขข้อมูลส่วนตัว - TEAM 3 Ticket'); ?>

<?php $__env->startSection('content'); ?>

  <div class="profile-grid">

    <!-- แก้ไขข้อมูลส่วนตัว -->
    <section>
      <h1 class="profile-section-title">แก้ไขข้อมูลส่วนตัว</h1>
      <div class="profile-panel">

      <form action="<?php echo e(route('profile.update')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>

        <div class="row-2">
          <div class="field">
            <label for="first_name">ชื่อ</label>
            <input type="text" id="first_name" name="first_name"
                   value="<?php echo e(old('first_name', $customer->first_name ?? '')); ?>"
                   class="<?php echo e($errors->has('first_name') ? 'is-error' : ''); ?>">
            <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
          <div class="field">
            <label for="last_name">นามสกุล</label>
            <input type="text" id="last_name" name="last_name"
                   value="<?php echo e(old('last_name', $customer->last_name ?? '')); ?>"
                   class="<?php echo e($errors->has('last_name') ? 'is-error' : ''); ?>">
            <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
        </div>

        <div class="field">
          <label for="phone">เบอร์โทรศัพท์</label>
          <div class="phone-field <?php echo e($errors->has('phone') ? 'is-error' : ''); ?>">
            <div class="phone-prefix">
              <span class="flag">🇹🇭</span>
              <span>+66</span>
            </div>
            <input type="tel" id="phone" name="phone"
                   value="<?php echo e(old('phone', $customer->phone ?? '')); ?>"
                   placeholder="081-234-5678">
          </div>
          <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="actions">
          <button type="submit" class="btn btn-orange" style="width:100%;border-radius:8px;padding:12px;">
            บันทึกการแก้ไขข้อมูลส่วนตัว
          </button>
        </div>
      </form>
      </div>
    </section>

    <!-- เปลี่ยนรหัสผ่าน -->
    <section>
      <h1 class="profile-section-title">เปลี่ยนรหัสผ่าน</h1>
      <div class="profile-panel">

      <form action="<?php echo e(route('profile.password.update')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>

        <div class="field">
          <label for="current_password">รหัสผ่านปัจจุบัน</label>
          <input type="password" id="current_password" name="current_password"
                 placeholder="กรอกรหัสผ่านปัจจุบันของคุณ"
                 class="<?php echo e($errors->has('current_password') ? 'is-error' : ''); ?>">
          <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="field">
          <label for="new_password">รหัสผ่านใหม่</label>
          <input type="password" id="new_password" name="new_password"
                 placeholder="กรอกรหัสผ่านใหม่"
                 class="<?php echo e($errors->has('new_password') ? 'is-error' : ''); ?>">
          <?php $__errorArgs = ['new_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="field">
          <label for="new_password_confirmation">ยืนยันรหัสผ่านใหม่</label>
          <input type="password" id="new_password_confirmation" name="new_password_confirmation"
                 placeholder="กรอกรหัสผ่านใหม่อีกครั้ง">
        </div>

        <div class="actions">
          <button type="submit" class="btn btn-navy" style="width:100%;border-radius:8px;padding:12px;">
            บันทึกรหัสผ่านใหม่
          </button>
        </div>
      </form>
      </div>
    </section>

  </div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('styles'); ?>
  .profile-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;max-width:1100px;}
  .profile-section-title{display:flex;align-items:center;gap:8px;font-size:16px;font-weight:700;margin:0 0 12px;}
  .profile-section-title::before{content:'';display:block;width:4px;height:17px;background:var(--orange);border-radius:2px;}
  .profile-panel{background:#fff;border:1px solid var(--gray-border);border-radius:8px;padding:20px 21px 23px;min-height:297px;}
  .profile-panel .card-title{display:none;}
  .profile-panel .actions{margin-top:16px;}
  .profile-panel .btn{font-size:12px;}
  @media(max-width:900px){.profile-grid{grid-template-columns:1fr;}}
  .is-error { border-color: #e8503c !important; }
  .phone-field.is-error { border-color: #e8503c !important; }
  .field-error { font-size: 11px; color: #e8503c; margin-top: 5px; }
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/profile/edit.blade.php ENDPATH**/ ?>