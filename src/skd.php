<?php

declare(strict_types=1);
function read_question(): void
{
    $conn = conn();

    // Get the current page from the URL, default to 1 if not set
    $limit = 15; // Number of results per page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1; // Ensure the page number is at least 1
    $offset = ($page - 1) * $limit;

    // Count total number of rows to calculate total pages
    $count_query = "SELECT COUNT(*) as total FROM skd_writeup WHERE 1=1";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_rows = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $limit); // Calculate total pages

    // Apply filters if set
    $query = "SELECT * FROM skd_writeup WHERE 1=1";
    $params = [];
    if (isset($_GET['category']) && $_GET['category'] != '') {
        $query .= " AND category = ?";
        $params[] = $_GET['category'];
    }

    if (isset($_GET['type']) && $_GET['type'] != '') {
        $query .= " AND type = ?";
        $params[] = $_GET['type'];
    }

    if (isset($_GET['search']) && $_GET['search'] != '') {
        $query .= " AND (question LIKE ? OR category LIKE ?)";
        $search_param = '%' . $_GET['search'] . '%';
        $params[] = $search_param;
        $params[] = $search_param;
    }

    // Add LIMIT and OFFSET for pagination
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $conn->prepare($query);
    $stmt->bind_param(str_repeat('s', count($params) - 2) . 'ii', ...$params); // Bind strings and integers
    $stmt->execute();
    $result = $stmt->get_result();
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Bagus Muhammad Wijaksono</title>
        <style>
            * {
                font-family: 'Courier New', monospace;
            }

            .container {
                max-width: 1200px;
                margin: 0 auto;
            }

            tbody {
                text-align: left;
                vertical-align: top;
            }

            table th,
            table td {
                border-bottom: #333333 solid 1px;
            }

            footer {
                color: #7c7c7c;
            }

            .pagination {
                margin-top: 20px;
                text-align: center;
            }

            .pagination a {
                margin: 0 5px;
                text-decoration: none;
                padding: 5px 10px;
                border: 1px solid #333;
            }

            .pagination a.active {
                background-color: #333;
                color: #fff;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <main>
                <h1>SKD WRITE UP</h1>
                <form method="GET" action="">
                    <label for="category">Category:</label>
                    <select name="category" id="category">
                        <option value="">All</option>
                        <?php
                        // Fetch distinct categories for the dropdown
                        $category_stmt = $conn->prepare("SELECT DISTINCT category FROM skd_writeup;");
                        $category_stmt->execute();
                        $category_result = $category_stmt->get_result();
                        while ($category_row = $category_result->fetch_assoc()) {
                            $selected = (isset($_GET['category']) && $_GET['category'] == $category_row['category']) ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($category_row['category']) ?>" <?= $selected ?>><?= htmlspecialchars($category_row['category']) ?></option>
                        <?php
                        }
                        $category_stmt->close();
                        ?>
                    </select>

                    <label for="type">Type:</label>
                    <select name="type" id="type">
                        <option value="">All</option>
                        <?php
                        // Fetch distinct types for the dropdown
                        $type_stmt = $conn->prepare("SELECT DISTINCT type FROM skd_writeup;");
                        $type_stmt->execute();
                        $type_result = $type_stmt->get_result();
                        while ($type_row = $type_result->fetch_assoc()) {
                            $selected = (isset($_GET['type']) && $_GET['type'] == $type_row['type']) ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($type_row['type']) ?>" <?= $selected ?>><?= htmlspecialchars($type_row['type']) ?></option>
                        <?php
                        }
                        $type_stmt->close();
                        ?>
                    </select>

                    <label for="search">Search:</label>
                    <input type="text" name="search" id="search" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    <button type="submit">Filter</button>
                </form>

                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Use htmlspecialchars to prevent XSS attacks
                ?>
                        <div>
                            <p><?= htmlspecialchars($row["question"]) ?></p>
                            <span style="background-color: yellow;"><?= htmlspecialchars($row["answer"]) ?></span>
                        </div>
                    <?php
                    }
                } else {
                    ?>
                    <p>No results found</p>
                <?php
                }
                ?>

                <div class="pagination">
                    <?php if ($page > 1) { ?>
                        <a href="?page=<?= $page - 1 ?><?= isset($_GET['category']) ? '&category=' . urlencode($_GET['category']) : '' ?><?= isset($_GET['type']) ? '&type=' . urlencode($_GET['type']) : '' ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>">Previous</a>
                    <?php } ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                        <a href="?page=<?= $i ?><?= isset($_GET['category']) ? '&category=' . urlencode($_GET['category']) : '' ?><?= isset($_GET['type']) ? '&type=' . urlencode($_GET['type']) : '' ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php } ?>

                    <?php if ($page < $total_pages) { ?>
                        <a href="?page=<?= $page + 1 ?><?= isset($_GET['category']) ? '&category=' . urlencode($_GET['category']) : '' ?><?= isset($_GET['type']) ? '&type=' . urlencode($_GET['type']) : '' ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>">Next</a>
                    <?php } ?>
                </div>

            </main>
        </div>
    </body>

    </html>
<?php
    $stmt->close();
    $conn->close();
}

function migrate(): void
{
    $conn = conn();

    $query = "
    CREATE TABLE skd_writeup (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        question TEXT,
        category ENUM('TIU','TWK','TKP'),
        answer TEXT,
        explanation LONGTEXT,
        type ENUM('Deret Angka','Verbal Analogi','Silogisme','Analitis','Operasi Bilangan','Perbandingan','Jarak Kecepatan Waktu','Figural Gambar','Pancasila','Bhineka Tunggal IKa','NKRI','UUD 1945','Integritas','Nasionalisme','Bela Negara','Bahasa Indonesia','Pelayanan Public','Profesionalisme','Jejaring kerja','Sosial Budaya','Teknologi Informasi dan KOmunikasi','Anti Radikalisme'),
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    $conn->query($query);

    $conn->close();
}
