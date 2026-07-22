<?php include __DIR__ . '/_nav.php'; ?>
<h1 class="h-heading">Comments</h1>
<?php if (!$comments): ?><p class="muted">No comments yet.</p><?php endif; ?>
<?php foreach ($comments as $c): ?>
<div class="card admin-comment">
  <p class="muted"><?= e($c['handle']) ?> · <?= e(time_ago($c['created_at'])) ?> ·
    on <a class="link" href="/story/<?= (int)$c['story_id'] ?>"><?= e(mb_strimwidth($c['story_title'], 0, 50, '…')) ?></a> ·
    <span class="status-tag status-<?= e($c['status']) ?>"><?= e($c['status']) ?></span></p>
  <p><?= nl2br(e($c['body'])) ?></p>
  <form method="post" action="/admin/comment" class="inline-form">
    <input type="hidden" name="comment_id" value="<?= (int)$c['id'] ?>">
    <?php if ($c['status'] === 'active'): ?>
      <button class="link-btn admin-danger-link" name="status" value="removed">Remove</button>
    <?php else: ?>
      <button class="link-btn" name="status" value="active">Restore</button>
    <?php endif; ?>
  </form>
</div>
<?php endforeach; ?>
