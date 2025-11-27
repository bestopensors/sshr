<?php
/**
 * Admin - Live Chat Management
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireAuth();

// Handle send message BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['conversation_id'])) {
    $convId = (int)$_POST['conversation_id'];
    $message = trim($_POST['message']);
    
    if ($convId && $message && dbAvailable()) {
        try {
            $stmt = db()->prepare("INSERT INTO chat_messages (conversation_id, sender_type, message) VALUES (?, 'admin', ?)");
            $stmt->execute([$convId, $message]);
            
            $stmt = db()->prepare("UPDATE chat_conversations SET updated_at = NOW() WHERE id = ?");
            $stmt->execute([$convId]);
            
            header("Location: chat.php?id=$convId");
            exit;
        } catch (Exception $e) {
            // Error
        }
    }
}

// Handle close conversation
if (isset($_GET['close'])) {
    $convId = (int)$_GET['close'];
    if ($convId && dbAvailable()) {
        try {
            $stmt = db()->prepare("UPDATE chat_conversations SET status = 'closed' WHERE id = ?");
            $stmt->execute([$convId]);
            header("Location: chat.php");
            exit;
        } catch (Exception $e) {
            // Error
        }
    }
}

$pageTitle = 'Live Chat';
require_once 'includes/header.php';

$conversations = [];
$activeConversation = null;
$messages = [];

if (dbAvailable()) {
    // Get active conversations
    try {
        $stmt = db()->query("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM chat_messages WHERE conversation_id = c.id AND sender_type = 'visitor' AND is_read = 0) as unread_count,
                   (SELECT message FROM chat_messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1) as last_message,
                   (SELECT created_at FROM chat_messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1) as last_message_time
            FROM chat_conversations c 
            WHERE c.status = 'active'
            ORDER BY c.updated_at DESC
        ");
        $conversations = $stmt->fetchAll();
    } catch (Exception $e) {
        // Table might not exist
    }
    
    // Get selected conversation
    if (isset($_GET['id'])) {
        $convId = (int)$_GET['id'];
        
        try {
            $stmt = db()->prepare("SELECT * FROM chat_conversations WHERE id = ?");
            $stmt->execute([$convId]);
            $activeConversation = $stmt->fetch();
            
            if ($activeConversation) {
                // Mark messages as read
                $stmt = db()->prepare("UPDATE chat_messages SET is_read = 1 WHERE conversation_id = ? AND sender_type = 'visitor'");
                $stmt->execute([$convId]);
                
                // Get messages
                $stmt = db()->prepare("SELECT * FROM chat_messages WHERE conversation_id = ? ORDER BY id ASC");
                $stmt->execute([$convId]);
                $messages = $stmt->fetchAll();
            }
        } catch (Exception $e) {
            // Error
        }
    }
}

?>

<style>
.chat-container {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 20px;
    height: calc(100vh - 180px);
    min-height: 500px;
}

.chat-sidebar {
    background: var(--bg-card);
    border-radius: 12px;
    border: 1px solid var(--border-color);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.chat-sidebar-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
    font-weight: 600;
}

.chat-list {
    flex: 1;
    overflow-y: auto;
}

.chat-item {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    transition: background 0.2s;
    text-decoration: none;
    display: block;
    color: inherit;
}

.chat-item:hover {
    background: var(--bg-hover);
}

.chat-item.active {
    background: rgba(99, 102, 241, 0.1);
    border-left: 3px solid var(--primary);
}

.chat-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.chat-item-name {
    font-weight: 500;
    color: var(--text-primary);
}

.chat-item-time {
    font-size: 12px;
    color: var(--text-muted);
}

.chat-item-preview {
    font-size: 13px;
    color: var(--text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chat-item-unread {
    background: var(--primary);
    color: white;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: 600;
}

.chat-main {
    background: var(--bg-card);
    border-radius: 12px;
    border: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.chat-main-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-main-info h3 {
    margin: 0 0 4px 0;
    font-size: 16px;
}

.chat-main-info span {
    font-size: 13px;
    color: var(--text-secondary);
}

.chat-main-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: var(--bg-secondary);
}

.chat-msg {
    max-width: 70%;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 14px;
    line-height: 1.5;
}

.chat-msg--visitor {
    align-self: flex-start;
    background: #e5e7eb;
    color: #1f2937;
    border: none;
}

.chat-msg--admin {
    align-self: flex-end;
    background: var(--primary);
    color: white;
}

.chat-msg-time {
    font-size: 11px;
    opacity: 0.7;
    margin-top: 4px;
}

.chat-main-form {
    padding: 16px 20px;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 12px;
}

.chat-main-form input {
    flex: 1;
}

.chat-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--text-muted);
    text-align: center;
    padding: 40px;
}

.chat-empty svg {
    margin-bottom: 16px;
    opacity: 0.5;
}

@media (max-width: 900px) {
    .chat-container {
        grid-template-columns: 1fr;
    }
    
    .chat-sidebar {
        max-height: 300px;
    }
}
</style>

<?php if (!dbAvailable()): ?>
<div class="alert alert--error">Baza podataka nije povezana.</div>
<?php else: ?>

<div class="chat-container">
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            Aktivni razgovori (<?php echo count($conversations); ?>)
        </div>
        <div class="chat-list">
            <?php if (empty($conversations)): ?>
                <div style="padding: 20px; text-align: center; color: var(--text-muted);">
                    Nema aktivnih razgovora
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                <a href="?id=<?php echo $conv['id']; ?>" class="chat-item <?php echo ($activeConversation && $activeConversation['id'] == $conv['id']) ? 'active' : ''; ?>">
                    <div class="chat-item-header">
                        <span class="chat-item-name">
                            <?php echo htmlspecialchars($conv['visitor_name'] ?: 'Posjetitelj #' . $conv['id']); ?>
                        </span>
                        <?php if ($conv['unread_count'] > 0): ?>
                            <span class="chat-item-unread"><?php echo $conv['unread_count']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="chat-item-preview">
                        <?php echo htmlspecialchars($conv['last_message'] ?? 'Novi razgovor'); ?>
                    </div>
                    <div class="chat-item-time">
                        <?php echo $conv['last_message_time'] ? date('d.m. H:i', strtotime($conv['last_message_time'])) : date('d.m. H:i', strtotime($conv['created_at'])); ?>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="chat-main">
        <?php if ($activeConversation): ?>
            <div class="chat-main-header">
                <div class="chat-main-info">
                    <h3><?php echo htmlspecialchars($activeConversation['visitor_name'] ?: 'Posjetitelj #' . $activeConversation['id']); ?></h3>
                    <span>
                        <?php if ($activeConversation['visitor_email']): ?>
                            <?php echo htmlspecialchars($activeConversation['visitor_email']); ?> • 
                        <?php endif; ?>
                        Započeto: <?php echo date('d.m.Y H:i', strtotime($activeConversation['created_at'])); ?>
                    </span>
                </div>
                <a href="?close=<?php echo $activeConversation['id']; ?>" class="btn btn--danger btn--sm" onclick="return confirm('Zatvoriti ovaj razgovor?')">Zatvori razgovor</a>
            </div>
            
            <div class="chat-main-messages" id="chat-messages">
                <?php foreach ($messages as $msg): ?>
                <div class="chat-msg chat-msg--<?php echo $msg['sender_type']; ?>">
                    <?php echo htmlspecialchars($msg['message']); ?>
                    <div class="chat-msg-time"><?php echo date('H:i', strtotime($msg['created_at'])); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <form method="POST" class="chat-main-form">
                <input type="hidden" name="conversation_id" value="<?php echo $activeConversation['id']; ?>">
                <input type="text" name="message" class="form-control" placeholder="Napišite odgovor..." autocomplete="off" required autofocus>
                <button type="submit" class="btn btn--primary">Pošalji</button>
            </form>
            
            <script>
                // Auto-scroll to bottom
                const msgContainer = document.getElementById('chat-messages');
                msgContainer.scrollTop = msgContainer.scrollHeight;
                
                // Poll for new messages without page refresh
                const conversationId = <?php echo $activeConversation['id']; ?>;
                let lastMsgId = <?php echo !empty($messages) ? end($messages)['id'] : 0; ?>;
                
                async function checkNewMessages() {
                    try {
                        const response = await fetch('../chat-api.php?action=get&conversation_id=' + conversationId + '&last_id=' + lastMsgId + '&mark_read=admin');
                        const data = await response.json();
                        
                        if (data.success && data.messages.length > 0) {
                            data.messages.forEach(msg => {
                                if (msg.sender_type === 'visitor') {
                                    const div = document.createElement('div');
                                    div.className = 'chat-msg chat-msg--visitor';
                                    div.innerHTML = msg.message + '<div class="chat-msg-time">' + new Date(msg.created_at).toLocaleTimeString('hr-HR', {hour: '2-digit', minute: '2-digit'}) + '</div>';
                                    msgContainer.appendChild(div);
                                    msgContainer.scrollTop = msgContainer.scrollHeight;
                                }
                                lastMsgId = Math.max(lastMsgId, parseInt(msg.id));
                            });
                        }
                    } catch (e) {
                        console.error('Poll error:', e);
                    }
                }
                
                setInterval(checkNewMessages, 3000);
            </script>
        <?php else: ?>
            <div class="chat-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <h3>Odaberite razgovor</h3>
                <p>Odaberite razgovor s lijeve strane za pregled i odgovaranje.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

