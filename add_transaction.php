<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
require_once 'src/php/transaction.php';

// Initialize variables
$error = '';
$success = '';
$userId = $_SESSION['user_id'];
$db = new Database();
$conn = $db->connect();

// Default values
$formData = [
    'type' => 'expense',
    'amount' => '',
    'category' => '',
    'date' => date('Y-m-d'),
    'note' => ''
];

// Get categories from database
$incomeCategories = [];
$expenseCategories = [];
$bothCategories = [];

// Check if categories table exists, create if not
$checkTable = $conn->query("SHOW TABLES LIKE 'categories'");
if ($checkTable->num_rows == 0) {
    // Create table
    $createTableQuery = "
    CREATE TABLE IF NOT EXISTS `categories` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `name` varchar(50) NOT NULL,
      `type` enum('income','expense','both') NOT NULL DEFAULT 'both',
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `user_category_unique` (`user_id`, `name`),
      CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->query($createTableQuery);
    
    // Add default categories
    $defaultCategories = [
        ['Salary', 'income'],
        ['Bonus', 'income'],
        ['Freelance', 'income'],
        ['Gifts', 'income'], 
        ['Interest', 'income'],
        ['Other Income', 'income'],
        ['Housing', 'expense'],
        ['Utilities', 'expense'],
        ['Groceries', 'expense'],
        ['Transportation', 'expense'],
        ['Healthcare', 'expense'],
        ['Entertainment', 'expense'],
        ['Dining Out', 'expense'],
        ['Shopping', 'expense'],
        ['Education', 'expense'],
        ['Travel', 'expense'],
        ['Insurance', 'expense'],
        ['Savings', 'both'],
        ['Investments', 'both'],
        ['Other', 'both']
    ];
    
    $insertStmt = $conn->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
    
    foreach ($defaultCategories as $cat) {
        $insertStmt->bind_param("iss", $userId, $cat[0], $cat[1]);
        $insertStmt->execute();
    }
}

// Get user's categories
$stmt = $conn->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY name");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if ($row['type'] == 'income') {
        $incomeCategories[] = $row;
    } else if ($row['type'] == 'expense') {
        $expenseCategories[] = $row;
    } else {
        $bothCategories[] = $row;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transaction = new Transaction();
    $type = $_POST['type'];
    $amount = floatval($_POST['amount']);
    $category = $_POST['category'];
    $date = $_POST['date'];
    $note = $_POST['note'] ?? null;
    
    // Save form data in case of error
    $formData = [
        'type' => $type,
        'amount' => $amount,
        'category' => $category,
        'date' => $date,
        'note' => $note
    ];

    if ($amount <= 0) {
        $error = "Amount must be greater than 0";
    } else if ($transaction->addTransaction($userId, $type, $amount, $category, $date, $note)) {
        $success = "Transaction added successfully!";
        // Reset form for a new entry
        $formData = [
            'type' => $type, // Keep the same type for convenience
            'amount' => '',
            'category' => '',
            'date' => date('Y-m-d'),
            'note' => ''
        ];
    } else {
        $error = "Failed to add transaction. Please try again.";
    }
}

$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Transaction - Expense Tracker</title>
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
                        <a class="nav-link active" href="add_transaction.php">
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
                <h1 class="mb-3"><i class="bi bi-plus-circle me-2"></i>Add New Transaction</h1>
                <p class="text-muted">Record a new income or expense transaction.</p>
            </div>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST" id="transaction-form">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="type" id="type-expense" value="expense" <?= $formData['type'] == 'expense' ? 'checked' : '' ?> required>
                                        <label class="form-check-label" for="type-expense">
                                            <i class="bi bi-arrow-up-circle-fill text-danger me-1"></i>Expense
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="type" id="type-income" value="income" <?= $formData['type'] == 'income' ? 'checked' : '' ?> required>
                                        <label class="form-check-label" for="type-income">
                                            <i class="bi bi-arrow-down-circle-fill text-success me-1"></i>Income
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="amount" name="amount" min="0.01" step="0.01" value="<?= htmlspecialchars($formData['amount']) ?>" placeholder="0.00" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select a category</option>
                                    
                                    <!-- Income categories -->
                                    <optgroup label="Income Categories" id="income-categories" <?= $formData['type'] == 'expense' ? 'class="d-none"' : '' ?>>
                                        <?php foreach($incomeCategories as $category): ?>
                                        <option value="<?= htmlspecialchars($category['name']) ?>" <?= $formData['category'] == $category['name'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                        
                                        <?php foreach($bothCategories as $category): ?>
                                        <option value="<?= htmlspecialchars($category['name']) ?>" <?= $formData['category'] == $category['name'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    
                                    <!-- Expense categories -->
                                    <optgroup label="Expense Categories" id="expense-categories" <?= $formData['type'] == 'income' ? 'class="d-none"' : '' ?>>
                                        <?php foreach($expenseCategories as $category): ?>
                                        <option value="<?= htmlspecialchars($category['name']) ?>" <?= $formData['category'] == $category['name'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                        
                                        <?php foreach($bothCategories as $category): ?>
                                        <option value="<?= htmlspecialchars($category['name']) ?>" <?= $formData['category'] == $category['name'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                                <div class="form-text text-end">
                                    <a href="categories.php" class="text-decoration-none">Manage Categories</a>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($formData['date']) ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="note" class="form-label">Note (Optional)</label>
                                <textarea class="form-control" id="note" name="note" rows="3" placeholder="Add additional details about this transaction"><?= htmlspecialchars($formData['note']) ?></textarea>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php" class="btn btn-outline-secondary me-md-2">
                                    <i class="bi bi-x-circle me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-1"></i>Save Transaction
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title text-primary"><i class="bi bi-lightbulb me-2"></i>Tips</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item bg-transparent">Use consistent categories for better reporting</li>
                            <li class="list-group-item bg-transparent">Add detailed notes for easier tracking</li>
                            <li class="list-group-item bg-transparent">Record transactions regularly for accurate insights</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title text-primary"><i class="bi bi-clock-history me-2"></i>Quick Actions</h5>
                        <div class="d-grid gap-2">
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="bi bi-speedometer2 me-1"></i>Go to Dashboard
                            </a>
                            <a href="reports.php" class="btn btn-outline-info">
                                <i class="bi bi-graph-up me-1"></i>View Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle category options based on transaction type
        document.addEventListener('DOMContentLoaded', function() {
            const typeExpense = document.getElementById('type-expense');
            const typeIncome = document.getElementById('type-income');
            const incomeCategories = document.getElementById('income-categories');
            const expenseCategories = document.getElementById('expense-categories');
            
            function updateCategoryVisibility() {
                if (typeExpense.checked) {
                    incomeCategories.classList.add('d-none');
                    expenseCategories.classList.remove('d-none');
                } else {
                    incomeCategories.classList.remove('d-none');
                    expenseCategories.classList.add('d-none');
                }
            }
            
            // Initial update
            updateCategoryVisibility();
            
            // Update on change
            typeExpense.addEventListener('change', updateCategoryVisibility);
            typeIncome.addEventListener('change', updateCategoryVisibility);
        });
    </script>
</body>
</html>
