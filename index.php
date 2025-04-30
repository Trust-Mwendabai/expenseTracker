<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/src/php/transaction.php';
require_once __DIR__ . '/src/php/report.php';

$transaction = new Transaction();
$report = new Report();

$userId = $_SESSION['user_id'];

// Handle date filter
$filterYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$filterMonth = isset($_GET['month']) ? intval($_GET['month']) : date('m');

// Get available years for filter
$years = $transaction->getTransactionYears($userId);
if (empty($years)) {
    $years = [date('Y')]; // Default to current year if no data
}

// Get monthly summary
$monthlyBalanceData = $transaction->getMonthlyBalance($userId, $filterYear, $filterMonth);
$totalIncome = $monthlyBalanceData['total_income'];
$totalExpenses = $monthlyBalanceData['total_expense'];
$netBalance = $totalIncome - $totalExpenses;

// Get expense breakdown for pie chart
$expenseBreakdown = $report->getExpenseCategoryBreakdown($userId, $filterYear, $filterMonth);
$expenseBreakdown = array_map('floatval', $expenseBreakdown);

// Get spending trends for the last 6 months
$trendData = $report->getSpendingTrend($userId, 6);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Expense Tracker Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css for animations -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="src/css/styles.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
    <!-- Enhanced Header Section -->
    <header class="dashboard-header animate__animated animate__fadeIn">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="bi bi-wallet2 me-2"></i>Expense Tracker</h1>
                    <p>Track, analyze, and optimize your finances</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></p>
                    <small class="text-white-50"><?php echo date('l, F j, Y'); ?></small>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top py-2 animate__animated animate__fadeInDown">
        <div class="container">
            <a class="navbar-brand d-lg-none" href="index.php">
                <i class="bi bi-wallet2 me-2"></i>ExpenseTracker
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php">
                            <i class="bi bi-list-ul me-1"></i>Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-file-earmark-bar-graph me-1"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">
                            <i class="bi bi-tags me-1"></i>Categories
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link btn btn-sm btn-primary text-white px-3 mx-2" href="add_transaction.php">
                            <i class="bi bi-plus-circle me-1"></i>Add Transaction
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Stats Summary -->
    <div class="dashboard-stats mb-4 animate__animated animate__fadeInUp">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="stat-card income">
                        <div class="stat-icon">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <div class="stat-title">Monthly Income</div>
                        <div class="stat-value"><?php echo '$' . number_format($totalIncome, 2); ?></div>
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="stat-card expense">
                        <div class="stat-icon">
                            <i class="bi bi-graph-down-arrow"></i>
                        </div>
                        <div class="stat-title">Monthly Expenses</div>
                        <div class="stat-value"><?php echo '$' . number_format($totalExpenses, 2); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card balance">
                        <div class="stat-icon">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div class="stat-title">Net Balance</div>
                        <div class="stat-value"><?php echo '$' . number_format($netBalance, 2); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container animate__animated animate__fadeIn">
        <!-- Date Filter Bar -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <form class="row g-3 align-items-center" method="get">
                    <div class="col-md-4">
                        <h5 class="card-title mb-0"><i class="bi bi-calendar3 me-2"></i>Date Filter</h5>
                    </div>
                    <div class="col-md-3">
                        <label for="month" class="form-label">Month</label>
                        <select class="form-select" id="month" name="month">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $filterMonth == $m ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="year" class="form-label">Year</label>
                        <select class="form-select" id="year" name="year">
                            <?php foreach ($years as $year): ?>
                                <option value="<?= $year ?>" <?= $filterYear == $year ? 'selected' : '' ?>>
                                    <?= $year ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Dashboard Main Content with Tabs -->
        <div class="card border-0 shadow-sm rounded-custom mb-4">
            <div class="card-body">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-4" id="dashboardTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true">
                            <i class="bi bi-speedometer2 me-2"></i>Overview
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions" type="button" role="tab" aria-controls="transactions" aria-selected="false">
                            <i class="bi bi-list-ul me-2"></i>Transactions
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="trends-tab" data-bs-toggle="tab" data-bs-target="#trends" type="button" role="tab" aria-controls="trends" aria-selected="false">
                            <i class="bi bi-graph-up me-2"></i>Trends & Analytics
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="budget-tab" data-bs-toggle="tab" data-bs-target="#budget" type="button" role="tab" aria-controls="budget" aria-selected="false">
                            <i class="bi bi-piggy-bank me-2"></i>Budget
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="dashboardTabsContent">
                    <!-- Overview Tab -->
                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                        <!-- Financial Overview -->
                        <div class="row mb-4">
                            <div class="col-md-4 mb-4 mb-md-0">
                                <div class="card h-100 border-0 shadow-sm rounded-custom">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary">
                                            <i class="bi bi-cash-stack me-2"></i>Monthly Summary
                                        </h5>
                                        <div class="financial-breakdown mt-4">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <span>Total Income:</span>
                                                <span class="fs-5 text-success fw-bold">$<?php echo number_format($totalIncome, 2); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <span>Total Expenses:</span>
                                                <span class="fs-5 text-danger fw-bold">$<?php echo number_format($totalExpenses, 2); ?></span>
                                            </div>
                                            <hr class="my-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fs-5 fw-bold">Net Balance:</span>
                                                <span class="fs-4 <?php echo $netBalance >= 0 ? 'text-success' : 'text-danger'; ?> fw-bold">
                                                    $<?php echo number_format($netBalance, 2); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="card h-100 border-0 shadow-sm rounded-custom">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary">
                                            <i class="bi bi-pie-chart me-2"></i>Expense Breakdown
                                        </h5>
                                        <div class="chart-container" style="position: relative; height:300px; width:100%">
                                            <canvas id="expenseChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-0 shadow-sm rounded-custom p-2">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary mb-4">
                                            <i class="bi bi-lightning me-2"></i>Quick Actions
                                        </h5>
                                        <div class="row text-center">
                                            <div class="col-md-3 col-6 mb-3">
                                                <a href="add_transaction.php?type=expense" class="btn btn-light w-100 py-3 rounded-custom">
                                                    <i class="bi bi-dash-circle text-danger fs-3 d-block mb-2"></i>
                                                    Add Expense
                                                </a>
                                            </div>
                                            <div class="col-md-3 col-6 mb-3">
                                                <a href="add_transaction.php?type=income" class="btn btn-light w-100 py-3 rounded-custom">
                                                    <i class="bi bi-plus-circle text-success fs-3 d-block mb-2"></i>
                                                    Add Income
                                                </a>
                                            </div>
                                            <div class="col-md-3 col-6 mb-3">
                                                <a href="reports.php" class="btn btn-light w-100 py-3 rounded-custom">
                                                    <i class="bi bi-file-earmark-bar-graph text-primary fs-3 d-block mb-2"></i>
                                                    Generate Report
                                                </a>
                                            </div>
                                            <div class="col-md-3 col-6 mb-3">
                                                <a href="categories.php" class="btn btn-light w-100 py-3 rounded-custom">
                                                    <i class="bi bi-tags text-warning fs-3 d-block mb-2"></i>
                                                    Manage Categories
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Spending Trend Chart -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card border-0 shadow-sm rounded-custom">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary">
                                            <i class="bi bi-graph-up me-2"></i>6-Month Spending Trend
                                        </h5>
                                        <div class="chart-container" style="position: relative; height:250px; width:100%">
                                            <canvas id="trendChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transactions Tab -->
                    <div class="tab-pane fade" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title text-primary mb-0">
                                <i class="bi bi-clock-history me-2"></i>Recent Transactions
                            </h5>
                            <a href="transactions.php" class="btn btn-sm btn-outline-primary">
                                View All <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Type</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $recentTransactions = $transaction->getRecentTransactions($userId, 10);
                                    if (!empty($recentTransactions)) {
                                        foreach ($recentTransactions as $trans) {
                                            $typeClass = $trans['type'] == 'income' ? 'success' : 'danger';
                                            echo "<tr>";
                                            echo "<td>{$trans['date']}</td>";
                                            echo "<td>{$trans['description']}</td>";
                                            echo "<td>{$trans['category']}</td>";
                                            echo "<td class='fw-bold text-{$typeClass}'>$" . number_format($trans['amount'], 2) . "</td>";
                                            echo "<td><span class='badge bg-{$typeClass}'>" . ucfirst($trans['type']) . "</span></td>";
                                            echo "<td class='text-center'>
                                                <div class='btn-group btn-group-sm'>
                                                <a href='edit_transaction.php?id={$trans['id']}' class='btn btn-outline-secondary'><i class='bi bi-pencil'></i></a>
                                                <a href='delete_transaction.php?id={$trans['id']}' class='btn btn-outline-danger' onclick='return confirm(\"Are you sure you want to delete this transaction?\")' data-id='{$trans['id']}'><i class='bi bi-trash'></i></a>
                                                </div>
                                            </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center'>No transactions found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <a href="transactions.php" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>View All Transactions
                            </a>
                            <a href="add_transaction.php" class="btn btn-success ms-2">
                                <i class="bi bi-plus-circle me-2"></i>Add New Transaction
                            </a>
                        </div>
                    </div>

                    <!-- Trends & Analytics Tab -->
                    <div class="tab-pane fade" id="trends" role="tabpanel" aria-labelledby="trends-tab">
                        <div class="row mb-4">
                            <div class="col-md-6 mb-4">
                                <div class="card border-0 shadow-sm rounded-custom h-100">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary">
                                            <i class="bi bi-calendar-month me-2"></i>Monthly Comparison
                                        </h5>
                                        <div class="chart-container" style="position: relative; height:300px; width:100%">
                                            <canvas id="monthlyComparisonChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card border-0 shadow-sm rounded-custom h-100">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary">
                                            <i class="bi bi-category me-2"></i>Top Spending Categories
                                        </h5>
                                        <div class="chart-container" style="position: relative; height:300px; width:100%">
                                            <canvas id="topCategoriesChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="card border-0 shadow-sm rounded-custom">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary">
                                            <i class="bi bi-graph-up me-2"></i>Yearly Overview
                                        </h5>
                                        <div class="chart-container" style="position: relative; height:300px; width:100%">
                                            <canvas id="yearlyOverviewChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Budget Tab -->
                    <div class="tab-pane fade" id="budget" role="tabpanel" aria-labelledby="budget-tab">
                        <div class="text-center py-5 border-dashed mb-4">
                            <i class="bi bi-piggy-bank text-muted" style="font-size: 3rem;"></i>
                            <h4 class="mt-3">Budget Management Coming Soon</h4>
                            <p class="text-muted">We're working on new budget tracking and planning features to help you manage your finances better.</p>
                            <a href="#" class="btn btn-primary mt-2">
                                <i class="bi bi-bell me-2"></i>Get Notified When Available
                            </a>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card border-0 shadow-sm rounded-custom">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary">
                                            <i class="bi bi-question-circle me-2"></i>Savings Tips
                                        </h5>
                                        <ul class="list-group list-group-flush mt-3">
                                            <li class="list-group-item bg-transparent"><i class="bi bi-check-circle-fill text-success me-2"></i>Create a monthly budget and track your spending</li>
                                            <li class="list-group-item bg-transparent"><i class="bi bi-check-circle-fill text-success me-2"></i>Set up automatic transfers to your savings account</li>
                                            <li class="list-group-item bg-transparent"><i class="bi bi-check-circle-fill text-success me-2"></i>Review your subscriptions and cancel unused ones</li>
                                            <li class="list-group-item bg-transparent"><i class="bi bi-check-circle-fill text-success me-2"></i>Look for ways to reduce your biggest expenses</li>
                                            <li class="list-group-item bg-transparent"><i class="bi bi-check-circle-fill text-success me-2"></i>Use the 50/30/20 rule: 50% needs, 30% wants, 20% savings</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card border-0 shadow-sm rounded-custom">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary">
                                            <i class="bi bi-lightbulb me-2"></i>Financial Insights
                                        </h5>
                                        <div class="alert alert-info mt-3 mb-4" role="alert">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Based on your spending pattern, your top expense category is <strong>Food & Dining</strong>.
                                        </div>
                                        <p><strong>Spending Pattern Analysis:</strong></p>
                                        <p class="text-muted">Review your top spending categories to identify areas where you might be able to cut back. Consider setting specific budget goals for each category to help manage your finances better.</p>
                                        <a href="reports.php" class="btn btn-primary mt-2">
                                            <i class="bi bi-file-earmark-bar-graph me-2"></i>View Detailed Reports
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Expense Breakdown Chart
        const ctxPie = document.getElementById('expenseChart').getContext('2d');
        
        // Handle empty data case
        const expenseBreakdown = <?php echo json_encode(array_keys($expenseBreakdown)); ?>;
        const expenseAmounts = <?php echo json_encode(array_values($expenseBreakdown)); ?>;
        
        const expenseData = {
            labels: expenseBreakdown.length ? expenseBreakdown : ['No expense data'],
            datasets: [{
                label: 'Expense Categories',
                data: expenseBreakdown.length ? expenseAmounts : [1],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(199, 199, 199, 0.7)'
                ],
                borderWidth: 1,
                borderColor: '#fff'
            }]
        };

        new Chart(ctxPie, {
            type: 'doughnut',
            data: expenseData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 15,
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: $${value.toFixed(2)} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });

        // Spending Trend Chart
        const ctxLine = document.getElementById('trendChart').getContext('2d');

        // Sample data structure for trend chart
        const trendData = <?php echo json_encode($trendData); ?>;
        
        // Create gradient for area under the line
        const gradient = ctxLine.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(54, 162, 235, 0.5)');
        gradient.addColorStop(1, 'rgba(54, 162, 235, 0)');

        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [
                    {
                        label: 'Income',
                        data: trendData.income,
                        borderColor: 'rgba(40, 167, 69, 1)',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Expenses',
                        data: trendData.expenses,
                        borderColor: 'rgba(220, 53, 69, 1)',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        pointBackgroundColor: 'rgba(220, 53, 69, 1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.raw);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
    
    <script>
        // Additional charts for tab interface
        // These are placeholder charts that will be populated with real data in future updates

        // Monthly Comparison Chart
        const monthlyComparisonCtx = document.getElementById('monthlyComparisonChart')?.getContext('2d');
        if (monthlyComparisonCtx) {
            // Sample data - this would be replaced with actual data from PHP
            const monthlyComparisonData = {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [
                    {
                        label: 'This Year',
                        data: [1500, 1800, 1200, 2000, 1600, 2200],
                        borderColor: 'rgba(82, 113, 255, 1)',
                        backgroundColor: 'rgba(82, 113, 255, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Last Year',
                        data: [1300, 1600, 1100, 1800, 1400, 1900],
                        borderColor: 'rgba(160, 160, 160, 1)',
                        backgroundColor: 'rgba(160, 160, 160, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        borderDash: [5, 5]
                    }
                ]
            };

            new Chart(monthlyComparisonCtx, {
                type: 'line',
                data: monthlyComparisonData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': $' + context.raw;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Top Spending Categories Chart
        const topCategoriesCtx = document.getElementById('topCategoriesChart')?.getContext('2d');
        if (topCategoriesCtx) {
            // Sample data - this would be replaced with actual data from PHP
            const topCategoriesData = {
                labels: ['Food & Dining', 'Housing', 'Transportation', 'Entertainment', 'Shopping'],
                datasets: [{
                    label: 'Amount Spent',
                    data: [1200, 1800, 600, 400, 700],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            };

            new Chart(topCategoriesCtx, {
                type: 'bar',
                data: topCategoriesData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '$' + context.raw;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Yearly Overview Chart
        const yearlyOverviewCtx = document.getElementById('yearlyOverviewChart')?.getContext('2d');
        if (yearlyOverviewCtx) {
            // Sample data - this would be replaced with actual data from PHP
            const yearlyOverviewData = {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [
                    {
                        type: 'line',
                        label: 'Net Balance',
                        data: [300, 400, 200, 500, 600, 300, 250, 450, 500, 650, 700, 800],
                        borderColor: 'rgba(82, 113, 255, 1)',
                        backgroundColor: 'rgba(82, 113, 255, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: false,
                        yAxisID: 'y1'
                    },
                    {
                        type: 'bar',
                        label: 'Income',
                        data: [1800, 2000, 1700, 2200, 2400, 2000, 1850, 2100, 2300, 2500, 2600, 2800],
                        backgroundColor: 'rgba(80, 200, 120, 0.7)',
                        borderColor: 'rgba(80, 200, 120, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        type: 'bar',
                        label: 'Expenses',
                        data: [1500, 1600, 1500, 1700, 1800, 1700, 1600, 1650, 1800, 1850, 1900, 2000],
                        backgroundColor: 'rgba(255, 107, 107, 0.7)',
                        borderColor: 'rgba(255, 107, 107, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    }
                ]
            };

            new Chart(yearlyOverviewCtx, {
                type: 'bar',
                data: yearlyOverviewData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': $' + context.raw;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Income & Expenses'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Net Balance'
                            },
                            grid: {
                                drawOnChartArea: false
                            },
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-center gap-3">
            <a href="add_transaction.php" class="btn btn-primary">Add Transaction</a>
            <a href="transactions.php" class="btn btn-secondary">View Transactions</a>
            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?php echo date('Y'); ?> Expense Tracker. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end footer-links">
                    <a href="privacy.php">Privacy Policy</a>
                    <a href="terms.php">Terms of Service</a>
                    <a href="contact.php">Contact Us</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
