<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['customer']);

$page_title = 'Live Chat';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$customer_id = $_SESSION['customer_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;
if (!$customer_id) {
    $stmt = $db->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $customer_id = $stmt->fetchColumn();
    if ($customer_id) $_SESSION['customer_id'] = $customer_id;
}

$session_id = null;
$stmt = $db->prepare("SELECT id FROM chat_sessions WHERE customer_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$customer_id]);
$session_id = $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if ($message) {
        if (!$session_id) {
            $stmt = $db->prepare("INSERT INTO chat_sessions (customer_id, status, created_at) VALUES (?, 'active', NOW())");
            $stmt->execute([$customer_id]);
            $session_id = $db->lastInsertId();
        }
        $stmt = $db->prepare("INSERT INTO chat_messages (chat_session_id, sender_type, message, created_at) VALUES (?, 'customer', ?, NOW())");
        $stmt->execute([$session_id, $message]);
        set_flash('success', 'Message sent');
    }
    header('Location: live-chat.php');
    exit;
}

if (!$session_id) {
    $stmt = $db->prepare("INSERT INTO chat_sessions (customer_id, status, created_at) VALUES (?, 'active', NOW())");
    $stmt->execute([$customer_id]);
    $session_id = $db->lastInsertId();
}

$messages = [];
try {
    $stmt = $db->prepare("SELECT cm.* FROM chat_messages cm WHERE cm.chat_session_id = ? ORDER BY cm.created_at ASC");
    $stmt->execute([$session_id]);
    $messages = $stmt->fetchAll();
} catch (Exception $e) {}

$stmt = $db->prepare("SELECT status FROM chat_sessions WHERE id = ?");
$stmt->execute([$session_id]);
$chat_status = $stmt->fetchColumn();
?>
<style>
.chat-container { display: flex; flex-direction: column; height: calc(100vh - 260px); min-height: 500px; }
.chat-messages { flex: 1; overflow-y: auto; padding: 1rem; background: #f8f9fa; border-radius: 0.5rem; }
.chat-message { max-width: 75%; margin-bottom: 1rem; }
.chat-message.customer { margin-left: auto; }
.chat-message.support { margin-right: auto; }
.chat-message .msg-content { padding: 0.75rem 1rem; border-radius: 1rem; position: relative; }
.chat-message.customer .msg-content { background: #0d6efd; color: #fff; border-bottom-right-radius: 0.25rem; }
.chat-message.support .msg-content { background: #fff; color: #212529; border: 1px solid #dee2e6; border-bottom-left-radius: 0.25rem; }
.chat-message .msg-time { font-size: 0.7rem; margin-top: 0.25rem; opacity: 0.7; }
.chat-message.customer .msg-time { text-align: right; }
.chat-status-indicator { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 0.5rem; }
.chat-status-indicator.active { background: #198754; }
.chat-status-indicator.away { background: #ffc107; }
.chat-status-indicator.offline { background: #dc3545; }
</style>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Live Chat</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <h5 class="mb-0 fw-semibold me-3"><i class="bi bi-chat-dots text-primary"></i> Live Chat</h5>
                <span id="chatStatus">
                    <?php if ($chat_status === 'active'): ?>
                    <span class="chat-status-indicator active"></span><small class="text-muted">Connected</small>
                    <?php else: ?>
                    <span class="chat-status-indicator away"></span><small class="text-muted"><?php echo htmlspecialchars(ucfirst($chat_status)); ?></small>
                    <?php endif; ?>
                </span>
            </div>
            <small class="text-muted">
                <i class="bi bi-person"></i> Support Team
            </small>
        </div>
        <div class="card-body p-0">
            <div class="chat-container">
                <div class="chat-messages" id="chatMessages">
                    <?php if (empty($messages)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-chat display-4"></i>
                        <p class="mt-2">Start a conversation with our support team</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                    <div class="chat-message <?php echo $msg['sender_type'] === 'customer' ? 'customer' : 'support'; ?>">
                        <div class="msg-content">
                            <?php echo htmlspecialchars($msg['message']); ?>
                            <div class="msg-time">
                                <?php echo timeAgo($msg['created_at']); ?>
                                <?php if ($msg['sender_type'] === 'customer'): ?>
                                <i class="bi bi-check2<?php echo !empty($msg['is_read']) ? '-all' : ''; ?>"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <div id="chatScrollAnchor"></div>
                </div>

                <div class="border-top p-3 bg-white">
                    <form method="POST" class="d-flex gap-2">
                        <input type="text" name="message" class="form-control" placeholder="Type your message..." required autocomplete="off">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Send</button>
                    </form>
                    <small class="text-muted mt-2 d-block">
                        <i class="bi bi-info-circle"></i> We typically respond within a few minutes during business hours.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function scrollToBottom() {
    var anchor = document.getElementById('chatScrollAnchor');
    if (anchor) anchor.scrollIntoView({ behavior: 'smooth' });
}
document.addEventListener('DOMContentLoaded', function() {
    scrollToBottom();
    setInterval(function() {
        fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.text())
        .then(html => {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            var newMessages = doc.querySelector('.chat-messages');
            var oldMessages = document.querySelector('.chat-messages');
            if (newMessages && oldMessages) {
                oldMessages.innerHTML = newMessages.innerHTML;
                scrollToBottom();
            }
        })
        .catch(function() {});
    }, 10000);
});
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
