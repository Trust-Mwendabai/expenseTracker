<?php
require_once 'src/php/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $auth = new Auth();
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        if ($auth->register($username, $email, $password)) {
            header("Location: login.php");
            exit();
        } else {
            $error = "Registration failed. Username or email might already exist.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Expense Tracker</title>
    <link rel="stylesheet" href="src/css/styles.css">
</head>
<body>
    <div class="container">
        <form method="POST">
            <h2>Register</h2>
            <?php if ($error): ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <input type="submit" value="Register" class="btn">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </form>
    </div>
</body>
</html>
