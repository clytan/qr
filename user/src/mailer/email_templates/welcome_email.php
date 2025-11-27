<?php
function getWelcomeEmailContent($name = '') {
    $displayName = $name ? htmlspecialchars($name) : 'Member';
    $content = '<!doctype html>' .
        '<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' .
        '<title>Welcome to Zokli</title>' .
        '<style>body{font-family:Arial,Helvetica,sans-serif;color:#333} .header{background:#f5576c;color:#fff;padding:20px;text-align:center} .container{padding:20px} .btn{display:inline-block;padding:10px 16px;background:#4CAF50;color:#fff;text-decoration:none;border-radius:4px}</style>' .
        '</head><body>' .
        '<div class="header"><h1>Welcome to Zokli</h1></div>' .
        '<div class="container">' .
        '<p>Hi ' . $displayName . ',</p>' .
        '<p>A warm welcome to our digital community - <strong>Zokli</strong>. We are building the world\'s largest ever digital social media community — a ten million strong, diverse digital youth community across India.</p>' .
        '<p><strong>"ZOKLI" : Connect - Create - Communicate</strong></p>' .
        '<p>With Zokli you get a dynamic QR code for all your contact details & social media links. Make every day your lucky day with our community activities and daily rewards.</p>' .
        '<h3>Highlights</h3>' .
        '<ul>' .
        '<li>Win phones, cash coupons, trips, shopping vouchers and more.</li>' .
        '<li>Exclusive creator support, brand exposure and monetisation opportunities.</li>' .
        '<li>Referral bonuses and community discounts.</li>' .
        '</ul>' .
        '<p><a class="btn" href="https://www.zokli.io">Visit Zokli Website</a></p>' .
        '<p>Download our apps from the Apple Store & Play Store and follow us on Instagram, Facebook, X, LinkedIn, Telegram and YouTube.</p>' .
        '<p>Best Regards,<br>Zokli India</p>' .
        '<hr><small>If you did not register for Zokli, please ignore this email or contact support at Zokli.india@gmail.com</small>' .
        '</div></body></html>';

    return $content;
}

?>
