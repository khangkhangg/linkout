<section class="auth-card card">
  <div class="auth-brand">
    <svg width="40" height="40" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <rect x="2.5" y="2.5" width="17" height="25" rx="5.5" fill="#fdedea"/>
      <path d="M8 2.5h6.5a5.5 5.5 0 0 1 5.5 5.5v14a5.5 5.5 0 0 1-5.5 5.5H8"
            stroke="#f73b20" stroke-width="2.6" stroke-linecap="round" fill="none"/>
      <path d="M12.5 15h14.5m0 0-4.6-4.6M27 15l-4.6 4.6"
            stroke="#f73b20" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </div>
  <h1 class="h-heading"><?= e(t('reset_title')) ?></h1>
  <?php if (!empty($notice)): ?><p class="form-notice"><?= e($notice) ?></p><?php endif; ?>
  <?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="/reset">
    <input class="input" type="email" name="email" placeholder="<?= e(t('auth_email')) ?>" required>
    <button class="btn-primary" type="submit"><?= e(t('reset_send')) ?></button>
  </form>
  <p class="muted auth-foot"><a class="link" href="/login"><?= e(t('auth_login')) ?></a></p>
</section>
