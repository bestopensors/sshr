<?php
/**
 * Admin - Contacts/Leads Management
 */
// Start output buffering to prevent header issues
ob_start();

// Include required files BEFORE any output
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireAuth();

$pageTitle = 'Kontakti';

// Initialize variables
$contacts = [];
$message = '';
$error = '';

// Handle form submission (add/edit) - MUST be before header include
if ($_SERVER['REQUEST_METHOD'] === 'POST' && dbAvailable()) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $business_name = trim($_POST['business_name'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $notes_type = isset($_POST['notes_type']) && in_array($_POST['notes_type'], ['regular', 'bullets']) ? $_POST['notes_type'] : 'regular';
    $is_contacted = isset($_POST['is_contacted']) ? 1 : 0;
    
    if (empty($business_name)) {
        $error = 'Naziv tvrtke je obavezan.';
    } else {
        try {
            if ($id > 0) {
                // Update existing contact
                $contacted_date = $is_contacted ? date('Y-m-d H:i:s') : null;
                if (!$is_contacted) {
                    // If unchecking, clear contacted_date
                    $stmt = db()->prepare("UPDATE contacts SET business_name = ?, contact_phone = ?, contact_email = ?, notes = ?, notes_type = ?, is_contacted = ?, contacted_date = ? WHERE id = ?");
                    $stmt->execute([$business_name, $contact_phone, $contact_email, $notes, $notes_type, 0, null, $id]);
                } else {
                    // If checking, set contacted_date if not already set
                    $stmt = db()->prepare("UPDATE contacts SET business_name = ?, contact_phone = ?, contact_email = ?, notes = ?, notes_type = ?, is_contacted = ?, contacted_date = COALESCE(contacted_date, ?) WHERE id = ?");
                    $stmt->execute([$business_name, $contact_phone, $contact_email, $notes, $notes_type, 1, date('Y-m-d H:i:s'), $id]);
                }
                $message = 'Kontakt uspješno ažuriran.';
            } else {
                // Insert new contact
                $contacted_date = $is_contacted ? date('Y-m-d H:i:s') : null;
                $stmt = db()->prepare("INSERT INTO contacts (business_name, contact_phone, contact_email, notes, notes_type, is_contacted, contacted_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$business_name, $contact_phone, $contact_email, $notes, $notes_type, $is_contacted, $contacted_date]);
                $message = 'Kontakt uspješno dodan.';
            }
            
            // Redirect to prevent modal from showing after successful save
            if ($message) {
                ob_end_clean(); // Clear any output buffer
                header('Location: contacts.php?success=1');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Greška pri spremanju kontakta: ' . $e->getMessage();
        }
    }
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $msg = $_GET['msg'] ?? 'saved';
    switch ($msg) {
        case 'deleted':
            $message = 'Kontakt uspješno obrisan.';
            break;
        case 'toggled':
            $message = 'Status kontakta ažuriran.';
            break;
        default:
            $message = 'Kontakt uspješno spremljen.';
    }
    // Clear edit parameter to prevent modal from showing
    unset($_GET['edit']);
    $editContact = null;
}

// Handle delete
if (isset($_GET['delete']) && dbAvailable()) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = db()->prepare("DELETE FROM contacts WHERE id = ?");
        $stmt->execute([$id]);
        ob_end_clean(); // Clear any output buffer
        header('Location: contacts.php?success=1&msg=deleted');
        exit;
    } catch (Exception $e) {
        $error = 'Greška pri brisanju kontakta.';
    }
}

// Handle toggle contacted status
if (isset($_GET['toggle_contacted']) && dbAvailable()) {
    $id = (int)$_GET['toggle_contacted'];
    try {
        // Get current status
        $stmt = db()->prepare("SELECT is_contacted FROM contacts WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch();
        
        if ($current) {
            $new_status = $current['is_contacted'] ? 0 : 1;
            $contacted_date = $new_status ? date('Y-m-d H:i:s') : null;
            
            if ($new_status) {
                $stmt = db()->prepare("UPDATE contacts SET is_contacted = ?, contacted_date = COALESCE(contacted_date, ?) WHERE id = ?");
                $stmt->execute([$new_status, date('Y-m-d H:i:s'), $id]);
            } else {
                $stmt = db()->prepare("UPDATE contacts SET is_contacted = ?, contacted_date = ? WHERE id = ?");
                $stmt->execute([$new_status, null, $id]);
            }
            ob_end_clean(); // Clear any output buffer
            header('Location: contacts.php?success=1&msg=toggled');
            exit;
        }
    } catch (Exception $e) {
        $error = 'Greška pri ažuriranju statusa.';
    }
}

// Get filter and sort parameters
$filter_status = $_GET['filter'] ?? 'all'; // all, contacted, not_contacted
$sort_by = $_GET['sort'] ?? 'created_at'; // business_name, created_at, contacted_date
$sort_order = $_GET['order'] ?? 'desc'; // asc, desc
$search = $_GET['search'] ?? '';

// Build query
if (dbAvailable()) {
    try {
        $where = [];
        $params = [];
        
        if ($filter_status === 'contacted') {
            $where[] = "is_contacted = 1";
        } elseif ($filter_status === 'not_contacted') {
            $where[] = "is_contacted = 0";
        }
        
        if (!empty($search)) {
            $where[] = "(business_name LIKE ? OR contact_email LIKE ? OR contact_phone LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // Validate sort column
        $allowedSorts = ['business_name', 'created_at', 'contacted_date'];
        if (!in_array($sort_by, $allowedSorts)) {
            $sort_by = 'created_at';
        }
        $sort_order = strtolower($sort_order) === 'asc' ? 'ASC' : 'DESC';
        
        $sql = "SELECT * FROM contacts {$whereClause} ORDER BY {$sort_by} {$sort_order}";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $contacts = $stmt->fetchAll();
    } catch (Exception $e) {
        // Table might not exist
        $error = 'Greška pri učitavanju kontakata. Provjerite je li tablica kreirana.';
    }
}

// Get contact for editing
$editContact = null;
if (isset($_GET['edit']) && dbAvailable()) {
    $id = (int)$_GET['edit'];
    try {
        $stmt = db()->prepare("SELECT * FROM contacts WHERE id = ?");
        $stmt->execute([$id]);
        $editContact = $stmt->fetch();
    } catch (Exception $e) {
        $error = 'Greška pri učitavanju kontakta.';
    }
}

// Now include header AFTER all form processing and redirects
require_once 'includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert--success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert--error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if (!dbAvailable()): ?>
<div class="alert alert--error">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
    </svg>
    <span>Baza podataka nije povezana. Kontakti će se prikazivati nakon konfiguracije baze.</span>
</div>
<?php endif; ?>

<div class="card">
    <div class="card__header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <h2 class="card__title">Kontakti / Potencijalni klijenti</h2>
            <button class="btn btn--primary" onclick="document.getElementById('contact-form-modal').style.display='block'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Dodaj kontakt
            </button>
        </div>
    </div>
    
    <!-- Filters and Search -->
    <div class="card__body filters-section">
        <form method="GET" class="filters-form">
            <div class="filters-row">
                <div class="filter-group filter-group--search">
                    <svg class="filter-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" name="search" placeholder="Pretraži po nazivu, emailu ili telefonu..." value="<?php echo htmlspecialchars($search); ?>" class="filter-input">
                </div>
                
                <div class="filter-group">
                    <select name="filter" class="filter-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Svi kontakti</option>
                        <option value="contacted" <?php echo $filter_status === 'contacted' ? 'selected' : ''; ?>>Kontaktirani</option>
                        <option value="not_contacted" <?php echo $filter_status === 'not_contacted' ? 'selected' : ''; ?>>Nekontaktirani</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <select name="sort" class="filter-select" onchange="this.form.submit()">
                        <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Datum dodavanja</option>
                        <option value="business_name" <?php echo $sort_by === 'business_name' ? 'selected' : ''; ?>>Naziv tvrtke</option>
                        <option value="contacted_date" <?php echo $sort_by === 'contacted_date' ? 'selected' : ''; ?>>Datum kontakta</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <select name="order" class="filter-select" onchange="this.form.submit()">
                        <option value="desc" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Opadajuće</option>
                        <option value="asc" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Rastuće</option>
                    </select>
                </div>
                
                <?php if (!empty($search) || $filter_status !== 'all'): ?>
                    <a href="contacts.php" class="btn btn--outline filter-reset">Resetiraj</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <?php if (empty($contacts)): ?>
        <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <h3>Nema kontakata</h3>
            <p>Dodajte prvi kontakt kako biste započeli praćenje potencijalnih klijenata.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Naziv tvrtke</th>
                        <th>Telefon</th>
                        <th>Email</th>
                        <th>Napomene</th>
                        <th>Datum dodavanja</th>
                        <th>Datum kontakta</th>
                        <th>Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $contact): ?>
                    <tr style="background: <?php echo $contact['is_contacted'] ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)'; ?>;">
                        <td>
                            <span class="badge badge-contact" style="background: <?php echo $contact['is_contacted'] ? '#10b981' : '#ef4444'; ?>; color: white; white-space: nowrap;">
                                <?php echo $contact['is_contacted'] ? '✓ Kontaktiran' : '✗ Nekontaktiran'; ?>
                            </span>
                        </td>
                        <td><strong><?php echo htmlspecialchars($contact['business_name']); ?></strong></td>
                        <td>
                            <?php if ($contact['contact_phone']): ?>
                                <a href="tel:<?php echo htmlspecialchars($contact['contact_phone']); ?>"><?php echo htmlspecialchars($contact['contact_phone']); ?></a>
                            <?php else: ?>
                                <span style="color: var(--text-secondary);">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($contact['contact_email']): ?>
                                <a href="mailto:<?php echo htmlspecialchars($contact['contact_email']); ?>"><?php echo htmlspecialchars($contact['contact_email']); ?></a>
                            <?php else: ?>
                                <span style="color: var(--text-secondary);">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($contact['notes']): ?>
                                <button class="btn btn--sm btn--secondary" 
                                        data-contact-id="<?php echo $contact['id']; ?>"
                                        data-notes="<?php echo htmlspecialchars($contact['notes'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-notes-type="<?php echo htmlspecialchars($contact['notes_type'] ?? 'regular'); ?>"
                                        onclick="viewNotesFromButton(this)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    Pregled
                                </button>
                            <?php else: ?>
                                <span style="color: var(--text-secondary);">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($contact['created_at'])); ?></td>
                        <td>
                            <?php if ($contact['contacted_date']): ?>
                                <?php echo date('d.m.Y H:i', strtotime($contact['contacted_date'])); ?>
                            <?php else: ?>
                                <span style="color: var(--text-secondary);">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button onclick="confirmToggleContacted(<?php echo $contact['id']; ?>, <?php echo $contact['is_contacted'] ? 'true' : 'false'; ?>)" 
                                   class="btn btn--sm" 
                                   style="background: <?php echo $contact['is_contacted'] ? '#ef4444' : '#10b981'; ?>; color: white;">
                                    <?php echo $contact['is_contacted'] ? '✗' : '✓'; ?>
                                </button>
                                <a href="?edit=<?php echo $contact['id']; ?>" class="btn btn--sm btn--secondary">Uredi</a>
                                <button class="btn btn--sm btn--danger" 
                                        onclick="confirmDelete(<?php echo $contact['id']; ?>, '<?php echo htmlspecialchars(addslashes($contact['business_name'])); ?>')">Obriši</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="padding: 16px; border-top: 1px solid var(--border-color); background: var(--bg-secondary);">
            <p style="margin: 0; color: var(--text-secondary);">
                <strong>Ukupno:</strong> <?php echo count($contacts); ?> kontakt(a)
                <?php 
                $contacted_count = count(array_filter($contacts, fn($c) => $c['is_contacted']));
                $not_contacted_count = count($contacts) - $contacted_count;
                ?>
                | <span style="color: #10b981;">Kontaktirani: <?php echo $contacted_count; ?></span>
                | <span style="color: #ef4444;">Nekontaktirani: <?php echo $not_contacted_count; ?></span>
            </p>
        </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Contact Modal -->
<div id="contact-form-modal" class="modal" style="display: <?php echo ($editContact && !isset($_GET['success'])) ? 'block' : 'none'; ?>;">
    <div class="modal__content">
        <div class="modal__header">
            <h3><?php echo $editContact ? 'Uredi kontakt' : 'Dodaj novi kontakt'; ?></h3>
            <button class="modal__close" onclick="document.getElementById('contact-form-modal').style.display='none'; window.location.href='contacts.php';">&times;</button>
        </div>
        <form method="POST" class="modal__body">
            <?php if ($editContact): ?>
                <input type="hidden" name="id" value="<?php echo $editContact['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label class="form-label">Naziv tvrtke *</label>
                <input type="text" name="business_name" class="input" value="<?php echo htmlspecialchars($editContact['business_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Telefon</label>
                <input type="tel" name="contact_phone" class="input" value="<?php echo htmlspecialchars($editContact['contact_phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="contact_email" class="input" value="<?php echo htmlspecialchars($editContact['contact_email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Napomene</label>
                <div style="display: flex; gap: 12px; margin-bottom: 8px;">
                    <label class="form-radio">
                        <input type="radio" name="notes_type" value="regular" <?php echo ($editContact['notes_type'] ?? 'regular') === 'regular' ? 'checked' : ''; ?>>
                        <span>Običan tekst</span>
                    </label>
                    <label class="form-radio">
                        <input type="radio" name="notes_type" value="bullets" <?php echo ($editContact['notes_type'] ?? 'regular') === 'bullets' ? 'checked' : ''; ?>>
                        <span>Bullet liste (svaki red = bullet)</span>
                    </label>
                </div>
                <textarea name="notes" class="input" rows="4" placeholder="<?php echo ($editContact['notes_type'] ?? 'regular') === 'bullets' ? 'Svaki red će biti prikazan kao bullet točka' : 'Unesite napomene...'; ?>"><?php echo htmlspecialchars($editContact['notes'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" name="is_contacted" <?php echo ($editContact['is_contacted'] ?? 0) ? 'checked' : ''; ?>>
                    <span>Označi kao kontaktiran</span>
                </label>
            </div>
            
            <div class="modal__footer">
                <button type="button" class="btn btn--outline" onclick="document.getElementById('contact-form-modal').style.display='none'; window.location.href='contacts.php';">Odustani</button>
                <button type="submit" class="btn btn--primary"><?php echo $editContact ? 'Ažuriraj' : 'Dodaj'; ?></button>
            </div>
        </form>
    </div>
</div>

<style>
/* Filters Section */
.filters-section {
    padding: 20px !important;
    border-bottom: 1px solid var(--border) !important;
    background: var(--bg-card);
}

.filters-form {
    width: 100%;
}

.filters-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-group {
    position: relative;
    display: flex;
    align-items: center;
}

.filter-group--search {
    flex: 1;
    min-width: 250px;
}

.filter-icon {
    position: absolute;
    left: 12px;
    color: var(--text-secondary);
    pointer-events: none;
    z-index: 1;
}

.filter-input {
    width: 100%;
    padding: 10px 12px 10px 38px;
    background: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 14px;
    transition: all 0.2s ease;
}

.filter-input:focus {
    outline: none;
    border-color: var(--primary);
    background: var(--bg-card);
}

.filter-input::placeholder {
    color: var(--text-secondary);
}

.filter-select {
    padding: 10px 32px 10px 12px;
    background: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg width='12' height='12' viewBox='0 0 12 12' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M3 4.5L6 7.5L9 4.5' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    min-width: 160px;
}

.filter-select:hover {
    border-color: var(--primary);
}

.filter-select:focus {
    outline: none;
    border-color: var(--primary);
    background-color: var(--bg-card);
}

.filter-reset {
    margin-left: auto;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(4px);
    overflow: auto;
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.modal__content {
    background: var(--bg-card);
    margin: 50px auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
    border: 1px solid var(--border);
    animation: slideUp 0.3s ease;
    position: relative;
    z-index: 1001;
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal__header {
    padding: 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--bg-card);
}

.modal__header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: var(--text-primary);
}

.modal__close {
    background: var(--bg-input);
    border: none;
    font-size: 24px;
    line-height: 1;
    cursor: pointer;
    color: var(--text-secondary);
    padding: 8px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.modal__close:hover {
    background: var(--bg-dark);
    color: var(--text-primary);
}

.modal__body {
    padding: 24px;
    background: var(--bg-card);
}

.modal__body .input {
    background: var(--bg-input);
    border: 1px solid var(--border);
    color: var(--text-primary);
}

.modal__body .input:focus {
    background: var(--bg-card);
    border-color: var(--primary);
}

.modal__footer {
    padding: 24px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: var(--bg-card);
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--text-primary);
    font-size: 14px;
}

.form-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
}

.form-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--primary);
}

.form-checkbox span {
    color: var(--text-primary);
    font-size: 14px;
}

.form-radio {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
}

.form-radio input[type="radio"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--primary);
}

.form-radio span {
    color: var(--text-primary);
    font-size: 14px;
}

.badge-contact {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
}

.modal__content--small {
    max-width: 450px;
}

.notes-preview {
    color: var(--text-primary);
}
</style>

<!-- Confirmation Modal -->
<div id="confirm-modal" class="modal" style="display: none;">
    <div class="modal__content modal__content--small">
        <div class="modal__header">
            <h3>Potvrda</h3>
            <button class="modal__close" onclick="closeConfirmModal()">&times;</button>
        </div>
        <div class="modal__body">
            <p id="confirm-message" style="margin: 0; color: var(--text-primary);"></p>
        </div>
        <div class="modal__footer">
            <button type="button" class="btn btn--outline" onclick="closeConfirmModal()">Odustani</button>
            <button type="button" class="btn btn--primary" id="confirm-btn">Potvrdi</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="modal" style="display: none;">
    <div class="modal__content modal__content--small">
        <div class="modal__header">
            <h3>Potvrda brisanja</h3>
            <button class="modal__close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal__body">
            <p id="delete-message" style="margin: 0; color: var(--text-primary);"></p>
        </div>
        <div class="modal__footer">
            <button type="button" class="btn btn--outline" onclick="closeDeleteModal()">Odustani</button>
            <button type="button" class="btn btn--danger" id="delete-confirm-btn">Obriši</button>
        </div>
    </div>
</div>

<!-- View Notes Modal -->
<div id="notes-modal" class="modal" style="display: none;">
    <div class="modal__content">
        <div class="modal__header">
            <h3>Napomene</h3>
            <button class="modal__close" onclick="closeNotesModal()">&times;</button>
        </div>
        <div class="modal__body">
            <div id="notes-content" style="color: var(--text-primary); line-height: 1.6;"></div>
        </div>
        <div class="modal__footer">
            <button type="button" class="btn btn--primary" onclick="closeNotesModal()">Zatvori</button>
        </div>
    </div>
</div>

<script>
// Define escapeHtml first since it's used by other functions
window.escapeHtml = function(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
};

let pendingToggleId = null;
let pendingToggleStatus = null;
let pendingDeleteId = null;

// Toggle contacted confirmation
function confirmToggleContacted(id, isContacted) {
    pendingToggleId = id;
    pendingToggleStatus = isContacted;
    const message = isContacted 
        ? 'Jeste li sigurni da želite označiti ovaj kontakt kao nekontaktiran?' 
        : 'Jeste li sigurni da želite označiti ovaj kontakt kao kontaktiran?';
    document.getElementById('confirm-message').textContent = message;
    document.getElementById('confirm-modal').style.display = 'block';
}

function closeConfirmModal() {
    document.getElementById('confirm-modal').style.display = 'none';
    pendingToggleId = null;
    pendingToggleStatus = null;
}

// Delete confirmation
window.confirmDelete = function(id, businessName) {
    pendingDeleteId = id;
    const message = 'Jeste li sigurni da želite obrisati kontakt "' + escapeHtml(businessName) + '"? Ova akcija se ne može poništiti.';
    document.getElementById('delete-message').textContent = message;
    document.getElementById('delete-modal').style.display = 'block';
};

window.closeDeleteModal = function() {
    document.getElementById('delete-modal').style.display = 'none';
    pendingDeleteId = null;
};

// View notes - make it globally accessible (define first)
window.viewNotes = function(id, notes, notesType) {
    const notesContent = document.getElementById('notes-content');
    const notesModal = document.getElementById('notes-modal');
    
    if (!notesContent || !notesModal) {
        console.error('Notes modal elements not found');
        return;
    }
    
    // Handle null/undefined notes
    if (!notes) {
        notesContent.innerHTML = '<p style="color: var(--text-secondary); margin: 0;">Nema napomena.</p>';
        notesModal.style.display = 'block';
        return;
    }
    
    if (notesType === 'bullets') {
        const lines = notes.split('\n').filter(line => line.trim());
        if (lines.length > 0) {
            notesContent.innerHTML = '<ul style="margin: 0; padding-left: 20px; list-style-type: disc; color: var(--text-primary);">' + 
                lines.map(line => '<li style="margin-bottom: 8px;">' + escapeHtml(line.trim()) + '</li>').join('') + 
                '</ul>';
        } else {
            notesContent.innerHTML = '<p style="color: var(--text-secondary); margin: 0;">Nema napomena.</p>';
        }
    } else {
        notesContent.innerHTML = '<p style="white-space: pre-wrap; margin: 0; color: var(--text-primary);">' + escapeHtml(notes) + '</p>';
    }
    notesModal.style.display = 'block';
};

window.closeNotesModal = function() {
    const notesModal = document.getElementById('notes-modal');
    if (notesModal) {
        notesModal.style.display = 'none';
    }
};

// View notes from button data attributes (define after viewNotes)
window.viewNotesFromButton = function(button) {
    const id = button.getAttribute('data-contact-id');
    const notes = button.getAttribute('data-notes');
    const notesType = button.getAttribute('data-notes-type');
    window.viewNotes(id, notes, notesType);
};

// Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    // Confirm button handler for toggle contacted
    const confirmBtn = document.getElementById('confirm-btn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (pendingToggleId !== null) {
                window.location.href = '?toggle_contacted=' + pendingToggleId;
            }
        });
    }
    
    // Delete confirm button handler
    const deleteConfirmBtn = document.getElementById('delete-confirm-btn');
    if (deleteConfirmBtn) {
        deleteConfirmBtn.addEventListener('click', function() {
            if (pendingDeleteId !== null) {
                window.location.href = '?delete=' + pendingDeleteId;
            }
        });
    }
    
    const contactModal = document.getElementById('contact-form-modal');
    const confirmModal = document.getElementById('confirm-modal');
    const notesModal = document.getElementById('notes-modal');
    const deleteModal = document.getElementById('delete-modal');
    
    // Close modals when clicking outside
    [contactModal, confirmModal, notesModal, deleteModal].forEach(modal => {
        if (modal) {
            const modalContent = modal.querySelector('.modal__content');
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    if (modal === contactModal) {
                        modal.style.display = 'none';
                        window.location.href = 'contacts.php';
                    } else if (modal === confirmModal) {
                        closeConfirmModal();
                    } else if (modal === notesModal) {
                        closeNotesModal();
                    } else if (modal === deleteModal) {
                        closeDeleteModal();
                    }
                }
            });
            
            if (modalContent) {
                modalContent.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        }
    });
    
    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (contactModal && contactModal.style.display === 'block') {
                contactModal.style.display = 'none';
                window.location.href = 'contacts.php';
            } else if (confirmModal && confirmModal.style.display === 'block') {
                closeConfirmModal();
            } else if (notesModal && notesModal.style.display === 'block') {
                closeNotesModal();
            } else if (deleteModal && deleteModal.style.display === 'block') {
                closeDeleteModal();
            }
        }
    });
    
    // Update textarea placeholder based on notes type
    const notesTypeRadios = document.querySelectorAll('input[name="notes_type"]');
    const notesTextarea = document.querySelector('textarea[name="notes"]');
    if (notesTypeRadios.length && notesTextarea) {
        notesTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                notesTextarea.placeholder = this.value === 'bullets' 
                    ? 'Svaki red će biti prikazan kao bullet točka' 
                    : 'Unesite napomene...';
            });
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>

