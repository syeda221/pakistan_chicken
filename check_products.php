<?php
$conn = new PDO('mysql:host=127.0.0.1;dbname=bin_sultan', 'root', '');
$out = "";
$out .= "=== PRODUCTS TABLE ===\r\n";
$stmt = $conn->query('DESCRIBE products');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $out .= $row['Field'] . " -- " . $row['Type'] . "\r\n";
}
$out .= "\r\n=== STOCKS TABLE ===\r\n";
$stmt2 = $conn->query('DESCRIBE stocks');
$rows2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows2 as $row) {
    $out .= $row['Field'] . " -- " . $row['Type'] . "\r\n";
}
$out .= "\r\n=== product_variants check ===\r\n";
$stmt3 = $conn->query('SHOW TABLES LIKE "product_variants"');
$out .= ($stmt3->rowCount() > 0 ? "EXISTS" : "DOES NOT EXIST") . "\r\n";
file_put_contents('db_check_output.txt', $out);
echo "Done - check db_check_output.txt\r\n";
