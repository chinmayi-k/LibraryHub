
<?php
session_start();
require_once "db.php";

// Require student login.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "student") {
    header("Location: dashboard.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];
// Get borrowing summary for the logged-in student.
$summarySql = "SELECT
    SUM(CASE WHEN status = 'issued' THEN 1 ELSE 0 END)
        AS borrowed_count,

    SUM(CASE
        WHEN status = 'issued' AND due_date < CURDATE()
        THEN 1 ELSE 0
    END) AS overdue_count,

    SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END)
        AS returned_count

    FROM issued_books
    WHERE user_id = ?";

$summaryStmt = mysqli_prepare($conn, $summarySql);

mysqli_stmt_bind_param($summaryStmt, "i", $user_id);
mysqli_stmt_execute($summaryStmt);

$summaryResult = mysqli_stmt_get_result($summaryStmt);
$summary = mysqli_fetch_assoc($summaryResult);

mysqli_stmt_close($summaryStmt);

$sql = "SELECT
            ib.id,
            b.title,
            b.author,
            ib.issue_date,
            ib.due_date,
            ib.return_date,
            ib.status
        FROM issued_books ib
        INNER JOIN books b ON ib.book_id = b.id
        WHERE ib.user_id = ?
        ORDER BY
            CASE WHEN ib.status = 'issued' THEN 0 ELSE 1 END,
            ib.due_date ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

function h($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Borrowed Books | LibraryHub</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .borrowed-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 28px;
        }

        .borrowed-container h1 {
            color: #234b3a;
            margin-bottom: 10px;
        }

        .borrowed-table-wrapper {
            overflow-x: auto;
            margin-top: 25px;
        }

        .borrowed-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .borrowed-table th,
        .borrowed-table td {
            padding: 14px;
            border-bottom: 1px solid #e5e5e5;
            text-align: left;
        }

        .borrowed-table th {
            background: #234b3a;
            color: white;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: bold;
        }

        .issued {
            background: #fff0c2;
            color: #795600;
        }

        .returned {
            background: #dff4e4;
            color: #216b35;
        }

        .overdue {
            background: #ffe0e0;
            color: #9b2020;
        }

        .empty-message {
            margin-top: 25px;
            padding: 20px;
            background: white;
            border-radius: 8px;
        }

        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #234b3a;
        }
        .summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 18px;
    margin-top: 25px;
}

.summary-card {
    background: #ffffff;
    padding: 22px;
    border-radius: 12px;
    border-top: 4px solid #234b3a;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.summary-card h3 {
    color: #426b5a;
    font-size: 16px;
    margin-bottom: 12px;
}

.summary-card p {
    color: #234b3a;
    font-size: 30px;
    font-weight: bold;
}
    </style>
</head>

<body>

<header class="navbar">
    <a href="index.php" class="logo">📚 LibraryHub</a>

    <nav>
        <a href="index.php">Home</a>
        <a href="books.php">Books</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="borrowed-container">
    <h1>My Borrowed Books</h1>

    <p>View your borrowed books, due dates, and return history.</p>
    <div class="summary-cards">

    <div class="summary-card">
        <h3>Currently Borrowed</h3>
        <p><?= (int)($summary["borrowed_count"] ?? 0) ?></p>
    </div>

    <div class="summary-card">
        <h3>Overdue Books</h3>
        <p><?= (int)($summary["overdue_count"] ?? 0) ?></p>
    </div>

    <div class="summary-card">
        <h3>Books Returned</h3>
        <p><?= (int)($summary["returned_count"] ?? 0) ?></p>
    </div>

</div>

    <?php if (mysqli_num_rows($result) > 0): ?>

        <div class="borrowed-table-wrapper">
            <table class="borrowed-table">
                <thead>
                    <tr>
                        <th>Book</th>
                        <th>Author</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <?php
                        $isOverdue =
                            $row["status"] === "issued" &&
                            $row["due_date"] < date("Y-m-d");
                        ?>

                        <tr>
                            <td><?= h($row["title"]) ?></td>
                            <td><?= h($row["author"]) ?></td>
                            <td><?= h($row["issue_date"]) ?></td>
                            <td><?= h($row["due_date"]) ?></td>
                            <td>
                                <?= $row["return_date"]
                                    ? h($row["return_date"])
                                    : "—" ?>
                            </td>
                            <td>
                                <?php if ($row["status"] === "returned"): ?>
                                    <span class="status returned">Returned</span>
                                <?php elseif ($isOverdue): ?>
                                    <span class="status overdue">Overdue</span>
                                <?php else: ?>
                                    <span class="status issued">Issued</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>

        <div class="empty-message">
            You haven't borrowed any books yet.
        </div>

    <?php endif; ?>

    <a class="back-link" href="dashboard.php">← Back to Dashboard</a>
</main>

</body>
</html>