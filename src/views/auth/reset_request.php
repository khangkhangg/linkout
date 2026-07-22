<section class="auth-card card">
  <h1 class="h-heading"><?= e(t('reset_title')) ?></h1>
  <?php if (!empty($notice)): ?><p class="form-notice"><?= e($notice) ?></p><?php endif; ?>
  <form method="post" action="/reset">
    <input class="input" type="email" name="email" placeholder="<?= e(t('auth_email')) ?>" required>
    <button class="btn-primary" type="submit"><?= e(t('reset_send')) ?></button>
  </form>
</section>
