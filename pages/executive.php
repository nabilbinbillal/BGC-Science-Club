<?php
$slug = $_GET['slug'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM executives WHERE slug = ?");
$stmt->execute([$slug]);
$executive = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$executive) {
    ?>
    <section class="py-20 bg-gray-100 dark:bg-gray-900">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-4">Executive not found</h1>
            <p class="text-gray-600 dark:text-gray-300 mb-6">The profile you are looking for might have been archived or removed.</p>
            <a href="/executives" class="inline-flex items-center px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-md">
                Back to Executives
            </a>
        </div>
    </section>
    <?php
    return;
}

$profilePic = !empty($executive['profile_pic'])
    ? '/uploads/executives/' . htmlspecialchars($executive['profile_pic'])
    : 'assets/images/default-avatar.jpg';

$rawSocialLinks = [];
if (!empty($executive['social_links'])) {
    $rawSocialLinks = json_decode($executive['social_links'], true) ?: [];
}

$legacySocials = [
    'facebook' => $executive['facebook'] ?? '',
    'twitter' => $executive['twitter'] ?? '',
    'instagram' => $executive['instagram'] ?? '',
    'linkedin' => $executive['linkedin'] ?? '',
    'website' => $executive['website'] ?? ''
];

foreach ($legacySocials as $platform => $link) {
    if (!empty($link) && empty($rawSocialLinks[$platform])) {
        $rawSocialLinks[$platform] = $link;
    }
}

$socials = [];
foreach ($rawSocialLinks as $platform => $link) {
    if (!empty($link)) {
        $socials[] = [
            'platform' => ucfirst($platform),
            'url' => $link
        ];
    }
}

$contactDetails = [
    'Role' => $executive['role'] ?? $executive['position'],
    'Department' => $executive['department'] ?? 'N/A',
    'Type' => ucfirst($executive['type'] ?? ''),
    'Session' => $executive['session'] ?? 'N/A',
    'Email' => $executive['email'] ?? '',
    'Phone' => $executive['phone'] ?? ''
];
?>

<section class="bg-gradient-to-b from-primary-50 to-white dark:from-gray-900 dark:to-gray-800 py-16">
    <div class="container mx-auto px-4 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl p-8 text-center">
                <img src="<?php echo $profilePic; ?>"
                     alt="<?php echo htmlspecialchars($executive['name']); ?>"
                     class="w-48 h-48 rounded-full object-cover mx-auto shadow-lg mb-6">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($executive['name']); ?></h1>
                <p class="text-primary-500 font-semibold mt-2"><?php echo htmlspecialchars($executive['position']); ?></p>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1"><?php echo htmlspecialchars($executive['department']); ?></p>
                <?php if (!empty($contactDetails['Email'])): ?>
                    <a href="mailto:<?php echo htmlspecialchars($contactDetails['Email']); ?>" class="inline-block mt-6 px-5 py-2 bg-primary-100 text-primary-600 rounded-full text-sm hover:bg-primary-200">
                        Email <?php echo htmlspecialchars($executive['name']); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-lg p-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">Biography</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line"><?php echo nl2br(htmlspecialchars($executive['bio'])); ?></p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-lg p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Quick Facts</h3>
                        <dl class="space-y-3">
                            <?php foreach ($contactDetails as $label => $value): ?>
                                <?php if (!empty($value) && $label !== 'Email'): ?>
                                    <div class="flex justify-between text-gray-600 dark:text-gray-300 text-sm border-b border-gray-100 dark:border-gray-800 pb-2">
                                        <dt class="font-medium"><?php echo $label; ?></dt>
                                        <dd><?php echo htmlspecialchars($value); ?></dd>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </dl>
                    </div>
                    <?php if (!empty($socials)): ?>
                        <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-lg p-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Connect</h3>
                            <div class="space-y-3">
                                <?php foreach ($socials as $social): ?>
                                    <a href="<?php echo htmlspecialchars($social['url']); ?>" target="_blank" rel="noopener"
                                       class="flex items-center justify-between px-4 py-3 rounded-2xl border border-gray-100 dark:border-gray-800 hover:border-primary-500 dark:hover:border-primary-400 transition">
                                        <span class="text-gray-800 dark:text-gray-100 font-medium"><?php echo htmlspecialchars($social['platform']); ?></span>
                                        <span class="text-primary-500 text-sm">Visit →</span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>