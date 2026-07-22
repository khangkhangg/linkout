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

// ---------- story fragments to compose from ----------
$titles = [
    'Great mentorship, tiny paycheck', 'Left after the third pivot',
    'Ba năm gắn bó, ra đi nhẹ nhõm', 'Founder promised equity that never came',
    'Best first job I could have asked for', 'Đi làm như đi nghĩa vụ',
    'Overtime "culture" burned out the whole team', 'Small team, huge learning curve',
    'Quiet quitting was the only way out', 'Lương thấp nhưng học được nghề',
    'They walked me out the same day', 'A calm, boring, wonderful place to work',
    'Manager took credit for everything', 'Khách hàng là thượng đế, nhân viên là cái bóng',
    'Four-day weeks actually worked here', 'Growth stalled and so did we',
    'HR was one person and she tried her best', 'Sếp tốt nhưng công ty hết tiền',
    'The exit interview nobody read', 'Paid late three months in a row',
    'From intern to lead in two years', 'Môi trường trẻ, áp lực cao',
    'Good people, chaotic processes', 'They shrank the team by half over Zoom',
    'Honest work, honest pay, no drama', 'Promises in the interview, silence after',
    'Học được nhiều, mất ngủ cũng nhiều', 'Respectful layoff, rare and appreciated',
    'The pivot to AI killed our roadmap', 'Team nhỏ mà đầm ấm',
    'No process, all vibes', 'Left for salary, miss the people',
    'Toxic client, spineless management', 'Được tôn trọng đến ngày cuối cùng',
    'Two founders, two visions, zero direction', 'Steady ship, low ceiling',
    'Thưởng Tết đúng hẹn, tình nghĩa đủ đầy', 'Everyone wore five hats',
    'The good kind of small company', 'Ra đi vì không thấy tương lai',
    'Solid benefits for a company this size', 'Culture died when we hit 30 people',
    'They helped me find my next job', 'Chị kế toán là người tốt nhất công ty',
    'Remote-first until it wasn\'t', 'A fair place that pays what it can',
];
$bodies = [
    "The founders knew everyone's name and it showed. Salary was below market but reviews were honest and the exit was graceful. I'd go back if they could pay more.",
    "Mình vào từ những ngày đầu, văn phòng còn chưa có máy lạnh. Cực nhưng vui. Đến lúc công ty lớn hơn thì văn hóa cũng nhạt dần, nên mình chọn rời đi khi còn quý nhau.",
    "Three reorgs in one year. Every quarter a new direction, every direction a new excuse. The people were lovely; the whiplash was not.",
    "Overtime was framed as passion. Weekends were framed as commitment. When I resigned they finally offered the raise I'd asked about for a year.",
    "Học việc ở đây bằng ba năm ở nơi khác. Sếp chỉ tận tay, sai thì sửa không mắng. Chỉ tiếc là lương tăng không kịp giá nhà.",
    "Pay arrived late three months in a row with a different apology each time. Everyone stayed professional about it, which somehow made it sadder.",
    "A quiet, steady shop. No ping-pong table, no all-hands theatrics, just work done well and people going home on time. Underrated.",
    "Công ty nhỏ nên ai cũng kiêm nhiều việc. Được cái sếp sòng phẳng, thưởng phạt rõ ràng. Nghỉ rồi vẫn cà phê với đồng nghiệp cũ mỗi tháng.",
    "The client from hell owned 60% of our revenue, so their tantrums became our policies. Management never once pushed back.",
    "They laid off half the team over a video call, but the severance was fair, the reference letters were real, and the founder called each of us personally. It still hurt.",
    "Equity was always six months away. The paperwork never came, the valuation never happened, and one day the option pool quietly vanished from the deck.",
    "Môi trường trẻ, deadline dày. Ai chịu được nhịp thì lên nhanh, ai không chịu được thì âm thầm rơi rụng. Mình thuộc nhóm thứ hai và không hối hận.",
    "My manager presented my work to the board with his name on it twice. HR nodded sympathetically and did nothing. I left before the third time.",
    "From intern to team lead in under two years because they genuinely promote from within. Left only because the ceiling after that was the founder's chair.",
    "Chế độ đầy đủ, bảo hiểm đóng đúng, nghỉ phép không ai soi. Chỉ là công việc lặp lại, năm sau giống hệt năm trước, nên mình đi tìm thử thách mới.",
    "The four-day week trial became permanent and productivity went up. Proof it can work in a company this size. I left for family reasons, not the job.",
    "No documentation, no onboarding, no process — just a Slack full of tribal knowledge and a prayer. Fun for six months, exhausting after.",
    "Khi công ty gặp khó, sếp giảm lương mình trước rồi mới đến nhân viên. Đến lúc phải cắt người, anh ấy khóc trước cả tụi mình. Nghỉ mà vẫn thương.",
    "Interviews promised international projects and English-speaking clients. Reality was maintenance work on one legacy codebase. The gap between pitch and truth was the whole problem.",
    "An honest small business. They pay on time, say thank you, and leave you alone on weekends. The ambitious should look elsewhere; the tired should apply.",
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

    // companies
    $companyIds = [];
    foreach ($companies as [$name, $domain, $kind]) {
        $pdo->prepare('INSERT INTO companies (domain, name, created_by) VALUES (?,?,?)')
            ->execute([$domain, $name, $pick($userIds)]);
        $companyIds[] = (int)$pdo->lastInsertId();
    }

    // stories: 46, titles used once each, bodies reused with variation
    $storyIds = [];
    $titlePool = $titles;
    shuffle($titlePool);
    for ($i = 0; $i < 46; $i++) {
        $goodish = mt_rand(0, 1);                       // half lean positive, half negative
        $r = fn() => $goodish ? mt_rand(3, 5) : mt_rand(1, 3);
        $daysAgo = mt_rand(0, 28);
        $pdo->prepare('INSERT INTO stories (user_id, company_id, title, body,
            r_leadership, r_culture, r_benefits, r_balance, r_growth, r_exit,
            recommend, vote_score, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,0, NOW() - INTERVAL ? HOUR)')
            ->execute([
                $pick($userIds), $pick($companyIds),
                $titlePool[$i % count($titlePool)], $pick($bodies),
                $r(), $r(), $r(), $r(), $r(), $r(),
                $goodish, $daysAgo * 24 + mt_rand(0, 23),
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
