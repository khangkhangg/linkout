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
  <h1 class="h-heading"><?= e(t('auth_join_title')) ?></h1>
  <p class="muted auth-intro"><?= e(t('auth_email_note')) ?></p>
  <?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="/signup">
    <input class="input" type="email" name="email" placeholder="<?= e(t('auth_email')) ?>" required
           value="<?= e($_POST['email'] ?? '') ?>">
    <input class="input" type="password" name="password" placeholder="<?= e(t('auth_password')) ?>"
           minlength="8" required>
    <button class="btn-primary" type="submit"><?= e(t('auth_create')) ?></button>
  </form>
  <p class="muted auth-foot"><?= e(t('auth_have_account')) ?> <a class="link" href="/login"><?= e(t('auth_login')) ?></a></p>
</section>
