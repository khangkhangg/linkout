<?php include __DIR__ . '/_nav.php'; ?>
<h1 class="h-heading">Audit log</h1>
<?php if (!$actions): ?><p class="muted">No actions logged yet.</p><?php endif; ?>
<table class="admin-table admin-table-lined">
  <tr><th>When</th><th>Admin</th><th>Action</th><th>Target</th><th>Detail</th></tr>
  <?php foreach ($actions as $a): ?>
  <tr>
    <td class="muted"><?= e($a['created_at']) ?></td>
    <td><strong><?= e($a['admin_handle']) ?></strong></td>
    <td><?= e($a['action']) ?></td>
    <td class="muted"><?= e($a['target_type']) ?><?= $a['target_id'] ? ' #' . (int)$a['target_id'] : '' ?></td>
    <td class="muted"><?= e($a['detail']) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
