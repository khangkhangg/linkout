<?php include __DIR__ . '/_nav.php'; ?>
<h1 class="h-heading">Companies</h1>
<?php if (!empty($error)): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
<?php foreach ($companies as $c): ?>
<div class="card admin-company">
  <div class="admin-company-head">
    <a class="link" href="/company/<?= e($c['domain']) ?>"><?= e($c['domain']) ?></a>
    <span class="muted"><?= (int)$c['story_count'] ?> stories</span>
  </div>
  <form method="post" action="/admin/company" class="admin-filter">
    <input type="hidden" name="company_id" value="<?= (int)$c['id'] ?>">
    <input class="input" name="name" value="<?= e($c['name']) ?>">
    <button class="btn-primary" name="do" value="rename">Rename</button>
    <input class="input" name="merge_into_domain" placeholder="merge into domain…">
    <button class="btn-danger" name="do" value="merge">Merge</button>
    <?php if ((int)$c['story_count'] === 0): ?>
      <button class="link-btn admin-danger-link" name="do" value="delete"
              onclick="return confirm('Delete this empty company?')">Delete</button>
    <?php endif; ?>
  </form>
</div>
<?php endforeach; ?>
