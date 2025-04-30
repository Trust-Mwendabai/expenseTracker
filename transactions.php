<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/php/transaction.php';

$transaction = new Transaction();
$db = new Database();
$conn = $db->connect();
$userId = $_SESSION['user_id'];

// Initialize variables
$message = '';
$alertType = '';
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 20; // Transactions per page
$offset = ($currentPage - 1) * $perPage;

// Handle transaction deletion
if (isset($_POST['delete_transaction'])) {
    $transactionId = $_POST['transaction_id'];
    
    if ($transaction->deleteTransaction($transactionId, $userId)) {
        $message = "Transaction deleted successfully!";
        $alertType = "success";
    } else {
        $message = "Failed to delete transaction";
        $alertType = "danger";
    }
}

// Get filter values
$filterType = isset($_GET['type']) ? $_GET['type'] : '';
$filterCategory = isset($_GET['category']) ? $_GET['category'] : '';
$filterStartDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$filterEndDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$filterSort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';

// Prepare a clean URL for pagination
$filterParams = [];
if ($filterType) $filterParams[] = "type=" . urlencode($filterType);
if ($filterCategory) $filterParams[] = "category=" . urlencode($filterCategory);
if ($filterStartDate) $filterParams[] = "start_date=" . urlencode($filterStartDate);
if ($filterEndDate) $filterParams[] = "end_date=" . urlencode($filterEndDate);
if ($filterSort) $filterParams[] = "sort=" . urlencode($filterSort);
$filterUrl = !empty($filterParams) ? '&' . implode('&', $filterParams) : '';

// Get list of categories for the filter dropdown
$categories = [];
$stmt = $conn->prepare("SELECT DISTINCT category FROM transactions WHERE user_id = ? ORDER BY category");
$stmt->bind_param("i", $userId);
$stmt->execute();
$categoryResult = $stmt->get_result();
while ($row = $categoryResult->fetch_assoc()) {
    $categories[] = $row['category'];
}

// Get transactions with optional filtering
$transactions = $transaction->getFilteredTransactions(
    $userId, 
    $filterType, 
    $filterCategory, 
    $filterStartDate, 
    $filterEndDate,
    $filterSort,
    $perPage,
    $offset
);

// Get transaction count for pagination
$totalTransactions = $transaction->getFilteredTransactionCount(
    $userId, 
    $filterType, 
    $filterCategory, 
    $filterStartDate, 
    $filterEndDate
);

$totalPages = ceil($totalTransactions / $perPage);

// Calculate summary data
$summary = [
    'total_income' => 0,
    'total_expense' => 0,
    'net_balance' => 0,
    'count' => $totalTransactions
];

// Get summary data based on filtered results
$summaryData = $transaction->getFilteredSummary(
    $userId, 
    $filterType, 
    $filterCategory, 
    $filterStartDate, 
    $filterEndDate
);

if ($summaryData) {
    $summary['total_income'] = $summaryData['total_income'];
    $summary['total_expense'] = $summaryData['total_expense'];
    $summary['net_balance'] = $summary['total_income'] - $summary['total_expense'];
}

// Close database connection
$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transactions - Expense Tracker</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css for animations -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="src/css/styles.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 animate__animated animate__fadeInDown">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-wallet2 me-2"></i>Expense Tracker
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_transaction.php">
                            <i class="bi bi-plus-circle me-1"></i>Add Transaction
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-file-earmark-bar-graph me-1"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear me-1"></i>Settings
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="categories.php"><i class="bi bi-tags me-2"></i>Categories</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container animate__animated animate__fadeIn">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="mb-3"><i class="bi bi-list-ul me-2"></i>Transaction History</h1>
                <p class="text-muted">View, filter, edit, and manage all your transactions.</p>
            </div>
            <div class="col-md-4 text-md-end d-flex align-items-center justify-content-md-end">
                <a href="add_transaction.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Add New Transaction
                </a>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title text-primary mb-3">
                    <i class="bi bi-funnel me-2"></i>Filter Transactions
                </h5>
                <form class="row g-3" method="get">
                    <div class="col-md-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">All Types</option>
                            <option value="income" <?= $filterType == 'income' ? 'selected' : '' ?>>Income</option>
                            <option value="expense" <?= $filterType == 'expense' ? 'selected' : '' ?>>Expense</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars($category) ?>" <?= $filterCategory == $category ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="start_date" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $filterStartDate ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="end_date" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $filterEndDate ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="sort" class="form-label">Sort By</label>
                        <select class="form-select" id="sort" name="sort">
                            <option value="date_desc" <?= $filterSort == 'date_desc' ? 'selected' : '' ?>>Newest First</option>
                            <option value="date_asc" <?= $filterSort == 'date_asc' ? 'selected' : '' ?>>Oldest First</option>
                            <option value="amount_desc" <?= $filterSort == 'amount_desc' ? 'selected' : '' ?>>Amount (High to Low)</option>
                            <option value="amount_asc" <?= $filterSort == 'amount_asc' ? 'selected' : '' ?>>Amount (Low to High)</option>
                        </select>
                    </div>
                    
                    <div class="col-12 d-flex justify-content-end mt-4">
                        <a href="transactions.php" class="btn btn-outline-secondary me-2">
                            <i class="bi bi-x-circle me-1"></i>Clear Filters
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i>Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title text-primary mb-3">
                            <i class="bi bi-clipboard-data me-2"></i>Summary
                        </h5>
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="p-3 rounded bg-light mb-2">
                                    <h6 class="text-muted mb-2">Total Income</h6>
                                    <h4 class="text-success mb-0">$<?= number_format($summary['total_income'], 2) ?></h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 rounded bg-light mb-2">
                                    <h6 class="text-muted mb-2">Total Expenses</h6>
                                    <h4 class="text-danger mb-0">$<?= number_format($summary['total_expense'], 2) ?></h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 rounded bg-light mb-2">
                                    <h6 class="text-muted mb-2">Net Balance</h6>
                                    <h4 class="<?= $summary['net_balance'] >= 0 ? 'text-success' : 'text-danger' ?> mb-0">
                                        $<?= number_format($summary['net_balance'], 2) ?>
                                    </h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 rounded bg-light mb-2">
                                    <h6 class="text-muted mb-2">Transactions</h6>
                                    <h4 class="text-primary mb-0"><?= number_format($summary['count']) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Transactions Table -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title text-primary mb-3">
                    <i class="bi bi-list-ul me-2"></i>Transactions
                    <?php if ($totalTransactions > 0): ?>
                    <span class="badge bg-secondary ms-2"><?= $totalTransactions ?> found</span>
                    <?php endif; ?>
                </h5>
                
                <?php if (count($transactions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Note</th>
                                <th class="text-end">Amount</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $trans): ?>
                            <?php 
                                $typeClass = $trans['type'] == 'income' ? 'text-success' : 'text-danger';
                                $typeIcon = $trans['type'] == 'income' ? 'bi-arrow-down-circle-fill' : 'bi-arrow-up-circle-fill';
                            ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($trans['date'])) ?></td>
                                <td>
                                    <i class="bi <?= $typeIcon ?> <?= $typeClass ?> me-1"></i>
                                    <?= ucfirst(htmlspecialchars($trans['type'])) ?>
                                </td>
                                <td>
                                    <span class="badge rounded-pill bg-light text-dark">
                                        <?= htmlspecialchars($trans['category']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= empty($trans['note']) ? 
                                        '<span class="text-muted fst-italic">No description</span>' : 
                                        htmlspecialchars($trans['note']) 
                                    ?>
                                </td>
                                <td class="<?= $typeClass ?> fw-bold text-end">
                                    $<?= number_format($trans['amount'], 2) ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit_transaction.php?id=<?= $trans['id'] ?>" class="btn btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this transaction?')">
                                            <input type="hidden" name="transaction_id" value="<?= $trans['id'] ?>">
                                            <button type="submit" name="delete_transaction" class="btn btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Transaction pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $currentPage - 1 . $filterUrl ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        if ($startPage > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1' . $filterUrl . '">1</a></li>';
                            if ($startPage > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }
                        
                        for ($i = $startPage; $i <= $endPage; $i++) {
                            echo '<li class="page-item ' . ($i == $currentPage ? 'active' : '') . '">
                                    <a class="page-link" href="?page=' . $i . $filterUrl . '">' . $i . '</a>
                                  </li>';
                        }
                        
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . $filterUrl . '">' . $totalPages . '</a></li>';
                        }
                        ?>
                        
                        <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $currentPage + 1 . $filterUrl ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-search display-4 text-muted mb-3"></i>
                    <h4 class="text-muted">No transactions found</h4>
                    <p class="text-muted">Try changing your filters or add a new transaction</p>
                    <a href="add_transaction.php" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-circle me-1"></i>Add New Transaction
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
