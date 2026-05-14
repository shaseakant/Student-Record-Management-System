<?php
date_default_timezone_set('Asia/Kolkata');
include '../db.php';
$conn->query("SET time_zone = '+05:30'");
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

$loggedUserId = $_SESSION['user_id'];
$loggedUserName = $_SESSION['username'];

$edit = false;
$edit_order_id = 0;
$edit_order_items = [];

if (isset($_GET['edit_id'])) {
    $edit = true;
    $edit_order_id = (int)$_GET['edit_id'];
    $order = $conn->query("SELECT * FROM orders WHERE id = $edit_order_id")->fetch_assoc();
    
    // Prevent editing if not placed by current user
    if ($order['placed_by'] != $loggedUserId) {
        die("Unauthorized access to edit this order.");
    }

    $edit_order_items = $conn->query("SELECT * FROM order_items WHERE order_id = $edit_order_id")->fetch_all(MYSQLI_ASSOC);
}

// Delete order (only if placed by current user)
if (isset($_GET['delete_id'])) {
    $orderId = (int)$_GET['delete_id'];
    $check = $conn->query("SELECT placed_by FROM orders WHERE id = $orderId")->fetch_assoc();
    if ($check && $check['placed_by'] == $loggedUserId) {
        $conn->query("DELETE FROM order_items WHERE order_id = $orderId");
        $conn->query("DELETE FROM orders WHERE id = $orderId");
        echo "<p style='color:red;'>Order #$orderId deleted.</p>";
    } else {
        echo "<p style='color:red;'>Unauthorized to delete this order.</p>";
    }
}

// Submit
if (isset($_POST['submit'])) {
    $placed_by = $loggedUserId;
    $items = $_POST['items'];
    $total = 0;
    foreach ($items as $item) {
        $total += (float)$item['price'];
    }

    if (isset($_POST['edit_order_id']) && $_POST['edit_order_id']) {
        $order_id = (int)$_POST['edit_order_id'];
        $conn->query("UPDATE orders SET placed_by=$placed_by, total_amount=$total WHERE id=$order_id");
        $conn->query("DELETE FROM order_items WHERE order_id=$order_id");

        $stmt = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($items as $item) {
            $stmt->bind_param("iiid", $order_id, $item['id'], $item['qty'], $item['price']);
            $stmt->execute();
        }

        // Redirect to avoid resubmission
        header("Location: orders.php?edit_id=$order_id&updated=1");
        exit();
    } else {
        $stmt = $conn->prepare("INSERT INTO orders (placed_by, total_amount) VALUES (?, ?)");
        $stmt->bind_param("id", $placed_by, $total);
        $stmt->execute();
        $order_id = $conn->insert_id;

        $stmt2 = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($items as $item) {
            $stmt2->bind_param("iiid", $order_id, $item['id'], $item['qty'], $item['price']);
            $stmt2->execute();
        }

        // Redirect to avoid resubmission
        header("Location: orders.php?success=1&order_id=$order_id");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders Management - Royal Orbit</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #C41E3A;
            --primary-light: #E84545;
            --secondary: #F5E8C7;
            --accent: #2E8B57;
            --gold: #D4AF37;
            --dark: #222222;
            --light: #FFFFFF;
            --gray: #F5F5F5;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--gray);
            color: var(--dark);
            line-height: 1.6;
            padding: 0;
            margin: 0;
        }

        header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
        }

        .container {
            max-width: 1200px;
            margin: 1rem auto;
            padding: 1rem;
        }

        h2 {
            color: var(--primary);
            margin-bottom: 1rem;
            position: relative;
            display: inline-block;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--gold);
        }

        .order-form {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input, select {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            width: 100%;
            margin-bottom: 0.5rem;
        }

        button, .btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        button:hover, .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(196, 30, 58, 0.3);
        }

        #items {
            margin: 1.5rem 0;
        }

        .item-row {
            background: #f9f9f9;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }

        .menu-thumb {
            height: 50px;
            width: 50px;
            object-fit: cover;
            border-radius: 4px;
        }

        .img-fallback {
            height: 50px;
            width: 50px;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            color: #999;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: var(--primary);
            color: white;
        }

        .order-items li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .actions a {
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .edit-btn {
            background: var(--accent);
        }

        .delete-btn {
            background: #e74c3c;
        }

        .print-btn {
            background: var(--gold);
            color: var(--dark);
        }

        @media (max-width: 768px) {
            .item-row > div {
                flex: 1 1 100%;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <div>Welcome (<?= ucfirst($_SESSION['role']) ?>)</div>
        <div><h1>Royal Orbit - Orders</h1></div>
        <a href="dashboard.php" style="color:white; text-decoration:none;">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
    </header>

    <div class="container">
        <?php if(isset($_GET['delete_id'])): ?>
            <div style="padding:1rem; margin-bottom:1rem; background:#f8d7da; color:#721c24; border-radius:6px;">
                <?php 
                $orderId = (int)$_GET['delete_id'];
                $check = $conn->query("SELECT placed_by FROM orders WHERE id = $orderId")->fetch_assoc();
                echo $check && $check['placed_by'] == $loggedUserId ? 
                    "Order #$orderId deleted successfully." : 
                    "Unauthorized to delete this order.";
                ?>
            </div>
        <?php endif; ?>

        <h2><?= $edit ? "Edit Order #$edit_order_id" : "Place New Order" ?></h2>
        
        <form method="post" class="order-form">
            <input type="hidden" name="edit_order_id" value="<?= $edit ? $edit_order_id : '' ?>">
            <input type="hidden" name="placed_by" value="<?= $loggedUserId ?>">
            
            <div class="form-group">
                <label>Placed By:</label>
                <div><?= $loggedUserName ?></div>
            </div>

            <div class="form-group">
                <label>Order Items:</label>
                <div id="items"></div>
                <button type="button" onclick="addItemRow()" class="btn">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </div>

            <button type="submit" name="submit" class="btn">
                <i class="fas fa-<?= $edit ? 'save' : 'paper-plane' ?>"></i>
                <?= $edit ? "Update Order" : "Submit Order" ?>
            </button>
        </form>

        <div class="table-container">
            <h2>All Orders</h2>
            <form method="get" style="margin-bottom:1rem; display:flex; gap:0.5rem; flex-wrap:wrap;">
                <input type="date" name="filter_date" id="filter_date" 
                       value="<?= isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d') ?>"
                       style="flex:1; min-width:200px;">
                <button type="submit" class="btn">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </form>
            <?php if (isset($_GET['success']) && $_GET['order_id']): ?>
    <div style="padding:1rem; margin-bottom:1rem; background:#d4edda; color:#155724; border-radius:6px;">
        Order #<?= $_GET['order_id'] ?> placed successfully.
    </div>
<?php endif; ?>

<?php if (isset($_GET['updated']) && isset($_GET['edit_id'])): ?>
    <div style="padding:1rem; margin-bottom:1rem; background:#fff3cd; color:#856404; border-radius:6px;">
        Order #<?= $_GET['edit_id'] ?> updated successfully.
    </div>
<?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Placed By</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Items</th>
                        <th>KOT</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');
                    $orders = $conn->prepare("SELECT o.*, u.name AS user FROM orders o JOIN users u ON o.placed_by = u.id WHERE DATE(o.order_date) = ? ORDER BY o.id DESC");
                    $orders->bind_param("s", $filter_date);
                    $orders->execute();
                    $result = $orders->get_result();
                    $sn = 1;
                    while ($o = $result->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= $o['id'] ?></td>
                            <td><?= $o['user'] ?></td>
                            <td><?= date('d M Y h:i A', strtotime($o['order_date'])) ?></td>
                            <td>₹<?= number_format($o['total_amount'], 2) ?></td>
                            <td>
                                <ul style="list-style:none; padding:0;">
                                    <?php
                                    $items = $conn->query("SELECT m.name, m.image, oi.quantity, oi.price 
                                                         FROM order_items oi 
                                                         JOIN menu_items m ON oi.menu_item_id = m.id 
                                                         WHERE oi.order_id = {$o['id']}");
                                    while ($i = $items->fetch_assoc()):
                                        $imagePath = '../uploads/' . $i['image'];
                                        $imageExists = file_exists($imagePath);
                                    ?>
                                        <li>
                                            <?php if($imageExists): ?>
                                                <img src="<?= $imagePath ?>" class="menu-thumb">
                                            <?php else: ?>
                                                <div class="img-fallback">
                                                    <i class="fas fa-utensils"></i>
                                                </div>
                                            <?php endif; ?>
                                            <?= $i['name'] ?> × <?= $i['quantity'] ?> (₹<?= number_format($i['price'], 2) ?>)
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            </td>
                            <td>
                                <a href="kot.php?order_id=<?= $o['id'] ?>" target="_blank" class="print-btn">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                            <td>
                                <?php if(
                                    ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager') ||
                                    ($_SESSION['role'] === 'staff' && $o['placed_by'] == $loggedUserId)
                                ): ?>
                                    <div class="actions">
                                        <a href="orders.php?edit_id=<?= $o['id'] ?>&filter_date=<?= $filter_date ?>" class="edit-btn">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <?php if($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager'): ?>
                                            <a href="orders.php?delete_id=<?= $o['id'] ?>&filter_date=<?= $filter_date ?>"
                                            class="delete-btn"
                                            onclick="return confirm('Delete this order?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let menuItems = {};
        <?php
        $res = $conn->query("SELECT id, name, price, image FROM menu_items");
        while ($row = $res->fetch_assoc()) {
            $img = "../uploads/" . $row['image'];
            $imageExists = file_exists($img);
            echo "menuItems[{$row['id']}] = {
                name: `" . addslashes($row['name']) . "`,
                price: {$row['price']},
                image: `" . $img . "`,
                hasImage: " . ($imageExists ? 'true' : 'false') . "
            };\n";
        }
        ?>

        function addItemRow(id = '', qty = 1, price = '') {
            const itemList = document.getElementById("items");
            const div = document.createElement("div");
            div.className = "item-row";
            const index = itemList.children.length;

            let options = "<option value=''>Select Item</option>";
            for (let mid in menuItems) {
                const selected = (mid == id) ? "selected" : "";
                options += `<option value="${mid}" ${selected}>${menuItems[mid].name}</option>`;
            }

            div.innerHTML = `
                <div style="flex:1; display:flex; align-items:center; gap:0.5rem;">
                    ${id ? (menuItems[id].hasImage ? 
                        `<img id="img-${index}" src="${menuItems[id].image}" class="menu-thumb">` : 
                        `<div class="img-fallback"><i class="fas fa-utensils"></i></div>`) : 
                        `<div class="img-fallback"><i class="fas fa-utensils"></i></div>`}
                    <select name="items[${index}][id]" onchange="updatePrice(this, ${index})" style="flex:1;">
                        ${options}
                    </select>
                </div>
                <div style="flex:1; display:flex; align-items:center; gap:0.5rem;">
                    <input type="number" name="items[${index}][qty]" value="${qty}" min="1" 
                           onchange="updatePrice(this.parentNode.previousElementSibling.firstElementChild.nextElementSibling, ${index})" 
                           style="flex:1;">
                    <input type="text" name="items[${index}][price]" id="price-${index}" 
                           readonly value="${price}" placeholder="Price" style="flex:1;">
                    <button type="button" onclick="this.parentNode.parentNode.remove()" 
                            style="background:#e74c3c; color:white; border:none; padding:0.5rem; border-radius:4px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            itemList.appendChild(div);

            if (id) {
                updatePrice(div.querySelector(`select[name="items[${index}][id]"]`), index);
            }
        }

        function updatePrice(select, index) {
            const qtyInput = select.parentNode.nextElementSibling.firstElementChild;
            const qty = qtyInput.value || 1;
            const id = select.value;
            const imgContainer = select.previousElementSibling;
            
            if (id && menuItems[id]) {
                const price = menuItems[id].price * qty;
                document.getElementById(`price-${index}`).value = price.toFixed(2);
                
                if (menuItems[id].hasImage) {
                    imgContainer.innerHTML = `<img id="img-${index}" src="${menuItems[id].image}" class="menu-thumb">`;
                } else {
                    imgContainer.innerHTML = `<div class="img-fallback"><i class="fas fa-utensils"></i></div>`;
                }
            } else {
                document.getElementById(`price-${index}`).value = '';
                imgContainer.innerHTML = `<div class="img-fallback"><i class="fas fa-utensils"></i></div>`;
            }
        }

        // Initialize form
        <?php
        if ($edit && $edit_order_items) {
            foreach ($edit_order_items as $i) {
                echo "addItemRow({$i['menu_item_id']}, {$i['quantity']}, {$i['price']});\n";
            }
        } else {
            echo "addItemRow();";
        }
        ?>
    </script>
</body>
</html>