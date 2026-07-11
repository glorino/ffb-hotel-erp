<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/role-check.php';
checkRole(['receptionist']);

$page_title = 'Live Chat';
$base_url = '../';

require_once __DIR__ . '/../includes/dashboard-header.php';

$db = getDB();
$branch_id = $_SESSION['branch_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $session_id = (int)($_POST['session_id'] ?? 0);
    $message = sanitize($_POST['message'] ?? '');
    if ($session_id && $message) {
        try {
            $stmt = $db->prepare("INSERT INTO chat_messages (chat_session_id, sender_type, message, is_read, created_at) VALUES (?, 'receptionist', ?, 0, NOW())");
            $stmt->execute([$session_id, $message]);
            $stmt = $db->prepare("UPDATE chat_sessions SET updated_at = NOW() WHERE id = ?");
            $stmt->execute([$session_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_session'])) {
    $session_id = (int)($_POST['session_id'] ?? 0);
    try {
        $stmt = $db->prepare("UPDATE chat_sessions SET status = 'closed', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$session_id]);
        set_flash('success', 'Chat session closed');
    } catch (Exception $e) {
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header('Location: live-chat.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_self'])) {
    $session_id = (int)($_POST['session_id'] ?? 0);
    try {
        $stmt = $db->prepare("UPDATE chat_sessions SET assigned_to = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$user_id, $session_id]);
        set_flash('success', 'Session assigned to you');
    } catch (Exception $e) {
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header('Location: live-chat.php');
    exit;
}

$active_sessions = [];
try {
    $stmt = $db->prepare("
        SELECT cs.*, 
               c.full_name, c.email,
               (SELECT message FROM chat_messages WHERE chat_session_id = cs.id ORDER BY created_at DESC LIMIT 1) as last_message,
               (SELECT created_at FROM chat_messages WHERE chat_session_id = cs.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
               (SELECT COUNT(*) FROM chat_messages WHERE chat_session_id = cs.id AND sender_type = 'customer' AND is_read = 0) as unread_count
        FROM chat_sessions cs
        LEFT JOIN customers c ON cs.customer_id = c.id
        WHERE cs.status = 'active'
        ORDER BY unread_count DESC, last_message_time DESC
    ");
    $stmt->execute([$branch_id]);
    $active_sessions = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Live chat error: ' . $e->getMessage());
}

$selected_session = null;
$messages = [];
$session_id = $_GET['session'] ?? 0;

if ($session_id) {
    try {
        $stmt = $db->prepare("
            SELECT cs.*, c.full_name, c.email, u.full_name as staff_name
            FROM chat_sessions cs
            LEFT JOIN customers c ON cs.customer_id = c.id
            LEFT JOIN users u ON cs.assigned_to = u.id
            WHERE cs.id = ?
        ");
        $stmt->execute([$session_id]);
        $selected_session = $stmt->fetch();

        if ($selected_session) {
            $stmt = $db->prepare("SELECT * FROM chat_messages WHERE chat_session_id = ? ORDER BY created_at ASC");
            $stmt->execute([$session_id]);
            $messages = $stmt->fetchAll();

            $stmt = $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE chat_session_id = ? AND sender_type = 'customer' AND is_read = 0");
            $stmt->execute([$session_id]);
        }
    } catch (Exception $e) {}
}

$quick_replies = [
    'Welcome to FFB Hotel! How can I assist you today?',
    'Your check-in time is 2:00 PM. We look forward to welcoming you.',
    'Check-out time is 12:00 PM. Let us know if you need a late check-out.',
    'Room service is available 24/7. Please dial 0 from your room.',
    'Yes, we can arrange airport transfer for you. Please provide your flight details.',
    'Thank you for choosing FFB Hotel. We appreciate your patronage!',
    'I will connect you with the relevant department. Please hold on.',
    'Breakfast is served from 7:00 AM to 10:30 AM at the restaurant.',
];

if (isset($_GET['poll']) && $session_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM chat_messages WHERE chat_session_id = ? AND id > ? AND sender_type = 'customer' ORDER BY created_at ASC");
        $stmt->execute([$session_id, (int)($_GET['since'] ?? 0)]);
        $new_messages = $stmt->fetchAll();
        echo json_encode(['messages' => $new_messages]);
    } catch (Exception $e) {
        echo json_encode(['messages' => []]);
    }
    exit;
}
?>

<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i> Home</a></li>
            <li class="breadcrumb-item active">Live Chat</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm">
        <div class="row g-0">
            <div class="col-lg-4 col-md-5 border-end">
                <div class="p-3 border-bottom">
                    <h6 class="mb-0 fw-semibold">Active Sessions (<?php echo count($active_sessions); ?>)</h6>
                </div>
                <div class="chat-sessions-list" style="height:550px;overflow-y:auto;">
                    <?php if (empty($active_sessions)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-chat-dots fs-1 d-block mb-2"></i>
                        <p class="mb-0">No active chat sessions</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($active_sessions as $s): ?>
                        <a href="?session=<?php echo $s['id']; ?>" class="text-decoration-none <?php echo $session_id == $s['id'] ? '' : 'text-dark'; ?>">
                            <div class="chat-session-item p-3 border-bottom <?php echo $session_id == $s['id'] ? 'bg-primary-subtle' : 'hover-bg-light'; ?>" style="cursor:pointer;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center bg-<?php echo $s['unread_count'] > 0 ? 'success' : 'secondary'; ?> text-white" style="width:40px;height:40px;font-size:14px;font-weight:600;flex-shrink:0;">
                                            <?php echo strtoupper(substr($s['full_name'] ?? '?', 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($s['full_name'] ?? 'Guest'); ?></strong>
                                            <small class="d-block text-muted" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                                <?php echo htmlspecialchars(truncate($s['last_message'] ?? 'No messages', 40)); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-end" style="flex-shrink:0;">
                                        <small class="text-muted d-block"><?php echo $s['last_message_time'] ? timeAgo($s['last_message_time']) : ''; ?></small>
                                        <?php if ($s['unread_count'] > 0): ?>
                                        <span class="badge bg-danger rounded-pill"><?php echo $s['unread_count']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-8 col-md-7 d-flex flex-column">
                <?php if ($selected_session): ?>
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                    <div>
                        <h6 class="mb-0">
                            <?php echo htmlspecialchars($selected_session['full_name'] ?? 'Guest'); ?>
                            <?php if ($selected_session['email']): ?>
                            <small class="text-muted">(<?php echo htmlspecialchars($selected_session['email']); ?>)</small>
                            <?php endif; ?>
                        </h6>
                        <small class="text-muted">
                            <?php echo $selected_session['staff_name'] ? 'Assigned to: ' . htmlspecialchars($selected_session['staff_name']) : 'Unassigned'; ?>
                        </small>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if (!$selected_session['assigned_to']): ?>
                        <form method="POST" class="d-inline">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="session_id" value="<?php echo $selected_session['id']; ?>">
                            <button type="submit" name="assign_self" class="btn btn-sm btn-outline-primary"><i class="bi bi-person-check"></i> Assign</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Close this chat session?')">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="session_id" value="<?php echo $selected_session['id']; ?>">
                            <button type="submit" name="close_session" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Close</button>
                        </form>
                    </div>
                </div>

                <div class="chat-messages flex-grow-1 p-3" id="chatMessages" style="height:400px;overflow-y:auto;background:#f8f9fa;">
                    <?php foreach ($messages as $msg): ?>
                    <div class="d-flex mb-3 <?php echo $msg['sender_type'] === 'receptionist' ? 'justify-content-end' : 'justify-content-start'; ?>">
                        <div class="message-bubble <?php echo $msg['sender_type'] === 'receptionist' ? 'bg-primary text-white' : 'bg-white border'; ?> rounded-3 p-3" style="max-width:75%;">
                            <small class="d-block <?php echo $msg['sender_type'] === 'receptionist' ? 'text-white-50' : 'text-muted'; ?> mb-1">
                                <?php echo $msg['sender_type'] === 'receptionist' ? 'You' : 'Guest'; ?>
                                &middot; <?php echo date('h:i A', strtotime($msg['created_at'])); ?>
                            </small>
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div id="newMessagesContainer"></div>
                </div>

                <div class="p-3 border-top bg-white">
                    <div class="mb-2">
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach ($quick_replies as $qr): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary quick-reply" onclick="insertQuickReply(this)">
                                <?php echo htmlspecialchars(truncate($qr, 30)); ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <form method="POST" class="d-flex gap-2" id="messageForm">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="session_id" value="<?php echo $selected_session['id']; ?>">
                        <input type="hidden" name="send_message" value="1">
                        <input type="text" name="message" id="messageInput" class="form-control" placeholder="Type your message..." required autocomplete="off">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Send</button>
                    </form>
                </div>

                <script>
                let lastMessageId = <?php echo !empty($messages) ? end($messages)['id'] : 0; ?>;
                const sessionId = <?php echo $selected_session['id']; ?>;

                function pollMessages() {
                    fetch('live-chat.php?poll=1&session=' + sessionId + '&since=' + lastMessageId)
                        .then(r => r.json())
                        .then(data => {
                            if (data.messages && data.messages.length > 0) {
                                const container = document.getElementById('newMessagesContainer');
                                data.messages.forEach(msg => {
                                    const div = document.createElement('div');
                                    div.className = 'd-flex mb-3 justify-content-start';
                                    div.innerHTML = `<div class="message-bubble bg-white border rounded-3 p-3" style="max-width:75%;">
                                        <small class="d-block text-muted mb-1">Guest &middot; ${new Date(msg.created_at).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}</small>
                                        ${msg.message.replace(/\n/g, '<br>')}
                                    </div>`;
                                    container.appendChild(div);
                                    lastMessageId = msg.id;
                                });
                                const chat = document.getElementById('chatMessages');
                                chat.scrollTop = chat.scrollHeight;
                            }
                        })
                        .catch(() => {});
                }

                setInterval(pollMessages, 5000);

                function insertQuickReply(btn) {
                    document.getElementById('messageInput').value = btn.textContent.trim();
                    document.getElementById('messageInput').focus();
                }

                document.getElementById('messageForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const input = document.getElementById('messageInput');
                    const msg = input.value.trim();
                    if (!msg) return;

                    const formData = new FormData(this);
                    formData.set('send_message', '1');
                    formData.set('message', msg);

                    fetch('live-chat.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const container = document.getElementById('newMessagesContainer');
                            const div = document.createElement('div');
                            div.className = 'd-flex mb-3 justify-content-end';
                            div.innerHTML = `<div class="message-bubble bg-primary text-white rounded-3 p-3" style="max-width:75%;">
                                <small class="d-block text-white-50 mb-1">You &middot; Just now</small>
                                ${msg.replace(/\n/g, '<br>')}
                            </div>`;
                            container.appendChild(div);
                            input.value = '';
                            const chat = document.getElementById('chatMessages');
                            chat.scrollTop = chat.scrollHeight;
                            lastMessageId++;
                        }
                    })
                    .catch(() => {});
                });
                </script>

                <?php else: ?>
                <div class="d-flex align-items-center justify-content-center flex-grow-1 bg-light" style="height:550px;">
                    <div class="text-center text-muted">
                        <i class="bi bi-chat-square-dots fs-1 d-block mb-3"></i>
                        <h5>Select a conversation</h5>
                        <p>Choose a chat session from the left panel to start responding</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.chat-sessions-list a { color: inherit; }
.chat-sessions-list .hover-bg-light:hover { background: #f8f9fa; }
.message-bubble { word-wrap: break-word; }
#chatMessages { scroll-behavior: smooth; }
.quick-reply { font-size: 0.75rem; }
</style>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
