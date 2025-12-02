<?php
function getWelcomeEmailContent($name = '') {
    $displayName = $name ? htmlspecialchars($name) : 'Member';
    $content = '<!doctype html>' .
        '<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' .
        '<title>Welcome to Zokli</title>' .
        '<style>' .
        'body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;color:#2c3e50;line-height:1.7;margin:0;padding:0;background:#ecf0f1}' .
        '.email-wrapper{max-width:650px;margin:0 auto;background:#ffffff}' .
        '.header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:40px 30px;text-align:center}' .
        '.logo{margin-bottom:20px}' .
        '.logo img{max-width:180px;height:auto}' .
        '.header h1{margin:0;font-size:32px;font-weight:700;letter-spacing:-0.5px}' .
        '.container{padding:40px 30px;background:#fff}' .
        '.greeting{font-size:18px;color:#2c3e50;margin-bottom:20px;font-weight:500}' .
        '.intro-text{font-size:16px;line-height:1.8;margin-bottom:20px;color:#34495e}' .
        '.section-title{color:#667eea;font-size:22px;margin-top:30px;margin-bottom:18px;font-weight:700;border-left:4px solid #667eea;padding-left:15px}' .
        '.highlight{background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%);color:#fff;padding:20px;border-radius:8px;margin:20px 0;text-align:center;box-shadow:0 4px 15px rgba(240,147,251,0.3)}' .
        '.highlight p{margin:0;font-size:22px;font-weight:700;letter-spacing:0.5px}' .
        'ul{margin:15px 0;padding-left:0;list-style:none}' .
        'li{margin:12px 0;padding-left:30px;position:relative;font-size:15px;color:#34495e}' .
        'li:before{content:"✓";position:absolute;left:0;color:#667eea;font-weight:bold;font-size:18px}' .
        'ol{margin:15px 0;padding-left:25px;counter-reset:item}' .
        'ol li{margin:10px 0;padding-left:10px;font-size:15px;color:#34495e}' .
        '.mega-prize{background:linear-gradient(135deg,#f5af19 0%,#f12711 100%);color:#fff;padding:25px;border-radius:8px;margin:25px 0;box-shadow:0 6px 20px rgba(241,39,17,0.3)}' .
        '.mega-prize h3{margin:0 0 15px 0;text-align:center;font-size:24px;text-transform:uppercase;letter-spacing:1px}' .
        '.mega-prize ul{margin:10px 0}' .
        '.mega-prize li{color:#fff}' .
        '.mega-prize li:before{color:#fff}' .
        '.cta-section{text-align:center;margin:35px 0;padding:30px;background:linear-gradient(135deg,#e0f7fa 0%,#e1bee7 100%);border-radius:8px}' .
        '.btn{display:inline-block;padding:15px 40px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;text-decoration:none;border-radius:30px;margin:10px 0;font-weight:700;font-size:16px;box-shadow:0 4px 15px rgba(102,126,234,0.4);transition:transform 0.2s}' .
        '.btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(102,126,234,0.5)}' .
        '.social{margin:30px 0;padding:25px;background:#f8f9fa;border-radius:8px;border-left:4px solid #667eea}' .
        '.social p{margin:8px 0;font-size:15px;color:#34495e}' .
        '.footer{text-align:center;padding:30px;background:#34495e;color:#ecf0f1}' .
        '.footer p{margin:10px 0;font-size:14px}' .
        '.footer-note{font-size:12px;color:#95a5a6;margin-top:20px;padding-top:20px;border-top:1px solid #7f8c8d}' .
        '.emoji{font-size:24px;margin-right:10px;vertical-align:middle}' .
        '@media only screen and (max-width:600px){.container{padding:25px 20px}.header{padding:30px 20px}.logo img{max-width:140px}.header h1{font-size:26px}.section-title{font-size:20px}}' .
        '</style>' .
        '</head><body>' .
        '<div class="email-wrapper">' .
        '<div class="header">' .
        '<div class="logo"><img src="cid:logo" alt="Zokli Logo" /></div>' .
        '<h1>Welcome to Zokli Community!</h1>' .
        '</div>' .
        '<div class="container">' .
        '<p class="greeting">Hello <strong>' . $displayName . '</strong>,</p>' .
        '<p class="intro-text">A warm welcome to our digital community - <strong>Zokli</strong>. We are building world\'s largest ever digital social media community. A ten million strong, diverse digital youths across India.</p>' .
        '<div class="highlight">' .
        '<p>"ZOKLI" : Connect-Create-Communicate</p>' .
        '</div>' .
        '<p class="intro-text">A digital social media community, connecting via dynamic QR code (Single QR code for all of your contact details & social media links)</p>' .
        '<p class="intro-text"><strong>Make Every Day, Your Lucky Day with the Zokli Community.</strong> Ready to turn your ordinary days into extraordinarily lucky days? Check it out !!!</p>' .
        
        '<h2 class="section-title"><span class="emoji">🛑</span>For Individuals & Creators</h2>' .
        '<p class="intro-text">Participate in Zokli\'s exclusive reward based community activities, complete 10 daily tasks / activities online (Every Hour, 333 days) of the community and stand a chance to win amazing rewards & recognition, every single day !!!</p>' .
        '<p class="intro-text">Zokli Community Tasks / Activities are simple & easy to complete, just Subscribe & follow requests, brand ads, brand video views & community creators video views, which you will receive through notifications via app, So download Zokli app. Win following rewards daily !!!</p>' .
        
        '<ul>' .
        '<li>Brand new Apple iPhone 17 up for grabs daily.</li>' .
        '<li>Android phones to be won every day.</li>' .
        '<li>Hundreds of One Lakh Rupees worth of cash coupons daily, because winning cash makes every day better!</li>' .
        '<li>Fully paid Dubai & Goa trips and enjoy sun, sand, and fun at the top tourist destinations.</li>' .
        '<li>Win Amazon - Flipkart coupons everyday.</li>' .
        '<li>Win Shopping coupons everyday.</li>' .
        '<li>Win games - Play coupons daily.</li>' .
        '<li>Win Gold & Silver personalised QR codes.</li>' .
        '<li>Exclusive reference bonus (For Individuals Rs 100/- & for Creators Rs 200/-) for every successful references.</li>' .
        '<li>Get huge community discounts on top brands</li>' .
        '<li>Get IPL tickets to watch your favourite teams playing in your city.</li>' .
        '<li>Weekend Party / Event tickets to top clubs with beverages & food.</li>' .
        '<li>Play games on our app & win big !</li>' .
        '</ul>' .
        
        '<ul>' .
        '<li>Amazing community support for Creators economy & their contents.</li>' .
        '<li>Brand exposure to our community creators.</li>' .
        '<li>Readily available brand contracts / barter deals to our community Creators.</li>' .
        '</ul>' .
        
        '<div class="mega-prize">' .
        '<h3>"Mega Prize" is truly spectacular</h3>' .
        '<ul>' .
        '<li>Win 2026 - Tomorrowland fully paid tickets! Immerse yourself in the world\'s greatest music festival, with absolutely everything covered.</li>' .
        '<li>Win Super Bikes.</li>' .
        '<li>Win SUV Cars.</li>' .
        '</ul>' .
        '</div>' .
        
        '<h2 class="section-title"><span class="emoji">🛑</span>For Business</h2>' .
        '<p class="intro-text">Get your business a dynamic QR code from Zokli & promote your business within our community.</p>' .
        '<ul>' .
        '<li>Get upto "100 crores" of funding assistance for 100 startups / existing businesses.</li>' .
        '<li>Get marketing and sales support from the Zokli community.</li>' .
        '</ul>' .
        
        '<h3 class="section-title">How to participate & win?</h3>' .
        '<p class="intro-text">At Zokli, immerse yourself into community activities, and let the fortune favor you. The more you engage, the more chances to win. Make every day, your lucky day with Zokli - Community!!!</p>' .
        
        '<h3 class="section-title">Zokli Community Programs</h3>' .
        '<ol>' .
        '<li>Zokli Community city-wise get together & meet & greet events.</li>' .
        '<li>Zokli Community dating activities.</li>' .
        '<li>Zokli Community merchandise sales.</li>' .
        '<li>Zokli Community partner program (Sell Insurance, mutual funds, Edu tech products, & real estate and earn) business opportunities.</li>' .
        '<li>Zokli Community job creation programs.</li>' .
        '<li>Zokli Community startup creation programs.</li>' .
        '<li>Zokli Community freelancers support programs.</li>' .
        '<li>Zokli Community creators economy creation & support programs.</li>' .
        '<li>Zokli Community gaming support programs.</li>' .
        '<li>Zokli Community technical & skill education support programs.</li>' .
        '</ol>' .
        
        '<div class="cta-section">' .
        '<a class="btn" href="https://www.zokli.io">🚀 Visit www.zokli.io</a>' .
        '</div>' .
        
        '<div class="social">' .
        '<p><strong>📱 Download our apps from the Apple Store & Play Store.</strong></p>' .
        '<p><strong>🌐 Follow us on:</strong> Instagram • Facebook • X • LinkedIn • Telegram • Youtube</p>' .
        '</div>' .
        
        '</div>' .
        '<div class="footer">' .
        '<p><strong>Best Regards from,</strong><br/>Zokli India</p>' .
        '<div class="footer-note">If you did not register for Zokli, please ignore this email or contact support at Zokli.india@gmail.com</div>' .
        '</div>' .
        '</div></body></html>';

    return $content;
}

?>
