<?php
header('Content-Type: application/xml');

// Define the list of pages to include in the sitemap
$pages = [
    ['url' => 'https://it-future.jp/', 'priority' => '1.0', 'changefreq' => 'daily', 'file' => 'index.html'],
    ['url' => 'https://it-future.jp/about.html', 'priority' => '0.8', 'changefreq' => 'daily', 'file' => 'about.html'],
    ['url' => 'https://it-future.jp/news.html', 'priority' => '0.7', 'changefreq' => 'daily', 'file' => 'news.html'],
    ['url' => 'https://it-future.jp/saiyou.php', 'priority' => '0.8', 'changefreq' => 'daily', 'file' => 'saiyou.php'],
];

// Start XML output
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Base path for files
$base_path = __DIR__;

// Generate sitemap entries
foreach ($pages as $page) {
    $file_path = $base_path . '/' . $page['file'];
    // Get last modified time of the file, fallback to current date if file doesn't exist
    $lastmod = file_exists($file_path) ? date('Y-m-d', filemtime($file_path)) : date('Y-m-d');
    $xml .= "    <url>\n";
    $xml .= "        <loc>{$page['url']}</loc>\n";
    $xml .= "        <lastmod>{$lastmod}</lastmod>\n";
    $xml .= "        <changefreq>{$page['changefreq']}</changefreq>\n";
    $xml .= "        <priority>{$page['priority']}</priority>\n";
    $xml .= "    </url>\n";
}

// End XML
$xml .= '</urlset>';

// Save to sitemap.xml
file_put_contents($base_path . '/sitemap.xml', $xml);

// Output the XML (for testing)
echo $xml;
?>