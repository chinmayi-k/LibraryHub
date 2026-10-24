
<?php
session_start();
require_once "db.php";

// Admin-only access.
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: login.php");
    exit;
}

$sql = "SELECT
            ib.id,
            u.full_name,
            u.email,
            b.title,
            b.author,
            ib.issue_date,
            ib.due_date,
            ib.return_date,
            ib.status
        FROM issued_books ib
        INNER JOIN users u ON ib.user_id = u.id
        INNER JOIN books b ON ib.book_id = b.id
        ORDER BY
            CASE WHEN ib.status = 'issued' THEN 0 ELSE 1 END,
            ib.due_date ASC";

$result = mysqli_query($conn, $sql);

function h($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Issued Books | LibraryHub</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .issues-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 24px;
        }

        .issues-container h1 {
            color: #234b3a;
            margin-bottom: 10px;
        }

        .issues-table-wrapper {
            overflow-x: auto;
            margin-top: 25px;
        }

        .issues-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .issues-table th,
        .issues-table td {
            padding: 13px;
            border-bottom: 1px solid #e5e5e5;
            text-align: left;
            white-space: nowrap;
        }

        .issues-table th {
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
    </style>
</head>

<body>

<header class="navbar">
    <a href="index.php" class="logo">📚 LibraryHub</a>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="books.php">Books</a>
        <a href="issue_book.php">Issue Book</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="issues-container">
    <h1>Manage Issued Books</h1>
    <p>View borrowing records for all students.</p>

    <?php if (isset($_GET["returned"])): ?>
        <p role="status">Book returned successfully.</p>
    <?php endif; ?>

    <?php if ($result && mysqli_num_rows($result) > 0): ?>

        <div class="issues-table-wrapper">
            <table class="issues-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Book</th>
                        <th>Author</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Action</th>
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
                            <td><?= h($row["full_name"]) ?></td>
                            <td><?= h($row["email"]) ?></td>
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
                            <td>
                                <?php if ($row["status"] === "issued"): ?>
                                <form
                                action="return_book.php"
                                method="POST"
                                onsubmit="return confirm('Confirm that this book has been returned?');"
                                >
                            <input
                            type="hidden"
                            name="issue_id"
                            value="<?= (int)$row["id"] ?>"
                            >
                            <button type="submit">Return Book</button>
                            </form>
                            <?php else: ?>
                            —
                            <?php endif; ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>

        <div class="empty-message">
            No borrowing records found.
        </div>

    <?php endif; ?>

    <p style="margin-top: 24px;">
        <a href="dashboard.php">← Back to Dashboard</a>
    </p>
</main>

</body>
</html>