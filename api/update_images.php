<?php

header('Content-Type: application/json');

try {
    $pdo = new PDO(
        'mysql:host=db;dbname=web;charset=utf8mb4',
        'user',
        'pass',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $response = file_get_contents('https://dummyjson.com/products?limit=0');

    if ($response === false) {
        throw new Exception('Gagal mengambil data DummyJSON');
    }

    $data = json_decode($response, true);

    if (!isset($data['products'])) {
        throw new Exception('Data produk tidak valid');
    }

    $stmt = $pdo->prepare(
        'UPDATE products SET thumbnail = :thumbnail WHERE id = :id'
    );

    $pdo->beginTransaction();

    $total = 0;

    foreach ($data['products'] as $product) {
        $stmt->execute([
            ':thumbnail' => $product['thumbnail'] ?? '',
            ':id' => $product['id'],
        ]);

        $total += $stmt->rowCount();
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'URL gambar berhasil diperbarui',
        'total_updated' => $total
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