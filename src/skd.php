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
        <title>SKD CPNS WRITE UP</title>
        <style>
            * {
                font-family: 'Courier New', monospace;
            }

            body {
                background-color: #fff;
                color: #333;
                transition: background-color 0.3s, color 0.3s;
            }

            .dark-mode {
                background-color: #181818;
                color: #f5f5f5;
            }

            .dark-mode span {
                color: black;
                /* Set answer text color to black */
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
                border-bottom: #333 solid 1px;
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
                color: #333;
                transition: background-color 0.3s, color 0.3s;
            }

            .pagination a.active {
                background-color: #333;
                color: #fff;
            }

            .dark-mode-toggle {
                position: fixed;
                bottom: 20px;
                right: 20px;
                padding: 10px 15px;
                border: none;
                background-color: #333;
                color: #fff;
                cursor: pointer;
                z-index: 1000;
            }

            select {
                border: 1px solid #333;
                border-radius: 0;
                background-color: #fff;
                color: #333;
                font-size: 16px;
                appearance: none;
                outline: none;
                transition: border-color 0.3s;
            }

            select:focus {
                border-color: #555;
            }

            input[type="text"] {
                border: 1px solid #333;
                border-radius: 0;
                background-color: #fff;
                color: #333;
                font-size: 16px;
                outline: none;
                transition: border-color 0.3s;
            }

            input[type="text"]:focus {
                border-color: #555;
            }

            button {
                border: 1px solid #333;
                /* Border color */
                border-radius: 0;
                background-color: white;
                /* Background color */
                color: black;
                /* Text color */
                font-size: 16px;
                cursor: pointer;
                transition: background-color 0.3s, border-color 0.3s;
            }

            .dark-mode button {
                background-color: #333;
                /* Dark mode background */
                color: #fff;
                /* Dark mode text color */
            }

            button:hover {
                background-color: #45a049;
                /* Darken background on hover */
            }

            .dark-mode button:hover {
                background-color: #555;
                /* Dark mode hover background */
            }


            button:focus {
                outline: none;
                border-color: #555;
            }

            /* Specific styles for the dark mode */
            .dark-mode .pagination a {
                background-color: #333;
                color: #f5f5f5;
            }

            .dark-mode .pagination a:hover {
                background-color: #555;
            }

            .dark-mode .pagination a.active {
                background-color: #555;
                /* Dark background for active link */
                color: #fff;
                /* White text color for active link */
            }


            .dark-mode select,
            .dark-mode input[type="text"] {
                background-color: #333;
                color: #f5f5f5;
                border: 1px solid #555;
            }
        </style>

    </head>

    <body>
        <div class="container">
            <main>
                <h1>
                    <a href="/" style="color: inherit; text-decoration: none;">CPNS WRITE UP</a> / SKD
                </h1>
                <button class="dark-mode-toggle" id="toggle-dark-mode">Dark Mode</button>
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
                            <h3>WRITEUP<?= $row["id"] ?>
                                <small class="badge badge-category" style="background-color: #e0bbf4; color: #000; font-size: 0.9em; display: inline-block; font-weight: normal;"><?= htmlspecialchars($row['category']) ?></small>
                                <small class="badge badge-type" style="background-color: #f4bbf0; color: #000; font-size: 0.9em; display: inline-block; font-weight: normal;"><?= htmlspecialchars($row['type']) ?></small>
                            </h3>
                            <p><?= htmlspecialchars($row["question"]) ?></p>
                            <span style="background-color: #FFFF99;"><?= htmlspecialchars($row["answer"]) ?></span>
                            <span style="background-color: #99FF99;"><a href="/explain/<?= $row["id"] ?>" style="color: black; cursor: default;">explanation</a></span>
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

        <script>
            const toggleButton = document.getElementById('toggle-dark-mode');
            const body = document.body;

            // Check for saved dark mode preference in local storage
            if (localStorage.getItem('dark-mode') === 'enabled') {
                body.classList.add('dark-mode');
            }

            toggleButton.addEventListener('click', () => {
                body.classList.toggle('dark-mode');

                // Save the current preference in local storage
                if (body.classList.contains('dark-mode')) {
                    localStorage.setItem('dark-mode', 'enabled');
                } else {
                    localStorage.removeItem('dark-mode');
                }
            });
        </script>
    </body>

    </html>
    <?php
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

function read_explanation($id): void
{
    $conn = conn();

    // Prepare the SQL statement to fetch the question, answer, explanation, category, and type by ID
    $query = "SELECT question, answer, explanation, category, type FROM skd_writeup WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id); // Bind the ID as an integer
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a record was found
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
    ?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta http-equiv="content-type" content="text/html; charset=UTF-8">
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
            <meta http-equiv="X-UA-Compatible" content="ie=edge">
            <title>Write Up #<?= htmlspecialchars($id) ?></title>
            <style>
                * {
                    font-family: 'Courier New', monospace;
                }

                body {
                    transition: background-color 0.3s, color 0.3s;
                }

                .container {
                    max-width: 1200px;
                    margin: 0 auto;
                }

                .dark-mode-toggle {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    padding: 10px 15px;
                    border: none;
                    background-color: #333;
                    color: #fff;
                    cursor: pointer;
                    z-index: 1000;
                }

                .question,
                .category,
                .type,
                .explanation {
                    background-color: #f9f9f9;
                    padding: 15px;
                    border: 1px solid #ccc;
                    margin-bottom: 20px;
                    transition: background-color 0.3s, border-color 0.3s;
                }

                .answer {
                    background-color: #d0f0c0;
                    padding: 15px;
                    border: 1px solid #ccc;
                    margin-bottom: 20px;
                    border-color: #00796b;
                    transition: background-color 0.3s, border-color 0.3s;
                }

                .explanation {
                    background-color: #e0f7fa;
                    border-color: #00796b;
                    transition: background-color 0.3s, border-color 0.3s;
                }

                .badge {
                    display: inline-block;
                    font-size: 0.9em;
                    font-weight: bold;
                    color: #fff;
                    padding: 5px 10px;
                }

                .badge-category {
                    background-color: #a4c8f0;
                }

                .badge-type {
                    background-color: #a8e6a3;
                }

                /* Dark Mode Styles */
                body.dark {
                    background-color: #121212;
                    color: #ffffff;
                }

                body.dark .question,
                body.dark .category,
                body.dark .type,
                body.dark .explanation {
                    background-color: #1e1e1e;
                    border-color: #444444;
                }

                body.dark .answer {
                    background-color: #2e7d32;
                    border-color: #00796b;
                }

                body.dark .badge-category {
                    background-color: #8ab4f8;
                }

                body.dark .badge-type {
                    background-color: #c6ffb2;
                }
            </style>
        </head>

        <body>
            <div class="container">
                <main>
                    <h1>
                        <a href="/" style="color: inherit; text-decoration: none;">CPNS WRITE UP</a> / <a href="/skd" style="color: inherit; text-decoration: none;">SKD</a> / <?= htmlspecialchars($id) ?>
                    </h1>

                    <button class="dark-mode-toggle" id="toggle-dark-mode">Dark Mode</button>

                    <div class="question">
                        <h3>Question:</h3>
                        <p><?= htmlspecialchars($row['question']) ?></p>
                        <small class="badge badge-category">
                            <a href="/skd?category=<?= urlencode($row['category']) ?>" style="color: inherit; text-decoration: none;">
                                <?= htmlspecialchars($row['category']) ?>
                            </a>
                        </small>
                        <small class="badge badge-type">
                            <a href="/skd?category=&type=<?= urlencode($row['type']) ?>" style="color: inherit; text-decoration: none;">
                                <?= htmlspecialchars($row['type']) ?>
                            </a>
                        </small>
                    </div>

                    <div class="answer">
                        <h3>Answer:</h3>
                        <p><?= htmlspecialchars($row['answer']) ?></p>
                    </div>

                    <div class="explanation">
                        <h3>Explanation:</h3>
                        <p><?= htmlspecialchars($row['explanation']) ?></p>
                    </div>

                    <a href="javascript:history.back()">Back</a>
                </main>
            </div>
            <script>
                const toggleDarkModeButton = document.getElementById('toggle-dark-mode');

                // Check local storage for dark mode preference
                if (localStorage.getItem('darkMode') === 'enabled') {
                    document.body.classList.add('dark');
                }

                toggleDarkModeButton.addEventListener('click', () => {
                    // Toggle dark mode class
                    document.body.classList.toggle('dark');

                    // Save preference to local storage
                    if (document.body.classList.contains('dark')) {
                        localStorage.setItem('darkMode', 'enabled');
                    } else {
                        localStorage.setItem('darkMode', 'disabled');
                    }
                });
            </script>
        </body>

        </html>
    <?php
    } else {
        echo "<p>No explanation found for this question.</p>";
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}



function flash(): void
{
    $conn = conn();

    // Fetch a random question from the database
    $query = "SELECT id, question, answer, category, type, explanation FROM skd_writeup ORDER BY RAND() LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a record was found
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $question = htmlspecialchars($row['question']);
        $answer = htmlspecialchars($row['answer']);
        $category = htmlspecialchars($row['category']);
        $type = htmlspecialchars($row['type']);
        $explanation = htmlspecialchars($row['explanation']);
    ?>

        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Flash Card</title>
            <style>
                body {
                    transition: background-color 0.3s, color 0.3s;
                    background-color: #f9f9f9;
                    /* Light mode background */
                    color: #333;
                    /* Light mode text color */
                }

                body.dark-mode {
                    background-color: #181818;
                    /* Dark mode background */
                    color: #f9f9f9;
                    /* Dark mode text color */
                }

                .dark-mode-toggle {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    padding: 10px 15px;
                    border: none;
                    background-color: #333;
                    color: #fff;
                    cursor: pointer;
                    z-index: 1000;
                }

                * {
                    font-family: 'Courier New', monospace;
                }

                .container {
                    max-width: 600px;
                    margin: 0 auto;
                }

                .flashcard {
                    border: 1px solid #333;
                    padding: 20px;
                    margin: 20px 0;
                    position: relative;
                    transition: border-color 0.3s;
                }

                body.dark-mode .flashcard {
                    border-color: #f9f9f9;
                    /* Dark mode border color */
                }

                .badge {
                    display: inline-block;
                    color: #fff;
                }

                .category-badge {
                    background-color: #a4c8f0;
                    /* Pastel blue */
                }

                .type-badge {
                    background-color: #d4a4f0;
                    /* Pastel purple */
                }

                .answer-container {
                    display: none;
                    /* Hide by default */
                    margin-top: 10px;
                }

                .answer {
                    background-color: #d0f0c0;
                    /* Pastel green */
                    padding: 10px;
                    border: 1px solid #ccc;
                    border-color: #00796b;
                }

                body.dark-mode .answer {
                    background-color: #3a3a3a;
                    /* Dark mode answer background */
                    border-color: #5a5a5a;
                    /* Dark mode answer border color */
                }

                button {
                    margin-top: 10px;
                    padding: 10px 15px;
                    border: none;
                    background-color: black;
                    color: #fff;
                    cursor: pointer;
                }

                button.secondary-button {
                    background-color: white;
                    color: black;
                    border: 1px solid black;
                    /* Add a white border */
                }

                body.dark-mode button.secondary-button {
                    background-color: #444;
                    /* Dark mode secondary button */
                    color: white;
                    border-color: #666;
                    /* Dark mode border color */
                }



                button:hover {
                    background-color: #555;
                }

                body.dark-mode button:hover {
                    background-color: #777;
                    /* Dark mode hover effect */
                }
            </style>
        </head>

        <body>
            <div class="container">
                <h1>FLASH CARD</h1>
                <div class="flashcard">
                    <span class="badge category-badge"><?= $category ?></span>
                    <span class="badge type-badge"><?= $type ?></span>
                    <h3><?= $question ?></h3>
                    <button id="toggleAnswer">Show Answer</button>
                    <div class="answer-container" id="answerContainer">
                        <div class="answer" id="answer"><?= $answer ?></div>
                        <div id="explanation" style="margin-top: 10px;">
                            <strong>Explanation:</strong> <?= $explanation ?>
                        </div>
                    </div>
                </div>

                <button class="secondary-button" onclick="history.back();">Back</button>
                <button onclick="window.location.reload();">Next</button>
                <button class="dark-mode-toggle" id="toggleDarkMode">Dark Mode</button>
            </div>
            <script>
                // Check local storage for dark mode preference
                if (localStorage.getItem('darkMode') === 'enabled') {
                    document.body.classList.add('dark-mode');
                }

                document.getElementById('toggleAnswer').onclick = function() {
                    var answerContainer = document.getElementById('answerContainer');
                    if (answerContainer.style.display === "none" || answerContainer.style.display === "") {
                        answerContainer.style.display = "block";
                        this.textContent = "Hide Answer";
                    } else {
                        answerContainer.style.display = "none";
                        this.textContent = "Show Answer";
                    }
                };

                document.getElementById('toggleDarkMode').onclick = function() {
                    document.body.classList.toggle('dark-mode');
                    // Save preference to local storage
                    if (document.body.classList.contains('dark-mode')) {
                        localStorage.setItem('darkMode', 'enabled');
                    } else {
                        localStorage.setItem('darkMode', 'disabled');
                    }
                };
            </script>
        </body>

        </html>

<?php
    } else {
        echo "<p>No questions available.</p>";
    }

    $stmt->close();
    $conn->close();
}
