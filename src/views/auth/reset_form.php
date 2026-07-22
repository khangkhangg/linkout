<section class="auth-card card">
  <h1 class="h-heading">Choose a new password</h1>
  <?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="/reset/<?= e($token) ?>">
    <input class="input" type="password" name="password" minlength="8" required
           placeholder="New password (8+ chars)">
    <button class="btn-primary" type="submit">Set password</button>
  </form>
</section>
