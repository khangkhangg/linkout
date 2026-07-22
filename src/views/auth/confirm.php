<section class="auth-card card">
  <h1 class="h-heading"><?= $confirmed ? 'Email confirmed' : 'Link invalid or already used' ?></h1>
  <p><a class="link" href="<?= $confirmed ? '/login' : '/signup' ?>">
     <?= $confirmed ? 'Log in' : 'Back to signup' ?></a></p>
</section>
