<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

$sql = "
    SELECT id, team1_name, team1_logo, team2_name, team2_logo, league, match_time, views
    FROM matches
    WHERE status = 'active'
      AND match_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY match_time ASC
";
$result = $db->query($sql);
$matches = $result->fetch_all(MYSQLI_ASSOC);

function match_state(string $matchTime): array {
    $start = new DateTimeImmutable($matchTime);
    $now = new DateTimeImmutable('now');
    $diff = $now->getTimestamp() - $start->getTimestamp();

    if ($diff >= -900 && $diff <= 3 * 3600) {
        return ['live', 'LIVE'];
    }

    if ($diff > 3 * 3600) {
        return ['ended', 'انتهت'];
    }

    return ['upcoming', 'قريباً'];
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e(SITE_NAME) ?></title>
    <meta name="description" content="مباريات اليوم والبث المباشر">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="index.php">
            <span class="brand-mark">★</span>
            <span><?= e(SITE_NAME) ?></span>
        </a>
        <nav class="nav">
            <a href="index.php">الرئيسية</a>
            <a href="#matches">المباريات</a>
        </nav>
    </div>
</header>

<main class="container">
    <section class="hero">
        <p class="eyebrow">مباريات اليوم</p>
        <h1>تابع أهم المباريات من مكان واحد</h1>
        <p>اختر المباراة واضغط على شاهد الآن.</p>
    </section>

    <section id="matches" class="matches-section">
        <div class="section-title">
            <h2>جدول المباريات</h2>
            <span><?= count($matches) ?> مباراة</span>
        </div>

        <?php if (!$matches): ?>
            <div class="empty-state">
                <div class="empty-icon">⚽</div>
                <h3>لا توجد مباريات متاحة حالياً</h3>
                <p>ارجع لاحقاً لمتابعة أحدث المباريات.</p>
            </div>
        <?php else: ?>
            <div class="matches-grid">
                <?php foreach ($matches as $match): ?>
                    <?php [$stateClass, $stateText] = match_state($match['match_time']); ?>
                    <article class="match-card">
                        <div class="match-meta">
                            <span class="league"><?= e($match['league']) ?></span>
                            <span class="status <?= e($stateClass) ?>"><?= e($stateText) ?></span>
                        </div>

                        <div class="match-time">
                            <?= e((new DateTimeImmutable($match['match_time']))->format('H:i')) ?>
                        </div>

                        <div class="teams">
                            <div class="team">
                                <?php if (!empty($match['team1_logo'])): ?>
                                    <img src="uploads/teams/<?= e(safe_logo_name($match['team1_logo'])) ?>" alt="<?= e($match['team1_name']) ?>">
                                <?php else: ?>
                                    <div class="logo-fallback"><?= e(mb_substr($match['team1_name'], 0, 1, 'UTF-8')) ?></div>
                                <?php endif; ?>
                                <span><?= e($match['team1_name']) ?></span>
                            </div>

                            <div class="versus">VS</div>

                            <div class="team">
                                <?php if (!empty($match['team2_logo'])): ?>
                                    <img src="uploads/teams/<?= e(safe_logo_name($match['team2_logo'])) ?>" alt="<?= e($match['team2_name']) ?>">
                                <?php else: ?>
                                    <div class="logo-fallback"><?= e(mb_substr($match['team2_name'], 0, 1, 'UTF-8')) ?></div>
                                <?php endif; ?>
                                <span><?= e($match['team2_name']) ?></span>
                            </div>
                        </div>

                        <a class="watch-btn" href="go.php?id=<?= (int)$match['id'] ?>" rel="nofollow">
                            شاهد الآن
                        </a>

                        <div class="views">المشاهدات: <?= number_format((int)$match['views']) ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="footer">
    <div class="container">
        <p>© <?= date('Y') ?> <?= e(SITE_NAME) ?> — جميع الحقوق محفوظة.</p>
    </div>
</footer>

<script src="assets/js/app.js"></script>
</body>
</html>
