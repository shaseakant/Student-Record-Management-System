<?php
session_start();
include '../db.php';

// Access control
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

$logged_user = $_SESSION['username'];
$view = $_GET['view'] ?? 'available';
$allowed_views = ['in', 'out', 'available', 'history'];
if (!in_array($view, $allowed_views)) $view = 'available';
$search = trim($_GET['search'] ?? '');
$search_sql = $search ? " AND item_name LIKE '%" . $conn->real_escape_string($search) . "%'" : "";

// Available items
$available_items = [];
if (in_array($view, ['available', 'out'])) {
    $res = $conn->query("
        SELECT item_name, category, unit,
        SUM(CASE WHEN transaction_type='in' THEN quantity ELSE 0 END)
        - SUM(CASE WHEN transaction_type='out' THEN quantity ELSE 0 END) AS total_quantity
        FROM stocks WHERE 1=1 $search_sql
        GROUP BY item_name, category, unit
        HAVING total_quantity > 0
        ORDER BY category, item_name
    ");
    while ($row = $res->fetch_assoc()) {
        $available_items[$row['category']][] = $row;
    }
}

// Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transaction'])) {
    $t = $_POST['transaction_type'];
    $done_by = $logged_user;
    $ok = false;

    if ($t === 'in') {
        $item = trim($_POST['item_name_in'] ?? '');
        $unit = trim($_POST['unit_in'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $cat = $_POST['category_in'] ?? '';
        $qty = (int)($_POST['quantity'] ?? 0);
        if ($item && $unit && $cat && $qty > 0) $ok = true;
    } else {
        $cat = $_POST['out_cat'] ?? '';
        $item = trim($_POST['item_name_out'] ?? '');
        $qty = (int)($_POST['quantity_out'] ?? 0);
        if ($item && $cat && $qty > 0) {
            $res = $conn->query("
                SELECT unit,
                SUM(CASE WHEN transaction_type='in' THEN quantity ELSE 0 END)
                - SUM(CASE WHEN transaction_type='out' THEN quantity ELSE 0 END) AS avail
                FROM stocks WHERE item_name='" . $conn->real_escape_string($item) . "'
            ");
            $r = $res->fetch_assoc();
            if ($r && $qty <= (int)$r['avail']) {
                $unit = $r['unit'];
                $price = 0;
                $ok = true;
            }
        }
    }

    if ($ok) {
        $stmt = $conn->prepare("
            INSERT INTO stocks
            (item_name, category, quantity, unit, price, transaction_type, done_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssisdss", $item, $cat, $qty, $unit, $price, $t, $done_by);
        $stmt->execute();
        $stmt->close();
        header("Location: stocks.php?view=$t");
        exit;
    } else {
        $error = "Please fill all required fields correctly.";
    }
}

// Fetch stock data
if ($view === 'available') {
    $stocks_data = [];
    foreach ($available_items as $catItems) {
        foreach ($catItems as $i) $stocks_data[] = $i;
    }
} else {
    $qtype = $view === 'history' ? "" : "transaction_type='$view'";
    $stocks_data = $conn->query("SELECT * FROM stocks WHERE 1=1"
        . ($qtype ? " AND $qtype" : "")
        . $search_sql . " ORDER BY timestamp DESC");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Stocks</title>
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

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: var(--gray);
  margin: 0;
  padding: 0;
  color: var(--dark);
}

header {
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: var(--light);
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: var(--shadow);
}

header h1 {
  margin: 0;
  font-size: 1.5rem;
}

header a {
  color: var(--light);
  text-decoration: none;
  font-weight: bold;
  transition: var(--transition);
}

header a:hover {
  text-decoration: underline;
  color: var(--gold);
}

.nav {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 2rem;
  gap: 10px;
  background-color: var(--light);
  box-shadow: var(--shadow);
}

.nav form {
  display: flex;
  align-items: center;
  gap: 10px;
}

.nav input[type="text"],
.nav select,
.nav button {
  padding: 8px 12px;
  font-size: 1rem;
  border: 1px solid var(--primary);
  border-radius: 5px;
  outline: none;
  transition: var(--transition);
}

.nav button {
  background-color: var(--primary);
  color: var(--light);
  cursor: pointer;
}

.nav button:hover {
  background-color: var(--primary-light);
}

.form-section {
  max-width: 800px;
  margin: 2rem auto;
  padding: 2rem;
  background-color: var(--light);
  border-radius: 10px;
  box-shadow: var(--shadow);
}

.form-section form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.form-section label {
  font-weight: 600;
}

.form-section input,
.form-section select {
  padding: 10px;
  font-size: 1rem;
  border: 1px solid #ccc;
  border-radius: 6px;
  width: 100%;
  box-sizing: border-box;
}

.form-section button {
  grid-column: span 2;
  padding: 12px;
  font-size: 1rem;
  background-color: var(--accent);
  color: var(--light);
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: var(--transition);
}

.form-section button:hover {
  background-color: #256d47;
}

table {
  width: 95%;
  margin: 2rem auto;
  border-collapse: collapse;
  background-color: var(--light);
  box-shadow: var(--shadow);
  border-radius: 10px;
  overflow: hidden;
}

th, td {
  padding: 14px 18px;
  border-bottom: 1px solid #ddd;
  text-align: left;
}

th {
  background-color: var(--primary);
  color: var(--light);
  font-weight: bold;
  text-transform: uppercase;
}

td {
  background-color: #fff;
}

tr:hover td {
  background-color: #fdf2f2;
}

.error {
  max-width: 800px;
  margin: 1rem auto;
  color: var(--primary);
  font-weight: bold;
  padding: 0.75rem 1.25rem;
  background: #ffeaea;
  border-left: 5px solid var(--primary);
  border-radius: 6px;
}
</style>

</head>
<body>

<header>
  <h1>📦 Royal Orbit - Stock Management</h1>
  <a href="dashboard.php">← Dashboard</a>
</header>

<div class="nav">
  <form method="GET">
    <select name="view" onchange="this.form.submit()">
      <option value="in" <?= $view === 'in' ? 'selected' : '' ?>>Stock In</option>
      <option value="out" <?= $view === 'out' ? 'selected' : '' ?>>Stock Out</option>
      <option value="available" <?= $view === 'available' ? 'selected' : '' ?>>Available</option>
      <option value="history" <?= $view === 'history' ? 'selected' : '' ?>>History</option>
    </select>
  </form>

  <form method="GET">
    <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
    <input type="text" name="search" placeholder="Search item..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">🔍</button>
    <?php if ($search): ?>
      <a href="stocks.php?view=<?= $view ?>">Clear</a>
    <?php endif; ?>
  </form>
</div>

<?php if (isset($error)): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (in_array($view, ['in', 'out'])): ?>
  <div class="form-section">
    <form method="POST">
      <input type="hidden" name="transaction_type" value="<?= $view ?>">

      <?php if ($view === 'in'): ?>
        <label>Category:</label>
        <select name="category_in">
          <option>Daily Items</option>
          <option>Groceries</option>
          <option>Container</option>
          <option>Other Items</option>
        </select>

        <label>Item Name:</label>
        <input type="text" name="item_name_in" required>

        <label>Quantity:</label>
        <input type="number" name="quantity" min="1" required>

        <label>Unit:</label>
        <select name="unit_in">
          <option value="">-- Select Unit --</option>
          <option value="Kg">Kg</option>
          <option value="Litre">Litre</option>
          <option value="Piece">Piece</option>
          <option value="Other">Other</option>
        </select>

        <label>Price (optional):</label>
        <input type="number" step="0.01" name="price">

      <?php else: ?>
        <label>Category:</label>
        <select id="category_out" onchange="updateOutItems()">
          <option value="">-- Select Category --</option>
          <?php foreach (array_keys($available_items) as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="hidden" name="out_cat" id="category_out_hidden">

        <label>Item:</label>
        <select name="item_name_out" id="item_name_out"></select>

        <label>Quantity:</label>
        <input type="number" name="quantity_out" min="1">

        <div id="display_unit">-</div>
      <?php endif; ?>

      <button type="submit" name="submit_transaction">✅ Submit</button>
    </form>
  </div>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <?php if ($view === 'available'): ?>
        <th>Item</th><th>Category</th><th>Unit</th><th>Qty</th>
      <?php else: ?>
        <th>Time</th><th>Item</th><th>Category</th><th>Qty</th><th>Unit</th><th>Price</th><th>Type</th><th>By</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php
    $none = true;
    if ($view === 'available') {
      foreach ($stocks_data as $r) {
        echo "<tr><td>{$r['item_name']}</td><td>{$r['category']}</td><td>{$r['unit']}</td><td>{$r['total_quantity']}</td></tr>";
        $none = false;
      }
    } elseif ($stocks_data && $stocks_data->num_rows) {
      while ($r = $stocks_data->fetch_assoc()) {
        echo "<tr>
          <td>{$r['timestamp']}</td>
          <td>{$r['item_name']}</td>
          <td>{$r['category']}</td>
          <td>{$r['quantity']}</td>
          <td>{$r['unit']}</td>
          <td>" . number_format($r['price'], 2) . "</td>
          <td>{$r['transaction_type']}</td>
          <td>{$r['done_by']}</td>
        </tr>";
        $none = false;
      }
    }
    if ($none) echo "<tr><td colspan='8' style='text-align:center;'>No records found.</td></tr>";
    ?>
  </tbody>
</table>

<script>
function updateOutItems() {
  const cat = document.getElementById('category_out').value;
  const items = <?= json_encode($available_items) ?>[cat] || [];
  const sel = document.getElementById('item_name_out');
  sel.innerHTML = '<option value="">-- Select item --</option>';
  items.forEach(i => {
    const opt = document.createElement('option');
    opt.value = i.item_name;
    opt.textContent = `${i.item_name} (Qty: ${i.total_quantity} ${i.unit})`;
    opt.setAttribute('data-unit', i.unit);
    opt.setAttribute('data-qty', i.total_quantity);
    sel.append(opt);
  });
  document.getElementById('category_out_hidden').value = cat;
  document.getElementById('display_unit').textContent = '-';
}

document.getElementById('item_name_out')?.addEventListener('change', function () {
  const sel = this.options[this.selectedIndex];
  document.getElementById('display_unit').textContent = sel.getAttribute('data-unit') || '-';
});
</script>

</body>
</html>
