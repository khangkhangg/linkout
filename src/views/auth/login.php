<section class="auth-card card">
  <h1 class="h-heading"><?= e(t('auth_login_title')) ?></h1>
  <?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
  <?php if (!empty($notice)): ?><p class="form-notice"><?= e($notice) ?></p><?php endif; ?>
  <form method="post" action="/login">
    <input class="input" type="email" name="email" placeholder="<?= e(t('auth_email')) ?>" required>
    <input class="input" type="password" name="password" placeholder="<?= e(t('auth_password')) ?>" required>
    <button class="btn-primary" type="submit"><?= e(t('auth_login')) ?></button>
  </form>
  <p class="muted"><a class="link" href="/signup"><?= e(t('auth_signup')) ?></a> ·
     <a class="link" href="/reset"><?= e(t('auth_forgot')) ?></a></p>
</section>
