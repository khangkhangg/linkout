#!/usr/bin/env php
<?php
// Seed demo content: fictional small/mid companies + stories + votes + comments.
// All company names and domains are invented — never attach seed stories to
// real businesses. Refuses to run on a DB that already has stories unless
// --force is given. Usage: php bin/seed.php [--db=NAME] [--force]
$dbName = null;
$force = false;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--db=')) $dbName = substr($arg, 5);
    if ($arg === '--force') $force = true;
}
require __DIR__ . '/../src/bootstrap.php';

if ($dbName) {
    $pdo = new PDO(
        preg_replace('/dbname=\w+/', "dbname=$dbName", config('db_dsn')),
        config('db_user'), config('db_pass'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $pdo->exec("SET time_zone = '+00:00'");
} else {
    $pdo = db();
}

if (!$force && (int)$pdo->query('SELECT COUNT(*) FROM stories')->fetchColumn() > 0) {
    fwrite(STDERR, "stories table is not empty — pass --force to seed anyway\n");
    exit(1);
}

// ---------- fictional companies: small & mid-size only, varied industries ----------
$companies = [
    ['Mintleaf Studio',        'mintleaf-studio.com',      'design agency'],
    ['Song Xanh Logistics',    'songxanhlogistics.vn',     'logistics SME'],
    ['Brightpath Edtech',      'brightpath-edu.com',       'edtech startup'],
    ['Cà Phê Nhà Mây',         'nhamaycoffee.vn',          'cafe chain'],
    ['Quill & Co Accounting',  'quillandco.com',           'accounting firm'],
    ['Lumo Games',             'lumogames.io',             'indie game studio'],
    ['An Phú Garment',         'anphugarment.vn',          'garment workshop'],
    ['Harbor Lane Travel',     'harborlanetravel.com',     'travel agency'],
    ['Zenbyte Software',       'zenbyte.dev',              'software house'],
    ['Mai Spa & Wellness',     'maispawellness.vn',        'spa chain'],
    ['Copperfield Print',      'copperfieldprint.com',     'print shop'],
    ['Vườn Lành Organics',     'vuonlanh.vn',              'agri co-op'],
    ['Northwind BPO',          'northwindbpo.com',         'outsourcing SME'],
    ['Tia Chớp Delivery',      'tiachopdelivery.vn',       'delivery startup'],
    ['Foxglove Media',         'foxglovemedia.co',         'content agency'],
    ['Kiến Vàng Interiors',    'kienvanginteriors.vn',     'interior contractor'],
    ['Pebble Fintech',         'pebblefin.app',            'fintech micro-startup'],
    ['Sunrise Language House', 'sunriselanguage.vn',       'language school'],
];

// ---------- long-form stories from data/seed_stories/*.php ----------
// Each entry: ['kind' => company kind, 'recommend' => 0|1, 'title', 'body'].
// Written in four voices (gen-Z EN, mid-career EN, VI mixed ages, veterans
// EN/VI); bodies 350-900 words; companies referenced only generically.
$storyData = [];
foreach (glob(__DIR__ . '/../data/seed_stories/*.php') as $file) {
    $storyData = array_merge($storyData, require $file);
}
if (count($storyData) < 43) {
    fwrite(STDERR, 'expected 43+ seed stories, found ' . count($storyData) . "\n");
    exit(1);
}
$comments = [
    'This matches what I heard from two other people there.',
    'Cảm ơn bạn đã chia sẻ, mình đang cân nhắc offer ở đây.',
    'Same experience on a different team, sadly.',
    'The severance detail is important — few small companies do that.',
    'Mình làm ở đây 2 năm, xác nhận là đúng.',
    'Did this change after the new manager joined?',
    'Appreciate the balanced take, not just venting.',
    'Câu chuyện giống hệt công ty cũ của mình.',
    'Đọc mà tưởng mình viết lúc nào không nhớ.',
    'This should be required reading before anyone signs there.',
];
$comments = [
    'This matches what I heard from two other people there.',
    'Cảm ơn bạn đã chia sẻ, mình đang cân nhắc offer ở đây.',
    'Same experience on a different team, sadly.',
    'The severance detail is important — few small companies do that.',
    'Mình làm ở đây 2 năm, xác nhận là đúng.',
    'Did this change after the new manager joined?',
    'Appreciate the balanced take, not just venting.',
    'Câu chuyện giống hệt công ty cũ của mình.',
];

mt_srand(20260723);
$pick = fn(array $a) => $a[mt_rand(0, count($a) - 1)];

$pdo->beginTransaction();
try {
    // users
    $userIds = [];
    for ($i = 1; $i <= 14; $i++) {
        $pdo->prepare('INSERT INTO users (email, password_hash, handle, email_verified_at, created_at)
            VALUES (?,?,?, NOW() - INTERVAL ? DAY, NOW() - INTERVAL ? DAY)')
            ->execute(["seed$i@linkout-seed.example", password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT),
                       generate_handle() . $i, 40 + $i, 40 + $i]);
        $userIds[] = (int)$pdo->lastInsertId();
    }

    // companies, indexed by kind so stories land on a matching company
    $companyIds = [];
    $companiesByKind = [];
    foreach ($companies as [$name, $domain, $kind]) {
        $pdo->prepare('INSERT INTO companies (domain, name, created_by) VALUES (?,?,?)')
            ->execute([$domain, $name, $pick($userIds)]);
        $id = (int)$pdo->lastInsertId();
        $companyIds[] = $id;
        $companiesByKind[$kind][] = $id;
    }

    // stories: one row per long-form entry, attached to a company of its kind
    $storyIds = [];
    shuffle($storyData);
    foreach ($storyData as $s) {
        $rec = (int)$s['recommend'];
        $r = fn() => $rec ? mt_rand(3, 5) : mt_rand(1, 3);
        $daysAgo = mt_rand(0, 28);
        $companyId = isset($companiesByKind[$s['kind']])
            ? $pick($companiesByKind[$s['kind']])
            : $pick($companyIds);
        $pdo->prepare('INSERT INTO stories (user_id, company_id, title, body,
            r_leadership, r_culture, r_benefits, r_balance, r_growth, r_exit,
            recommend, vote_score, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,0, NOW() - INTERVAL ? HOUR)')
            ->execute([
                $pick($userIds), $companyId,
                $s['title'], trim($s['body']),
                $r(), $r(), $r(), $r(), $r(), $r(),
                $rec, $daysAgo * 24 + mt_rand(0, 23),
            ]);
        $storyIds[] = (int)$pdo->lastInsertId();
    }

    // votes: each user votes on ~60% of stories, mostly up on positive ones
    foreach ($storyIds as $sid) {
        $score = 0;
        foreach ($userIds as $uid) {
            if (mt_rand(1, 10) > 6) continue;
            $v = mt_rand(1, 10) > 3 ? 1 : -1;
            $pdo->prepare('INSERT IGNORE INTO votes (user_id, story_id, value) VALUES (?,?,?)')
                ->execute([$uid, $sid, $v]);
            $score += $v;
        }
        $pdo->prepare('UPDATE stories SET vote_score = ? WHERE id = ?')->execute([$score, $sid]);
    }

    // comments on ~third of stories
    foreach ($storyIds as $sid) {
        if (mt_rand(1, 3) !== 1) continue;
        $n = mt_rand(1, 3);
        for ($j = 0; $j < $n; $j++) {
            $pdo->prepare('INSERT INTO comments (story_id, user_id, body, created_at)
                VALUES (?,?,?, NOW() - INTERVAL ? HOUR)')
                ->execute([$sid, $pick($userIds), $pick($comments), mt_rand(0, 240)]);
        }
    }

    $pdo->commit();
} catch (Throwable $t) {
    $pdo->rollBack();
    throw $t;
}

echo 'seeded: ' . count($companyIds) . ' companies, ' . count($storyIds) . " stories, "
   . (int)$pdo->query('SELECT COUNT(*) FROM votes')->fetchColumn() . ' votes, '
   . (int)$pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn() . " comments\n";
