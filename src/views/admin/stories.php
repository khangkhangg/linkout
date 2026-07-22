<?php include __DIR__ . '/_nav.php'; ?>
<h1 class="h-heading">Stories</h1>
<form class="admin-filter" method="get" action="/admin/stories">
  <input class="input" type="search" name="q" placeholder="Search title or company" value="<?= e($q ?? '') ?>">
  <select class="input" name="status">
    <?php foreach (['' => 'All statuses', 'active' => 'Active', 'auto_hidden' => 'Auto-hidden', 'removed' => 'Removed'] as $v => $lbl): ?>
      <option value="<?= $v ?>" <?= ($status ?? '') === $v ? 'selected' : '' ?>><?= e($lbl) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn-primary" type="submit">Filter</button>
</form>
<?php if (!$stories): ?><p class="muted">No stories match.</p><?php endif; ?>
<table class="admin-table admin-table-lined">
  <tr><th>Story</th><th>Company</th><th>By</th><th>Status</th><th>Score</th><th>Reports</th><th>Actions</th></tr>
  <?php foreach ($stories as $s): ?>
  <tr>
    <td><a class="link" href="/story/<?= (int)$s['id'] ?>"><?= e(mb_strimwidth($s['title'], 0, 60, '…')) ?></a>
      <div class="muted"><?= e(time_ago($s['created_at'])) ?></div></td>
    <td><?= e($s['company_name']) ?></td>
    <td class="muted"><?= e($s['handle']) ?></td>
    <td><span class="status-tag status-<?= e($s['status']) ?>"><?= e($s['status']) ?></span></td>
    <td><?= (int)$s['vote_score'] ?></td>
    <td><?= (int)$s['report_count'] ? '<span class="admin-badge">' . (int)$s['report_count'] . '</span>' : '—' ?></td>
    <td>
      <form method="post" action="/admin/story" class="inline-form">
        <input type="hidden" name="story_id" value="<?= (int)$s['id'] ?>">
        <?php if ($s['status'] !== 'active'): ?><button class="link-btn" name="status" value="active">Activate</button><?php endif; ?>
        <?php if ($s['status'] !== 'auto_hidden'): ?><button class="link-btn" name="status" value="auto_hidden">Hide</button><?php endif; ?>
        <?php if ($s['status'] !== 'removed'): ?><button class="link-btn admin-danger-link" name="status" value="removed">Remove</button><?php endif; ?>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
