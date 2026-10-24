
<?php
session_start();
require_once "db.php";

// Only admins can issue books.
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: login.php");
    exit;
}

$error = "";
$success = "";

// Process the form.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = filter_input(INPUT_POST, "user_id", FILTER_VALIDATE_INT);
    $book_id = filter_input(INPUT_POST, "book_id", FILTER_VALIDATE_INT);
    $due_date = trim($_POST["due_date"] ?? "");

    $date = DateTime::createFromFormat("!Y-m-d", $due_date);
    $valid_date = $date && $date->format("Y-m-d") === $due_date;

    if (!$user_id || !$book_id || !$valid_date) {
        $error = "Please select a student, book, and valid due date.";
    } elseif ($due_date < date("Y-m-d")) {
        $error = "The due date cannot be in the past.";
    } else {
        mysqli_begin_transaction($conn);

        try {
            // Check that the selected student exists and is a student.
            $stmt = mysqli_prepare(
                $conn,
                "SELECT id FROM users WHERE id = ? AND role = 'student'"
            );
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (!$student) {
                throw new Exception("The selected student does not exist.");
            }

            // Lock the book row and check availability.
            $stmt = mysqli_prepare(
                $conn,
                "SELECT id, available FROM books WHERE id = ? FOR UPDATE"
            );
            mysqli_stmt_bind_param($stmt, "i", $book_id);
            mysqli_stmt_execute($stmt);
            $book = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (!$book) {
                throw new Exception("The selected book was not found.");
            }

            if ((int)$book["available"] < 1) {
                throw new Exception("This book is currently unavailable.");
            }

            // Record the borrowing.
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO issued_books
                    (user_id, book_id, issue_date, due_date, status)
                 VALUES (?, ?, CURDATE(), ?, 'issued')"
            );
            mysqli_stmt_bind_param(
                $stmt,
                "iis",
                $user_id,
                $book_id,
                $due_date
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Decrease availability only if a copy is still available.
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE books
                 SET available = available - 1
                 WHERE id = ? AND available > 0"
            );
            mysqli_stmt_bind_param($stmt, "i", $book_id);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) !== 1) {
                mysqli_stmt_close($stmt);
                throw new Exception("Availability changed. Please try again.");
            }

            mysqli_stmt_close($stmt);
            mysqli_commit($conn);

            $success = "Book issued successfully!";
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $error = $e instanceof mysqli_sql_exception
                ? "A database error occurred. Please check the selected records."
                : $e->getMessage();
        }
    }
}

// Get registered students.
$students = mysqli_query(
    $conn,
    "SELECT id, full_name, email
     FROM users
     WHERE role = 'student'
     ORDER BY full_name"
);

// Get books that have copies available.
$books = mysqli_query(
    $conn,
    "SELECT id, title, author, available
     FROM books
     WHERE available > 0
     ORDER BY title"
);

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Book | LibraryHub</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .issue-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 28px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
        }

        .issue-container h1 {
            margin-bottom: 10px;
        }

        .issue-container label {
            display: block;
            margin-top: 18px;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .issue-container select,
        .issue-container input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        .issue-container button {
            margin-top: 22px;
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 6px;
            background: #234b3a;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        .message {
            padding: 12px;
            margin: 18px 0;
            border-radius: 6px;
        }

        .error {
            background: #ffe8e8;
            color: #8a1c1c;
        }

        .success {
            background: #e4f5e8;
            color: #1d6530;
        }
    </style>
</head>

<body>

<header class="navbar">
    <a href="index.php" class="logo">📚 LibraryHub</a>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="books.php">Books</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="issue-container">
    <h1>Issue a Book</h1>
    <p>Select a student, a book, and its due date.</p>

    <?php if ($error !== ""): ?>
        <div class="message error" role="alert"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
        <div class="message success" role="status"><?= h($success) ?></div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($students) === 0): ?>
        <p>No student accounts are registered yet.</p>
    <?php elseif (mysqli_num_rows($books) === 0): ?>
        <p>No books are currently available to issue.</p>
    <?php else: ?>
        <form method="POST" action="issue_book.php">
            <label for="user_id">Select Student</label>
            <select name="user_id" id="user_id" required>
                <option value="">-- Choose a student --</option>
                <?php while ($student = mysqli_fetch_assoc($students)): ?>
                    <option value="<?= (int)$student["id"] ?>">
                        <?= h($student["full_name"]) ?>
                        (<?= h($student["email"]) ?>)
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="book_id">Select Book</label>
            <select name="book_id" id="book_id" required>
                <option value="">-- Choose a book --</option>
                <?php while ($book = mysqli_fetch_assoc($books)): ?>
                    <option value="<?= (int)$book["id"] ?>">
                        <?= h($book["title"]) ?>
                        — <?= h($book["author"]) ?>
                        (<?= (int)$book["available"] ?> available)
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="due_date">Due Date</label>
            <input
                type="date"
                name="due_date"
                id="due_date"
                min="<?= date("Y-m-d") ?>"
                value="<?= date("Y-m-d", strtotime("+14 days")) ?>"
                required
            >

            <button type="submit">Issue Book</button>
        </form>
    <?php endif; ?>

    <p style="margin-top: 20px;">
        <a href="dashboard.php">← Back to Dashboard</a>
    </p>
</main>

</body>
</html>
