<h1 class="h-heading">Companies</h1>
<?php foreach ($companies as $c): ?>
<form method="post" action="/admin/company" class="card inline-form">
  <input type="hidden" name="company_id" value="<?= (int)$c['id'] ?>">
  <span class="muted"><?= e($c['domain']) ?></span>
  <input class="input" name="name" value="<?= e($c['name']) ?>">
  <button class="btn-primary" name="do" value="rename">Rename</button>
  <input class="input" name="merge_into_domain" placeholder="merge into domain…">
  <button class="btn-danger" name="do" value="merge">Merge</button>
</form>
<?php endforeach; ?>
