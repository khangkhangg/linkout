<?php include __DIR__ . '/_nav.php'; ?>
<h1 class="h-heading">Email blocklist</h1>
<p class="muted">Corporate emails on these domains (and their subdomains) can't be used to
report stories — on top of the built-in free-mail list. Add disposable-mail providers here.</p>
<form method="post" action="/admin/blocklist" class="admin-filter">
  <input class="input" name="domain" placeholder="e.g. tempmail.io" required>
  <button class="btn-primary" name="do" value="add" type="submit">Block domain</button>
</form>
<table class="admin-table admin-table-lined">
  <tr><th>Domain</th><th>Added</th><th></th></tr>
  <?php foreach ($domains as $d): ?>
  <tr>
    <td><?= e($d['domain']) ?></td>
    <td class="muted"><?= e(time_ago($d['created_at'])) ?></td>
    <td><form method="post" action="/admin/blocklist" class="inline-form">
      <input type="hidden" name="domain" value="<?= e($d['domain']) ?>">
      <button class="link-btn admin-danger-link" name="do" value="remove">Remove</button>
    </form></td>
  </tr>
  <?php endforeach; ?>
</table>
