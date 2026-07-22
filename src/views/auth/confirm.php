<section class="auth-card card">
  <h1 class="h-heading"><?= $confirmed ? e(t('auth_confirmed')) : e(t('auth_confirm_bad')) ?></h1>
  <p><a class="link" href="<?= $confirmed ? '/login' : '/signup' ?>">
     <?= $confirmed ? e(t('auth_login')) : 'Back to signup' ?></a></p>
</section>
