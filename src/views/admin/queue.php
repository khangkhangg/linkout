<h1 class="h-heading">Report queue</h1>
<?php if (!$queue): ?><p class="muted">Queue is empty.</p><?php endif; ?>
<?php foreach ($queue as $r): ?>
<div class="card admin-report">
  <p>
    <?php if ((int)$r['is_company_match']): ?><span class="match-badge">FROM COMPANY</span><?php endif; ?>
    <strong><?= e($r['reason']) ?></strong> on
    <a class="link" href="/story/<?= (int)$r['story_id'] ?>"><?= e($r['story_title']) ?></a>
    <span class="muted">(story: <?= e($r['story_status']) ?>)</span>
  </p>
  <?php if ($r['reason_text']): ?><p><?= e($r['reason_text']) ?></p><?php endif; ?>
  <p class="muted">reporter <?= e($r['reporter_handle']) ?> · <?= e($r['corp_email']) ?>
     · <?= e($r['created_at']) ?></p>
  <form method="post" action="/admin/action" class="inline-form">
    <input type="hidden" name="story_id" value="<?= (int)$r['story_id'] ?>">
    <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
    <button class="btn-primary" name="do" value="restore">Restore story</button>
    <button class="btn-danger" name="do" value="remove">Remove story</button>
    <button class="link-btn" name="do" value="dismiss">Dismiss report</button>
  </form>
</div>
<?php endforeach; ?>
<p><a class="link" href="/admin/users">Users</a> · <a class="link" href="/admin/companies">Companies</a></p>
