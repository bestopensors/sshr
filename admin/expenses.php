<?php
/**
 * Admin - Expenses/Troskovnik Management
 */
// Start output buffering to prevent header issues
ob_start();

// Include required files BEFORE any output
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireAuth();

$pageTitle = 'Troskovnik';

// Initialize variables
$expenses = [];
$message = '';
$error = '';
$companyTotals = [];
$upcomingSubscriptions = [];
$monthlyEquivalent = 0;
$subscriptionFilter = $_GET['subscription_filter'] ?? 'all';
$subscriptionSort = $_GET['subscription_sort'] ?? 'next_payment_date';
$subscriptionOrder = strtolower($_GET['subscription_order'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

// Handle form submission (add/edit) - MUST be before header include
if ($_SERVER['REQUEST_METHOD'] === 'POST' && dbAvailable()) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $description = trim($_POST['description'] ?? '');
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $expense_date = trim($_POST['expense_date'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $paid_by = trim($_POST['paid_by'] ?? '');
    $payment_type = isset($_POST['payment_type']) && in_array($_POST['payment_type'], ['one_time', 'monthly', 'weekly', 'yearly']) ? $_POST['payment_type'] : 'one_time';
    $next_payment_date = !empty($_POST['next_payment_date']) ? trim($_POST['next_payment_date']) : null;
    $notes = trim($_POST['notes'] ?? '');
    
    // Calculate next payment date for subscriptions
    if ($payment_type !== 'one_time' && !empty($expense_date)) {
        if (empty($next_payment_date)) {
            // Calculate from expense_date
            $baseDate = new DateTime($expense_date);
            switch ($payment_type) {
                case 'weekly':
                    $baseDate->modify('+1 week');
                    break;
                case 'monthly':
                    $baseDate->modify('+1 month');
                    break;
                case 'yearly':
                    $baseDate->modify('+1 year');
                    break;
            }
            $next_payment_date = $baseDate->format('Y-m-d');
        }
    } else {
        $next_payment_date = null;
    }
    
    // Handle file upload
    $file_path = $_POST['current_file_path'] ?? '';
    $file_type = $_POST['current_file_type'] ?? '';
    
    if (isset($_FILES['expense_file']) && $_FILES['expense_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/expenses/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $ext = strtolower(pathinfo($_FILES['expense_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx'];
        
        if (in_array($ext, $allowed)) {
            $filename = 'expense_' . time() . '_' . uniqid() . '.' . $ext;
            $filePath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['expense_file']['tmp_name'], $filePath)) {
                $file_path = 'uploads/expenses/' . $filename;
                $file_type = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']) ? 'image' : 'document';
                
                // Delete old file if exists
                if (!empty($_POST['current_file_path']) && file_exists(__DIR__ . '/../' . $_POST['current_file_path'])) {
                    @unlink(__DIR__ . '/../' . $_POST['current_file_path']);
                }
            }
        }
    }
    
    if (empty($description)) {
        $error = 'Opis je obavezan.';
    } elseif (empty($amount) || $amount <= 0) {
        $error = 'Iznos mora biti veći od 0.';
    } elseif (empty($expense_date)) {
        $error = 'Datum je obavezan.';
    } else {
        try {
            if ($id > 0) {
                // Update existing expense
                $stmt = db()->prepare("UPDATE expenses SET description = ?, amount = ?, expense_date = ?, category = ?, company_name = ?, paid_by = ?, payment_type = ?, next_payment_date = ?, file_path = ?, file_type = ?, notes = ? WHERE id = ?");
                $stmt->execute([$description, $amount, $expense_date, $category, $company_name, $paid_by, $payment_type, $next_payment_date, $file_path, $file_type, $notes, $id]);
                $message = 'Trošak uspješno ažuriran.';
            } else {
                // Insert new expense
                $stmt = db()->prepare("INSERT INTO expenses (description, amount, expense_date, category, company_name, paid_by, payment_type, next_payment_date, file_path, file_type, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$description, $amount, $expense_date, $category, $company_name, $paid_by, $payment_type, $next_payment_date, $file_path, $file_type, $notes]);
                $message = 'Trošak uspješno dodan.';
            }
            
            // Redirect to prevent modal from showing after successful save
            if ($message) {
                ob_end_clean();
                header('Location: expenses.php?success=1');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Greška pri spremanju troška: ' . $e->getMessage();
        }
    }
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $message = 'Trošak uspješno spremljen.';
    unset($_GET['edit']);
    $editExpense = null;
}

// Handle delete
if (isset($_GET['delete']) && dbAvailable()) {
    $id = (int)$_GET['delete'];
    try {
        // Get file path before deleting
        $stmt = db()->prepare("SELECT file_path FROM expenses WHERE id = ?");
        $stmt->execute([$id]);
        $expense = $stmt->fetch();
        
        // Delete expense
        $stmt = db()->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->execute([$id]);
        
        // Delete file if exists
        if ($expense && !empty($expense['file_path']) && file_exists(__DIR__ . '/../' . $expense['file_path'])) {
            @unlink(__DIR__ . '/../' . $expense['file_path']);
        }
        
        ob_end_clean();
        header('Location: expenses.php?success=1&msg=deleted');
        exit;
    } catch (Exception $e) {
        $error = 'Greška pri brisanju troška.';
    }
}

// Get filter and sort parameters
$filter_category = $_GET['category'] ?? 'all';
$filter_company = $_GET['company'] ?? 'all';
$sort_by = $_GET['sort'] ?? 'expense_date';
$sort_order = $_GET['order'] ?? 'desc';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
if (dbAvailable()) {
    try {
        $where = [];
        $params = [];
        
        if ($filter_category !== 'all' && !empty($filter_category)) {
            $where[] = "category = ?";
            $params[] = $filter_category;
        }
        
        if ($filter_company !== 'all' && !empty($filter_company)) {
            $where[] = "company_name = ?";
            $params[] = $filter_company;
        }
        
        if (!empty($search)) {
            $where[] = "(description LIKE ? OR company_name LIKE ? OR notes LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($date_from)) {
            $where[] = "expense_date >= ?";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where[] = "expense_date <= ?";
            $params[] = $date_to;
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // Validate sort column
        $allowedSorts = ['expense_date', 'amount', 'description', 'category'];
        if (!in_array($sort_by, $allowedSorts)) {
            $sort_by = 'expense_date';
        }
        $sort_order = strtolower($sort_order) === 'asc' ? 'ASC' : 'DESC';
        
        $sql = "SELECT * FROM expenses {$whereClause} ORDER BY {$sort_by} {$sort_order}";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $expenses = $stmt->fetchAll();
        
        // Get unique categories and companies for filters
        $categoriesStmt = db()->query("SELECT DISTINCT category FROM expenses WHERE category IS NOT NULL AND category != '' ORDER BY category");
        $categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $companiesStmt = db()->query("SELECT DISTINCT company_name FROM expenses WHERE company_name IS NOT NULL AND company_name != '' ORDER BY company_name");
        $companies = $companiesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Calculate totals
        $totalStmt = db()->prepare("SELECT SUM(amount) as total FROM expenses {$whereClause}");
        $totalStmt->execute($params);
        $total = $totalStmt->fetch()['total'] ?? 0;
        
        // Calculate totals per company (all expenses, not filtered)
        $companyTotalsStmt = db()->query("SELECT company_name, SUM(amount) as total FROM expenses WHERE company_name IS NOT NULL AND company_name != '' GROUP BY company_name ORDER BY total DESC");
        $companyTotals = $companyTotalsStmt->fetchAll();
        
        // Get upcoming subscription payments with time filter and sorting
        $subscriptionDateFilter = '';
        
        if ($subscriptionFilter === '1month') {
            $subscriptionDateFilter = "AND next_payment_date <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH)";
        } elseif ($subscriptionFilter === '6months') {
            $subscriptionDateFilter = "AND next_payment_date <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH)";
        } elseif ($subscriptionFilter === '1year') {
            $subscriptionDateFilter = "AND next_payment_date <= DATE_ADD(CURDATE(), INTERVAL 1 YEAR)";
        }
        
        // Validate sort column
        $allowedSubscriptionSorts = ['next_payment_date', 'amount', 'description', 'company_name'];
        if (!in_array($subscriptionSort, $allowedSubscriptionSorts)) {
            $subscriptionSort = 'next_payment_date';
        }
        
        $upcomingSubscriptionsStmt = db()->query("SELECT * FROM expenses WHERE payment_type != 'one_time' AND next_payment_date IS NOT NULL AND next_payment_date >= CURDATE() {$subscriptionDateFilter} ORDER BY {$subscriptionSort} {$subscriptionOrder} LIMIT 50");
        $upcomingSubscriptions = $upcomingSubscriptionsStmt->fetchAll();
        
        // Calculate subscription totals
        $monthlySubscriptionsStmt = db()->query("SELECT SUM(amount) as total FROM expenses WHERE payment_type = 'monthly'");
        $monthlyTotal = $monthlySubscriptionsStmt->fetch()['total'] ?? 0;
        
        $weeklySubscriptionsStmt = db()->query("SELECT SUM(amount) as total FROM expenses WHERE payment_type = 'weekly'");
        $weeklyTotal = $weeklySubscriptionsStmt->fetch()['total'] ?? 0;
        
        $yearlySubscriptionsStmt = db()->query("SELECT SUM(amount) as total FROM expenses WHERE payment_type = 'yearly'");
        $yearlyTotal = $yearlySubscriptionsStmt->fetch()['total'] ?? 0;
        
        // Calculate monthly equivalent (weekly * 4.33, yearly / 12)
        $monthlyEquivalent = $monthlyTotal + ($weeklyTotal * 4.33) + ($yearlyTotal / 12);
    } catch (Exception $e) {
        $error = 'Greška pri učitavanju troškova. Provjerite je li tablica kreirana.';
    }
}

// Get expense for editing
$editExpense = null;
if (isset($_GET['edit']) && dbAvailable()) {
    $id = (int)$_GET['edit'];
    try {
        $stmt = db()->prepare("SELECT * FROM expenses WHERE id = ?");
        $stmt->execute([$id]);
        $editExpense = $stmt->fetch();
    } catch (Exception $e) {
        $error = 'Greška pri učitavanju troška.';
    }
}

// Now include header after form processing
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
    <span>Baza podataka nije povezana. Troškovi će se prikazivati nakon konfiguracije baze.</span>
</div>
<?php endif; ?>

<div class="card">
    <div class="card__header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2 class="card__title">Troskovnik</h2>
                <p style="color: var(--text-secondary); font-size: 13px; margin-top: 8px;">Upravljanje svim troškovima</p>
            </div>
            <button class="btn btn--primary" onclick="document.getElementById('expense-form-modal').style.display='block'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Dodaj trošak
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
                    <input type="text" name="search" placeholder="Pretraži po opisu, tvrtki..." value="<?php echo htmlspecialchars($search); ?>" class="filter-input" onchange="this.form.submit()">
                </div>
                
                <div class="filter-group">
                    <select name="category" class="filter-select" onchange="this.form.submit()">
                        <option value="all">Sve kategorije</option>
                        <?php foreach ($categories ?? [] as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filter_category === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <select name="company" class="filter-select" onchange="this.form.submit()">
                        <option value="all">Sve tvrtke</option>
                        <?php foreach ($companies ?? [] as $comp): ?>
                            <option value="<?php echo htmlspecialchars($comp); ?>" <?php echo $filter_company === $comp ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($comp); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <input type="date" name="date_from" class="filter-input" value="<?php echo htmlspecialchars($date_from); ?>" onchange="this.form.submit()" style="width: 150px;">
                </div>
                
                <div class="filter-group">
                    <input type="date" name="date_to" class="filter-input" value="<?php echo htmlspecialchars($date_to); ?>" onchange="this.form.submit()" style="width: 150px;">
                </div>
                
                <div class="filter-group">
                    <select name="sort" class="filter-select" onchange="this.form.submit()">
                        <option value="expense_date" <?php echo $sort_by === 'expense_date' ? 'selected' : ''; ?>>Datum</option>
                        <option value="amount" <?php echo $sort_by === 'amount' ? 'selected' : ''; ?>>Iznos</option>
                        <option value="description" <?php echo $sort_by === 'description' ? 'selected' : ''; ?>>Opis</option>
                        <option value="category" <?php echo $sort_by === 'category' ? 'selected' : ''; ?>>Kategorija</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <select name="order" class="filter-select" onchange="this.form.submit()">
                        <option value="desc" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Opadajuće</option>
                        <option value="asc" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Rastuće</option>
                    </select>
                </div>
                
                <?php if (!empty($search) || $filter_category !== 'all' || $filter_company !== 'all' || !empty($date_from) || !empty($date_to)): ?>
                    <a href="expenses.php" class="btn btn--outline filter-reset">Resetiraj</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <?php if (empty($expenses)): ?>
        <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <h3>Nema troškova</h3>
            <p>Dodajte novi trošak klikom na gumb "Dodaj trošak".</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Opis</th>
                        <th class="amount-column">Iznos</th>
                        <th>Kategorija</th>
                        <th>Tvrtka</th>
                        <th>Platio/la</th>
                        <th>Tip plaćanja</th>
                        <th>Sljedeće plaćanje</th>
                        <th>Dokument</th>
                        <th>Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?php echo date('d.m.Y', strtotime($expense['expense_date'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($expense['description']); ?></strong></td>
                            <td class="amount-column" style="white-space: nowrap; text-align: right; padding-right: 16px;">
                                <strong style="color: var(--primary); font-size: 0.9em;"><?php echo number_format($expense['amount'], 2, ',', '.'); ?> <span style="font-size: 0.85em;">€</span></strong>
                            </td>
                            <td><?php echo htmlspecialchars($expense['category'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($expense['company_name'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($expense['paid_by'] ?: '-'); ?></td>
                            <td>
                                <?php
                                $paymentTypes = [
                                    'one_time' => 'Jednokratno',
                                    'monthly' => 'Mjesečno',
                                    'weekly' => 'Tjedno',
                                    'yearly' => 'Godišnje'
                                ];
                                $paymentType = $expense['payment_type'] ?? 'one_time';
                                echo htmlspecialchars($paymentTypes[$paymentType] ?? 'Jednokratno');
                                ?>
                            </td>
                            <td>
                                <?php if ($paymentType !== 'one_time' && !empty($expense['next_payment_date'])): ?>
                                    <?php 
                                    $nextDate = new DateTime($expense['next_payment_date']);
                                    $today = new DateTime();
                                    $daysUntil = $today->diff($nextDate)->days;
                                    $isOverdue = $nextDate < $today;
                                    $isDueSoon = $daysUntil <= 7 && !$isOverdue;
                                    ?>
                                    <span style="color: <?php echo $isOverdue ? '#ef4444' : ($isDueSoon ? '#f59e0b' : 'var(--text-primary)'); ?>;">
                                        <?php echo date('d.m.Y', strtotime($expense['next_payment_date'])); ?>
                                        <?php if ($isOverdue): ?>
                                            <span style="color: #ef4444; font-size: 11px;">(Kasni)</span>
                                        <?php elseif ($isDueSoon): ?>
                                            <span style="color: #f59e0b; font-size: 11px;">(Uskoro)</span>
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($expense['file_path']): ?>
                                    <a href="../<?php echo htmlspecialchars($expense['file_path']); ?>" target="_blank" class="btn btn--sm btn--secondary">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14,2 14,8 20,8"></polyline>
                                            <line x1="16" y1="13" x2="8" y2="13"></line>
                                            <line x1="16" y1="17" x2="8" y2="17"></line>
                                            <polyline points="10,9 9,9 8,9"></polyline>
                                        </svg>
                                        Pregled
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <a href="?edit=<?php echo $expense['id']; ?>" class="btn btn--sm btn--secondary">Uredi</a>
                                    <button class="btn btn--sm btn--danger" 
                                            onclick="confirmDelete(<?php echo $expense['id']; ?>, '<?php echo htmlspecialchars(addslashes($expense['description'])); ?>')">Obriši</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (isset($total)): ?>
        <div style="padding: 16px; border-top: 1px solid var(--border-color); background: var(--bg-secondary);">
            <p style="margin: 0; color: var(--text-secondary);">
                <strong style="color: var(--text-primary);">Ukupno:</strong> <?php echo number_format($total, 2, ',', '.'); ?> €
                | <strong>Broj troškova:</strong> <?php echo count($expenses); ?>
                <?php if (isset($monthlyEquivalent) && $monthlyEquivalent > 0): ?>
                    | <strong style="color: var(--primary);">Mjesečni ekvivalent pretplata:</strong> <?php echo number_format($monthlyEquivalent, 2, ',', '.'); ?> €
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if (!empty($companyTotals) || !empty($upcomingSubscriptions)): ?>
<div class="card" style="margin-top: 24px;">
    <div class="card__header">
        <h2 class="card__title">Pregled i analitika</h2>
    </div>
    <div class="card__body">
        <?php if (!empty($companyTotals)): ?>
        <div style="margin-bottom: 32px;">
            <h3 style="color: var(--text-primary); margin-bottom: 16px; font-size: 16px;">Ukupni troškovi po tvrtki</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px;">
                <?php foreach ($companyTotals as $companyTotal): ?>
                    <div style="padding: 16px; background: var(--bg-input); border-radius: 8px; border-left: 4px solid var(--primary);">
                        <div style="color: var(--text-secondary); font-size: 13px; margin-bottom: 4px;"><?php echo htmlspecialchars($companyTotal['company_name']); ?></div>
                        <div style="color: var(--text-primary); font-size: 20px; font-weight: 600;"><?php echo number_format($companyTotal['total'], 2, ',', '.'); ?> €</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                <h3 style="color: var(--text-primary); margin: 0; font-size: 16px;">Nadolazeća plaćanja pretplata</h3>
                <form method="GET" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <?php
                    // Preserve existing GET parameters
                    foreach ($_GET as $key => $value) {
                        if (!in_array($key, ['subscription_filter', 'subscription_sort', 'subscription_order'])) {
                            echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                        }
                    }
                    ?>
                    <label style="color: var(--text-secondary); font-size: 14px; margin-right: 4px;">Filtriraj:</label>
                    <select name="subscription_filter" class="filter-select" style="min-width: 150px;" onchange="this.form.submit()">
                        <option value="all" <?php echo $subscriptionFilter === 'all' ? 'selected' : ''; ?>>Sve</option>
                        <option value="1month" <?php echo $subscriptionFilter === '1month' ? 'selected' : ''; ?>>Sljedeći mjesec</option>
                        <option value="6months" <?php echo $subscriptionFilter === '6months' ? 'selected' : ''; ?>>Sljedećih 6 mjeseci</option>
                        <option value="1year" <?php echo $subscriptionFilter === '1year' ? 'selected' : ''; ?>>Sljedeća godina</option>
                    </select>
                    
                    <label style="color: var(--text-secondary); font-size: 14px; margin-left: 8px; margin-right: 4px;">Sortiraj:</label>
                    <select name="subscription_sort" class="filter-select" style="min-width: 140px;" onchange="this.form.submit()">
                        <option value="next_payment_date" <?php echo $subscriptionSort === 'next_payment_date' ? 'selected' : ''; ?>>Datum</option>
                        <option value="amount" <?php echo $subscriptionSort === 'amount' ? 'selected' : ''; ?>>Iznos</option>
                        <option value="description" <?php echo $subscriptionSort === 'description' ? 'selected' : ''; ?>>Opis</option>
                        <option value="company_name" <?php echo $subscriptionSort === 'company_name' ? 'selected' : ''; ?>>Tvrtka</option>
                    </select>
                    
                    <select name="subscription_order" class="filter-select" style="min-width: 120px;" onchange="this.form.submit()">
                        <option value="asc" <?php echo $subscriptionOrder === 'ASC' ? 'selected' : ''; ?>>Rastuće</option>
                        <option value="desc" <?php echo $subscriptionOrder === 'DESC' ? 'selected' : ''; ?>>Opadajuće</option>
                    </select>
                </form>
            </div>
            
            <?php if (!empty($upcomingSubscriptions)): ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Opis</th>
                            <th class="amount-column">Iznos</th>
                            <th>Tvrtka</th>
                            <th>Tip</th>
                            <th>Dana do plaćanja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcomingSubscriptions as $sub): ?>
                            <?php
                            $nextDate = new DateTime($sub['next_payment_date']);
                            $today = new DateTime();
                            $daysUntil = $today->diff($nextDate)->days;
                            $isOverdue = $nextDate < $today;
                            $isDueSoon = $daysUntil <= 7 && !$isOverdue;
                            
                            $paymentTypes = [
                                'monthly' => 'Mjesečno',
                                'weekly' => 'Tjedno',
                                'yearly' => 'Godišnje'
                            ];
                            ?>
                            <tr style="background: <?php echo $isOverdue ? 'rgba(239, 68, 68, 0.1)' : ($isDueSoon ? 'rgba(245, 158, 11, 0.1)' : 'transparent'); ?>;">
                                <td><?php echo date('d.m.Y', strtotime($sub['next_payment_date'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($sub['description']); ?></strong></td>
                                <td class="amount-column" style="white-space: nowrap; text-align: right; padding-right: 16px;">
                                    <strong style="color: var(--primary); font-size: 0.9em;"><?php echo number_format($sub['amount'], 2, ',', '.'); ?> <span style="font-size: 0.85em;">€</span></strong>
                                </td>
                                <td><?php echo htmlspecialchars($sub['company_name'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($paymentTypes[$sub['payment_type']] ?? ''); ?></td>
                                <td>
                                    <span style="color: <?php echo $isOverdue ? '#ef4444' : ($isDueSoon ? '#f59e0b' : 'var(--text-primary)'); ?>; font-weight: 500;">
                                        <?php if ($isOverdue): ?>
                                            <?php echo $daysUntil; ?> dana kasni
                                        <?php else: ?>
                                            <?php echo $daysUntil; ?> dana
                                        <?php endif; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state" style="padding: 40px 20px; text-align: center; color: var(--text-secondary);">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 16px; opacity: 0.5;">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <p style="margin: 0; color: var(--text-secondary);">Nema pretplata za odabrani period.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

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

.modal__content--small {
    max-width: 450px;
}

/* Amount column styling to prevent wrapping */
.table th.amount-column,
.table td.amount-column {
    white-space: nowrap !important;
    min-width: 110px;
    text-align: right;
    padding-right: 16px;
}

/* Amount display styling */
.table td.amount-column strong {
    display: inline-block;
    font-size: 0.9em;
    white-space: nowrap;
}

.table td.amount-column strong span {
    font-size: 0.85em;
}
</style>

<!-- Add/Edit Expense Modal -->
<div id="expense-form-modal" class="modal" style="display: <?php echo ($editExpense && !isset($_GET['success'])) ? 'block' : 'none'; ?>;">
    <div class="modal__content">
        <div class="modal__header">
            <h3><?php echo $editExpense ? 'Uredi trošak' : 'Dodaj novi trošak'; ?></h3>
            <button class="modal__close" onclick="closeExpenseModal()">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="modal__body">
            <?php if ($editExpense): ?>
                <input type="hidden" name="id" value="<?php echo $editExpense['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label class="form-label">Opis *</label>
                <input type="text" name="description" class="input" value="<?php echo htmlspecialchars($editExpense['description'] ?? ''); ?>" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Iznos (€) *</label>
                    <input type="number" name="amount" class="input" step="0.01" min="0.01" value="<?php echo htmlspecialchars($editExpense['amount'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Datum *</label>
                    <input type="date" name="expense_date" class="input" value="<?php echo htmlspecialchars($editExpense['expense_date'] ?? date('Y-m-d')); ?>" required>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Kategorija</label>
                    <input type="text" name="category" class="input" value="<?php echo htmlspecialchars($editExpense['category'] ?? ''); ?>" placeholder="npr. Marketing, IT, Putovanje...">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tvrtka</label>
                    <input type="text" name="company_name" class="input" value="<?php echo htmlspecialchars($editExpense['company_name'] ?? ''); ?>" placeholder="Ime tvrtke">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Platio/la</label>
                <input type="text" name="paid_by" class="input" value="<?php echo htmlspecialchars($editExpense['paid_by'] ?? ''); ?>" placeholder="Tko je platio trošak (npr. Ime, Tvrtka, Klijent...)">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Tip plaćanja</label>
                    <select name="payment_type" class="input" id="payment-type-select" onchange="toggleNextPaymentDate()">
                        <option value="one_time" <?php echo ($editExpense['payment_type'] ?? 'one_time') === 'one_time' ? 'selected' : ''; ?>>Jednokratno</option>
                        <option value="weekly" <?php echo ($editExpense['payment_type'] ?? 'one_time') === 'weekly' ? 'selected' : ''; ?>>Tjedno</option>
                        <option value="monthly" <?php echo ($editExpense['payment_type'] ?? 'one_time') === 'monthly' ? 'selected' : ''; ?>>Mjesečno</option>
                        <option value="yearly" <?php echo ($editExpense['payment_type'] ?? 'one_time') === 'yearly' ? 'selected' : ''; ?>>Godišnje</option>
                    </select>
                </div>
                
                <div class="form-group" id="next-payment-group" style="display: <?php echo ($editExpense['payment_type'] ?? 'one_time') !== 'one_time' ? 'block' : 'none'; ?>;">
                    <label class="form-label">Sljedeće plaćanje</label>
                    <input type="date" name="next_payment_date" class="input" value="<?php echo htmlspecialchars($editExpense['next_payment_date'] ?? ''); ?>" id="next-payment-date">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Dokument (PDF, slika)</label>
                <?php if ($editExpense && !empty($editExpense['file_path'])): ?>
                    <div style="margin-bottom: 8px;">
                        <a href="../<?php echo htmlspecialchars($editExpense['file_path']); ?>" target="_blank" class="btn btn--sm btn--secondary">
                            Trenutni dokument
                        </a>
                    </div>
                    <input type="hidden" name="current_file_path" value="<?php echo htmlspecialchars($editExpense['file_path']); ?>">
                    <input type="hidden" name="current_file_type" value="<?php echo htmlspecialchars($editExpense['file_type']); ?>">
                <?php endif; ?>
                <input type="file" name="expense_file" class="input" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx">
                <small style="color: var(--text-secondary); font-size: 12px;">Maksimalna veličina: 10MB</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Napomene</label>
                <textarea name="notes" class="input" rows="3"><?php echo htmlspecialchars($editExpense['notes'] ?? ''); ?></textarea>
            </div>
            
            <div class="modal__footer">
                <button type="button" class="btn btn--outline" onclick="closeExpenseModal()">Odustani</button>
                <button type="submit" class="btn btn--primary"><?php echo $editExpense ? 'Ažuriraj' : 'Dodaj'; ?></button>
            </div>
        </form>
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

<script>
// Define escapeHtml first since it's used by other functions
window.escapeHtml = function(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
};

let pendingDeleteId = null;

window.confirmDelete = function(id, description) {
    pendingDeleteId = id;
    const message = 'Jeste li sigurni da želite obrisati trošak "' + escapeHtml(description) + '"? Ova akcija se ne može poništiti.';
    document.getElementById('delete-message').textContent = message;
    document.getElementById('delete-modal').style.display = 'block';
};

window.closeDeleteModal = function() {
    document.getElementById('delete-modal').style.display = 'none';
    pendingDeleteId = null;
};

window.closeExpenseModal = function() {
    document.getElementById('expense-form-modal').style.display = 'none';
    window.location.href = 'expenses.php';
};

// Toggle next payment date field based on payment type
window.toggleNextPaymentDate = function() {
    const paymentType = document.getElementById('payment-type-select');
    const nextPaymentGroup = document.getElementById('next-payment-group');
    const nextPaymentDate = document.getElementById('next-payment-date');
    
    if (!paymentType || !nextPaymentGroup || !nextPaymentDate) return;
    
    if (paymentType.value !== 'one_time') {
        nextPaymentGroup.style.display = 'block';
        if (!nextPaymentDate.value) {
            // Auto-calculate next payment date
            const expenseDateInput = document.querySelector('input[name="expense_date"]');
            if (expenseDateInput && expenseDateInput.value) {
                const date = new Date(expenseDateInput.value);
                switch (paymentType.value) {
                    case 'weekly':
                        date.setDate(date.getDate() + 7);
                        break;
                    case 'monthly':
                        date.setMonth(date.getMonth() + 1);
                        break;
                    case 'yearly':
                        date.setFullYear(date.getFullYear() + 1);
                        break;
                }
                nextPaymentDate.value = date.toISOString().split('T')[0];
            }
        }
    } else {
        nextPaymentGroup.style.display = 'none';
        nextPaymentDate.value = '';
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const expenseModal = document.getElementById('expense-form-modal');
    const deleteModal = document.getElementById('delete-modal');
    
    // Delete confirm button
    const deleteConfirmBtn = document.getElementById('delete-confirm-btn');
    if (deleteConfirmBtn) {
        deleteConfirmBtn.addEventListener('click', function() {
            if (pendingDeleteId !== null) {
                window.location.href = '?delete=' + pendingDeleteId;
            }
        });
    }
    
    // Close modals when clicking outside
    [expenseModal, deleteModal].forEach(modal => {
        if (modal) {
            const modalContent = modal.querySelector('.modal__content');
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    if (modal === expenseModal) {
                        closeExpenseModal();
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
            if (expenseModal && expenseModal.style.display === 'block') {
                closeExpenseModal();
            } else if (deleteModal && deleteModal.style.display === 'block') {
                closeDeleteModal();
            }
        }
    });
    
    // Initialize payment type toggle
    const paymentTypeSelect = document.getElementById('payment-type-select');
    if (paymentTypeSelect) {
        // Set initial state
        toggleNextPaymentDate();
        
        // Also listen to expense date changes to auto-update next payment date
        const expenseDateInput = document.querySelector('input[name="expense_date"]');
        if (expenseDateInput) {
            expenseDateInput.addEventListener('change', function() {
                if (paymentTypeSelect.value !== 'one_time') {
                    toggleNextPaymentDate();
                }
            });
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>

