<?php
/**
 * Admin - View Single Message
 */
$pageTitle = 'Pregled poruke';
require_once 'includes/header.php';

$msg = null;
$error = '';

if (!dbAvailable()) {
    $error = 'Baza podataka nije povezana.';
} else {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id > 0) {
        try {
            // Mark as read
            $stmt = db()->prepare("UPDATE contact_submissions SET is_read = 1 WHERE id = ?");
            $stmt->execute([$id]);
            
            // Get message
            $stmt = db()->prepare("SELECT * FROM contact_submissions WHERE id = ?");
            $stmt->execute([$id]);
            $msg = $stmt->fetch();
            
            if (!$msg) {
                $error = 'Poruka nije pronađena.';
            }
        } catch (Exception $e) {
            $error = 'Greška pri učitavanju poruke.';
        }
    } else {
        $error = 'Nevažeći ID poruke.';
    }
}
?>

<?php if ($error): ?>
    <div class="alert alert--error"><?php echo $error; ?></div>
    <a href="messages.php" class="btn btn--secondary">← Natrag na poruke</a>
<?php elseif ($msg): ?>

<div class="card">
    <div class="card__header">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div>
                <h2 class="card__title">Poruka od <?php echo htmlspecialchars($msg['name']); ?></h2>
                <?php if (isset($msg['is_replied']) && $msg['is_replied']): ?>
                    <span class="badge badge--success" style="margin-top: 8px; display: inline-block;">Odgovoreno</span>
                <?php endif; ?>
            </div>
            <a href="messages.php" class="btn btn--secondary btn--sm">← Natrag</a>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <div>
            <label style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Ime</label>
            <p style="color: var(--text-primary); font-size: 16px; margin-top: 4px;"><?php echo htmlspecialchars($msg['name']); ?></p>
        </div>
        
        <div>
            <label style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Email</label>
            <p style="margin-top: 4px;">
                <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" style="color: var(--primary); font-size: 16px;">
                    <?php echo htmlspecialchars($msg['email']); ?>
                </a>
            </p>
        </div>
        
        <div>
            <label style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Telefon</label>
            <p style="color: var(--text-primary); font-size: 16px; margin-top: 4px;">
                <?php if ($msg['phone']): ?>
                    <a href="tel:<?php echo htmlspecialchars($msg['phone']); ?>" style="color: var(--primary);">
                        <?php echo htmlspecialchars($msg['phone']); ?>
                    </a>
                <?php else: ?>
                    <span style="color: var(--text-muted);">-</span>
                <?php endif; ?>
            </p>
        </div>
        
        <div>
            <label style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Datum</label>
            <p style="color: var(--text-primary); font-size: 16px; margin-top: 4px;">
                <?php echo date('d.m.Y H:i', strtotime($msg['created_at'])); ?>
            </p>
        </div>
    </div>
    
    <div style="border-top: 1px solid var(--border-color); padding-top: 20px;">
        <label style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Poruka</label>
        <div style="background: var(--bg-input); padding: 20px; border-radius: 8px; color: var(--text-primary); line-height: 1.7; white-space: pre-wrap;"><?php echo htmlspecialchars($msg['message']); ?></div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Tehnički detalji</h2>
    </div>
    
    <div style="display: grid; gap: 12px; font-size: 14px;">
        <div>
            <label style="color: var(--text-muted);">IP adresa:</label>
            <span style="color: var(--text-secondary); margin-left: 8px;"><?php echo htmlspecialchars($msg['ip_address'] ?? '-'); ?></span>
        </div>
        <div>
            <label style="color: var(--text-muted);">User Agent:</label>
            <span style="color: var(--text-secondary); margin-left: 8px; word-break: break-all;"><?php echo htmlspecialchars($msg['user_agent'] ?? '-'); ?></span>
        </div>
    </div>
</div>

<div style="display: flex; gap: 12px; flex-wrap: wrap;">
    <?php if (!isset($msg['is_replied']) || !$msg['is_replied']): ?>
    <button type="button" class="btn btn--primary" onclick="openReplyModal()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
            <polyline points="22,6 12,13 2,6"></polyline>
        </svg>
        Odgovori na email
    </button>
    <?php else: ?>
    <button type="button" class="btn btn--secondary" disabled style="min-width: 150px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-7.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        Već odgovoreno
    </button>
    <?php endif; ?>
    <a href="messages.php?delete=<?php echo $msg['id']; ?>" class="btn btn--danger" onclick="return confirm('Jeste li sigurni da želite obrisati ovu poruku?')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
        </svg>
        Obriši poruku
    </a>
</div>

<!-- Reply Modal -->
<div id="replyModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="max-width: 900px; width: 95%; max-height: 95vh; overflow-y: auto; margin: 20px;">
        <div class="card__header">
            <h2 class="card__title">Odgovori na email</h2>
            <button type="button" onclick="closeReplyModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-secondary);">&times;</button>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;" id="replyModalContent">
            <!-- Left Column: Form -->
            <div>
                <form id="replyForm" onsubmit="sendReply(event)">
                    <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                    <input type="hidden" name="recipient_name" value="<?php echo htmlspecialchars($msg['name']); ?>">
                    <input type="hidden" name="recipient_email" value="<?php echo htmlspecialchars($msg['email']); ?>">
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: var(--text-primary); font-weight: 500;">Za:</label>
                        <input type="text" value="<?php echo htmlspecialchars($msg['email']); ?>" readonly style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-input); color: var(--text-secondary); cursor: not-allowed;">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;" id="languageGenderRow">
                        <div>
                            <label style="display: block; margin-bottom: 8px; color: var(--text-primary); font-weight: 500;">Jezik:</label>
                            <select name="reply_language" id="reply_language" onchange="updatePreview(); toggleGenderSelector()" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-family: inherit; font-size: 14px; background: var(--bg-input); color: var(--text-primary);">
                                <option value="hr">Hrvatski</option>
                                <option value="en">English</option>
                            </select>
                        </div>
                        <div id="genderSelectorContainer">
                            <label style="display: block; margin-bottom: 8px; color: var(--text-primary); font-weight: 500;">Pozdrav:</label>
                            <select name="reply_gender" id="reply_gender" onchange="updatePreview()" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-family: inherit; font-size: 14px; background: var(--bg-input); color: var(--text-primary);">
                                <option value="poštovani">Poštovani</option>
                                <option value="poštovana">Poštovana</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: var(--text-primary); font-weight: 500;">Predmet:</label>
                        <input type="text" name="reply_subject" id="reply_subject" oninput="savePreferences(); updatePreview()" onchange="savePreferences()" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-family: inherit; font-size: 14px; background: var(--bg-input); color: var(--text-primary);">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: var(--text-primary); font-weight: 500;">Vaš odgovor:</label>
                        <textarea name="reply_message" id="reply_message" required rows="12" oninput="updatePreview(); savePreferences()" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-family: inherit; font-size: 14px; resize: vertical; background: var(--bg-input); color: var(--text-primary);" placeholder="Napišite vaš odgovor..."></textarea>
                    </div>
                    
                    <div id="replyError" style="display: none; padding: 12px; background: #fee; color: #c33; border-radius: 6px; margin-bottom: 16px;"></div>
                    
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button type="button" class="btn btn--secondary" onclick="closeReplyModal()">Odustani</button>
                        <button type="submit" class="btn btn--primary" id="replySubmitBtn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                            Pošalji odgovor
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Right Column: Preview -->
            <div>
                <div style="margin-bottom: 12px;">
                    <label style="display: block; margin-bottom: 8px; color: var(--text-primary); font-weight: 500;">Pregled:</label>
                </div>
                <div id="emailPreview" style="border: 1px solid var(--border-color); border-radius: 6px; padding: 20px; background: var(--bg-input); font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; max-height: 600px; overflow-y: auto; color: var(--text-primary);">
                    <!-- Preview will be generated here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const recipientName = '<?php echo htmlspecialchars($msg['name'], ENT_QUOTES); ?>';
const recipientEmail = '<?php echo htmlspecialchars($msg['email'], ENT_QUOTES); ?>';

// Load saved preferences
function loadPreferences() {
    const saved = localStorage.getItem('replyPreferences');
    if (saved) {
        try {
            const prefs = JSON.parse(saved);
            if (prefs.language) document.getElementById('reply_language').value = prefs.language;
            if (prefs.gender) document.getElementById('reply_gender').value = prefs.gender;
            if (prefs.subject) {
                document.getElementById('reply_subject').value = prefs.subject;
            } else {
                // Set default subject based on language
                const lang = prefs.language || 'hr';
                document.getElementById('reply_subject').value = lang === 'en' ? 'Re: Your message from the website' : 'Re: Vaša poruka s web stranice';
            }
            if (prefs.message) document.getElementById('reply_message').value = prefs.message;
        } catch (e) {
            console.error('Error loading preferences:', e);
            // Set defaults on error
            setDefaults();
        }
    } else {
        setDefaults();
    }
    updatePreview();
}

function setDefaults() {
    document.getElementById('reply_language').value = 'hr';
    document.getElementById('reply_gender').value = 'poštovani';
    document.getElementById('reply_subject').value = 'Re: Vaša poruka s web stranice';
}

// Save preferences
function savePreferences() {
    const prefs = {
        language: document.getElementById('reply_language').value,
        gender: document.getElementById('reply_gender').value,
        subject: document.getElementById('reply_subject').value,
        message: document.getElementById('reply_message').value
    };
    localStorage.setItem('replyPreferences', JSON.stringify(prefs));
}

// Toggle gender selector visibility
function toggleGenderSelector() {
    const language = document.getElementById('reply_language').value;
    const genderContainer = document.getElementById('genderSelectorContainer');
    const genderSelect = document.getElementById('reply_gender');
    
    if (language === 'en') {
        genderContainer.style.display = 'none';
        // Set default to poštovani when Croatian is selected
        genderSelect.value = 'poštovani';
    } else {
        genderContainer.style.display = 'block';
    }
    updatePreview();
}

// Update email preview
function updatePreview() {
    const language = document.getElementById('reply_language').value;
    const gender = document.getElementById('reply_gender').value;
    const subject = document.getElementById('reply_subject').value || (language === 'en' ? 'Re: Your message from the website' : 'Re: Vaša poruka s web stranice');
    const message = document.getElementById('reply_message').value;
    
    const greetings = {
        hr: {
            'poštovani': 'Poštovani',
            'poštovana': 'Poštovana'
        },
        en: {
            'poštovani': 'Dear',
            'poštovana': 'Dear'
        }
    };
    
    const closings = {
        hr: {
            text: 'Srdačan pozdrav,',
            company: 'Start Smart HR'
        },
        en: {
            text: 'Best regards,',
            company: 'Start Smart HR'
        }
    };
    
    const greeting = greetings[language][gender] || greetings.hr['poštovani'];
    const closing = closings[language] || closings.hr;
    
    const companyInfo = `
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h3 style="margin: 0 0 10px 0; color: #6366f1; font-size: 18px;">Start Smart HR</h3>
                <p style="margin: 0; color: #6b7280; font-size: 12px;">Start Smart, zajednički obrt za izradu i optimizaciju web stranica, vl. Mihael Kovačić i Roko Nevistić</p>
            </div>
            <div style="text-align: center; margin-bottom: 15px;">
                <p style="margin: 5px 0; color: #374151; font-size: 13px;"><strong>Email:</strong> <a href="mailto:contact@startsmarthr.eu" style="color: #6366f1; text-decoration: none;">contact@startsmarthr.eu</a></p>
                <p style="margin: 5px 0; color: #374151; font-size: 13px;"><strong>Telefon:</strong> <a href="tel:+385996105673" style="color: #6366f1; text-decoration: none;">+385 99 610 5673</a> | <a href="tel:+385958374220" style="color: #6366f1; text-decoration: none;">+385 95 837 4220</a></p>
                <p style="margin: 5px 0; color: #374151; font-size: 13px;"><strong>Adresa:</strong> Seljine Brigade 72, Velika Gorica, Hrvatska</p>
            </div>
            <div style="text-align: center;">
                <div style="display: inline-flex; gap: 15px; align-items: center;">
                    <a href="https://www.facebook.com/people/Start-Smart-HR/61581505773838/" target="_blank" style="color: #6366f1; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        <span style="font-size: 12px;">Facebook</span>
                    </a>
                    <a href="https://www.instagram.com/startsmarthr.eu/" target="_blank" style="color: #6366f1; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                        <span style="font-size: 12px;">Instagram</span>
                    </a>
                </div>
            </div>
        </div>
    `;
    
    const previewHTML = `
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e5e7eb;">
                <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Predmet:</div>
                <div style="font-weight: 600; color: #1f2937;">${subject}</div>
            </div>
            <div style="margin-bottom: 15px;">
                <p style="margin: 0 0 15px 0; color: #374151;">${greeting} ${recipientName},</p>
                ${message ? `<div style="padding: 15px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #6366f1; margin-bottom: 15px; white-space: pre-wrap; color: #374151;">${message.replace(/\n/g, '<br>')}</div>` : '<div style="padding: 15px; background: #f8f9fa; border-radius: 6px; color: #9ca3af; font-style: italic;">Vaš odgovor će se prikazati ovdje...</div>'}
                <p style="margin: 15px 0 0 0; color: #6b7280;">
                    ${closing.text}<br>
                    <strong style="color: #1f2937;">${closing.company}</strong>
                </p>
                ${companyInfo}
            </div>
        </div>
    `;
    
    document.getElementById('emailPreview').innerHTML = previewHTML;
}

function openReplyModal() {
    document.getElementById('replyModal').style.display = 'flex';
    loadPreferences();
    toggleGenderSelector(); // Set initial visibility
    setTimeout(() => {
        document.getElementById('reply_message').focus();
    }, 100);
}

function closeReplyModal() {
    document.getElementById('replyModal').style.display = 'none';
    document.getElementById('replyForm').reset();
    document.getElementById('replyError').style.display = 'none';
    // Don't clear preferences, keep them for next time
}

async function sendReply(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const submitBtn = document.getElementById('replySubmitBtn');
    const errorDiv = document.getElementById('replyError');
    
    // Disable submit button
    submitBtn.disabled = true;
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> Šalje se...';
    errorDiv.style.display = 'none';
    
    try {
        const response = await fetch('reply-handler.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Clear message from preferences but keep other settings
            const prefs = JSON.parse(localStorage.getItem('replyPreferences') || '{}');
            prefs.message = '';
            localStorage.setItem('replyPreferences', JSON.stringify(prefs));
            // Success - reload page to show updated status
            window.location.reload();
        } else {
            // Show error
            errorDiv.textContent = data.message || 'Greška pri slanju odgovora.';
            errorDiv.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    } catch (error) {
        errorDiv.textContent = 'Greška pri slanju odgovora. Molimo pokušajte ponovno.';
        errorDiv.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// Close modal on outside click
document.getElementById('replyModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeReplyModal();
    }
});

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('replyModal').style.display === 'flex') {
        closeReplyModal();
    }
});

// Make modal responsive
function adjustModalLayout() {
    const modalContent = document.getElementById('replyModalContent');
    if (window.innerWidth < 900) {
        modalContent.style.gridTemplateColumns = '1fr';
    } else {
        modalContent.style.gridTemplateColumns = '1fr 1fr';
    }
}

window.addEventListener('resize', adjustModalLayout);
adjustModalLayout();

// Update preview when language or gender changes
document.getElementById('reply_language').addEventListener('change', function() {
    savePreferences();
    toggleGenderSelector();
    // Update subject based on language
    const lang = this.value;
    const currentSubject = document.getElementById('reply_subject').value;
    if (!currentSubject || currentSubject === 'Re: Vaša poruka s web stranice' || currentSubject === 'Re: Your message from the website') {
        document.getElementById('reply_subject').value = lang === 'en' ? 'Re: Your message from the website' : 'Re: Vaša poruka s web stranice';
    }
    updatePreview();
});

document.getElementById('reply_gender').addEventListener('change', function() {
    savePreferences();
    updatePreview();
});
</script>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

