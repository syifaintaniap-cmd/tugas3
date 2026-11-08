<?php

header('Content-Type: application/json');

try {
    // Koneksi ke database MySQL
    $pdo = new PDO(
        'mysql:host=db;dbname=web;charset=utf8mb4',
        'user',
        'pass',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Ambil semua produk dari DummyJSON
    $response = file_get_contents(
        'https://dummyjson.com/products?limit=0'
    );

    if ($response === false) {
        throw new Exception('Gagal mengambil data DummyJSON');
    }

    $data = json_decode($response, true);

    if (!isset($data['products'])) {
        throw new Exception('Format data DummyJSON tidak valid');
    }

    $sql = "INSERT INTO products
        (title, price, stock, category, brand, description, thumbnail)
        VALUES
        (:title, :price, :stock, :category, :brand, :description, '')";

    $stmt = $pdo->prepare($sql);

    $pdo->beginTransaction();

    $total = 0;

    foreach ($data['products'] as $product) {
        $stmt->execute([
            ':title' => $product['title'] ?? '',
            ':price' => $product['price'] ?? 0,
            ':stock' => $product['stock'] ?? 0,
            ':category' => $product['category'] ?? '',
            ':brand' => $product['brand'] ?? '',
            ':description' => $product['description'] ?? '',
        ]);

        $total++;
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Import berhasil',
        'total_imported' => $total
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}