<section class="auth-card card">
  <h1 class="h-heading">Reset password</h1>
  <?php if (!empty($notice)): ?><p class="form-notice"><?= e($notice) ?></p><?php endif; ?>
  <form method="post" action="/reset">
    <input class="input" type="email" name="email" placeholder="Your account email" required>
    <button class="btn-primary" type="submit">Send reset link</button>
  </form>
</section>
