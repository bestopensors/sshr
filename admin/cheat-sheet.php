<?php
/**
 * Admin - Cheat Sheet
 */
$pageTitle = 'Cheat Sheet';
require_once 'includes/header.php';
?>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Tier-Specific Cheat Sheet</h2>
        <p style="color: var(--text-secondary); margin-top: 8px;">Step-by-step guide for each package tier on Infonet.hr hosting</p>
    </div>
</div>

<!-- Basic Website Tier -->
<div class="card" style="border-left: 4px solid #10b981;">
    <div class="card__header">
        <h2 class="card__title" style="color: #10b981;">📄 Osnovna Stranica (Basic Website) - €300</h2>
        <p style="color: var(--text-secondary); margin-top: 8px;">ETA: 24-48 sati | Static HTML/CSS/JS website</p>
    </div>
    <div class="card__body">
        <div style="display: grid; gap: 20px;">
            <div style="padding: 16px; background: var(--bg-input); border-radius: 8px;">
                <h3 style="color: var(--primary); margin-bottom: 12px;">✅ What to Include (DO):</h3>
                <ul style="list-style: none; padding: 0; line-height: 1.8; color: var(--text-secondary);">
                    <li>✓ <strong>Static HTML pages</strong> - index.html, about.html, contact.html, privacy.html, terms.html</li>
                    <li>✓ <strong>CSS styling</strong> - Single style.css file, responsive design</li>
                    <li>✓ <strong>Basic JavaScript</strong> - Theme toggle, form validation, smooth scrolling</li>
                    <li>✓ <strong>Contact form</strong> - Simple form that sends email via PHP (no database)</li>
                    <li>✓ <strong>SEO basics</strong> - Meta tags, title tags, alt text for images</li>
                    <li>✓ <strong>Umami Analytics</strong> - Add analytics script</li>
                    <li>✓ <strong>Cloudflare Turnstile</strong> - Bot protection on contact form</li>
                </ul>
            </div>
            
            <div style="padding: 16px; background: var(--bg-input); border-radius: 8px; border-left: 4px solid #10b981;">
                <h3 style="color: #10b981; margin-bottom: 12px;">🔧 Exact Technologies & External Services:</h3>
                <div style="display: grid; gap: 12px;">
                    <div>
                        <strong style="color: var(--text-primary);">Frontend Technologies:</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• <strong>HTML5</strong> - Semantic markup</li>
                            <li>• <strong>CSS3</strong> - Custom CSS (no framework needed, but Bootstrap 5 optional)</li>
                            <li>• <strong>Vanilla JavaScript</strong> - No frameworks (jQuery, React, Vue, etc.)</li>
                            <li>• <strong>Bootstrap 5</strong> (optional) - CDN: <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css</code></li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">Google Fonts:</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• <strong>Inter</strong> - Weights: 300,400,500,600,700,800</li>
                            <li>• <strong>Space Grotesk</strong> - Weights: 300,400,500,600,700</li>
                            <li>• CDN: <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700&display=swap</code></li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">Cloudflare Turnstile (Bot Protection):</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• <strong>Script URL:</strong> <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">https://challenges.cloudflare.com/turnstile/v0/api.js</code></li>
                            <li>• <strong>Site Key:</strong> Get from <a href="https://dash.cloudflare.com/" target="_blank" style="color: var(--primary);">Cloudflare Dashboard</a> → Turnstile</li>
                            <li>• <strong>Secret Key:</strong> Store in PHP config (never expose in frontend)</li>
                            <li>• <strong>Verify API:</strong> <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">https://challenges.cloudflare.com/turnstile/v0/siteverify</code></li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">Umami Analytics:</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• <strong>Script URL:</strong> <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">https://cloud.umami.is/script.js</code></li>
                            <li>• <strong>Website ID:</strong> Get from <a href="https://cloud.umami.is" target="_blank" style="color: var(--primary);">Umami Dashboard</a></li>
                            <li>• Add to all HTML pages: <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">&lt;script defer src="https://cloud.umami.is/script.js" data-website-id="YOUR_ID"&gt;&lt;/script&gt;</code></li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">Infonet SMTP Email Setup:</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• <strong>SMTP Host:</strong> <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">cp7.infonet.hr</code></li>
                            <li>• <strong>SMTP Port:</strong> <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">465</code></li>
                            <li>• <strong>Security:</strong> <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">SSL</code></li>
                            <li>• <strong>Authentication:</strong> Use client's email credentials from Infonet cPanel</li>
                            <li>• Use PHP <code>mail()</code> function or simple SMTP via cURL/fsockopen</li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">SEO & Meta Tags:</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• Meta description, keywords, author</li>
                            <li>• Open Graph tags (Facebook)</li>
                            <li>• Twitter Card tags</li>
                            <li>• Canonical URLs</li>
                            <li>• JSON-LD structured data (Organization, LocalBusiness, WebSite)</li>
                            <li>• XML sitemap (sitemap.xml)</li>
                            <li>• robots.txt file</li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">Security (.htaccess):</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• Protect config/ and install/ directories</li>
                            <li>• Security headers (X-Content-Type-Options, X-Frame-Options, X-XSS-Protection)</li>
                            <li>• Enable compression (mod_deflate)</li>
                            <li>• Browser caching for static files</li>
                            <li>• Custom 404 error page</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div style="padding: 16px; background: var(--bg-input); border-radius: 8px;">
                <h3 style="color: var(--danger); margin-bottom: 12px;">❌ What NOT to Include (DON'T):</h3>
                <ul style="list-style: none; padding: 0; line-height: 1.8; color: var(--text-secondary);">
                    <li>✗ <strong>NO Database</strong> - Don't set up MySQL database</li>
                    <li>✗ <strong>NO Admin Panel</strong> - No backend admin system</li>
                    <li>✗ <strong>NO CMS</strong> - No content management system</li>
                    <li>✗ <strong>NO Blog</strong> - No blog functionality</li>
                    <li>✗ <strong>NO User Accounts</strong> - No login/signup system</li>
                    <li>✗ <strong>NO Complex Features</strong> - Keep it simple!</li>
                </ul>
            </div>
            
            <div style="padding: 16px; background: var(--bg-input); border-radius: 8px; border-left: 4px solid #10b981;">
                <h3 style="color: #10b981; margin-bottom: 12px;">🚀 Infonet.hr Hosting Setup:</h3>
                <ol style="line-height: 2; color: var(--text-secondary); padding-left: 20px;">
                    <li><strong>Login to Infonet.hr</strong> - Access your existing hosting account</li>
                    <li><strong>Connect domain</strong> (if new domain):
                        <ul style="list-style: disc; margin-top: 8px; padding-left: 20px;">
                            <li>Go to cPanel → Domains → Addon Domains (or use main domain)</li>
                            <li>Point domain DNS to Infonet nameservers (if external domain)</li>
                        </ul>
                    </li>
                    <li><strong>Upload files via FTP</strong> (FileZilla or cPanel File Manager):
                        <ul style="list-style: disc; margin-top: 8px; padding-left: 20px;">
                            <li>FTP Host: <code>ftp.infonet.hr</code></li>
                            <li>Upload all HTML, CSS, JS files to <code>public_html</code> folder</li>
                            <li>Upload images to <code>public_html/images</code></li>
                            <li>Upload PHP files (contact form handler) to <code>public_html</code></li>
                        </ul>
                    </li>
                    <li><strong>Set file permissions</strong> (via cPanel File Manager or FTP):
                        <ul style="list-style: disc; margin-top: 8px; padding-left: 20px;">
                            <li>Folders: <code>755</code></li>
                            <li>Files: <code>644</code></li>
                        </ul>
                    </li>
                    <li><strong>Set up email system</strong> - Configure SMTP in contact form PHP file:
                        <ul style="list-style: disc; margin-top: 8px; padding-left: 20px;">
                            <li>SMTP Host: <code>cp7.infonet.hr</code></li>
                            <li>SMTP Port: <code>465</code> (SSL)</li>
                            <li>Get email credentials from cPanel → Email Accounts</li>
                            <li>Use client's email address and password</li>
                        </ul>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Professional Website Tier -->
<div class="card" style="border-left: 4px solid #6366f1;">
    <div class="card__header">
        <h2 class="card__title" style="color: #6366f1;">💼 Profesionalna Stranica (Professional Website) - €500</h2>
        <p style="color: var(--text-secondary); margin-top: 8px;">ETA: 72 sata | CMS with database, blog, gallery</p>
    </div>
    <div class="card__body">
        <div style="display: grid; gap: 20px;">
            <div style="padding: 16px; background: var(--bg-input); border-radius: 8px;">
                <h3 style="color: var(--primary); margin-bottom: 12px;">✅ What to Include (DO):</h3>
                <ul style="list-style: none; padding: 0; line-height: 1.8; color: var(--text-secondary);">
                    <li>✓ <strong>Database</strong> - MySQL database for content management</li>
                    <li>✓ <strong>Admin Panel</strong> - Simple admin interface for content editing</li>
                    <li>✓ <strong>CMS Features</strong> - Edit pages, manage content</li>
                    <li>✓ <strong>Blog System</strong> - Create, edit, delete blog posts</li>
                    <li>✓ <strong>Gallery</strong> - Image gallery with admin upload</li>
                    <li>✓ <strong>Contact Form</strong> - With database storage + email</li>
                    <li>✓ <strong>SEO</strong> - Meta tags, structured data, sitemap</li>
                    <li>✓ <strong>Analytics</strong> - Umami Analytics integration</li>
                </ul>
            </div>
            
            <div style="padding: 16px; background: var(--bg-input); border-radius: 8px; border-left: 4px solid #6366f1;">
                <h3 style="color: #6366f1; margin-bottom: 12px;">🔧 Exact Technologies & External Services:</h3>
                <div style="display: grid; gap: 12px;">
                    <div>
                        <strong style="color: var(--text-primary);">Backend Technologies:</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• <strong>PHP 8+</strong> - Server-side scripting</li>
                            <li>• <strong>MySQL/MariaDB</strong> - Database (via Infonet cPanel)</li>
                            <li>• <strong>PDO</strong> - Database abstraction layer (prepared statements)</li>
                            <li>• <strong>Session Management</strong> - PHP sessions for admin authentication</li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">Frontend (same as Basic tier):</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• HTML5, CSS3, Vanilla JavaScript</li>
                            <li>• Bootstrap 5 (optional) - <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css</code></li>
                            <li>• Google Fonts (Inter, Space Grotesk)</li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">Cloudflare Turnstile (REQUIRED):</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• <strong>Script:</strong> <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">https://challenges.cloudflare.com/turnstile/v0/api.js</code></li>
                            <li>• Add to contact form and admin login</li>
                            <li>• Verify token server-side on form submission</li>
                            <li>• Get keys from: <a href="https://dash.cloudflare.com/" target="_blank" style="color: var(--primary);">Cloudflare Dashboard</a></li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">Umami Analytics (REQUIRED):</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• <strong>Script URL:</strong> <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">https://cloud.umami.is/script.js</code></li>
                            <li>• <strong>Website ID:</strong> Get from <a href="https://cloud.umami.is" target="_blank" style="color: var(--primary);">Umami Dashboard</a></li>
                            <li>• <strong>API Integration (for admin stats):</strong>
                                <ul style="list-style: disc; margin-top: 4px; padding-left: 20px;">
                                    <li>API URL: <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">https://api.umami.is/v1</code></li>
                                    <li>Get API key from: Umami Dashboard → Settings → API Keys</li>
                                    <li>Store in <code>config/analytics-alternative.php</code></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">Infonet SMTP Email (REQUIRED):</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• <strong>Host:</strong> <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">cp7.infonet.hr</code></li>
                            <li>• <strong>Port:</strong> <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">465</code></li>
                            <li>• <strong>Security:</strong> <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">SSL</code></li>
                            <li>• Configure in <code>config/database.php</code>:
                                <ul style="list-style: disc; margin-top: 4px; padding-left: 20px;">
                                    <li>SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_FROM</li>
                                </ul>
                            </li>
                            <li>• Use for: Contact form emails, admin notifications, password resets</li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">Database Schema:</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• Packages table (for pricing tiers)</li>
                            <li>• Package features & details tables</li>
                            <li>• Optional services table</li>
                            <li>• Messages/contact form submissions</li>
                            <li>• Blog posts table</li>
                            <li>• Gallery/images table</li>
                            <li>• Admin users table</li>
                            <li>• Translations table (optional)</li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">Admin Panel (CUSTOM BUILT FROM SCRATCH):</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• <strong>Build completely custom admin</strong> - NO pre-built admin frameworks (NO AdminLTE, NO Admin Panel templates)</li>
                            <li>• <strong>Pure PHP</strong> - Build from scratch using PHP, HTML, CSS, JavaScript</li>
                            <li>• <strong>Custom authentication</strong> - Login/authentication system built from scratch</li>
                            <li>• <strong>Custom UI</strong> - Design your own admin interface</li>
                            <li>• Features to include:
                                <ul style="list-style: disc; margin-top: 4px; padding-left: 20px;">
                                    <li>Package management (CRUD)</li>
                                    <li>Blog post management</li>
                                    <li>Gallery/image upload</li>
                                    <li>Message viewing/replying</li>
                                    <li>Analytics dashboard (Umami stats)</li>
                                    <li>Settings page</li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div style="padding: 16px; background: var(--bg-input); border-radius: 8px;">
                <h3 style="color: var(--danger); margin-bottom: 12px;">❌ What NOT to Include (DON'T):</h3>
                <ul style="list-style: none; padding: 0; line-height: 1.8; color: var(--text-secondary);">
                    <li>✗ <strong>NO E-commerce</strong> - No shopping cart or payment processing</li>
                    <li>✗ <strong>NO User Registration</strong> - No public user accounts/login</li>
                    <li>✗ <strong>NO Payment Integration</strong> - No Stripe, PayPal, etc.</li>
                    <li>✗ <strong>NO Complex APIs</strong> - Keep integrations simple</li>
                    <li>✗ <strong>NO Multi-language CMS</strong> - Basic CMS only</li>
                </ul>
            </div>
            
            <div style="padding: 16px; background: var(--bg-input); border-radius: 8px; border-left: 4px solid #6366f1;">
                <h3 style="color: #6366f1; margin-bottom: 12px;">🚀 Infonet.hr Hosting Setup:</h3>
                <ol style="line-height: 2; color: var(--text-secondary); padding-left: 20px;">
                    <li><strong>Login to Infonet.hr</strong> - Access your existing hosting account</li>
                    <li><strong>Connect domain</strong> (if new domain):
                        <ul style="list-style: disc; margin-top: 8px; padding-left: 20px;">
                            <li>Go to cPanel → Domains → Addon Domains (or use main domain)</li>
                            <li>Point domain DNS to Infonet nameservers (if external domain)</li>
                        </ul>
                    </li>
                    <li><strong>Create MySQL database</strong> via cPanel:
                        <ul style="list-style: disc; margin-top: 8px; padding-left: 20px;">
                            <li>Go to cPanel → MySQL Databases</li>
                            <li>Create new database (e.g., <code>clientname_db</code>)</li>
                            <li>Create database user with password</li>
                            <li>Add user to database with ALL PRIVILEGES</li>
                        </ul>
                    </li>
                    <li><strong>Run SQL schema</strong>:
                        <ul style="list-style: disc; margin-top: 8px; padding-left: 20px;">
                            <li>Go to cPanel → phpMyAdmin</li>
                            <li>Select the database</li>
                            <li>Import <code>install/schema.sql</code> file</li>
                            <li>Verify tables are created</li>
                        </ul>
                    </li>
                    <li><strong>Configure database connection</strong>:
                        <ul style="list-style: disc; margin-top: 8px; padding-left: 20px;">
                            <li>Edit <code>config/database.php</code></li>
                            <li>Set DB_HOST: <code>localhost</code></li>
                            <li>Set DB_NAME: <code>your_database_name</code></li>
                            <li>Set DB_USER and DB_PASS from cPanel</li>
                        </ul>
                    </li>
                    <li><strong>Upload all files</strong> via FTP to <code>public_html</code>:
                        <ul style="list-style: disc; margin-top: 8px; padding-left: 20px;">
                            <li>FTP Host: <code>ftp.infonet.hr</code></li>
                            <li>Upload all project files maintaining folder structure</li>
                        </ul>
                    </li>
                    <li><strong>Set file permissions</strong>:
                        <ul style="list-style: disc; margin-top: 8px; padding-left: 20px;">
                            <li>Folders: <code>755</code></li>
                            <li>Files: <code>644</code></li>
                            <li>Upload folders (images/uploads): <code>755</code></li>
                        </ul>
                    </li>
                    <li><strong>Set up email system</strong> - Configure SMTP in <code>config/database.php</code>:
                        <ul style="list-style: disc; margin-top: 8px; padding-left: 20px;">
                            <li>SMTP Host: <code>cp7.infonet.hr</code></li>
                            <li>SMTP Port: <code>465</code> (SSL)</li>
                            <li>Get email credentials from cPanel → Email Accounts</li>
                            <li>Set SMTP_USER, SMTP_PASS, SMTP_FROM</li>
                        </ul>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Premium Website Tier -->
<div class="card" style="border-left: 4px solid #a855f7;">
    <div class="card__header">
        <h2 class="card__title" style="color: #a855f7;">⭐ Premium Stranica (Premium Website) - €1000</h2>
        <p style="color: var(--text-secondary); margin-top: 8px;">ETA: 7 dana | E-commerce, multi-user, payment integration</p>
    </div>
    <div class="card__body">
        <div style="display: grid; gap: 20px;">
            <div style="padding: 16px; background: var(--bg-input); border-radius: 8px;">
                <h3 style="color: var(--primary); margin-bottom: 12px;">✅ What to Include (DO):</h3>
                <ul style="list-style: none; padding: 0; line-height: 1.8; color: var(--text-secondary);">
                    <li>✓ <strong>Everything from Professional tier</strong> (CMS, blog, gallery)</li>
                    <li>✓ <strong>E-commerce</strong> - Product catalog, shopping cart</li>
                    <li>✓ <strong>User System</strong> - Registration, login, user profiles</li>
                    <li>✓ <strong>Payment Integration</strong> - Stripe or PayPal (choose one, not both)</li>
                    <li>✓ <strong>Order Management</strong> - Admin can view/manage orders</li>
                    <li>✓ <strong>Email Notifications</strong> - Order confirmations, receipts</li>
                    <li>✓ <strong>Advanced Analytics</strong> - Track sales, user behavior</li>
                </ul>
            </div>
            
            <div style="padding: 16px; background: var(--bg-input); border-radius: 8px; border-left: 4px solid #a855f7;">
                <h3 style="color: #a855f7; margin-bottom: 12px;">🔧 Exact Technologies & External Services:</h3>
                <div style="display: grid; gap: 12px;">
                    <div>
                        <strong style="color: var(--text-primary);">Everything from Professional tier PLUS:</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• All backend technologies (PHP, MySQL, PDO)</li>
                            <li>• Cloudflare Turnstile</li>
                            <li>• Umami Analytics (with API)</li>
                            <li>• Infonet SMTP email</li>
                            <li>• Google Fonts, Bootstrap 5</li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">Payment Gateway (CHOOSE ONE):</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• <strong>Stripe:</strong>
                                <ul style="list-style: disc; margin-top: 4px; padding-left: 20px;">
                                    <li>Get API keys from: <a href="https://dashboard.stripe.com" target="_blank" style="color: var(--primary);">Stripe Dashboard</a></li>
                                    <li>Use Stripe Checkout or Stripe Elements</li>
                                    <li>Test with test keys first (pk_test_... and sk_test_...)</li>
                                    <li>Store keys in <code>config/database.php</code> or separate config file</li>
                                </ul>
                            </li>
                            <li>• <strong>PayPal:</strong>
                                <ul style="list-style: disc; margin-top: 4px; padding-left: 20px;">
                                    <li>Get credentials from: <a href="https://developer.paypal.com" target="_blank" style="color: var(--primary);">PayPal Developer</a></li>
                                    <li>Use PayPal REST API</li>
                                    <li>Client ID and Secret Key</li>
                                    <li>Test in sandbox mode first</li>
                                </ul>
                            </li>
                            <li style="color: var(--danger); margin-top: 8px;">⚠️ <strong>IMPORTANT:</strong> Only implement ONE payment gateway, not both!</li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">Extended Database Schema:</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• Products table (id, name, description, price, image, stock, etc.)</li>
                            <li>• Orders table (id, user_id, total, status, payment_id, created_at)</li>
                            <li>• Order_items table (order_id, product_id, quantity, price)</li>
                            <li>• Users table (id, email, password_hash, name, created_at)</li>
                            <li>• User sessions table (optional, for "remember me")</li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">User Authentication:</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• Registration form with validation</li>
                            <li>• Login system with sessions</li>
                            <li>• Password hashing: <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">password_hash()</code> and <code style="background: var(--bg-dark); padding: 2px 6px; border-radius: 4px;">password_verify()</code></li>
                            <li>• Email verification (optional but recommended)</li>
                            <li>• Password reset functionality</li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">Shopping Cart:</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• Session-based cart (store in <code>$_SESSION['cart']</code>)</li>
                            <li>• Add to cart, remove from cart, update quantities</li>
                            <li>• Cart persists for logged-in users (optional: save to database)</li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">Order Processing:</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• Checkout page with billing/shipping info</li>
                            <li>• Payment processing via chosen gateway</li>
                            <li>• Order confirmation emails (via Infonet SMTP)</li>
                            <li>• Admin notification emails for new orders</li>
                            <li>• Order status management in custom admin panel (built from scratch in PHP)</li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">Admin Panel (CUSTOM BUILT FROM SCRATCH):</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• <strong>Build completely custom admin</strong> - NO pre-built admin frameworks (NO AdminLTE, NO Admin Panel templates)</li>
                            <li>• <strong>Pure PHP</strong> - Build from scratch using PHP, HTML, CSS, JavaScript</li>
                            <li>• <strong>Custom authentication</strong> - Login/authentication system built from scratch</li>
                            <li>• <strong>Custom UI</strong> - Design your own admin interface</li>
                            <li>• Features to include:
                                <ul style="list-style: disc; margin-top: 4px; padding-left: 20px;">
                                    <li>Product management (CRUD)</li>
                                    <li>Order management (view, update status, mark as shipped)</li>
                                    <li>User management (view registered users)</li>
                                    <li>Sales analytics/dashboard</li>
                                    <li>All features from Professional tier admin</li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary);">Security (CRITICAL for payments):</strong>
                        <ul style="list-style: none; padding: 0; margin-top: 8px; line-height: 1.8; color: var(--text-secondary);">
                            <li>• <strong>MUST use HTTPS</strong> - SSL certificate required (Let's Encrypt free)</li>
                            <li>• Validate all payment data server-side</li>
                            <li>• Never store credit card info</li>
                            <li>• Use prepared statements for all database queries</li>
                            <li>• Rate limit payment attempts</li>
                            <li>• CSRF protection on all forms</li>
                            <li>• Sanitize all user inputs</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div style="padding: 16px; background: var(--bg-input); border-radius: 8px;">
                <h3 style="color: var(--danger); margin-bottom: 12px;">❌ What NOT to Include (DON'T):</h3>
                <ul style="list-style: none; padding: 0; line-height: 1.8; color: var(--text-secondary);">
                    <li>✗ <strong>NO Multiple Payment Gateways</strong> - Choose ONE (Stripe OR PayPal)</li>
                    <li>✗ <strong>NO Complex Inventory</strong> - Keep product management simple</li>
                    <li>✗ <strong>NO Subscription System</strong> - One-time payments only</li>
                    <li>✗ <strong>NO Advanced Reporting</strong> - Basic sales tracking only</li>
                    <li>✗ <strong>NO Mobile App</strong> - Web only</li>
                </ul>
            </div>
            
            <div style="padding: 16px; background: var(--bg-input); border-radius: 8px; border-left: 4px solid #a855f7;">
                <h3 style="color: #a855f7; margin-bottom: 12px;">🚀 Infonet.hr Hosting Setup:</h3>
                <ol style="line-height: 2; color: var(--text-secondary); padding-left: 20px;">
                    <li><strong>Login to Infonet.hr</strong> - Access your existing hosting account</li>
                    <li><strong>Connect domain</strong> (if new domain):
                        <ul style="list-style: disc; margin-top: 8px; padding-left: 20px;">
                            <li>Go to cPanel → Domains → Addon Domains (or use main domain)</li>
                            <li>Point domain DNS to Infonet nameservers (if external domain)</li>
                        </ul>
                    </li>
                    <li><strong>Follow Professional tier database setup</strong> (steps 3-5 from Professional tier)</li>
                    <li><strong>Extended database schema</strong>:
                        <ul style="list-style: disc; margin-top: 8px; padding-left: 20px;">
                            <li>Add products table</li>
                            <li>Add orders table</li>
                            <li>Add users table (if not already in schema)</li>
                            <li>Add order_items table</li>
                        </ul>
                    </li>
                    <li><strong>Upload all files</strong> via FTP to <code>public_html</code>:
                        <ul style="list-style: disc; margin-top: 8px; padding-left: 20px;">
                            <li>FTP Host: <code>ftp.infonet.hr</code></li>
                            <li>Upload all project files maintaining folder structure</li>
                        </ul>
                    </li>
                    <li><strong>Set file permissions</strong> (same as Professional tier)</li>
                    <li><strong>Set up email system</strong> - Configure SMTP in <code>config/database.php</code>:
                        <ul style="list-style: disc; margin-top: 8px; padding-left: 20px;">
                            <li>SMTP Host: <code>cp7.infonet.hr</code></li>
                            <li>SMTP Port: <code>465</code> (SSL)</li>
                            <li>Get email credentials from cPanel → Email Accounts</li>
                        </ul>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Custom Project Tier -->
<div class="card" style="border-left: 4px solid #f59e0b;">
    <div class="card__header">
        <h2 class="card__title" style="color: #f59e0b;">🎯 Custom Projekt (Custom Project) - Po dogovoru</h2>
        <p style="color: var(--text-secondary); margin-top: 8px;">Fully customized solution based on client requirements</p>
    </div>
    <div class="card__body">
        <div style="padding: 16px; background: var(--bg-input); border-radius: 8px;">
            <h3 style="color: var(--primary); margin-bottom: 12px;">📋 Process:</h3>
            <ol style="line-height: 2; color: var(--text-secondary); padding-left: 20px;">
                <li><strong>Client consultation</strong> - Understand specific requirements</li>
                <li><strong>Create project plan</strong> - Document all features and scope</li>
                <li><strong>Get approval</strong> - Client must approve plan before starting</li>
                <li><strong>Choose base tier</strong> - Start with closest tier (Professional or Premium)</li>
                <li><strong>Add custom features</strong> - Implement specific requirements</li>
                <li><strong>Follow hosting steps</strong> - Use appropriate tier's Infonet.hr setup guide</li>
                <li><strong>Custom integrations</strong> - Add any required third-party services</li>
                <li><strong>Testing</strong> - Thorough testing of all custom features</li>
            </ol>
            <p style="color: var(--text-secondary); margin-top: 16px; padding: 12px; background: var(--bg-dark); border-radius: 8px;">
                <strong>⚠️ Important:</strong> Document everything! Keep track of custom features, APIs used, and any deviations from standard tiers.
            </p>
        </div>
    </div>
</div>

<!-- Quick Reference Section -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">⚡ Quick Reference - Infonet.hr</h2>
    </div>
    <div class="card__body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div style="padding: 16px; background: var(--bg-input); border-radius: 8px;">
                <h3 style="color: var(--primary); margin-bottom: 12px;">FTP Connection</h3>
                <div style="font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.8; color: var(--text-secondary);">
                    <div>Host: <span style="color: var(--text-primary);">ftp.infonet.hr</span></div>
                    <div>Port: <span style="color: var(--text-primary);">21</span></div>
                    <div>Username: <span style="color: var(--text-primary);">cpanel_username</span></div>
                    <div>Password: <span style="color: var(--text-primary);">cpanel_password</span></div>
                </div>
            </div>
            
            <div style="padding: 16px; background: var(--bg-input); border-radius: 8px;">
                <h3 style="color: var(--primary); margin-bottom: 12px;">Database Connection</h3>
                <div style="font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.8; color: var(--text-secondary);">
                    <div>Host: <span style="color: var(--text-primary);">localhost</span></div>
                    <div>Database: <span style="color: var(--text-primary);">cpanel_username_dbname</span></div>
                    <div>Username: <span style="color: var(--text-primary);">cpanel_username_dbuser</span></div>
                    <div>Password: <span style="color: var(--text-primary);">database_password</span></div>
                </div>
            </div>
            
            <div style="padding: 16px; background: var(--bg-input); border-radius: 8px;">
                <h3 style="color: var(--primary); margin-bottom: 12px;">SMTP Settings</h3>
                <div style="font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.8; color: var(--text-secondary);">
                    <div>Host: <span style="color: var(--text-primary);">cp7.infonet.hr</span></div>
                    <div>Port: <span style="color: var(--text-primary);">465</span></div>
                    <div>Security: <span style="color: var(--text-primary);">SSL</span></div>
                    <div>Auth: <span style="color: var(--text-primary);">Required</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
