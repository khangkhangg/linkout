<section class="auth-card card">
  <h1 class="h-heading">Join LinkOut</h1>
  <p class="muted">Your email is never shown. You get a random handle.</p>
  <?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="/signup">
    <input class="input" type="email" name="email" placeholder="Email" required
           value="<?= e($_POST['email'] ?? '') ?>">
    <input class="input" type="password" name="password" placeholder="Password (8+ chars)"
           minlength="8" required>
    <button class="btn-primary" type="submit">Create account</button>
  </form>
  <p class="muted">Already a member? <a class="link" href="/login">Log in</a></p>
</section>
