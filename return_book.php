
<?php
session_start();
require_once "db.php";

// Only admins can process book returns.
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: login.php");
    exit;
}

// Only accept POST requests.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: manage_issues.php");
    exit;
}

$issue_id = filter_input(INPUT_POST, "issue_id", FILTER_VALIDATE_INT);

if (!$issue_id) {
    header("Location: manage_issues.php?error=invalid");
    exit;
}

mysqli_begin_transaction($conn);

try {
    // Lock the borrowing record.
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, book_id, status
         FROM issued_books
         WHERE id = ?
         FOR UPDATE"
    );

    mysqli_stmt_bind_param($stmt, "i", $issue_id);
    mysqli_stmt_execute($stmt);

    $issue = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$issue) {
        throw new Exception("record_not_found");
    }

    if ($issue["status"] !== "issued") {
        throw new Exception("already_returned");
    }

    $book_id = (int) $issue["book_id"];

    // Lock and verify the book record.
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id FROM books WHERE id = ? FOR UPDATE"
    );

    mysqli_stmt_bind_param($stmt, "i", $book_id);
    mysqli_stmt_execute($stmt);

    $book = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$book) {
        throw new Exception("book_not_found");
    }

    // Mark the borrowing record as returned.
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE issued_books
         SET status = 'returned', return_date = CURDATE()
         WHERE id = ? AND status = 'issued'"
    );

    mysqli_stmt_bind_param($stmt, "i", $issue_id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) !== 1) {
        mysqli_stmt_close($stmt);
        throw new Exception("already_returned");
    }

    mysqli_stmt_close($stmt);

    // Increase availability once.
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE books
         SET available = available + 1
         WHERE id = ? AND available < quantity"
    );

    mysqli_stmt_bind_param($stmt, "i", $book_id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) !== 1) {
        mysqli_stmt_close($stmt);
        throw new Exception("availability_error");
    }

    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

    header("Location: manage_issues.php?returned=1");
    exit;

} catch (Throwable $e) {
    mysqli_rollback($conn);

    $error = $e instanceof mysqli_sql_exception
        ? "database"
        : $e->getMessage();

    $allowedErrors = [
        "record_not_found",
        "already_returned",
        "book_not_found",
        "availability_error",
        "database"
    ];

    if (!in_array($error, $allowedErrors, true)) {
        $error = "database";
    }

    header("Location: manage_issues.php?error=" . urlencode($error));
    exit;
}
