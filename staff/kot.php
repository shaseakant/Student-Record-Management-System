<?php
include '../db.php';

if (!isset($_GET['order_id'])) die("No order ID provided.");
$order_id = (int)$_GET['order_id'];

$order = $conn->query("
    SELECT o.*, u.name AS user 
    FROM orders o 
    JOIN users u ON o.placed_by = u.id 
    WHERE o.id = $order_id
")->fetch_assoc();

$items = $conn->query("
    SELECT m.name, oi.quantity 
    FROM order_items oi 
    JOIN menu_items m ON oi.menu_item_id = m.id 
    WHERE oi.order_id = $order_id
");

date_default_timezone_set("Asia/Kolkata");
$date = date("d/m/Y", strtotime($order['order_date']));
$time = date("h:i A", strtotime($order['order_date']));

$lineLength = 32;
$kot = "";

// Header
$kot .= "\n";
$kot .= str_pad("KITCHEN ORDER TICKET", $lineLength, " ", STR_PAD_BOTH) . "\n";
$kot .= str_repeat("-", $lineLength) . "\n";
$kot .= "KOT No: $order_id\n";
$kot .= "Date: $date  Time: $time\n";
$kot .= str_repeat("-", $lineLength) . "\n";

// Table Header
$kot .= str_pad("Item", 20);
$kot .= str_pad("Qty", 6, " ", STR_PAD_LEFT) . "\n";
$kot .= str_repeat("-", $lineLength) . "\n";

// Items
while ($i = $items->fetch_assoc()) {
    $name = strtoupper(substr($i['name'], 0, 20));
    $qty = str_pad($i['quantity'], 6, " ", STR_PAD_LEFT);
    $kot .= str_pad($name, 20) . $qty . "\n";
}

$kot .= str_repeat("-", $lineLength) . "\n";
$kot .= str_pad("PRINTED: " . date("h:i A"), $lineLength, " ", STR_PAD_BOTH) . "\n";
$kot .= str_repeat("-", $lineLength) . "\n";

// Encode for RawBT
$encoded = urlencode($kot);
?>
<!DOCTYPE html>
<html>
<head>
    <title>KOT - Order #<?= $order_id ?></title>
    <style>
        body {
            font-family: monospace;
            background: #fff;
            margin: 0;
            padding: 5mm;
        }
        .kot-container {
            width: 100%;
            max-width: 58mm;
            margin: 0 auto;
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
        }
        .btn {
            display: block;
            margin: 20px auto;
            padding: 10px;
            width: 100%;
            max-width: 300px;
            font-size: 16px;
            cursor: pointer;
            border: none;
            color: white;
        }
        .print-browser { background-color: #007bff; }
        .print-rawbt { background-color: #28a745; margin-top: 10px; }

        @media print {
            .btn {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="kot-container">
        <pre><?= htmlspecialchars($kot) ?></pre>
    </div>

    <a href="rawbt://print?text=<?= $encoded ?>" class="btn print-rawbt">📡 Print via Bluetooth (RawBT)</a>
</body>
</html>
