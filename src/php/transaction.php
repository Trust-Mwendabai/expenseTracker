<?php
require_once dirname(dirname(__DIR__)) . '/config/database.php';

class Transaction {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->connect();
    }

    public function addTransaction($userId, $type, $amount, $category, $date, $note = null) {
        $stmt = $this->conn->prepare("INSERT INTO transactions (user_id, type, amount, category, date, note) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdsss", $userId, $type, $amount, $category, $date, $note);
        
        return $stmt->execute();
    }

    public function getTransactions($userId, $startDate = null, $endDate = null, $category = null) {
        $query = "SELECT * FROM transactions WHERE user_id = ?";
        $paramTypes = "i";
        $params = [$userId];

        if ($startDate) {
            $query .= " AND date >= ?";
            $paramTypes .= "s";
            $params[] = $startDate;
        }

        if ($endDate) {
            $query .= " AND date <= ?";
            $paramTypes .= "s";
            $params[] = $endDate;
        }

        if ($category) {
            $query .= " AND category = ?";
            $paramTypes .= "s";
            $params[] = $category;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($paramTypes, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRecentTransactions($userId, $limit = 5) {
        $stmt = $this->conn->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC LIMIT ?");
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

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

    /**
     * Get all years for which a user has transactions
     * Used for the year filter dropdown
     * 
     * @param int $userId The user ID
     * @return array List of years in descending order
     */
    public function getTransactionYears($userId) {
        $stmt = $this->conn->prepare("SELECT DISTINCT YEAR(date) as year FROM transactions WHERE user_id = ? ORDER BY year DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $years = [];
        
        while ($row = $result->fetch_assoc()) {
            $years[] = $row['year'];
        }
        
        return $years;
    }
    
    /**
     * Delete a transaction
     * 
     * @param int $transactionId Transaction ID
     * @param int $userId User ID (for security check)
     * @return bool Success status
     */
    public function deleteTransaction($transactionId, $userId) {
        $stmt = $this->conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $transactionId, $userId);
        
        return $stmt->execute();
    }

    /**
     * Get filtered transactions with pagination
     * 
     * @param int $userId User ID
     * @param string $type Optional filter by type (income or expense)
     * @param string $category Optional filter by category
     * @param string $startDate Optional start date (YYYY-MM-DD)
     * @param string $endDate Optional end date (YYYY-MM-DD)
     * @param string $sort Sorting option (date_desc, date_asc, amount_desc, amount_asc)
     * @param int $limit Number of records per page
     * @param int $offset Offset for pagination
     * @return array List of transactions matching the filters
     */
    public function getFilteredTransactions($userId, $type = '', $category = '', $startDate = '', $endDate = '', $sort = 'date_desc', $limit = 20, $offset = 0) {
        list($whereClause, $params, $paramTypes) = $this->buildFilterQuery($userId, $type, $category, $startDate, $endDate);
        
        // Add sorting
        $orderBy = '';
        switch ($sort) {
            case 'date_asc':
                $orderBy = 'ORDER BY date ASC, id ASC';
                break;
            case 'amount_desc':
                $orderBy = 'ORDER BY amount DESC, date DESC';
                break;
            case 'amount_asc':
                $orderBy = 'ORDER BY amount ASC, date DESC';
                break;
            case 'date_desc':
            default:
                $orderBy = 'ORDER BY date DESC, id DESC';
                break;
        }
        
        $query = "SELECT * FROM transactions $whereClause $orderBy LIMIT ?, ?";
        $paramTypes .= 'ii';
        $params[] = $offset;
        $params[] = $limit;
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($paramTypes, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get the total count of transactions matching the filters
     * 
     * @param int $userId User ID
     * @param string $type Optional filter by type
     * @param string $category Optional filter by category
     * @param string $startDate Optional start date
     * @param string $endDate Optional end date
     * @return int Total number of transactions matching the filters
     */
    public function getFilteredTransactionCount($userId, $type = '', $category = '', $startDate = '', $endDate = '') {
        list($whereClause, $params, $paramTypes) = $this->buildFilterQuery($userId, $type, $category, $startDate, $endDate);
        
        $query = "SELECT COUNT(*) as count FROM transactions $whereClause";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($paramTypes, ...$params);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }
    
    /**
     * Get a summary of filtered transactions (income and expense totals)
     * 
     * @param int $userId User ID
     * @param string $type Optional filter by type
     * @param string $category Optional filter by category
     * @param string $startDate Optional start date
     * @param string $endDate Optional end date
     * @return array|null Summary data with total_income and total_expense
     */
    public function getFilteredSummary($userId, $type = '', $category = '', $startDate = '', $endDate = '') {
        list($whereClause, $params, $paramTypes) = $this->buildFilterQuery($userId, $type, $category, $startDate, $endDate);
        
        $query = "SELECT 
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
            FROM transactions $whereClause";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($paramTypes, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Build the WHERE clause and parameters for filtered queries
     * 
     * @param int $userId User ID
     * @param string $type Optional filter by type
     * @param string $category Optional filter by category
     * @param string $startDate Optional start date
     * @param string $endDate Optional end date
     * @return array Array containing [whereClause, params, paramTypes]
     */
    private function buildFilterQuery($userId, $type = '', $category = '', $startDate = '', $endDate = '') {
        $whereConditions = ['user_id = ?'];
        $params = [$userId];
        $paramTypes = 'i';
        
        if ($type) {
            $whereConditions[] = 'type = ?';
            $params[] = $type;
            $paramTypes .= 's';
        }
        
        if ($category) {
            $whereConditions[] = 'category = ?';
            $params[] = $category;
            $paramTypes .= 's';
        }
        
        if ($startDate) {
            $whereConditions[] = 'date >= ?';
            $params[] = $startDate;
            $paramTypes .= 's';
        }
        
        if ($endDate) {
            $whereConditions[] = 'date <= ?';
            $params[] = $endDate;
            $paramTypes .= 's';
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        return [$whereClause, $params, $paramTypes];
    }
    
    public function __destruct() {
        $this->db->close();
    }
}
?>
