<?php
require_once dirname(dirname(__DIR__)) . '/config/database.php';

class Report {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->connect();
    }

    /**
     * Generate a CSV report of transactions in a given date range
     * 
     * @param int $userId User ID
     * @param string|null $startDate Optional start date (YYYY-MM-DD)
     * @param string|null $endDate Optional end date (YYYY-MM-DD) 
     * @param string|null $reportTitle Optional report title for the filename
     */
    public function generateCSVReport($userId, $startDate = null, $endDate = null, $reportTitle = null) {
        $transaction = new Transaction();
        $transactions = $transaction->getTransactions($userId, $startDate, $endDate);

        // Get total income and expenses
        $totalIncome = 0;
        $totalExpense = 0;
        foreach ($transactions as $trans) {
            if ($trans['type'] == 'income') {
                $totalIncome += $trans['amount'];
            } else {
                $totalExpense += $trans['amount'];
            }
        }

        // Format the filename
        if ($reportTitle) {
            $filename = "expense_report_" . str_replace(' ', '_', strtolower($reportTitle)) . ".csv";
        } else {
            $filename = "expense_report_" . date('Y-m-d') . ".csv";
        }

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        // Open output stream
        $output = fopen('php://output', 'w');

        // Add report title and summary
        fputcsv($output, ['Expense Tracker Report: ' . ($reportTitle ?? date('Y-m-d'))]);
        fputcsv($output, ['Generated on:', date('Y-m-d H:i:s')]);
        fputcsv($output, ['Period:', 
            ($startDate ? date('M d, Y', strtotime($startDate)) : 'All time') . 
            ($endDate ? ' to ' . date('M d, Y', strtotime($endDate)) : '')
        ]);
        fputcsv($output, ['']);
        fputcsv($output, ['Summary:']);
        fputcsv($output, ['Total Income:', '$' . number_format($totalIncome, 2)]);
        fputcsv($output, ['Total Expenses:', '$' . number_format($totalExpense, 2)]);
        fputcsv($output, ['Net Balance:', '$' . number_format($totalIncome - $totalExpense, 2)]);
        fputcsv($output, ['']);
        
        // Add column headers
        fputcsv($output, ['ID', 'Date', 'Type', 'Category', 'Amount', 'Note']);

        // Add transaction data
        foreach ($transactions as $trans) {
            fputcsv($output, [
                $trans['id'], 
                $trans['date'],
                ucfirst($trans['type']), 
                $trans['category'], 
                '$' . number_format($trans['amount'], 2), 
                $trans['note']
            ]);
        }

        fclose($output);
        exit();
    }
    
    /**
     * Generate a PDF report of transactions in a given date range
     * This method uses a simple HTML-to-PDF approach with basic styling
     * 
     * @param int $userId User ID
     * @param string|null $startDate Optional start date (YYYY-MM-DD)
     * @param string|null $endDate Optional end date (YYYY-MM-DD)
     * @param string|null $reportTitle Optional report title for the filename
     */
    public function generatePDFReport($userId, $startDate = null, $endDate = null, $reportTitle = null) {
        $transaction = new Transaction();
        $transactions = $transaction->getTransactions($userId, $startDate, $endDate);
        
        // Get total income and expenses
        $totalIncome = 0;
        $totalExpense = 0;
        foreach ($transactions as $trans) {
            if ($trans['type'] == 'income') {
                $totalIncome += $trans['amount'];
            } else {
                $totalExpense += $trans['amount'];
            }
        }
        
        // Create a title for the report
        $title = $reportTitle ? 'Expense Report: ' . $reportTitle : 'Expense Report';
        
        // Format the filename
        if ($reportTitle) {
            $filename = "expense_report_" . str_replace(' ', '_', strtolower($reportTitle)) . ".pdf";
        } else {
            $filename = "expense_report_" . date('Y-m-d') . ".pdf";
        }
        
        // Start building HTML content for the PDF
        $html = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>' . $title . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                h1 { color: #3498db; }
                .period { color: #555; margin-bottom: 20px; }
                .summary { margin: 20px 0; }
                .summary table { width: 300px; }
                .summary td { padding: 5px 0; }
                .summary td:first-child { font-weight: bold; }
                .summary .balance { font-weight: bold; }
                .positive { color: #27ae60; }
                .negative { color: #e74c3c; }
                .transactions { width: 100%; border-collapse: collapse; margin-top: 20px; }
                .transactions th { background-color: #f2f2f2; text-align: left; padding: 8px; }
                .transactions td { border-bottom: 1px solid #ddd; padding: 8px; }
                .transactions .amount { text-align: right; }
                .transactions .income { color: #27ae60; }
                .transactions .expense { color: #e74c3c; }
                .footer { margin-top: 30px; font-size: 12px; color: #777; text-align: center; }
            </style>
        </head>
        <body>
            <h1>' . $title . '</h1>
            <p class="period">Period: ' . 
                ($startDate ? date('M d, Y', strtotime($startDate)) : 'All time') . 
                ($endDate ? ' to ' . date('M d, Y', strtotime($endDate)) : '') . 
            '</p>
            
            <div class="summary">
                <h2>Financial Summary</h2>
                <table>
                    <tr>
                        <td>Total Income:</td>
                        <td>$' . number_format($totalIncome, 2) . '</td>
                    </tr>
                    <tr>
                        <td>Total Expenses:</td>
                        <td>$' . number_format($totalExpense, 2) . '</td>
                    </tr>
                    <tr class="balance">
                        <td>Net Balance:</td>
                        <td class="' . (($totalIncome - $totalExpense) >= 0 ? 'positive' : 'negative') . '">
                            $' . number_format($totalIncome - $totalExpense, 2) . '
                        </td>
                    </tr>
                </table>
            </div>
            
            <h2>Transaction Details</h2>';
            
        if (count($transactions) > 0) {
            $html .= '<table class="transactions">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Note</th>
                        <th class="amount">Amount</th>
                    </tr>
                </thead>
                <tbody>';
            
            foreach ($transactions as $trans) {
                $typeClass = $trans['type'] == 'income' ? 'income' : 'expense';
                $html .= '<tr>
                    <td>' . date('M d, Y', strtotime($trans['date'])) . '</td>
                    <td>' . htmlspecialchars($trans['category']) . '</td>
                    <td>' . ucfirst($trans['type']) . '</td>
                    <td>' . htmlspecialchars($trans['note'] ?? '') . '</td>
                    <td class="amount ' . $typeClass . '">$' . number_format($trans['amount'], 2) . '</td>
                </tr>';
            }
            
            $html .= '</tbody></table>';
        } else {
            $html .= '<p>No transactions found for this period.</p>';
        }
        
        $html .= '<div class="footer">Generated on ' . date('F j, Y \a\t h:i A') . '</div>
        </body>
        </html>';
        
        // Try to use a library for PDF generation if available, otherwise use browser PDF printing
        if (class_exists('TCPDF')) {
            // Using TCPDF if available
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator('Expense Tracker');
            $pdf->SetAuthor('Expense Tracker');
            $pdf->SetTitle($title);
            $pdf->SetHeaderData('', 0, $title, '');
            $pdf->setHeaderFont(Array('helvetica', '', 10));
            $pdf->setFooterFont(Array('helvetica', '', 8));
            $pdf->SetDefaultMonospacedFont('helvetica');
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 15);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->AddPage();
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output($filename, 'D');
        } else {
            // Fallback to browser rendering if no PDF library is available
            header('Content-Type: text/html');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $html;
        }
        
        exit();
    }

    public function getExpenseCategoryBreakdown($userId, $year, $month) {
        $startDate = date("Y-m-01", strtotime("$year-$month-01"));
        $endDate = date("Y-m-t", strtotime("$year-$month-01"));

        $stmt = $this->conn->prepare("
            SELECT category, SUM(amount) as total_amount
            FROM transactions 
            WHERE user_id = ? AND type = 'expense' AND date BETWEEN ? AND ?
            GROUP BY category
        ");
        $stmt->bind_param("iss", $userId, $startDate, $endDate);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $breakdown = [];
        
        while ($row = $result->fetch_assoc()) {
            $breakdown[$row['category']] = floatval($row['total_amount']);
        }
        
        return $breakdown;
    }

    /**
     * Generate spending trend data for the last N months
     * 
     * @param int $userId User ID
     * @param int $monthCount Number of months to include
     * @return array Trend data with labels, income, and expense arrays
     */
    public function getSpendingTrend($userId, $monthCount = 6) {
        $labels = [];
        $incomeData = [];
        $expenseData = [];
        
        // Get current month and year
        $currentDate = new DateTime();
        
        // Initialize result data structure
        $result = [
            'labels' => [],
            'income' => [],
            'expenses' => []
        ];
        
        // Loop through the last N months
        for ($i = $monthCount - 1; $i >= 0; $i--) {
            // Clone current date to avoid modifying it
            $date = clone $currentDate;
            // Subtract months to get to the target month
            $date->modify("-$i months");
            
            $year = $date->format('Y');
            $month = $date->format('m');
            $monthName = $date->format('M Y');
            
            // Add month name to labels
            $result['labels'][] = $monthName;
            
            // Get monthly balance for this month
            $monthlyBalance = $this->getMonthlyBalance($userId, $year, $month);
            
            // Add data to the result arrays
            $result['income'][] = floatval($monthlyBalance['total_income'] ?? 0);
            $result['expenses'][] = floatval($monthlyBalance['total_expense'] ?? 0);
        }
        
        return $result;
    }
    
    /**
     * Get monthly balance data for a specific month
     * 
     * @param int $userId User ID
     * @param int $year Year
     * @param int $month Month (1-12)
     * @return array Balance data with total_income and total_expense
     */
    public function getMonthlyBalance($userId, $year, $month) {
        $startDate = date("Y-m-01", strtotime("$year-$month-01"));
        $endDate = date("Y-m-t", strtotime("$year-$month-01"));

        $stmt = $this->conn->prepare("
            SELECT 
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
            FROM transactions 
            WHERE user_id = ? AND date BETWEEN ? AND ?
        ");
        $stmt->bind_param("iss", $userId, $startDate, $endDate);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? $result : ['total_income' => 0, 'total_expense' => 0];
    }
    
    public function __destruct() {
        $this->db->close();
    }
}
?>
