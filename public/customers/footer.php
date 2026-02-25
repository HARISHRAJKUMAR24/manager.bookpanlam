<?php
// manager.bookpanlam/public/customers/footer.php
header("Access-Control-Allow-Origin: http://localhost:3001");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../config/config.php";
require_once "../../src/database.php";

$pdo = getDbConnection();

// Get slug from query parameter
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    echo json_encode([
        "success" => false,
        "message" => "Site slug is required"
    ]);
    exit();
}

try {
    // Debug: Log the slug
    error_log("Footer API called with slug: " . $slug);

    // ✅ FIXED: Use site_slug column (correct column name from your users table)
    $userSql = "SELECT user_id, site_name FROM users WHERE site_slug = :slug LIMIT 1";
    $userStmt = $pdo->prepare($userSql);
    $userStmt->execute([':slug' => $slug]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        error_log("User not found for slug: " . $slug);
        echo json_encode([
            "success" => false,
            "message" => "Site not found"
        ]);
        exit();
    }

    $user_id = $user['user_id'];
    $site_name = $user['site_name'] ?? 'BookPanlam';

    // Get site settings
    $settingsSql = "SELECT * FROM site_settings WHERE user_id = :user_id LIMIT 1";
    $settingsStmt = $pdo->prepare($settingsSql);
    $settingsStmt->execute([':user_id' => $user_id]);
    $siteSettings = $settingsStmt->fetch(PDO::FETCH_ASSOC);

    // Define quick links
    $quickLinks = [
        ['label' => 'Home', 'href' => "/{$slug}"],
        ['label' => 'About Us', 'href' => "/{$slug}/about"],
        ['label' => 'Services', 'href' => "/{$slug}/services"],
        ['label' => 'Contact Us', 'href' => "/{$slug}/contact"],
        ['label' => 'Privacy Policy', 'href' => "/{$slug}/privacy-policy"],
        ['label' => 'Terms & Conditions', 'href' => "/{$slug}/terms-conditions"]
    ];

    // Define social links with icons
    $socialPlatforms = [
        ['platform' => 'facebook', 'icon' => '/social-icons/facebook.png'],
        ['platform' => 'twitter', 'icon' => '/social-icons/twitter.png'],
        ['platform' => 'instagram', 'icon' => '/social-icons/instagram.png'],
        ['platform' => 'linkedin', 'icon' => '/social-icons/linkedin.png'],
        ['platform' => 'youtube', 'icon' => '/social-icons/youtube.png'],
        ['platform' => 'pinterest', 'icon' => '/social-icons/pinterest.png']
    ];

    $socialLinks = [];
    foreach ($socialPlatforms as $social) {
        $url = $siteSettings ? ($siteSettings[$social['platform']] ?? null) : null;
        if ($url && !empty($url)) {
            // Ensure URL has protocol
            if (!preg_match('/^https?:\/\//', $url)) {
                $url = 'https://' . $url;
            }
            $socialLinks[] = [
                'platform' => $social['platform'],
                'url' => $url,
                'icon' => $social['icon']
            ];
        }
    }

    // Add WhatsApp if available
    if ($siteSettings && !empty($siteSettings['whatsapp'])) {
        $whatsapp = $siteSettings['whatsapp'];
        // Clean phone number
        $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);
        if (!empty($whatsapp)) {
            $socialLinks[] = [
                'platform' => 'whatsapp',
                'url' => "https://wa.me/{$whatsapp}",
                'icon' => '/social-icons/whatsapp.png'
            ];
        }
    }

    // Disclaimer and copyright
    $disclaimer = "Ztorespot.com, a brand of 1Milestone Technology Solution Pvt Ltd, is not liable for the sale of products or materials. We provide complete DIY platform which is used to connect Merchant & buyers, but any transactions or purchases made through our platform are the sole responsibility of the respective sellers and buyers. Please exercise caution and conduct due when engaging in any transactions on our website.";
    
    $currentYear = date('Y');
    $copyright = "© {$currentYear} {$site_name}. All rights reserved. Powered by BookPanlam";

    echo json_encode([
        'success' => true,
        'data' => [
            'siteSettings' => $siteSettings,
            'quickLinks' => $quickLinks,
            'socialLinks' => $socialLinks,
            'disclaimer' => $disclaimer,
            'copyright' => $copyright
        ]
    ]);

} catch (PDOException $e) {
    error_log("Footer API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>