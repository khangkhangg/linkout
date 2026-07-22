<?php include __DIR__ . '/_nav.php'; ?>
<h1 class="h-heading">Users</h1>
<form class="admin-filter" method="get" action="/admin/users">
  <input class="input" type="search" name="q" placeholder="Search handle or email" value="<?= e($q ?? '') ?>">
  <button class="btn-primary" type="submit">Search</button>
</form>
<table class="admin-table admin-table-lined">
  <tr><th>Handle</th><th>Email</th><th>Role</th><th>Verified</th><th>Banned</th><th></th></tr>
  <?php foreach ($users as $usr): ?>
  <tr>
    <td><a class="link" href="/admin/user/<?= (int)$usr['id'] ?>"><?= e($usr['handle']) ?></a></td>
    <td class="muted"><?= e($usr['email']) ?></td>
    <td><?= $usr['role'] === 'admin' ? '<strong>admin</strong>' : 'user' ?></td>
    <td><?= $usr['email_verified_at'] ? 'yes' : '<span class="muted">no</span>' ?></td>
    <td><?= $usr['banned_at'] ? '<span class="status-tag status-removed">yes</span>' : '—' ?></td>
    <td><form method="post" action="/admin/ban" class="inline-form">
      <input type="hidden" name="user_id" value="<?= (int)$usr['id'] ?>">
      <input type="hidden" name="banned" value="<?= $usr['banned_at'] ? 0 : 1 ?>">
      <button class="link-btn"><?= $usr['banned_at'] ? 'Unban' : 'Ban' ?></button>
    </form></td>
  </tr>
  <?php endforeach; ?>
</table>
