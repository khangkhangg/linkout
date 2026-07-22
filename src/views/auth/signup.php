<section class="auth-card card">
  <h1 class="h-heading"><?= e(t('auth_join_title')) ?></h1>
  <p class="muted"><?= e(t('auth_email_note')) ?></p>
  <?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="/signup">
    <input class="input" type="email" name="email" placeholder="<?= e(t('auth_email')) ?>" required
           value="<?= e($_POST['email'] ?? '') ?>">
    <input class="input" type="password" name="password" placeholder="<?= e(t('auth_password')) ?>"
           minlength="8" required>
    <button class="btn-primary" type="submit"><?= e(t('auth_create')) ?></button>
  </form>
  <p class="muted"><?= e(t('auth_have_account')) ?> <a class="link" href="/login"><?= e(t('auth_login')) ?></a></p>
</section>
