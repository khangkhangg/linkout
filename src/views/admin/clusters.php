<?php include __DIR__ . '/_nav.php'; ?>
<h1 class="h-heading">Coordinated reports</h1>
<p class="muted">Stories with multiple verified reports. Tight time windows or few distinct
domains relative to report count can signal a coordinated takedown of a true story.</p>
<?php if (!$clusters): ?><p class="muted">No stories with multiple reports.</p><?php endif; ?>
<table class="admin-table admin-table-lined">
  <tr><th>Story</th><th>Status</th><th>Reports</th><th>Distinct domains</th><th>Window</th><th>Last</th></tr>
  <?php foreach ($clusters as $c):
    $suspicious = (int)$c['report_count'] >= 3 && ((int)$c['span_minutes'] <= 120 || (int)$c['distinct_domains'] < (int)$c['report_count']); ?>
  <tr class="<?= $suspicious ? 'admin-row-flag' : '' ?>">
    <td><a class="link" href="/story/<?= (int)$c['story_id'] ?>"><?= e(mb_strimwidth($c['title'], 0, 55, '…')) ?></a></td>
    <td><span class="status-tag status-<?= e($c['status']) ?>"><?= e($c['status']) ?></span></td>
    <td><strong><?= (int)$c['report_count'] ?></strong></td>
    <td><?= (int)$c['distinct_domains'] ?></td>
    <td><?= $c['span_minutes'] === null ? '—' : (int)$c['span_minutes'] . ' min' ?></td>
    <td class="muted"><?= e(time_ago($c['last_report'])) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
