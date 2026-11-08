<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

$host = "db";
$dbname = "web";
$username = "user";
$password = "pass";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Koneksi database gagal"]);
    exit;
}

function response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function getInput() {
    return json_decode(file_get_contents("php://input"), true) ?? [];
}

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    if (($_GET["action"] ?? "") === "categories") {
        $stmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category");
        response($stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    $sql = "SELECT * FROM products WHERE 1=1";
    $params = [];

    if (!empty($_GET["search"])) {
        $sql .= " AND title LIKE ?";
        $params[] = "%" . $_GET["search"] . "%";
    }

    if (!empty($_GET["category"])) {
        $sql .= " AND category = ?";
        $params[] = $_GET["category"];
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    response([
        "products" => $products,
        "total" => count($products)
    ]);
}

if ($method === "POST") {
    $data = getInput();

    if (empty($data["title"]) || !isset($data["price"]) || empty($data["category"])) {
        response(["error" => "Title, price, dan category wajib diisi"], 400);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO products
        (title, price, stock, category, brand, description, thumbnail)
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $data["title"],
        $data["price"],
        $data["stock"] ?? 0,
        $data["category"],
        $data["brand"] ?? "",
        $data["description"] ?? "",
        $data["thumbnail"] ?? ""
    ]);

    $id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);

    response($stmt->fetch(PDO::FETCH_ASSOC), 201);
}

if ($method === "PUT") {
    $id = $_GET["id"] ?? null;
    $data = getInput();

    if (!$id) {
        response(["error" => "ID produk wajib diisi"], 400);
    }

    $stmt = $pdo->prepare(
        "UPDATE products SET
        title = ?, price = ?, stock = ?, category = ?,
        brand = ?, description = ?, thumbnail = ?
        WHERE id = ?"
    );

    $stmt->execute([
        $data["title"] ?? "",
        $data["price"] ?? 0,
        $data["stock"] ?? 0,
        $data["category"] ?? "",
        $data["brand"] ?? "",
        $data["description"] ?? "",
        $data["thumbnail"] ?? "",
        $id
    ]);

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        response(["error" => "Produk tidak ditemukan"], 404);
    }

    response($product);
}

if ($method === "DELETE") {
    $id = $_GET["id"] ?? null;

    if (!$id) {
        response(["error" => "ID produk wajib diisi"], 400);
    }

    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        response(["error" => "Produk tidak ditemukan"], 404);
    }

    response(["message" => "Produk berhasil dihapus"]);
}

response(["error" => "Method tidak didukung"], 405);