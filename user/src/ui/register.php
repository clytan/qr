<!DOCTYPE html>
<html lang="zxx">

<head>
    <title>ZQR</title>
    <link rel="icon" href="../assets/images/company_logo.jpg" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="" name="keywords" />
    <meta content="" name="author" />
    <!-- CSS Files
    ================================================== -->
    <?php include('../components/csslinks.php') ?>
</head>

<body>
    <div id="wrapper">

        <!-- header begin -->
        <header class="transparent d-none">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="de-flex sm-pt10">
                            <div class="de-flex-col">
                                <div class="de-flex-col">
                                    <!-- logo begin -->
                                    <div id="logo">
                                        <a href="index.php">
                                            <img alt="" class="logo" src="../assets/images/logo-3.png" />
                                            <img alt="" class="logo-2" src="../assets/images/logo-3.png" />
                                        </a>
                                    </div>
                                    <!-- logo close -->
                                </div>
                            </div>
                            <div class="de-flex-col header-col-mid">
                                <!-- mainmenu begin -->
                                <ul id="mainmenu">
                                    <li>
                                        <a href="login.php">Login<span></span></a>
                                    </li>
                                </ul>
                                <div class="menu_side_area">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <!-- header close -->
        <!-- content begin -->
        <div class="no-bottom no-top" id="content">
            <div id="top"></div>

            <section class="full-height relative no-top no-bottom vertical-center"
                data-bgimage="url(../assets/images/background/6.jpg) top" data-stellar-background-ratio=".5">
                <div class="overlay-gradient t50">
                    <div class="center-y relative">
                        <div class="container">
                            <div class="row align-items-center">
                                <div class="col-lg-5 text-light wow fadeInRight" data-wow-delay=".5s">
                                    <div class="spacer-10"></div>
                                    <h1>Create, sell or collect digital items.</h1>
                                </div>

                                <div class="col-lg-4 offset-lg-2 wow fadeIn" data-wow-delay=".5s">
                                    <div class="box-rounded padding40" data-bgcolor="#ffffff">
                                        <h3 class="mb10">Register</h3>
                                        <p>Already have an account? <a href="login.php">Login here<span></span></a>.</p>
                                        <form name="registerForm" id='register_form' class="form-border" method="post"
                                            action=''>

                                            <!-- Email Field with Cool Design -->
                                            <div class="field-set" style="position:relative; margin-bottom:20px;">
                                                <input type='text' name='email' id='email' 
                                                       placeholder="✉️ Email Address"
                                                       style="background: rgba(255,255,255,0.95);
                                                              border: 2px solid #e1e8ed;
                                                              border-radius: 12px;
                                                              padding: 18px 20px;
                                                              font-size: 16px;
                                                              color: #2c3e50;
                                                              width: 100%;
                                                              box-sizing: border-box;
                                                              transition: all 0.3s ease;
                                                              box-shadow: 0 4px 15px rgba(0,0,0,0.08);"
                                                       onfocus="this.style.borderColor='#667eea';
                                                                this.style.boxShadow='0 6px 20px rgba(102,126,234,0.15)';
                                                                this.style.transform='translateY(-2px)';"
                                                       onblur="this.style.borderColor='#e1e8ed';
                                                               this.style.boxShadow='0 4px 15px rgba(0,0,0,0.08)';
                                                               this.style.transform='translateY(0)';">
                                                <div id="verify-email-section" style="display:none; margin-top:15px; padding-top:10px;">
                                                    <a href="#" id="verify-email-link" 
                                                       style="background: linear-gradient(135deg, #74b9ff, #0984e3);
                                                              color: white;
                                                              padding: 10px 20px;
                                                              border-radius: 25px;
                                                              text-decoration: none;
                                                              font-size: 14px;
                                                              font-weight: 600;
                                                              box-shadow: 0 4px 15px rgba(116,185,255,0.3);
                                                              transition: all 0.3s ease;
                                                              display: inline-block;"
                                                       onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(116,185,255,0.4)'"
                                                       onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 15px rgba(116,185,255,0.3)'">
                                                        🔐 Verify Email
                                                    </a>
                                                    <span id="email-verified-status" style="margin-left:15px; font-weight:600; font-size:14px;"></span>
                                                </div>
                                            </div>

                                            <!-- Password Field -->
                                            <div class="field-set">
                                                <input type='password' name='password' id='password'
                                                       placeholder="🔒 Password"
                                                       style="background: rgba(255,255,255,0.95);
                                                              border: 2px solid #e1e8ed;
                                                              border-radius: 12px;
                                                              padding: 18px 20px;
                                                              font-size: 16px;
                                                              color: #2c3e50;
                                                              width: 100%;
                                                              box-sizing: border-box;
                                                              transition: all 0.3s ease;
                                                              box-shadow: 0 4px 15px rgba(0,0,0,0.08);"
                                                       onfocus="this.style.borderColor='#667eea';
                                                                this.style.boxShadow='0 6px 20px rgba(102,126,234,0.15)';
                                                                this.style.transform='translateY(-2px)';"
                                                       onblur="this.style.borderColor='#e1e8ed';
                                                               this.style.boxShadow='0 4px 15px rgba(0,0,0,0.08)';
                                                               this.style.transform='translateY(0)';">
                                            </div>

                                            <!-- Confirm Password Field -->
                                            <div class="field-set">
                                                <input type='password' name='confirm_password' id='confirm_password'
                                                       placeholder="🔐 Confirm Password"
                                                       style="background: rgba(255,255,255,0.95);
                                                              border: 2px solid #e1e8ed;
                                                              border-radius: 12px;
                                                              padding: 18px 20px;
                                                              font-size: 16px;
                                                              color: #2c3e50;
                                                              width: 100%;
                                                              box-sizing: border-box;
                                                              transition: all 0.3s ease;
                                                              box-shadow: 0 4px 15px rgba(0,0,0,0.08);"
                                                       onfocus="this.style.borderColor='#667eea';
                                                                this.style.boxShadow='0 6px 20px rgba(102,126,234,0.15)';
                                                                this.style.transform='translateY(-2px)';"
                                                       onblur="this.style.borderColor='#e1e8ed';
                                                               this.style.boxShadow='0 4px 15px rgba(0,0,0,0.08)';
                                                               this.style.transform='translateY(0)';">
                                                <span id="password-match-status" style="margin-left:8px; margin-top:8px; display:block; font-weight:600;"></span>
                                            </div>

                                            <!-- User Slab Selection with Cool Design -->
                                            <div class="field-set" style="margin-top: -5%;">
                                                <label style="margin-bottom:12px;display:block;font-weight:600;color:#2c3e50;font-size:14px;">💎 Select Your Plan</label>
                                                <select name="user_slab" id="user_slab" required 
                                                        style="background: rgba(255,255,255,0.95);
                                                               border: 2px solid #e1e8ed;
                                                               border-radius: 12px;
                                                               padding: 18px 20px;
                                                               font-size: 16px;
                                                               color: #2c3e50;
                                                               width: 100%;
                                                               box-sizing: border-box;
                                                               transition: all 0.3s ease;
                                                               box-shadow: 0 4px 15px rgba(0,0,0,0.08);
                                                               cursor: pointer;
                                                               appearance: none;
                                                               background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 4 5\"><path fill=\"%23666\" d=\"M2 0L0 2h4zm0 5L0 3h4z\"/></svg>');
                                                               background-repeat: no-repeat;
                                                               background-position: right 20px center;
                                                               background-size: 12px;"
                                                        onfocus="this.style.borderColor='#667eea';
                                                                 this.style.boxShadow='0 6px 20px rgba(102,126,234,0.15)';
                                                                 this.style.transform='translateY(-2px)';"
                                                        onblur="this.style.borderColor='#e1e8ed';
                                                                this.style.boxShadow='0 4px 15px rgba(0,0,0,0.08)';
                                                                this.style.transform='translateY(0)';"
                                                        onmouseover="if(this !== document.activeElement) {
                                                                        this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)';
                                                                        this.style.transform='translateY(-1px)';
                                                                    }"
                                                        onmouseout="if(this !== document.activeElement) {
                                                                       this.style.boxShadow='0 4px 15px rgba(0,0,0,0.08)';
                                                                       this.style.transform='translateY(0)';
                                                                   }">
                                                    <option value="" style="background:#fff;color:#666;">🎯 Select your plan...</option>
                                                </select>
                                            </div>

                                            <!-- Register As Section with Better Responsive Design -->
                                            <div class="field-set" style="margin-bottom:8%;">
                                                <label style="margin-bottom:20px;display:block;font-weight:600;color:#2c3e50;font-size:16px;">👤 Register as</label>
                                                
                                                <!-- Basic User Types - 3 columns on desktop, 1 on mobile -->
                                                <div style="display:grid;
                                                           grid-template-columns:repeat(3,1fr);
                                                           gap:15px;
                                                           margin-bottom:20px;"
                                                     class="basic-user-types">
                                                    
                                                    <!-- Individual Card -->
                                                    <label class="user-type-card" data-type="individual"
                                                           style="background:linear-gradient(135deg,#74b9ff,#0984e3);
                                                                  border-radius:15px;
                                                                  padding:20px 15px;
                                                                  text-align:center;
                                                                  cursor:pointer;
                                                                  transition:all 0.3s ease;
                                                                  box-shadow:0 6px 20px rgba(116,185,255,0.25);
                                                                  border:3px solid transparent;
                                                                  position:relative;
                                                                  min-height:70px;
                                                                  display:flex;
                                                                  align-items:center;
                                                                  justify-content:center;"
                                                           onmouseover="this.style.transform='translateY(-8px)';this.style.boxShadow='0 12px 30px rgba(116,185,255,0.4)'"
                                                           onmouseout="if(!this.classList.contains('selected')) { this.style.transform='translateY(0)';this.style.boxShadow='0 6px 20px rgba(116,185,255,0.25)' }">
                                                        <input type="radio" name="user_type" value="1" required style="display:none;">
                                                        <span style="color:white;font-weight:700;font-size:15px;text-shadow:0 2px 4px rgba(0,0,0,0.2);">👤 Individual</span>
                                                    </label>

                                                    <!-- Creator Card -->
                                                    <label class="user-type-card" data-type="creator"
                                                           style="background:linear-gradient(135deg,#fd79a8,#e84393);
                                                                  border-radius:15px;
                                                                  padding:20px 15px;
                                                                  text-align:center;
                                                                  cursor:pointer;
                                                                  transition:all 0.3s ease;
                                                                  box-shadow:0 6px 20px rgba(253,121,168,0.25);
                                                                  border:3px solid transparent;
                                                                  position:relative;
                                                                  min-height:70px;
                                                                  display:flex;
                                                                  align-items:center;
                                                                  justify-content:center;"
                                                           onmouseover="this.style.transform='translateY(-8px)';this.style.boxShadow='0 12px 30px rgba(253,121,168,0.4)'"
                                                           onmouseout="if(!this.classList.contains('selected')) { this.style.transform='translateY(0)';this.style.boxShadow='0 6px 20px rgba(253,121,168,0.25)' }">
                                                        <input type="radio" name="user_type" value="2" style="display:none;">
                                                        <span style="color:white;font-weight:700;font-size:15px;text-shadow:0 2px 4px rgba(0,0,0,0.2);">🎨 Creator</span>
                                                    </label>

                                                    <!-- Business Card -->
                                                    <label class="user-type-card" data-type="business"
                                                           style="background:linear-gradient(135deg,#00b894,#00a085);
                                                                  border-radius:15px;
                                                                  padding:20px 15px;
                                                                  text-align:center;
                                                                  cursor:pointer;
                                                                  transition:all 0.3s ease;
                                                                  box-shadow:0 6px 20px rgba(0,184,148,0.25);
                                                                  border:3px solid transparent;
                                                                  position:relative;
                                                                  min-height:70px;
                                                                  display:flex;
                                                                  align-items:center;
                                                                  justify-content:center;"
                                                           onmouseover="this.style.transform='translateY(-8px)';this.style.boxShadow='0 12px 30px rgba(0,184,148,0.4)'"
                                                           onmouseout="if(!this.classList.contains('selected')) { this.style.transform='translateY(0)';this.style.boxShadow='0 6px 20px rgba(0,184,148,0.25)' }">
                                                        <input type="radio" name="user_type" value="3" style="display:none;">
                                                        <span style="color:white;font-weight:700;font-size:15px;text-shadow:0 2px 4px rgba(0,0,0,0.2);">🏢 Business</span>
                                                    </label>
                                                </div>

                                                <!-- Premium Membership Cards - 2 columns -->
                                                <div style="display:grid;
                                                           grid-template-columns:1fr 1fr;
                                                           gap:15px;"
                                                     class="premium-types">
                                                     
                                                    <!-- Gold Member Card -->
                                                    <label class="user-type-card" data-type="gold"
                                                           style="background:linear-gradient(135deg,#f39c12,#e67e22);
                                                                  border-radius:15px;
                                                                  padding:25px 20px;
                                                                  text-align:center;
                                                                  cursor:pointer;
                                                                  transition:all 0.3s ease;
                                                                  box-shadow:0 8px 25px rgba(243,156,18,0.3);
                                                                  border:3px solid transparent;
                                                                  position:relative;
                                                                  overflow:hidden;
                                                                  min-height:80px;
                                                                  display:flex;
                                                                  align-items:center;
                                                                  justify-content:center;"
                                                           onmouseover="this.style.transform='translateY(-10px)';this.style.boxShadow='0 15px 40px rgba(243,156,18,0.5)'"
                                                           onmouseout="if(!this.classList.contains('selected')) { this.style.transform='translateY(0)';this.style.boxShadow='0 8px 25px rgba(243,156,18,0.3)' }">
                                                        <input type="radio" name="user_type" value="1" data-tag="gold" style="display:none;">
                                                        <div style="position:absolute;top:-50%;right:-50%;width:100%;height:100%;background:rgba(255,255,255,0.1);border-radius:50%;"></div>
                                                        <span style="color:white;font-weight:800;font-size:16px;text-shadow:0 3px 6px rgba(0,0,0,0.3);position:relative;z-index:2;">✨ Gold Member</span>
                                                    </label>

                                                    <!-- Silver Member Card -->
                                                    <label class="user-type-card" data-type="silver"
                                                           style="background:linear-gradient(135deg,#95a5a6,#7f8c8d);
                                                                  border-radius:15px;
                                                                  padding:25px 20px;
                                                                  text-align:center;
                                                                  cursor:pointer;
                                                                  transition:all 0.3s ease;
                                                                  box-shadow:0 8px 25px rgba(149,165,166,0.3);
                                                                  border:3px solid transparent;
                                                                  position:relative;
                                                                  overflow:hidden;
                                                                  min-height:80px;
                                                                  display:flex;
                                                                  align-items:center;
                                                                  justify-content:center;"
                                                           onmouseover="this.style.transform='translateY(-10px)';this.style.boxShadow='0 15px 40px rgba(149,165,166,0.5)'"
                                                           onmouseout="if(!this.classList.contains('selected')) { this.style.transform='translateY(0)';this.style.boxShadow='0 8px 25px rgba(149,165,166,0.3)' }">
                                                        <input type="radio" name="user_type" value="1" data-tag="silver" style="display:none;">
                                                        <div style="position:absolute;top:-50%;right:-50%;width:100%;height:100%;background:rgba(255,255,255,0.1);border-radius:50%;"></div>
                                                        <span style="color:white;font-weight:800;font-size:16px;text-shadow:0 3px 6px rgba(0,0,0,0.3);position:relative;z-index:2;">🥈 Silver Member</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Reference Code Section with Modern Toggle -->
                                            <div class="field-set" style="margin-bottom:20px;">
                                                <div style="background:linear-gradient(135deg,#a29bfe,#6c5ce7);
                                                           border-radius:15px;
                                                           padding:20px;
                                                           box-shadow:0 8px 25px rgba(162,155,254,0.3);
                                                           transition:all 0.3s ease;"
                                                     onmouseover="this.style.boxShadow='0 12px 35px rgba(162,155,254,0.4)'"
                                                     onmouseout="this.style.boxShadow='0 8px 25px rgba(162,155,254,0.3)'">
                                                    <label style="display:flex;align-items:center;font-weight:600;cursor:pointer;color:white;margin-bottom:0;">
                                                        <div style="position:relative;margin-right:15px;">
                                                            <input type="checkbox" id="has_reference" name="has_reference" 
                                                                   style="width:24px;height:24px;cursor:pointer;opacity:0;position:absolute;">
                                                            <div style="width:24px;
                                                                        height:24px;
                                                                        border:3px solid white;
                                                                        border-radius:6px;
                                                                        background:rgba(255,255,255,0.1);
                                                                        transition:all 0.3s ease;
                                                                        display:flex;
                                                                        align-items:center;
                                                                        justify-content:center;"
                                                                 id="checkbox_visual">
                                                                <span style="color:white;font-size:16px;font-weight:bold;opacity:0;transition:opacity 0.3s ease;" id="check_mark">✓</span>
                                                            </div>
                                                        </div>
                                                        <span style="font-size:16px;">🎯 Do you have a reference code?</span>
                                                    </label>
                                                    
                                                    <div id="reference_section" style="display:none;margin-top:15px;animation:slideDown 0.3s ease;">
                                                        <input type="text" name="reference_code" id="reference_code" class="form-control" 
                                                               placeholder="🔗 Enter your reference code"
                                                               style="background:rgba(255,255,255,0.95);
                                                                      border:none;
                                                                      border-radius:10px;
                                                                      padding:15px;
                                                                      font-size:16px;
                                                                      color:#2c3e50;
                                                                      box-shadow:0 4px 15px rgba(0,0,0,0.1);
                                                                      transition:all 0.3s ease;"
                                                               onfocus="this.style.boxShadow='0 6px 20px rgba(0,0,0,0.15)';this.style.transform='translateY(-2px)'"
                                                               onblur="this.style.boxShadow='0 4px 15px rgba(0,0,0,0.1)';this.style.transform='translateY(0)'">
                                                        <span id="reference-status" style="margin-left:8px; margin-top:8px; display:block; font-size:14px; font-weight:500;"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <style>
                                                @keyframes slideDown {
                                                    from { opacity: 0; transform: translateY(-10px); }
                                                    to { opacity: 1; transform: translateY(0); }
                                                }
                                                
                                                @keyframes pulseSelection {
                                                    0% { box-shadow: 0 0 0 0 rgba(255,255,255,0.7); }
                                                    70% { box-shadow: 0 0 0 10px rgba(255,255,255,0); }
                                                    100% { box-shadow: 0 0 0 0 rgba(255,255,255,0); }
                                                }
                                                
                                                /* User Type Card Selection Highlighting */
                                                .user-type-card {
                                                    position: relative;
                                                }
                                                
                                                .user-type-card.selected {
                                                    border: 4px solid #ffffff !important;
                                                    box-shadow: 0 15px 40px rgba(255,255,255,0.4) !important;
                                                    transform: translateY(-10px) scale(1.02) !important;
                                                    animation: pulseSelection 0.6s ease-out;
                                                }
                                                
                                                .user-type-card.selected::before {
                                                    content: '✓';
                                                    position: absolute;
                                                    top: -8px;
                                                    right: -8px;
                                                    background: #ffffff;
                                                    color: #28a745;
                                                    width: 30px;
                                                    height: 30px;
                                                    border-radius: 50%;
                                                    display: flex;
                                                    align-items: center;
                                                    justify-content: center;
                                                    font-weight: bold;
                                                    font-size: 16px;
                                                    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                                                    z-index: 10;
                                                }
                                                
                                                /* Responsive Design */
                                                @media (max-width: 768px) {
                                                    .basic-user-types {
                                                        grid-template-columns: 1fr !important;
                                                        gap: 12px !important;
                                                    }
                                                    
                                                    .premium-types {
                                                        grid-template-columns: 1fr !important;
                                                        gap: 12px !important;
                                                    }
                                                    
                                                    .user-type-card {
                                                        padding: 18px 15px !important;
                                                        min-height: 60px !important;
                                                    }
                                                    
                                                    .user-type-card span {
                                                        font-size: 14px !important;
                                                    }
                                                }
                                                
                                                @media (max-width: 576px) {
                                                    .basic-user-types {
                                                        gap: 10px !important;
                                                    }
                                                    
                                                    .premium-types {
                                                        gap: 10px !important;
                                                    }
                                                    
                                                    .user-type-card {
                                                        padding: 15px 12px !important;
                                                        min-height: 55px !important;
                                                    }
                                                    
                                                    .user-type-card span {
                                                        font-size: 13px !important;
                                                    }
                                                }
                                            </style>

                                            <script>
                                                // Enhanced user type selection with visual feedback
                                                document.addEventListener('DOMContentLoaded', function() {
                                                    // Handle user type card selection
                                                    const userTypeCards = document.querySelectorAll('.user-type-card');
                                                    
                                                    userTypeCards.forEach(card => {
                                                        card.addEventListener('click', function() {
                                                            // Remove selected class from all cards
                                                            userTypeCards.forEach(c => c.classList.remove('selected'));
                                                            
                                                            // Add selected class to clicked card
                                                            this.classList.add('selected');
                                                            
                                                            // Check the radio button
                                                            const radio = this.querySelector('input[type="radio"]');
                                                            if (radio) {
                                                                radio.checked = true;
                                                                // Trigger change event for form validation
                                                                radio.dispatchEvent(new Event('change'));
                                                            }
                                                        });
                                                    });
                                                });
                                                
                                                // Custom checkbox animation
                                                document.getElementById('has_reference').addEventListener('change', function() {
                                                    const visual = document.getElementById('checkbox_visual');
                                                    const checkMark = document.getElementById('check_mark');
                                                    
                                                    if (this.checked) {
                                                        visual.style.background = 'rgba(255,255,255,0.8)';
                                                        checkMark.style.opacity = '1';
                                                        checkMark.style.color = '#6c5ce7';
                                                    } else {
                                                        visual.style.background = 'rgba(255,255,255,0.1)';
                                                        checkMark.style.opacity = '0';
                                                    }
                                                });
                                            </script>



                                            <!-- Cool Submit Button -->
                                            <div class="field-set" style="margin-top:25px;">
                                                <button type='submit' id='register_user_form' disabled
                                                        style="width:100%;
                                                               background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                                               border: none;
                                                               border-radius: 15px;
                                                               padding: 18px 30px;
                                                               color: white;
                                                               font-size: 18px;
                                                               font-weight: 700;
                                                               text-transform: uppercase;
                                                               letter-spacing: 1px;
                                                               cursor: pointer;
                                                               transition: all 0.4s ease;
                                                               box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
                                                               position: relative;
                                                               overflow: hidden;"
                                                        onmouseover="if(!this.disabled) { 
                                                                        this.style.transform='translateY(-3px)'; 
                                                                        this.style.boxShadow='0 12px 35px rgba(102, 126, 234, 0.6)';
                                                                        this.style.background='linear-gradient(135deg, #764ba2 0%, #667eea 100%)';
                                                                    }"
                                                        onmouseout="if(!this.disabled) { 
                                                                       this.style.transform='translateY(0)'; 
                                                                       this.style.boxShadow='0 8px 25px rgba(102, 126, 234, 0.4)';
                                                                       this.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                                                                   }">
                                                    <span style="position:relative;z-index:2;">🚀 Submit Registration</span>
                                                    <div style="position:absolute;
                                                                top:50%;
                                                                left:50%;
                                                                width:0;
                                                                height:0;
                                                                background:rgba(255,255,255,0.2);
                                                                border-radius:50%;
                                                                transform:translate(-50%, -50%);
                                                                transition:all 0.6s ease;
                                                                z-index:1;" 
                                                         id="ripple_effect"></div>
                                                </button>
                                            </div>

                                            <style>
                                                #register_user_form:disabled {
                                                    background: linear-gradient(135deg, #bdc3c7, #95a5a6) !important;
                                                    cursor: not-allowed !important;
                                                    transform: none !important;
                                                    box-shadow: 0 4px 15px rgba(189, 195, 199, 0.3) !important;
                                                }
                                                
                                                #register_user_form:disabled span {
                                                    opacity: 0.7;
                                                }
                                                
                                                #register_user_form:not(:disabled):active {
                                                    transform: translateY(-1px) !important;
                                                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5) !important;
                                                }
                                                
                                                /* Ripple effect on click */
                                                #register_user_form:not(:disabled):active #ripple_effect {
                                                    width: 300px !important;
                                                    height: 300px !important;
                                                }
                                            </style>


                                            <div class="clearfix"></div>
                                            <!-- Email OTP Modal -->
                                            <div id="email-otp-modal" class="modal" tabindex="-1" role="dialog"
                                                style="display:none; background:rgba(0,0,0,0.5); position:fixed; top:0; left:0; width:100vw; height:100vh; z-index:9999; align-items:center; justify-content:center;">
                                                <div class="modal-dialog" role="document"
                                                    style="max-width:400px; margin:auto;">
                                                    <div class="modal-content" style="padding:30px; border-radius:8px;">
                                                        <div class="modal-header" style="border:none;">
                                                            <h5 class="modal-title">Verify Email</h5>
                                                            <button type="button" class="close" id="close-otp-modal"
                                                                style="background:none; border:none; font-size:24px;">&times;</button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>An OTP has been sent to <span
                                                                    id="otp-email-display"></span></p>
                                                            <input type="text" id="email-otp-input" maxlength="6"
                                                                class="form-control" placeholder="Enter 6-digit OTP"
                                                                style="margin-bottom:10px;">
                                                            <button type="button" id="submit-otp-btn"
                                                                class="btn btn-main btn-fullwidth color-2"
                                                                style="margin-bottom:10px;">Verify OTP</button>
                                                            <button type="button" id="resend-otp-btn"
                                                                class="btn btn-secondary btn-fullwidth">Resend
                                                                OTP</button>
                                                            <div id="otp-status-msg"
                                                                style="margin-top:10px; color:red;"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="spacer-single"></div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
        <!-- content close -->

        <!-- footer begin -->
        <?php 
        //include('../components/footer.php'); 
        ?>
        <!-- footer close -->

    </div>



    <!-- Javascript Files
    ================================================== -->
    <?php include('../components/jslinks.php'); ?>
    <script src="./custom_js/custom_register.js"></script>



</body>

</html>
