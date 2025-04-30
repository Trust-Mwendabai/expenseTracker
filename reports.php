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

// Get available years for the filter
$years = $transaction->getTransactionYears($userId);
if (empty($years)) {
    $years = [date('Y')]; // Default to current year if no data
}

// Handle report generation
if (isset($_GET['generate'])) {
    $reportType = $_GET['report_type'] ?? 'monthly';
    $format = $_GET['format'] ?? 'csv';
    $year = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? date('m');
    $startDate = null;
    $endDate = null;
    
    // Set appropriate date range based on report type
    if ($reportType == 'monthly') {
        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        $reportTitle = date('F Y', strtotime($startDate));
    } elseif ($reportType == 'yearly') {
        $startDate = "$year-01-01";
        $endDate = "$year-12-31";
        $reportTitle = $year;
    } elseif ($reportType == 'custom' && isset($_GET['start_date']) && isset($_GET['end_date'])) {
        $startDate = $_GET['start_date'];
        $endDate = $_GET['end_date'];
        $reportTitle = date('M j, Y', strtotime($startDate)) . ' to ' . date('M j, Y', strtotime($endDate));
    }
    
    // Generate the report based on the requested format
    if ($format == 'csv') {
        $report->generateCSVReport($userId, $startDate, $endDate, $reportTitle);
    } elseif ($format == 'pdf') {
        $report->generatePDFReport($userId, $startDate, $endDate, $reportTitle);
    }
}

// Get summary data for the current month (for displaying on the page)
$currentYear = date('Y');
$currentMonth = date('m');
$monthlyBalance = $transaction->getMonthlyBalance($userId, $currentYear, $currentMonth);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Financial Reports - Expense Tracker</title>
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
                        <a class="nav-link active" href="reports.php">
                            <i class="bi bi-file-earmark-bar-graph me-1"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear me-1"></i>Settings
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
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
                <h1 class="mb-3"><i class="bi bi-file-earmark-bar-graph me-2"></i>Financial Reports</h1>
                <p class="text-muted">Generate, download, and analyze your financial reports.</p>
            </div>
        </div>
        
        <!-- Report Generation Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title text-primary mb-4">Generate Report</h5>
                        
                        <form action="" method="get" class="row g-3">
                            <input type="hidden" name="generate" value="1">
                            
                            <div class="col-md-3 mb-3">
                                <label for="report_type" class="form-label">Report Type</label>
                                <select class="form-select" id="report_type" name="report_type" required>
                                    <option value="monthly">Monthly Report</option>
                                    <option value="yearly">Annual Report</option>
                                    <option value="custom">Custom Date Range</option>
                                </select>
                            </div>
                            
                            <!-- Monthly options -->
                            <div class="col-md-3 mb-3 monthly-option">
                                <label for="month" class="form-label">Month</label>
                                <select class="form-select" id="month" name="month">
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= sprintf('%02d', $m) ?>" <?= date('m') == $m ? 'selected' : '' ?>>
                                            <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3 mb-3 monthly-option yearly-option">
                                <label for="year" class="form-label">Year</label>
                                <select class="form-select" id="year" name="year">
                                    <?php foreach ($years as $year): ?>
                                        <option value="<?= $year ?>"><?= $year ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Custom date range options -->
                            <div class="col-md-3 mb-3 custom-option d-none">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-select" id="start_date" name="start_date">
                            </div>
                            
                            <div class="col-md-3 mb-3 custom-option d-none">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-select" id="end_date" name="end_date">
                            </div>
                            
                            <!-- Format options -->
                            <div class="col-md-3 mb-3">
                                <label for="format" class="form-label">Format</label>
                                <select class="form-select" id="format" name="format" required>
                                    <option value="csv">CSV File</option>
                                    <option value="pdf">PDF Document</option>
                                </select>
                            </div>
                            
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-download me-2"></i>Generate & Download Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Reports Section -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title text-primary">Quick Reports</h5>
                        <p class="text-muted">Download pre-configured reports with a single click.</p>
                        
                        <div class="list-group mt-4">
                            <a href="?generate=1&report_type=monthly&month=<?= date('m') ?>&year=<?= date('Y') ?>&format=csv" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-calendar-month me-2 text-primary"></i>
                                    <span>Current Month Report (CSV)</span>
                                </div>
                                <i class="bi bi-file-earmark-spreadsheet text-muted"></i>
                            </a>
                            <a href="?generate=1&report_type=monthly&month=<?= date('m') ?>&year=<?= date('Y') ?>&format=pdf" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-calendar-month me-2 text-primary"></i>
                                    <span>Current Month Report (PDF)</span>
                                </div>
                                <i class="bi bi-file-earmark-pdf text-muted"></i>
                            </a>
                            <a href="?generate=1&report_type=yearly&year=<?= date('Y') ?>&format=csv" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-calendar-event me-2 text-primary"></i>
                                    <span>Current Year Report (CSV)</span>
                                </div>
                                <i class="bi bi-file-earmark-spreadsheet text-muted"></i>
                            </a>
                            <a href="?generate=1&report_type=yearly&year=<?= date('Y') ?>&format=pdf" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-calendar-event me-2 text-primary"></i>
                                    <span>Current Year Report (PDF)</span>
                                </div>
                                <i class="bi bi-file-earmark-pdf text-muted"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title text-primary">Current Month Summary</h5>
                        <div class="financial-summary mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Income:</span>
                                <span class="badge bg-success px-3 py-2 fs-6">$<?= number_format($monthlyBalance['total_income'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Expenses:</span>
                                <span class="badge bg-danger px-3 py-2 fs-6">$<?= number_format($monthlyBalance['total_expense'], 2) ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Net Balance:</span>
                                <?php
                                $netBalance = $monthlyBalance['total_income'] - $monthlyBalance['total_expense'];
                                $balanceClass = $netBalance >= 0 ? 'bg-success' : 'bg-danger';
                                ?>
                                <span class="badge <?= $balanceClass ?> px-3 py-2 fs-5">$<?= number_format($netBalance, 2) ?></span>
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
        // JavaScript to toggle form fields based on report type
        document.addEventListener('DOMContentLoaded', function() {
            const reportTypeSelect = document.getElementById('report_type');
            
            reportTypeSelect.addEventListener('change', function() {
                const monthlyOptions = document.querySelectorAll('.monthly-option');
                const yearlyOptions = document.querySelectorAll('.yearly-option');
                const customOptions = document.querySelectorAll('.custom-option');
                
                // Hide all options first
                monthlyOptions.forEach(option => option.classList.add('d-none'));
                customOptions.forEach(option => option.classList.add('d-none'));
                
                // Show relevant options based on selection
                if (this.value === 'monthly') {
                    monthlyOptions.forEach(option => option.classList.remove('d-none'));
                } else if (this.value === 'yearly') {
                    yearlyOptions.forEach(option => option.classList.remove('d-none'));
                } else if (this.value === 'custom') {
                    customOptions.forEach(option => option.classList.remove('d-none'));
                }
            });
        });
    </script>
</body>
</html>
