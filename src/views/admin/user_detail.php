<?php include __DIR__ . '/_nav.php';
$u = $detail['user']; ?>
<p><a class="link" href="/admin/users">&larr; Users</a></p>
<div class="admin-user-head">
  <span class="avatar avatar-lg" style="background: <?= e(avatar_color($u['handle'])) ?>"><?= e(mb_strtoupper(mb_substr($u['handle'], 0, 1))) ?></span>
  <div>
    <h1 class="h-heading"><?= e($u['handle']) ?></h1>
    <p class="muted"><?= e($u['email']) ?> · <?= e($u['role']) ?> ·
      joined <?= e(time_ago($u['created_at'])) ?>
      <?php if ($u['banned_at']): ?> · <span class="status-tag status-removed">banned</span><?php endif; ?>
      <?php if (!$u['email_verified_at']): ?> · <span class="muted">unverified</span><?php endif; ?></p>
  </div>
</div>

<div class="admin-filter">
  <form method="post" action="/admin/ban" class="inline-form">
    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
    <input type="hidden" name="banned" value="<?= $u['banned_at'] ? 0 : 1 ?>">
    <input type="hidden" name="return" value="/admin/user/<?= (int)$u['id'] ?>">
    <button class="btn-danger"><?= $u['banned_at'] ? 'Unban' : 'Ban' ?></button>
  </form>
  <?php if ($u['role'] !== 'admin'): ?>
  <form method="post" action="/admin/role" class="inline-form">
    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
    <input type="hidden" name="role" value="admin">
    <button class="btn-primary">Make admin</button>
  </form>
  <?php else: ?>
  <form method="post" action="/admin/role" class="inline-form">
    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
    <input type="hidden" name="role" value="user">
    <button class="link-btn admin-danger-link">Revoke admin</button>
  </form>
  <?php endif; ?>
</div>

<p class="muted"><?= (int)$detail['counts']['votes'] ?> votes cast ·
  <?= (int)$detail['counts']['reports_filed'] ?> reports filed</p>

<section class="card">
  <h2 class="h-subheading"><?= count($detail['stories']) ?> stories</h2>
  <table class="admin-table">
    <?php foreach ($detail['stories'] as $s): ?>
    <tr>
      <td><a class="link" href="/story/<?= (int)$s['id'] ?>"><?= e(mb_strimwidth($s['title'], 0, 55, '…')) ?></a></td>
      <td class="muted"><?= e($s['company_name']) ?></td>
      <td><span class="status-tag status-<?= e($s['status']) ?>"><?= e($s['status']) ?></span></td>
      <td class="muted"><?= e(time_ago($s['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</section>

<section class="card">
  <h2 class="h-subheading"><?= count($detail['comments']) ?> comments</h2>
  <table class="admin-table">
    <?php foreach ($detail['comments'] as $c): ?>
    <tr>
      <td><a class="link" href="/story/<?= (int)$c['story_id'] ?>#comments"><?= e(mb_strimwidth($c['body'], 0, 70, '…')) ?></a></td>
      <td><span class="status-tag status-<?= e($c['status']) ?>"><?= e($c['status']) ?></span></td>
      <td class="muted"><?= e(time_ago($c['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</section>
