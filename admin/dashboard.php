<?php
// dashboard.php - Zamzy Webinar Admin Dashboard
session_start();
if (!isset($_SESSION['zamzy_admin_logged']) || $_SESSION['zamzy_admin_logged'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../db.php';

// Handle Action: Delete Message
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->execute([$del_id]);
    $_SESSION['flash_msg'] = 'Message deleted successfully.';
    header('Location: dashboard.php');
    exit;
}

// Handle Action: Mark Status (read / replied / new)
if (isset($_GET['action']) && $_GET['action'] === 'status' && isset($_GET['id']) && isset($_GET['to'])) {
    $msg_id = (int)$_GET['id'];
    $new_status = in_array($_GET['to'], ['new', 'read', 'replied']) ? $_GET['to'] : 'read';
    $stmt = $pdo->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $msg_id]);
    $_SESSION['flash_msg'] = "Status updated to {$new_status}.";
    header('Location: dashboard.php');
    exit;
}

// Search and filter parameters
$search = trim($_GET['q'] ?? '');
$status_filter = trim($_GET['status'] ?? 'all');

$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}

if ($status_filter !== 'all' && in_array($status_filter, ['new', 'read', 'replied'])) {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Stats
$total_count = $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
$new_count = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
$replied_count = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'replied'")->fetchColumn();
$read_count = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'read'")->fetchColumn();

// Fetch Messages
$sql = "SELECT * FROM contact_messages {$where_sql} ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

$flash = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Zamzy &mdash; Admin Dashboard</title>
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Nunito+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap & Icons -->
  <link href="../assets/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    :root {
      --bg: #000000;
      --surface: #0a0e17;
      --card-bg: #0f1420;
      --card-hover: #141b2b;
      --border: rgba(255, 255, 255, 0.08);
      --primary: #10b981;
      --primary-dark: #059669;
      --primary-glow: rgba(16, 185, 129, 0.2);
      --secondary: #f59e0b;
      --text: #f1f5f9;
      --muted: #94a3b8;
    }

    * { box-sizing: border-box; }
    
    body {
      background-color: var(--bg);
      color: var(--text);
      font-family: 'Nunito Sans', sans-serif;
      min-height: 100vh;
      overflow-x: hidden;
    }

    h1, h2, h3, h4, h5, h6, .font-display {
      font-family: 'Poppins', sans-serif;
    }

    /* Top Navigation Bar */
    .admin-navbar {
      background: rgba(10, 14, 23, 0.95);
      backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--border);
      padding: 14px 0;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .brand-logo {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-weight: 800;
      font-size: 1.35rem;
      color: #fff;
      text-decoration: none;
    }

    .brand-mark {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 1.1rem;
      box-shadow: 0 4px 14px -2px rgba(16, 185, 129, 0.5);
    }

    /* Stat Cards */
    .stat-card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 22px 24px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .stat-card:hover {
      transform: translateY(-4px);
      border-color: rgba(255, 255, 255, 0.16);
      box-shadow: 0 14px 30px rgba(0, 0, 0, 0.6);
    }

    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.35rem;
    }

    .stat-val {
      font-size: 2rem;
      font-weight: 800;
      line-height: 1.1;
      margin: 8px 0 2px;
      font-family: 'Poppins', sans-serif;
    }

    .stat-label {
      color: var(--muted);
      font-size: 0.85rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    /* Main Table Container */
    .table-container {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 24px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
    }

    .search-input {
      background: #060910;
      border: 1.5px solid var(--border);
      border-radius: 999px;
      color: #fff;
      padding: 10px 20px;
      font-size: 0.9rem;
      transition: all 0.3s ease;
    }

    .search-input:focus {
      background: #090e18;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px var(--primary-glow);
      color: #fff;
    }

    .filter-btn {
      background: #090e18;
      border: 1px solid var(--border);
      color: var(--muted);
      border-radius: 999px;
      padding: 7px 16px;
      font-size: 0.84rem;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.25s ease;
    }

    .filter-btn:hover, .filter-btn.active {
      background: var(--primary);
      color: #fff;
      border-color: var(--primary);
    }

    /* Custom Dark Table */
    .custom-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 8px;
    }

    .custom-table th {
      color: var(--muted);
      font-size: 0.76rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-weight: 700;
      padding: 12px 16px;
      border-bottom: 1px solid var(--border);
    }

    .custom-table tr.data-row {
      background: rgba(255, 255, 255, 0.02);
      border-radius: 12px;
      transition: all 0.2s ease;
    }

    .custom-table tr.data-row:hover {
      background: rgba(255, 255, 255, 0.05);
    }

    .custom-table td {
      padding: 16px;
      vertical-align: middle;
      border-top: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
      font-size: 0.92rem;
    }

    .custom-table td:first-child {
      border-left: 1px solid var(--border);
      border-top-left-radius: 12px;
      border-bottom-left-radius: 12px;
    }

    .custom-table td:last-child {
      border-right: 1px solid var(--border);
      border-top-right-radius: 12px;
      border-bottom-right-radius: 12px;
    }

    /* Status Badges */
    .badge-status {
      font-size: 0.75rem;
      font-weight: 700;
      padding: 5px 12px;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .badge-new {
      background: rgba(16, 185, 129, 0.15);
      color: #34d399;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .badge-read {
      background: rgba(59, 130, 246, 0.15);
      color: #60a5fa;
      border: 1px solid rgba(59, 130, 246, 0.3);
    }

    .badge-replied {
      background: rgba(245, 158, 11, 0.15);
      color: #fbbf24;
      border: 1px solid rgba(245, 158, 11, 0.3);
    }

    /* Action Buttons */
    .btn-action {
      width: 34px;
      height: 34px;
      border-radius: 10px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid var(--border);
      background: rgba(255, 255, 255, 0.04);
      color: var(--muted);
      transition: all 0.2s ease;
      text-decoration: none;
    }

    .btn-action:hover {
      background: var(--primary);
      color: #fff;
      border-color: var(--primary);
      transform: translateY(-2px);
    }

    .btn-action.btn-del:hover {
      background: #ef4444;
      border-color: #ef4444;
    }

    /* Modal */
    .modal-content {
      background-color: #0f1522;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 20px;
      color: #fff;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.9);
    }

    .modal-header {
      border-bottom: 1px solid var(--border);
    }

    .modal-footer {
      border-top: 1px solid var(--border);
    }

    .btn-close {
      filter: invert(1) grayscale(100%) brightness(200%);
    }

    .btn-primary-custom {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      border: none;
      color: #fff;
      font-weight: 600;
      border-radius: 999px;
      padding: 9px 22px;
      box-shadow: 0 6px 16px -2px rgba(16, 185, 129, 0.5);
    }
    .btn-primary-custom:hover {
      color: #fff;
      transform: translateY(-2px);
    }
  </style>
</head>
<body>

  <!-- Top Navbar -->
  <header class="admin-navbar">
    <div class="container-fluid px-4 d-flex justify-content-between align-items-center">
      <a href="dashboard.php" class="brand-logo">
        <span class="brand-mark"><i class="bi bi-shield-check"></i></span>
        <span>Zam<span style="color:var(--primary);">zy</span> <small style="font-size:0.8rem;opacity:0.6;font-weight:500;">Webinar Admin</small></span>
      </a>

      <div class="d-flex align-items-center gap-3">
        <a href="export.php" class="btn btn-sm btn-outline-success rounded-pill px-3 d-none d-sm-inline-flex align-items-center gap-1">
          <i class="bi bi-download"></i> Export CSV
        </a>
        <a href="../index.html" target="_blank" class="btn btn-sm btn-outline-light rounded-pill px-3 d-inline-flex align-items-center gap-1">
          <i class="bi bi-box-arrow-up-right"></i> Live Site
        </a>
        <div class="vr bg-secondary mx-1"></div>
        <div class="d-flex align-items-center gap-2">
          <span class="small text-muted d-none d-md-inline"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['zamzy_admin_user']) ?></span>
          <a href="logout.php" class="btn btn-sm btn-outline-danger rounded-pill px-3 d-inline-flex align-items-center gap-1">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
        </div>
      </div>
    </div>
  </header>

  <main class="container-fluid px-4 py-4">

    <!-- Flash message -->
    <?php if ($flash): ?>
      <div class="alert alert-success alert-dismissible fade show rounded-4 d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        <div><?= htmlspecialchars($flash) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Title and quick action bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h2 class="fw-bold mb-1">Contact Messages &amp; Leads</h2>
        <p class="text-muted small mb-0">Connected to MySQL Database: <strong class="text-white">webinar</strong> &bull; Table: <strong class="text-white">contact_messages</strong></p>
      </div>
      <div>
        <span class="badge bg-dark border border-secondary text-secondary px-3 py-2 rounded-pill">
          <i class="bi bi-database me-1 text-success"></i> Connected: 127.0.0.1 / webinar
        </span>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="stat-label">Total Messages</div>
              <div class="stat-val"><?= number_format($total_count) ?></div>
            </div>
            <div class="stat-icon" style="background:rgba(16, 185, 129, 0.15);color:#34d399;">
              <i class="bi bi-inbox-fill"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="stat-label">New &amp; Unread</div>
              <div class="stat-val text-warning"><?= number_format($new_count) ?></div>
            </div>
            <div class="stat-icon" style="background:rgba(245, 158, 11, 0.15);color:#fbbf24;">
              <i class="bi bi-envelope-exclamation-fill"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="stat-label">Read / Reviewed</div>
              <div class="stat-val text-info"><?= number_format($read_count) ?></div>
            </div>
            <div class="stat-icon" style="background:rgba(59, 130, 246, 0.15);color:#60a5fa;">
              <i class="bi bi-envelope-open-fill"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="stat-label">Replied</div>
              <div class="stat-val text-success"><?= number_format($replied_count) ?></div>
            </div>
            <div class="stat-icon" style="background:rgba(34, 197, 94, 0.15);color:#4ade80;">
              <i class="bi bi-check2-all"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="table-container mb-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        
        <!-- Status Filter Pills -->
        <div class="d-flex flex-wrap gap-2">
          <a href="dashboard.php?status=all&q=<?= urlencode($search) ?>" class="filter-btn <?= $status_filter === 'all' ? 'active' : '' ?>">All (<?= $total_count ?>)</a>
          <a href="dashboard.php?status=new&q=<?= urlencode($search) ?>" class="filter-btn <?= $status_filter === 'new' ? 'active' : '' ?>">New (<?= $new_count ?>)</a>
          <a href="dashboard.php?status=read&q=<?= urlencode($search) ?>" class="filter-btn <?= $status_filter === 'read' ? 'active' : '' ?>">Read (<?= $read_count ?>)</a>
          <a href="dashboard.php?status=replied&q=<?= urlencode($search) ?>" class="filter-btn <?= $status_filter === 'replied' ? 'active' : '' ?>">Replied (<?= $replied_count ?>)</a>
        </div>

        <!-- Search Form -->
        <form method="GET" action="dashboard.php" class="d-flex gap-2">
          <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
          <div class="position-relative">
            <input type="text" name="q" class="search-input" placeholder="Search by name, email, subject..." value="<?= htmlspecialchars($search) ?>" style="min-width:260px;">
          </div>
          <button type="submit" class="btn btn-sm btn-primary-custom d-inline-flex align-items-center">
            <i class="bi bi-search"></i>
          </button>
          <?php if (!empty($search)): ?>
            <a href="dashboard.php?status=<?= urlencode($status_filter) ?>" class="btn btn-sm btn-outline-secondary rounded-pill d-inline-flex align-items-center">Clear</a>
          <?php endif; ?>
        </form>

      </div>

      <!-- Messages Table -->
      <div class="table-responsive">
        <table class="custom-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Sender</th>
              <th>Subject</th>
              <th>Message Snippet</th>
              <th>Status</th>
              <th>Received</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($messages)): ?>
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                  No messages found matching your criteria.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($messages as $msg): ?>
                <tr class="data-row">
                  <td class="fw-bold text-muted">#<?= $msg['id'] ?></td>
                  <td>
                    <div class="fw-bold text-white"><?= htmlspecialchars($msg['name']) ?></div>
                    <div class="small text-muted"><a href="mailto:<?= htmlspecialchars($msg['email']) ?>" class="text-decoration-none text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($msg['email']) ?></a></div>
                  </td>
                  <td>
                    <span class="fw-semibold text-white"><?= htmlspecialchars($msg['subject'] ?: 'General Inquiry') ?></span>
                  </td>
                  <td style="max-width:320px;">
                    <span class="text-truncate d-block text-secondary" title="<?= htmlspecialchars($msg['message']) ?>">
                      <?= htmlspecialchars(mb_strimwidth($msg['message'], 0, 70, '...')) ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($msg['status'] === 'new'): ?>
                      <span class="badge-status badge-new"><i class="bi bi-circle-fill" style="font-size:6px;"></i> New</span>
                    <?php elseif ($msg['status'] === 'read'): ?>
                      <span class="badge-status badge-read"><i class="bi bi-eye"></i> Read</span>
                    <?php else: ?>
                      <span class="badge-status badge-replied"><i class="bi bi-check2"></i> Replied</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-muted small">
                    <?= date('M d, Y h:i A', strtotime($msg['created_at'])) ?>
                  </td>
                  <td class="text-end">
                    <div class="d-inline-flex gap-1">
                      <!-- View Button -->
                      <button type="button" class="btn-action" title="View Full Message" 
                        data-bs-toggle="modal" 
                        data-bs-target="#msgModal"
                        data-id="<?= $msg['id'] ?>"
                        data-name="<?= htmlspecialchars($msg['name']) ?>"
                        data-email="<?= htmlspecialchars($msg['email']) ?>"
                        data-subject="<?= htmlspecialchars($msg['subject'] ?: 'General Inquiry') ?>"
                        data-message="<?= htmlspecialchars($msg['message']) ?>"
                        data-date="<?= date('M d, Y h:i A', strtotime($msg['created_at'])) ?>"
                        data-status="<?= $msg['status'] ?>">
                        <i class="bi bi-eye"></i>
                      </button>

                      <!-- Mark as replied -->
                      <a href="dashboard.php?action=status&id=<?= $msg['id'] ?>&to=replied" class="btn-action" title="Mark as Replied">
                        <i class="bi bi-reply"></i>
                      </a>

                      <!-- Delete Button -->
                      <a href="dashboard.php?action=delete&id=<?= $msg['id'] ?>" class="btn-action btn-del" title="Delete" onclick="return confirm('Are you sure you want to delete message #<?= $msg['id'] ?>?');">
                        <i class="bi bi-trash"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>

  </main>

  <!-- View Message Modal -->
  <div class="modal fade" id="msgModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title font-display fw-bold" id="modalSubject">Message Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3 mb-4 p-3 rounded-4" style="background:rgba(255,255,255,0.03);border:1px solid var(--border);">
            <div class="col-sm-6">
              <div class="small text-muted text-uppercase fw-bold">From</div>
              <div class="fw-bold fs-6 text-white" id="modalName">-</div>
              <div class="small"><a href="#" id="modalEmailLink" class="text-primary text-decoration-none"></a></div>
            </div>
            <div class="col-sm-6 text-sm-end">
              <div class="small text-muted text-uppercase fw-bold">Received At</div>
              <div class="text-white" id="modalDate">-</div>
              <div class="mt-1" id="modalStatusBadge"></div>
            </div>
          </div>

          <div class="mb-2 text-muted small text-uppercase fw-bold">Message Content</div>
          <div class="p-3 rounded-3" style="background:#070a10;border:1px solid var(--border);white-space:pre-wrap;line-height:1.7;color:#e2e8f0;font-size:0.95rem;" id="modalMessage">
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-between">
          <div id="modalStatusActions" class="d-flex gap-2"></div>
          <div>
            <a href="#" id="modalReplyBtn" class="btn btn-sm btn-primary-custom d-inline-flex align-items-center gap-1">
              <i class="bi bi-reply-fill"></i> Reply via Email
            </a>
            <button type="button" class="btn btn-sm btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="../assets/bootstrap.bundle.min.js"></script>
  <script>
    // Modal dynamic data loader
    var msgModal = document.getElementById('msgModal');
    if (msgModal) {
      msgModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name');
        var email = button.getAttribute('data-email');
        var subject = button.getAttribute('data-subject');
        var message = button.getAttribute('data-message');
        var date = button.getAttribute('data-date');
        var status = button.getAttribute('data-status');

        document.getElementById('modalSubject').textContent = subject;
        document.getElementById('modalName').textContent = name;
        
        var emailLink = document.getElementById('modalEmailLink');
        emailLink.textContent = email;
        emailLink.href = 'mailto:' + email + '?subject=Re: ' + encodeURIComponent(subject);

        document.getElementById('modalDate').textContent = date;
        document.getElementById('modalMessage').textContent = message;

        // Reply button
        var replyBtn = document.getElementById('modalReplyBtn');
        replyBtn.href = 'mailto:' + email + '?subject=Re: ' + encodeURIComponent(subject);

        // Status badge
        var statusBadge = document.getElementById('modalStatusBadge');
        if (status === 'new') {
          statusBadge.innerHTML = '<span class="badge-status badge-new">New</span>';
        } else if (status === 'read') {
          statusBadge.innerHTML = '<span class="badge-status badge-read">Read</span>';
        } else {
          statusBadge.innerHTML = '<span class="badge-status badge-replied">Replied</span>';
        }

        // Quick status actions inside modal
        var actionsDiv = document.getElementById('modalStatusActions');
        actionsDiv.innerHTML = `
          <a href="dashboard.php?action=status&id=${id}&to=read" class="btn btn-sm btn-outline-info rounded-pill">Mark Read</a>
          <a href="dashboard.php?action=status&id=${id}&to=replied" class="btn btn-sm btn-outline-warning rounded-pill">Mark Replied</a>
        `;
      });
    }
  </script>
</body>
</html>
