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

function post(string $path, callable $handler): void
{
    global $routes;
    $routes['POST'][$path] = $handler;
}

function put(string $path, callable $handler): void
{
    global $routes;
    $routes['PUT'][$path] = $handler;
}

function delete(string $path, callable $handler): void
{
    global $routes;
    $routes['DELETE'][$path] = $handler;
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
    $dbname = "cpns_wrtp2";
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
        </style>
    </head>

    <body>
        <div class="container">
            <main>
                <h1>CPNS WRITE UP</h1>

                <div>
                    <p>An archive of write-ups and explanations created for learning purposes</p>

                </div>
                <h2>Write Up</h2>
                <p>Please check the following link for updates.</p>
                <ul>
                    <li>
                        <a href="/skd">SKD 2024</a>
                    </li>
                    <li>
                        <a href="/skb">SKB 2023</a>
                    </li>
                </ul>

                <small>Note: The content of the subject may change base on PANRB hint.</small>

            </main>
            <br>
            <footer>
                <small>© Bagus Wijaksono</small>
            </footer>
        </div>



    </body>

    </html>
<?php
}

listen();
