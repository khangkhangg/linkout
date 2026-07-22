<?php include __DIR__ . '/_nav.php'; ?>
<h1 class="h-heading">Dashboard</h1>

<div class="admin-stats">
  <?php
  $cards = [
      ['Pending reports', $stats['pending_reports'], '/admin/queue', $stats['pending_reports'] > 0],
      ['Auto-hidden stories', $stats['auto_hidden'], '/admin/stories?status=auto_hidden', $stats['auto_hidden'] > 0],
      ['Active stories', $stats['stories_total'], '/admin/stories', false],
      ['Stories today', $stats['stories_today'], null, false],
      ['Stories this week', $stats['stories_week'], null, false],
      ['Comments today', $stats['comments_today'], '/admin/comments', false],
      ['Companies', $stats['companies_total'], '/admin/companies', false],
      ['Users', $stats['users_total'], '/admin/users', false],
      ['New users today', $stats['users_today'], null, false],
      ['Banned users', $stats['banned_users'], '/admin/users', false],
  ];
  foreach ($cards as [$label, $val, $href, $alert]): ?>
    <?php $tag = $href ? 'a' : 'div'; ?>
    <<?= $tag ?> class="admin-stat <?= $alert ? 'admin-stat-alert' : '' ?>"<?= $href ? ' href="' . e($href) . '"' : '' ?>>
      <span class="admin-stat-num"><?= (int)$val ?></span>
      <span class="admin-stat-label"><?= e($label) ?></span>
    </<?= $tag ?>>
  <?php endforeach; ?>
</div>

<section class="card">
  <h2 class="h-subheading">Recent moderator actions</h2>
  <?php if (!$actions): ?><p class="muted">No actions logged yet.</p><?php endif; ?>
  <table class="admin-table">
    <?php foreach ($actions as $a): ?>
    <tr>
      <td class="muted"><?= e(time_ago($a['created_at'])) ?></td>
      <td><strong><?= e($a['admin_handle']) ?></strong></td>
      <td><?= e($a['action']) ?></td>
      <td class="muted"><?= e($a['target_type']) ?><?= $a['target_id'] ? ' #' . (int)$a['target_id'] : '' ?>
        <?= $a['detail'] ? '· ' . e($a['detail']) : '' ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <p><a class="link" href="/admin/log">Full audit log &rarr;</a></p>
</section>
