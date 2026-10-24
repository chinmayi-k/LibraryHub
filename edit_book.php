
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    http_response_code(403);
    exit("Access denied. Admin login required.");
}

$error = "";
$success = "";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit("Invalid book ID.");
}

$stmt = mysqli_prepare($conn, "SELECT * FROM books WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$book = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$book) {
    exit("Book not found.");
}

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
        // Calculate how many copies are currently borrowed.
        $borrowed = (int)$book["quantity"] - (int)$book["available"];

        // Never allow total quantity to be less than borrowed copies.
        if ($quantity < $borrowed) {
            $error = "Quantity cannot be less than the number of borrowed copies ($borrowed).";
        } else {
            $available = $quantity - $borrowed;
            $isbnValue = ($isbn === "") ? null : $isbn;

            $update = mysqli_prepare(
                $conn,
                "UPDATE books
                 SET title = ?, author = ?, category = ?, isbn = ?,
                     quantity = ?, available = ?
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $update,
                "ssssiii",
                $title,
                $author,
                $category,
                $isbnValue,
                $quantity,
                $available,
                $id
            );

            if (mysqli_stmt_execute($update)) {
                $success = "Book updated successfully!";

                $book["title"] = $title;
                $book["author"] = $author;
                $book["category"] = $category;
                $book["isbn"] = $isbnValue;
                $book["quantity"] = $quantity;
                $book["available"] = $available;
            } else {
                $error = (mysqli_errno($conn) === 1062)
                    ? "That ISBN is already assigned to another book."
                    : "Could not update the book. Please try again.";
            }

            mysqli_stmt_close($update);
        }
    }
}

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book | LibraryHub</title>
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
        .error { color: #b00020; }
        .success { color: #176b38; }
    </style>
</head>
<body>
    <main class="book-form">
        <h1>Edit Book</h1>

        <?php if ($error !== ""): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <p class="success"><?= e($success) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="title">Book Title *</label>
            <input id="title" name="title" value="<?= e($book["title"]) ?>" required>

            <label for="author">Author *</label>
            <input id="author" name="author" value="<?= e($book["author"]) ?>" required>

            <label for="category">Category</label>
            <input id="category" name="category" value="<?= e($book["category"]) ?>">

            <label for="isbn">ISBN (optional)</label>
            <input id="isbn" name="isbn" maxlength="20" value="<?= e($book["isbn"]) ?>">

            <label for="quantity">Total Copies *</label>
            <input id="quantity" type="number" name="quantity"
                   min="1" value="<?= (int)$book["quantity"] ?>" required>

            <button type="submit">Save Changes</button>
            <a href="books.php">View Books</a>
            <a href="dashboard.php">Dashboard</a>
        </form>
    </main>
</body>
</html>
