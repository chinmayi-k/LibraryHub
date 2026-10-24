
<?php
session_start();
require_once "db.php";

// Only admins can add books
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    http_response_code(403);
    exit("Access denied. Admin login required.");
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $author = trim($_POST["author"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $isbn = trim($_POST["isbn"] ?? "");
    $quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT);

    if ($title === "" || $author === "" || $quantity === false
        || $quantity === null || $quantity < 1) {
        $error = "Enter a title, author, and a quantity of at least 1.";
    } else {
        $available = $quantity;

        // Store an empty ISBN as NULL
        $isbnValue = ($isbn === "") ? null : $isbn;

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO books (title, author, category, isbn, quantity, available)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssssii",
            $title,
            $author,
            $category,
            $isbnValue,
            $quantity,
            $available
        );

        if (mysqli_stmt_execute($stmt)) {
            $success = "Book added successfully!";
        } else {
            $error = ($isbnValue !== null && mysqli_errno($conn) === 1062)
                ? "That ISBN already exists. Please enter a unique ISBN."
                : "Could not add the book. Please try again.";
        }

        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book | LibraryHub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .book-form {
            max-width: 550px;
            margin: 40px auto;
            padding: 28px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0,0,0,.08);
        }
        .book-form label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        .book-form input {
            width: 100%;
            padding: 11px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }
        .book-form button, .book-form a {
            display: inline-block;
            margin-top: 20px;
            padding: 11px 16px;
            border: none;
            border-radius: 6px;
            background: #234b3a;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }
        .message { margin-top: 15px; }
        .error { color: #b00020; }
        .success { color: #176b38; }
    </style>
</head>
<body>
    <main class="book-form">
        <h1>Add a New Book</h1>

        <?php if ($error !== ""): ?>
            <p class="message error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <p class="message success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="title">Book Title *</label>
            <input id="title" name="title" required>

            <label for="author">Author *</label>
            <input id="author" name="author" required>

            <label for="category">Category</label>
            <input id="category" name="category">

            <label for="isbn">ISBN (optional)</label>
            <input id="isbn" name="isbn" maxlength="20">

            <label for="quantity">Number of Copies *</label>
            <input id="quantity" type="number" name="quantity"
                   min="1" value="1" required>

            <button type="submit">Add Book</button>
            <a href="dashboard.php">Dashboard</a>
            <a href="books.php">View Books</a>
        </form>
    </main>
</body>
</html>