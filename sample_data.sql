-- Sample data for Expense Tracker
-- To use this file: 
-- 1. Make sure your database exists (expense_tracker)
-- 2. Import this file through phpMyAdmin or run it with mysql command line

-- Create tables if they don't exist
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `note` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample user (password is 'password123')
INSERT INTO `users` (`username`, `email`, `password`) VALUES
('demouser', 'demo@example.com', '$2y$10$Ot0pv6v5CqVVKoQI2J4ZQ.XCPw3fFSGDY7NFvJ/aH75aIkWMbA64K');

-- Get the user ID for the sample user
SET @user_id = LAST_INSERT_ID();

-- Sample income transactions for the past 6 months
-- Current Month (April 2025)
INSERT INTO `transactions` (`user_id`, `type`, `amount`, `category`, `date`, `note`) VALUES
(@user_id, 'income', 3500.00, 'Salary', '2025-04-05', 'Monthly salary'),
(@user_id, 'income', 250.00, 'Freelance', '2025-04-12', 'Website project'),
(@user_id, 'income', 100.00, 'Interest', '2025-04-20', 'Savings account interest');

-- March 2025
INSERT INTO `transactions` (`user_id`, `type`, `amount`, `category`, `date`, `note`) VALUES
(@user_id, 'income', 3500.00, 'Salary', '2025-03-05', 'Monthly salary'),
(@user_id, 'income', 175.00, 'Gifts', '2025-03-18', 'Birthday gift');

-- February 2025
INSERT INTO `transactions` (`user_id`, `type`, `amount`, `category`, `date`, `note`) VALUES
(@user_id, 'income', 3500.00, 'Salary', '2025-02-05', 'Monthly salary'),
(@user_id, 'income', 300.00, 'Freelance', '2025-02-22', 'Logo design project');

-- January 2025
INSERT INTO `transactions` (`user_id`, `type`, `amount`, `category`, `date`, `note`) VALUES
(@user_id, 'income', 3500.00, 'Salary', '2025-01-05', 'Monthly salary'),
(@user_id, 'income', 3000.00, 'Bonus', '2025-01-10', 'Year-end bonus');

-- December 2024
INSERT INTO `transactions` (`user_id`, `type`, `amount`, `category`, `date`, `note`) VALUES
(@user_id, 'income', 3500.00, 'Salary', '2024-12-05', 'Monthly salary'),
(@user_id, 'income', 200.00, 'Gifts', '2024-12-24', 'Holiday gift');

-- November 2024
INSERT INTO `transactions` (`user_id`, `type`, `amount`, `category`, `date`, `note`) VALUES
(@user_id, 'income', 3500.00, 'Salary', '2024-11-05', 'Monthly salary'),
(@user_id, 'income', 400.00, 'Freelance', '2024-11-15', 'Consulting work');

-- Sample expense transactions for the past 6 months
-- Current Month (April 2025)
INSERT INTO `transactions` (`user_id`, `type`, `amount`, `category`, `date`, `note`) VALUES
(@user_id, 'expense', 1200.00, 'Housing', '2025-04-01', 'Monthly rent'),
(@user_id, 'expense', 350.00, 'Utilities', '2025-04-05', 'Electricity, water, and internet'),
(@user_id, 'expense', 500.00, 'Groceries', '2025-04-10', 'Weekly grocery shopping'),
(@user_id, 'expense', 250.00, 'Transportation', '2025-04-15', 'Gas and public transit'),
(@user_id, 'expense', 200.00, 'Entertainment', '2025-04-18', 'Movie and dinner'),
(@user_id, 'expense', 120.00, 'Healthcare', '2025-04-22', 'Pharmacy'),
(@user_id, 'expense', 70.00, 'Dining Out', '2025-04-25', 'Lunch with colleagues'),
(@user_id, 'expense', 150.00, 'Shopping', '2025-04-28', 'New clothes');

-- March 2025
INSERT INTO `transactions` (`user_id`, `type`, `amount`, `category`, `date`, `note`) VALUES
(@user_id, 'expense', 1200.00, 'Housing', '2025-03-01', 'Monthly rent'),
(@user_id, 'expense', 320.00, 'Utilities', '2025-03-05', 'Electricity, water, and internet'),
(@user_id, 'expense', 480.00, 'Groceries', '2025-03-09', 'Weekly grocery shopping'),
(@user_id, 'expense', 240.00, 'Transportation', '2025-03-15', 'Gas and public transit'),
(@user_id, 'expense', 180.00, 'Entertainment', '2025-03-18', 'Concert tickets'),
(@user_id, 'expense', 90.00, 'Healthcare', '2025-03-22', 'Gym membership'),
(@user_id, 'expense', 110.00, 'Dining Out', '2025-03-25', 'Dinner with friends');

-- February 2025
INSERT INTO `transactions` (`user_id`, `type`, `amount`, `category`, `date`, `note`) VALUES
(@user_id, 'expense', 1200.00, 'Housing', '2025-02-01', 'Monthly rent'),
(@user_id, 'expense', 360.00, 'Utilities', '2025-02-05', 'Electricity, water, and internet'),
(@user_id, 'expense', 450.00, 'Groceries', '2025-02-10', 'Weekly grocery shopping'),
(@user_id, 'expense', 200.00, 'Transportation', '2025-02-15', 'Gas and public transit'),
(@user_id, 'expense', 150.00, 'Entertainment', '2025-02-18', 'Streaming services'),
(@user_id, 'expense', 300.00, 'Healthcare', '2025-02-20', 'Doctor visit'),
(@user_id, 'expense', 90.00, 'Dining Out', '2025-02-25', 'Lunch with friends');

-- January 2025
INSERT INTO `transactions` (`user_id`, `type`, `amount`, `category`, `date`, `note`) VALUES
(@user_id, 'expense', 1200.00, 'Housing', '2025-01-01', 'Monthly rent'),
(@user_id, 'expense', 380.00, 'Utilities', '2025-01-05', 'Electricity, water, and internet'),
(@user_id, 'expense', 520.00, 'Groceries', '2025-01-10', 'Weekly grocery shopping'),
(@user_id, 'expense', 230.00, 'Transportation', '2025-01-15', 'Gas and public transit'),
(@user_id, 'expense', 400.00, 'Shopping', '2025-01-20', 'New year shopping'),
(@user_id, 'expense', 100.00, 'Dining Out', '2025-01-25', 'Dinner date');

-- December 2024
INSERT INTO `transactions` (`user_id`, `type`, `amount`, `category`, `date`, `note`) VALUES
(@user_id, 'expense', 1200.00, 'Housing', '2024-12-01', 'Monthly rent'),
(@user_id, 'expense', 350.00, 'Utilities', '2024-12-05', 'Electricity, water, and internet'),
(@user_id, 'expense', 600.00, 'Groceries', '2024-12-10', 'Holiday grocery shopping'),
(@user_id, 'expense', 200.00, 'Transportation', '2024-12-15', 'Gas and public transit'),
(@user_id, 'expense', 500.00, 'Gifts', '2024-12-20', 'Holiday gifts'),
(@user_id, 'expense', 300.00, 'Entertainment', '2024-12-24', 'Holiday celebration'),
(@user_id, 'expense', 150.00, 'Dining Out', '2024-12-28', 'Family dinner');

-- November 2024
INSERT INTO `transactions` (`user_id`, `type`, `amount`, `category`, `date`, `note`) VALUES
(@user_id, 'expense', 1200.00, 'Housing', '2024-11-01', 'Monthly rent'),
(@user_id, 'expense', 310.00, 'Utilities', '2024-11-05', 'Electricity, water, and internet'),
(@user_id, 'expense', 470.00, 'Groceries', '2024-11-10', 'Weekly grocery shopping'),
(@user_id, 'expense', 220.00, 'Transportation', '2024-11-15', 'Gas and public transit'),
(@user_id, 'expense', 180.00, 'Entertainment', '2024-11-20', 'Movie night'),
(@user_id, 'expense', 280.00, 'Shopping', '2024-11-25', 'Black Friday shopping'),
(@user_id, 'expense', 120.00, 'Dining Out', '2024-11-28', 'Thanksgiving dinner');
