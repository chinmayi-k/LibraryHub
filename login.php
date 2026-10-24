
<?php
session_start();
require_once "db.php";

$error = "";

if (isset($_GET["registered"])) {
    $success = "Registration successful! You can now log in.";
} else {
    $success = "";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, full_name, email, password, role
         FROM users
         WHERE email = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user["password"])) {
        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["full_name"] = $user["full_name"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["role"] = $user["role"];

        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Incorrect email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | LibraryHub</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-card">

        <a href="index.php" class="auth-logo">📚 LibraryHub</a>

        <h1>Welcome Back!</h1>
        <p class="auth-subtitle">Log in to your library account.</p>

        <?php if ($success !== ""): ?>
            <div class="auth-message success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="auth-message error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <label for="email">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                autocomplete="email"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <button type="submit" class="auth-button">
                Log In
            </button>

        </form>

        <p class="auth-switch">
            Don't have an account?
            <a href="register.php">Register here</a>
        </p>

        <a href="index.php" class="back-home">← Back to Home</a>

    </div>
</div>

</body>
</html>
