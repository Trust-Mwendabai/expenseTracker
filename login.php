<?php
session_start();
require_once 'src/php/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $auth = new Auth();
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($auth->login($username, $password)) {
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Expense Tracker</title>
    <link rel="stylesheet" href="src/css/styles.css">
</head>
<body>
    <div class="container">
        <form method="POST">
            <h2>Login</h2>
            <?php if ($error): ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="submit" value="Login" class="btn">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </form>
    </div>
</body>
</html>
