<h1 class="h-heading">Users</h1>
<table class="admin-table">
  <tr><th>id</th><th>handle</th><th>email</th><th>verified</th><th>banned</th><th></th></tr>
  <?php foreach ($users as $usr): ?>
  <tr>
    <td><?= (int)$usr['id'] ?></td><td><?= e($usr['handle']) ?></td>
    <td><?= e($usr['email']) ?></td>
    <td><?= $usr['email_verified_at'] ? 'yes' : 'no' ?></td>
    <td><?= $usr['banned_at'] ? e($usr['banned_at']) : '—' ?></td>
    <td><form method="post" action="/admin/ban" class="inline-form">
      <input type="hidden" name="user_id" value="<?= (int)$usr['id'] ?>">
      <input type="hidden" name="banned" value="<?= $usr['banned_at'] ? 0 : 1 ?>">
      <button class="link-btn"><?= $usr['banned_at'] ? 'Unban' : 'Ban' ?></button>
    </form></td>
  </tr>
  <?php endforeach; ?>
</table>
