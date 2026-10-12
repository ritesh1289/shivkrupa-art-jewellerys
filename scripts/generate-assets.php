<?php
/**
 * SHIVKRUPA ART JEWELLERY — High-Definition Luxury Visual Asset Generator
 * Creates elegant jewellery imagery with gold filigree frames, luxury gradients, and typography
 */

$productsDir = dirname(__DIR__) . '/uploads/products/';
$assetsDir = dirname(__DIR__) . '/assets/images/';

if (!is_dir($productsDir)) {
    mkdir($productsDir, 0777, true);
}
if (!is_dir($assetsDir)) {
    mkdir($assetsDir, 0777, true);
}

$items = [
    // Categories
    ['name' => 'Rings', 'file' => 'category-rings.jpg', 'type' => 'cat', 'icon' => '💍', 'sub' => 'Diamond & Heritage Gold'],
    ['name' => 'Earrings', 'file' => 'category-earrings.jpg', 'type' => 'cat', 'icon' => '✨', 'sub' => 'Chandbalis & Jhumkas'],
    ['name' => 'Necklaces', 'file' => 'category-necklaces.jpg', 'type' => 'cat', 'icon' => '👑', 'sub' => 'Royal Chokers & Haars'],
    ['name' => 'Bracelets', 'file' => 'category-bracelets.jpg', 'type' => 'cat', 'icon' => '⭐', 'sub' => 'Tennis & Filigree Cuffs'],
    ['name' => 'Bangles', 'file' => 'category-bangles.jpg', 'type' => 'cat', 'icon' => '💫', 'sub' => 'Kadas & Meenakari'],
    ['name' => 'Pendants', 'file' => 'category-pendants.jpg', 'type' => 'cat', 'icon' => '💎', 'sub' => 'Spiritual & Solitaires'],
    ['name' => 'Chains', 'file' => 'category-chains.jpg', 'type' => 'cat', 'icon' => '🔗', 'sub' => '22K Solid Gold Links'],
    ['name' => 'Jewellery Sets', 'file' => 'category-sets.jpg', 'type' => 'cat', 'icon' => '⚜️', 'sub' => 'Bridal Grand Suites'],

    // Products
    ['name' => 'Mayura Solitaire Ring', 'file' => 'product-ring-1.jpg', 'type' => 'prod', 'price' => '₹44,999', 'metal' => '18K Yellow Gold & Solitaire'],
    ['name' => 'Kundan Heritage Ring', 'file' => 'product-ring-2.jpg', 'type' => 'prod', 'price' => '₹26,500', 'metal' => '22K Gold & Polki Kundan'],
    ['name' => 'Kashmiri Chandbali', 'file' => 'product-earring-1.jpg', 'type' => 'prod', 'price' => '₹32,999', 'metal' => '22K Gold & Basra Pearls'],
    ['name' => 'Temple Floral Jhumka', 'file' => 'product-earring-2.jpg', 'type' => 'prod', 'price' => '₹42,800', 'metal' => '22K Antique Temple Gold'],
    ['name' => 'Padmavati Bridal Choker', 'file' => 'product-necklace-1.jpg', 'type' => 'prod', 'price' => '₹1,15,000', 'metal' => '22K Gold & Emerald Drops'],
    ['name' => 'Aura Diamond Necklace', 'file' => 'product-necklace-2.jpg', 'type' => 'prod', 'price' => '₹62,500', 'metal' => '18K Rose Gold Diamonds'],
    ['name' => 'Solitaire Tennis Bracelet', 'file' => 'product-bracelet-1.jpg', 'type' => 'prod', 'price' => '₹84,500', 'metal' => '18K White Gold Diamonds'],
    ['name' => 'Filigree Cuff Bracelet', 'file' => 'product-bracelet-2.jpg', 'type' => 'prod', 'price' => '₹49,999', 'metal' => '22K Royal Jaipuri Filigree'],
    ['name' => 'Rajputana Kada Bangles', 'file' => 'product-bangle-1.jpg', 'type' => 'prod', 'price' => '₹92,000', 'metal' => '22K Gold with Enamel (Pair)'],
    ['name' => 'Eternity Diamond Bangles', 'file' => 'product-bangle-2.jpg', 'type' => 'prod', 'price' => '₹76,000', 'metal' => '18K Pavé Diamond Bangles'],
    ['name' => 'Ganeshji Diamond Pendant', 'file' => 'product-pendant-1.jpg', 'type' => 'prod', 'price' => '₹19,999', 'metal' => '18K Dual Tone & Ruby Eye'],
    ['name' => 'Emerald Drop Pendant', 'file' => 'product-pendant-2.jpg', 'type' => 'prod', 'price' => '₹31,200', 'metal' => 'Zambian Emerald & Diamond'],
    ['name' => 'Royal Rope 22K Chain', 'file' => 'product-chain-1.jpg', 'type' => 'prod', 'price' => '₹78,500', 'metal' => '22K Solid Gold (15g)'],
    ['name' => 'Milano Diamond-Cut Chain', 'file' => 'product-chain-2.jpg', 'type' => 'prod', 'price' => '₹46,000', 'metal' => '18K Precision Italian Cut'],
    ['name' => 'Nizam-e-Khaas Bridal Set', 'file' => 'product-set-1.jpg', 'type' => 'prod', 'price' => '₹2,29,000', 'metal' => '22K Antique Polki & Rubies'],
    ['name' => 'Gulmohar Meenakari Set', 'file' => 'product-set-2.jpg', 'type' => 'prod', 'price' => '₹79,999', 'metal' => 'Pastel Enamel Choker & Drops']
];

$width = 600;
$height = 600;

foreach ($items as $item) {
    $img = imagecreatetruecolor($width, $height);

    // Luxury background palette: deep rich charcoal with warm gold accents or warm pearl cream
    $isProd = ($item['type'] === 'prod');

    // Create gradient background
    for ($y = 0; $y < $height; $y++) {
        $factor = $y / $height;
        if ($isProd) {
            // Warm rich cream gradient (#FBF9F5 to #EFE8DE)
            $r = (int)(251 - (251 - 239) * $factor);
            $g = (int)(249 - (249 - 232) * $factor);
            $b = (int)(245 - (245 - 222) * $factor);
        } else {
            // Royal velvet dark gradient (#1F1B18 to #12100E)
            $r = (int)(31 - (31 - 18) * $factor);
            $g = (int)(27 - (27 - 16) * $factor);
            $b = (int)(24 - (24 - 14) * $factor);
        }
        $lineColor = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $width, $y, $lineColor);
    }

    // Gold accent colors
    $gold = imagecolorallocate($img, 197, 160, 89);
    $goldLight = imagecolorallocate($img, 229, 195, 132);
    $goldDark = imagecolorallocate($img, 152, 117, 44);
    $white = imagecolorallocate($img, 255, 255, 255);
    $darkText = imagecolorallocate($img, 30, 27, 24);
    $mutedText = imagecolorallocate($img, 110, 104, 98);

    // Decorative Filigree Double Border
    imagesetthickness($img, 2);
    imagerectangle($img, 20, 20, $width - 20, $height - 20, $gold);
    imagerectangle($img, 26, 26, $width - 26, $height - 26, $isProd ? $goldLight : $goldDark);

    // Corner decorative accents
    $cornerSize = 18;
    // Top-left
    imagefilledrectangle($img, 16, 16, 24, 24, $gold);
    // Top-right
    imagefilledrectangle($img, $width - 24, 16, $width - 16, 24, $gold);
    // Bottom-left
    imagefilledrectangle($img, 16, $height - 24, 24, $height - 16, $gold);
    // Bottom-right
    imagefilledrectangle($img, $width - 24, $height - 24, $width - 16, $height - 16, $gold);

    // Brand medallion at top center
    imagefilledellipse($img, 300, 100, 70, 70, $gold);
    imagefilledellipse($img, 300, 100, 62, 62, $isProd ? imagecolorallocate($img, 248, 244, 237) : imagecolorallocate($img, 26, 23, 20));

    // Centered Watermark Emblem
    imagestring($img, 5, 288, 92, "SKJ", $gold);

    // Title / Text rendering
    $name = $item['name'];
    $xName = (int)(300 - (strlen($name) * 9 * 0.5));
    imagestring($img, 5, max(40, $xName), 260, $name, $isProd ? $darkText : $white);

    if ($isProd) {
        $metal = $item['metal'];
        $xMetal = (int)(300 - (strlen($metal) * 8 * 0.5));
        imagestring($img, 4, max(40, $xMetal), 300, $metal, $goldDark);

        // Price badge
        $price = $item['price'];
        $xPrice = (int)(300 - (strlen($price) * 9 * 0.5));
        imagefilledrectangle($img, 210, 350, 390, 395, $gold);
        imagestring($img, 5, 250, 365, $price, $white);

        // Subtitle
        $hallmark = "BIS 916 HALLMARKED CERTIFIED";
        $xHall = (int)(300 - (strlen($hallmark) * 7 * 0.5));
        imagestring($img, 2, max(40, $xHall), 460, $hallmark, $mutedText);
    } else {
        $sub = $item['sub'];
        $xSub = (int)(300 - (strlen($sub) * 8 * 0.5));
        imagestring($img, 4, max(40, $xSub), 310, $sub, $goldLight);

        $badge = "EXPLORE COLLECTION";
        imagefilledrectangle($img, 180, 380, 420, 425, $gold);
        imagestring($img, 4, 215, 395, $badge, $white);
    }

    // Save product images to uploads/products/ and category images to assets/images/
    $targetPath = $isProd ? ($productsDir . $item['file']) : ($assetsDir . $item['file']);
    imagejpeg($img, $targetPath, 92);
    imagedestroy($img);
}

echo "Successfully generated 16 products in uploads/products/ and 8 categories in assets/images/\n";
