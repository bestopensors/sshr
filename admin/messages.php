<?php
/**
 * Admin - Contact Messages
 */
$pageTitle = 'Poruke';
require_once 'includes/header.php';

$messages = [];
$message = '';
$error = '';

// Handle mark as read
if (isset($_GET['mark_read']) && dbAvailable()) {
    $id = (int)$_GET['mark_read'];
    try {
        $stmt = db()->prepare("UPDATE contact_submissions SET is_read = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Poruka označena kao pročitana.';
    } catch (Exception $e) {
        $error = 'Greška pri ažuriranju poruke.';
    }
}

// Handle delete
if (isset($_GET['delete']) && dbAvailable()) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = db()->prepare("DELETE FROM contact_submissions WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Poruka uspješno obrisana.';
    } catch (Exception $e) {
        $error = 'Greška pri brisanju poruke.';
    }
}

// Get messages from database
if (dbAvailable()) {
    try {
        $stmt = db()->query("SELECT * FROM contact_submissions ORDER BY created_at DESC");
        $messages = $stmt->fetchAll();
    } catch (Exception $e) {
        // Table might not exist
    }
}
?>

<?php if ($message): ?>
    <div class="alert alert--success"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert--error"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (!dbAvailable()): ?>
<div class="alert alert--error">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
    </svg>
    <span>Baza podataka nije povezana. Poruke će se prikazivati nakon konfiguracije baze.</span>
</div>
<?php endif; ?>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Poruke s kontakt forme</h2>
    </div>
    
    <?php if (empty($messages)): ?>
        <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
            <h3>Nema poruka</h3>
            <p>Još niste primili nijednu poruku s kontakt forme.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Ime</th>
                        <th>Email</th>
                        <th>Telefon</th>
                        <th>Poruka</th>
                        <th>Datum</th>
                        <th>Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): ?>
                    <tr style="<?php echo !$msg['is_read'] ? 'background: rgba(99, 102, 241, 0.05);' : ''; ?>">
                        <td>
                            <?php if (isset($msg['is_replied']) && $msg['is_replied']): ?>
                                <span class="badge badge--success">Odgovoreno</span>
                            <?php elseif (!$msg['is_read']): ?>
                                <span class="badge badge--warning">Nova</span>
                            <?php else: ?>
                                <span class="badge badge--secondary">Pročitana</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($msg['name']); ?></strong></td>
                        <td>
                            <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" style="color: var(--primary);">
                                <?php echo htmlspecialchars($msg['email']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($msg['phone'] ?? '-'); ?></td>
                        <td style="max-width: 300px;">
                            <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?php echo htmlspecialchars(substr($msg['message'], 0, 100)); ?>...
                            </div>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($msg['created_at'])); ?></td>
                        <td>
                            <div class="table-actions">
                                <a href="message-view.php?id=<?php echo $msg['id']; ?>" class="btn btn--secondary btn--sm">Pogledaj</a>
                                <?php if (!$msg['is_read']): ?>
                                <a href="?mark_read=<?php echo $msg['id']; ?>" class="btn btn--secondary btn--sm">Označi</a>
                                <?php endif; ?>
                                <a href="?delete=<?php echo $msg['id']; ?>" class="btn btn--danger btn--sm" data-confirm="Jeste li sigurni da želite obrisati ovu poruku?">Obriši</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

