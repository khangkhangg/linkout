<section class="auth-card card">
  <h1 class="h-heading">Log in</h1>
  <?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
  <?php if (!empty($notice)): ?><p class="form-notice"><?= e($notice) ?></p><?php endif; ?>
  <form method="post" action="/login">
    <input class="input" type="email" name="email" placeholder="Email" required>
    <input class="input" type="password" name="password" placeholder="Password" required>
    <button class="btn-primary" type="submit">Log in</button>
  </form>
  <p class="muted"><a class="link" href="/signup">Sign up</a> ·
     <a class="link" href="/reset">Forgot password</a></p>
</section>
