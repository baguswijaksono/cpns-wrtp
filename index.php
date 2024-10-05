<?php

declare(strict_types=1);

$routes = [
    'GET' => [],
    'POST' => [],
    'PUT' => [],
    'DELETE' => [],
];

function get(string $path, callable $handler): void
{
    global $routes;
    $routes['GET'][$path] = $handler;
}

function dispatch(string $url, string $method): void
{
    global $routes;

    if (!isset($routes[$method])) {
        http_response_code(405);
        echo "Method $method Not Allowed";
        return;
    }

    foreach ($routes[$method] as $path => $handler) {
        if (preg_match("#^$path$#", $url, $matches)) {
            array_shift($matches);
            call_user_func_array($handler, $matches);
            return;
        }
    }

    http_response_code(404);
    handleNotFound();
}

function handleNotFound(): void
{
    echo "404 Not Found";
}

function conn()
{
    $servername = "localhost";
    $username = "phpmyadmin";
    $password = "your_password";
    $dbname = "cpns_wrtp";
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

require_once __DIR__ . '/src/skd.php';

function listen(): void
{
    get('/', 'home');
    get('/migrate', 'migrate');
    get('/skd', 'read_question');
    get('/explain/([\w-]+)', 'read_explanation');
    get('/flash', 'flash');

    dispatch(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $_SERVER['REQUEST_METHOD']);
}

function home(): void
{
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>CPNS WRITE UP</title>

        <style>
            :root {
                --bg-color: #ffffff;
                /* Light mode background */
                --text-color: #000000;
                /* Light mode text color */
                --border-color: #333333;
                /* Light mode border color */
            }

            .dark-mode {
                --bg-color: #121212;
                /* Dark mode background */
                --text-color: #ffffff;
                /* Dark mode text color */
                --border-color: #444444;
                /* Dark mode border color */
            }

            body {
                background-color: var(--bg-color);
                color: var(--text-color);
                font-family: 'Courier New', monospace;
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
                border-bottom: var(--border-color) solid 1px;
            }

            footer {
                color: #7c7c7c;
            }

            button {
                cursor: pointer;
                margin: 10px 0;
                padding: 10px;
                border: none;
                border-radius: 5px;
                background-color: var(--border-color);
                color: var(--text-color);
                transition: background-color 0.3s;
            }

            button:hover {
                background-color: #555;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <button class="dark-mode-toggle" id="toggle-dark-mode">Dark Mode</button>
            <main>
                <h1>CPNS WRITE UP</h1>
                <div>
                    <p>An archive of write-ups and explanations created for learning purposes</p>
                </div>
                <h2>Write Up</h2>
                <p>Please check the following link for updates.</p>
                <ul>
                    <li>
                        <a href="/skd">SKD</a>
                    </li>
                    <li>
                        <a href="/skb">SKB</a>
                    </li>
                    <li>
                        <a href="/flash">Flash Card</a>
                    </li>
                </ul>
                <small>Note: The content of the subject may change based on PANRB hint.</small>
            </main>
            <br>
            <footer>
                <small>© Bagus Wijaksono</small>
            </footer>
        </div>

        <script>
            const toggleButton = document.getElementById('toggle-dark-mode');
            toggleButton.addEventListener('click', () => {
                document.body.classList.toggle('dark-mode');
            });
        </script>
    </body>

    </html>
<?php
}


listen();
