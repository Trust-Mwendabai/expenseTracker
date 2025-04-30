<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/config/database.php';

// Initialize database connection
$db = new Database();
$conn = $db->connect();
$userId = $_SESSION['user_id'];
$message = '';
$alertType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        // Add new category
        $name = trim($_POST['category_name']);
        $type = $_POST['category_type'];
        
        if (!empty($name)) {
            $stmt = $conn->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $userId, $name, $type);
            
            if ($stmt->execute()) {
                $message = "Category added successfully!";
                $alertType = "success";
            } else {
                $message = "Error adding category: " . $conn->error;
                $alertType = "danger";
            }
        } else {
            $message = "Category name cannot be empty";
            $alertType = "warning";
        }
    } elseif (isset($_POST['delete_category'])) {
        // Delete category
        $categoryId = $_POST['category_id'];
        
        // First check if category is in use
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ? AND category = (SELECT name FROM categories WHERE id = ? AND user_id = ?)");
        $checkStmt->bind_param("iii", $userId, $categoryId, $userId);
        $checkStmt->execute();
        $result = $checkStmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            $message = "Cannot delete category because it is used in transactions";
            $alertType = "warning";
        } else {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $categoryId, $userId);
            
            if ($stmt->execute()) {
                $message = "Category deleted successfully!";
                $alertType = "success";
            } else {
                $message = "Error deleting category: " . $conn->error;
                $alertType = "danger";
            }
        }
    } elseif (isset($_POST['edit_category'])) {
        // Edit category
        $categoryId = $_POST['category_id'];
        $name = trim($_POST['category_name']);
        $type = $_POST['category_type'];
        
        if (!empty($name)) {
            // Check if transactions use this category and update them too
            $getOldName = $conn->prepare("SELECT name FROM categories WHERE id = ? AND user_id = ?");
            $getOldName->bind_param("ii", $categoryId, $userId);
            $getOldName->execute();
            $oldNameResult = $getOldName->get_result();
            
            if ($oldName = $oldNameResult->fetch_assoc()) {
                // Begin transaction to ensure data consistency
                $conn->begin_transaction();
                
                try {
                    // Update category
                    $updateCat = $conn->prepare("UPDATE categories SET name = ?, type = ? WHERE id = ? AND user_id = ?");
                    $updateCat->bind_param("ssii", $name, $type, $categoryId, $userId);
                    $updateCat->execute();
                    
                    // Update transactions that use this category
                    $updateTrans = $conn->prepare("UPDATE transactions SET category = ? WHERE category = ? AND user_id = ?");
                    $updateTrans->bind_param("ssi", $name, $oldName['name'], $userId);
                    $updateTrans->execute();
                    
                    $conn->commit();
                    $message = "Category updated successfully!";
                    $alertType = "success";
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "Error updating category: " . $e->getMessage();
                    $alertType = "danger";
                }
            } else {
                $message = "Category not found";
                $alertType = "danger";
            }
        } else {
            $message = "Category name cannot be empty";
            $alertType = "warning";
        }
    }
}

// Create categories table if it doesn't exist
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

// Insert default categories if none exist for the user
$checkCats = $conn->prepare("SELECT COUNT(*) as count FROM categories WHERE user_id = ?");
$checkCats->bind_param("i", $userId);
$checkCats->execute();
$catCount = $checkCats->get_result()->fetch_assoc()['count'];

if ($catCount == 0) {
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

// Fetch all categories
$stmt = $conn->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY type, name");
$stmt->bind_param("i", $userId);
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Close connection
$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Categories - Expense Tracker</title>
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
                        <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear me-1"></i>Settings
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item active" href="categories.php"><i class="bi bi-tags me-2"></i>Categories</a></li>
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
                <h1 class="mb-3"><i class="bi bi-tags me-2"></i>Manage Categories</h1>
                <p class="text-muted">Create, edit, and manage your income and expense categories.</p>
            </div>
            <div class="col-md-4 text-md-end d-flex align-items-center justify-content-md-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="bi bi-plus-circle me-1"></i>Add New Category
                </button>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-4" id="categoryTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true">All</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="income-tab" data-bs-toggle="tab" data-bs-target="#income" type="button" role="tab" aria-controls="income" aria-selected="false">Income</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="expense-tab" data-bs-toggle="tab" data-bs-target="#expense" type="button" role="tab" aria-controls="expense" aria-selected="false">Expense</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="categoryTabContent">
                            <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
                                <?php displayCategoryTable($categories, 'all'); ?>
                            </div>
                            <div class="tab-pane fade" id="income" role="tabpanel" aria-labelledby="income-tab">
                                <?php displayCategoryTable($categories, 'income'); ?>
                            </div>
                            <div class="tab-pane fade" id="expense" role="tabpanel" aria-labelledby="expense-tab">
                                <?php displayCategoryTable($categories, 'expense'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel"><i class="bi bi-plus-circle me-2"></i>Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="category_name" name="category_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="category_type" class="form-label">Category Type</label>
                            <select class="form-select" id="category_type" name="category_type" required>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel"><i class="bi bi-pencil me-2"></i>Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post" id="editCategoryForm">
                    <div class="modal-body">
                        <input type="hidden" name="category_id" id="edit_category_id">
                        <div class="mb-3">
                            <label for="edit_category_name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_category_type" class="form-label">Category Type</label>
                            <select class="form-select" id="edit_category_type" name="category_type" required>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_category" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // JavaScript to handle edit category modal
        const editCategoryModal = document.getElementById('editCategoryModal');
        if (editCategoryModal) {
            editCategoryModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const categoryId = button.getAttribute('data-category-id');
                const categoryName = button.getAttribute('data-category-name');
                const categoryType = button.getAttribute('data-category-type');
                
                const modalTitle = this.querySelector('.modal-title');
                const categoryIdInput = document.getElementById('edit_category_id');
                const categoryNameInput = document.getElementById('edit_category_name');
                const categoryTypeSelect = document.getElementById('edit_category_type');
                
                modalTitle.textContent = 'Edit Category: ' + categoryName;
                categoryIdInput.value = categoryId;
                categoryNameInput.value = categoryName;
                categoryTypeSelect.value = categoryType;
            });
        }
    </script>
    
    <?php
    // Function to display category table filtered by type
    function displayCategoryTable($categories, $filter = 'all') {
        echo '<div class="table-responsive">';
        echo '<table class="table table-hover align-middle">';
        echo '<thead class="table-light">';
        echo '<tr>';
        echo '<th>Category Name</th>';
        echo '<th>Type</th>';
        echo '<th class="text-center">Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $hasCategories = false;
        
        foreach ($categories as $category) {
            // Skip if not matching filter
            if ($filter !== 'all' && $category['type'] !== $filter && $category['type'] !== 'both') {
                continue;
            }
            
            $hasCategories = true;
            $typeClass = '';
            $typeLabel = ucfirst($category['type']);
            
            if ($category['type'] === 'income') {
                $typeClass = 'bg-success';
            } elseif ($category['type'] === 'expense') {
                $typeClass = 'bg-danger';
            } else {
                $typeClass = 'bg-info';
            }
            
            echo '<tr>';
            echo '<td>' . htmlspecialchars($category['name']) . '</td>';
            echo '<td><span class="badge ' . $typeClass . '">' . $typeLabel . '</span></td>';
            echo '<td class="text-center">';
            echo '<div class="btn-group btn-group-sm">';
            echo '<button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editCategoryModal" ';
            echo 'data-category-id="' . $category['id'] . '" ';
            echo 'data-category-name="' . htmlspecialchars($category['name']) . '" ';
            echo 'data-category-type="' . $category['type'] . '">';
            echo '<i class="bi bi-pencil"></i></button>';
            
            echo '<form action="" method="post" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this category? This cannot be undone.\')">';
            echo '<input type="hidden" name="category_id" value="' . $category['id'] . '">';
            echo '<button type="submit" name="delete_category" class="btn btn-outline-danger">';
            echo '<i class="bi bi-trash"></i></button>';
            echo '</form>';
            
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }
        
        if (!$hasCategories) {
            echo '<tr><td colspan="3" class="text-center py-4">';
            echo '<i class="bi bi-info-circle me-2"></i>No categories found.';
            echo '</td></tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
    ?>
</body>
</html>
