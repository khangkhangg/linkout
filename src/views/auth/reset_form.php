<section class="auth-card card">
  <h1 class="h-heading"><?= e(t('reset_new_title')) ?></h1>
  <?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="/reset/<?= e($token) ?>">
    <input class="input" type="password" name="password" minlength="8" required
           placeholder="<?= e(t('auth_password')) ?>">
    <button class="btn-primary" type="submit"><?= e(t('reset_set')) ?></button>
  </form>
</section>
