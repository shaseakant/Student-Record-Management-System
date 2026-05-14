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
    SELECT m.name, oi.quantity, oi.price 
    FROM order_items oi 
    JOIN menu_items m ON oi.menu_item_id = m.id 
    WHERE oi.order_id = $order_id
");

date_default_timezone_set("Asia/Kolkata");
$date = date("d/m/Y", strtotime($order['order_date']));
$time = date("h:i A", strtotime($order['order_date']));

$lineLength = 32;
$bill = "";

// Header
$bill .="\n";
$bill .= str_pad("ROYAL ORBIT CAFE & RESTRO", $lineLength, " ", STR_PAD_BOTH) . "\n";
$bill .= str_pad("S.c Road, Nilkuthidanga", $lineLength, " ", STR_PAD_BOTH) . "\n";
$bill .= str_pad("West Bengal", $lineLength, " ", STR_PAD_BOTH) . "\n";
$bill .= str_pad("Ph: 8927727426", $lineLength, " ", STR_PAD_BOTH) . "\n";
$bill .= str_pad("royalorbitcaferestro@gmail.com", $lineLength, " ", STR_PAD_BOTH) . "\n";
$bill .= str_repeat("-", $lineLength) . "\n";

// Bill Info
$bill .= "Bill No: $order_id   Date: $date\n";
$bill .= "Time: $time \n";
$bill .= str_repeat("-", $lineLength) . "\n";

// Table Header
$bill .= str_pad("Item", 13);
$bill .= str_pad("Qty", 3, " ", STR_PAD_LEFT);
$bill .= str_pad("Rate", 7, " ", STR_PAD_LEFT);
$bill .= str_pad("Amt", 9, " ", STR_PAD_LEFT) . "\n";
$bill .= str_repeat("-", $lineLength) . "\n";

// Items Loop (Fixed Alignment)
$total = 0;
while ($i = $items->fetch_assoc()) {
    $name = substr($i['name'], 0, 15);
    $qty = (int)$i['quantity'];
    $rate = number_format($i['price'] / max($qty, 1), 2);
    $amt = number_format($i['price'], 2);

    $bill .= str_pad($name, 13);
    $bill .= str_pad($qty, 3, " ", STR_PAD_LEFT);
    $bill .= str_pad($rate, 7, " ", STR_PAD_LEFT);
    $bill .= str_pad($amt, 9, " ", STR_PAD_LEFT) . "\n";

    $total += $i['price'];
}

$bill .= str_repeat("-", $lineLength) . "\n";

// Totals
$totalFormatted = number_format($total, 2);
$bill .= str_pad("SubTotal", 23) . "₹" . str_pad($totalFormatted, 8, " ", STR_PAD_LEFT) . "\n";
$bill .= str_repeat("-", $lineLength) . "\n";
$bill .= str_pad("TOTAL", 23) . "₹" . str_pad($totalFormatted, 8, " ", STR_PAD_LEFT) . "\n";
$bill .= str_repeat("-", $lineLength) . "\n";

// Footer
$bill .= str_pad("Please Rate Us on Google", $lineLength, " ", STR_PAD_BOTH) . "\n";
$bill .= str_pad("Thank You! Visit Again 🙏", $lineLength, " ", STR_PAD_BOTH) . "\n";
$bill .= str_repeat("-", $lineLength) . "\n";

// Encode for RawBT
$encoded = urlencode($bill);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Print Bill #<?= $order_id ?></title>
    <style>
        body {
            font-family: monospace;
            background: #fff;
            margin: 0;
            padding: 5mm;
        }
        .bill-container {
            width: 100%;
            max-width: 58mm;
            margin: 0 auto;
        }
        .logo {
            display: block;
            margin: 0 auto 10px;
            width: 80px;
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
            * {
                font-family: monospace !important;
                font-size: 11px !important;
                line-height: 1.4 !important;
            }
            .btn, .logo {
                display: none !important;
            }
            body {
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="bill-container">
        <img src="logo.png" alt="Logo" class="logo">
        <pre><?= htmlspecialchars($bill) ?></pre>
    </div>
    <button onclick="window.print()" class="btn print-browser">🖨 Print via Browser</button>
    <a href="rawbt://print?text=<?= $encoded ?>" class="btn print-rawbt">🧾 Print via Bluetooth (RawBT)</a>
</body>
</html>