<?php
session_start();
include '../db.php';

// Check login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    die("Access Denied. Please <a href='../login.php'>login</a>.");
}

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'User';

$loggedUserId = $_SESSION['user_id'];
$loggedRole = $_SESSION['role'];

// Handle salary payment
if (isset($_POST['create']) && ($loggedRole == 'admin' || $loggedRole == 'manager')) {
    $user_id = $_POST['user_id'];
    $amount = $_POST['amount'];
    $payment_date = $_POST['payment_date'];
    $paid_by = $_POST['paid_by'];

    $stmt = $conn->prepare("INSERT INTO salary_payments (user_id, amount, payment_date, paid_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idsi", $user_id, $amount, $payment_date, $paid_by);
    $stmt->execute();
    header("Location: salary.php");
    exit();
}

// Handle Update (Admin only)
if (isset($_POST['update']) && $loggedRole === 'admin') {
    $id = $_POST['id'];
    $user_id = $_POST['user_id'];
    $amount = $_POST['amount'];
    $payment_date = $_POST['payment_date'];
    $paid_by = $_POST['paid_by'];

    $stmt = $conn->prepare("UPDATE salary_payments SET user_id=?, amount=?, payment_date=?, paid_by=? WHERE id=?");
    $stmt->bind_param("idsii", $user_id, $amount, $payment_date, $paid_by, $id);
    $stmt->execute();
    header("Location: salary.php");
    exit();
}

// Handle Delete (Admin only)
if (isset($_GET['delete']) && $loggedRole === 'admin') {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM salary_payments WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: salary.php");
    exit();
}


// Filter variables
$filter_user = $_GET['user_id'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salary Management - Royal Orbit</title>
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
            --gray: #f8f9fa;
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
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
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

        .user-name{
            width:100%;
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        h1, h2, h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            position: relative;
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

        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-row > * {
            flex: 1;
            min-width: 200px;
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
            transition: var(--transition);
        }

        input:focus, select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(196, 30, 58, 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(196, 30, 58, 0.3);
        }

        .btn-secondary {
            background: var(--accent);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: rgba(196, 30, 58, 0.1);
        }

        .paid {
            color: var(--accent);
            font-weight: 500;
        }

        .unpaid {
            color: #e74c3c;
            font-weight: 500;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .table-container {
            overflow-x: auto;
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 1rem;
            margin-bottom: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: var(--primary);
            color: white;
            position: sticky;
            top: 0;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .payment-form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }

        .payment-form input, .payment-form select {
            flex: 1;
            min-width: 120px;
        }

        .user-info {
            display: flex;
            align-items: center;
        
            
        }

        .user-name {
            font-weight: 600;
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .user-info {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .payment-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .payment-form button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
                <div class="user-name">
                    <div>Welcome (<?= ucfirst($role) ?>)</span></div>
                    <div id="title"><h1 style="color:white;">Royal Orbit - Salary Management</h1></div>
                    <div><a href="dashboard.php" class="btn btn-outline" style="color:white; background-color: var(--primary-light); border-color: ;">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a></div>
                </div>
    </header>

    <div class="container">
        <?php if ($loggedRole === 'admin' || $loggedRole === 'manager'): ?>
        <div class="card">
            <h2>Filter Salary Records</h2>
            <form method="get" class="filter-form">
                <div class="filter-group">
                    <label for="user_id">Employee</label>
                    <select id="user_id" name="user_id">
                        <option value="">All Employees</option>
                        <?php
                        $users_q = $conn->query("SELECT id, name FROM users WHERE role IN ('manager', 'staff')");
                        while ($u = $users_q->fetch_assoc()) {
                            $selected = ($filter_user == $u['id']) ? 'selected' : '';
                            echo "<option value='{$u['id']}' $selected>{$u['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="from_date">From Date</label>
                    <input type="date" id="from_date" name="from_date" value="<?= $from_date ?>">
                </div>
                
                <div class="filter-group">
                    <label for="to_date">To Date</label>
                    <input type="date" id="to_date" name="to_date" value="<?= $to_date ?>">
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                    <?php if ($filter_user || $from_date || $to_date): ?>
                    <a href="salary.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Clear
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="table-container">
                <h2>Salary Overview</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Role</th>
                            <th>Monthly Salary</th>
                            <th>Paid This Month</th>
                            <th>Unpaid</th>
                            <?php if ($loggedRole === 'admin' || $loggedRole === 'manager'): ?>
                            <th>Pay Salary</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where = ($loggedRole === 'admin' || $loggedRole === 'manager') 
                            ? "WHERE role IN ('manager','staff')" 
                            : "WHERE id = $loggedUserId";

                        $users = $conn->query("SELECT id, name, role, salary FROM users $where");

                        while ($user = $users->fetch_assoc()):
                            $uid = $user['id'];
                            $monthly_salary = $user['salary'];
                            $user_name = $user['name'];

                            // This month's range
                            $first_day = date('Y-m-01');
                            $last_day  = date('Y-m-t');

                            $sql = "SELECT SUM(amount) as total_paid 
                                    FROM salary_payments 
                                    WHERE user_id = $uid AND payment_date BETWEEN '$first_day' AND '$last_day'";
                            $res = $conn->query($sql)->fetch_assoc();
                            $paid_this_month = $res['total_paid'] ?? 0;

                            $unpaid = max($monthly_salary - $paid_this_month, 0);
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($user_name) ?></td>
                                <td><?= ucfirst($user['role']) ?></td>
                                <td>₹<?= number_format($monthly_salary, 2) ?></td>
                                <td class="paid">₹<?= number_format($paid_this_month, 2) ?></td>
                                <td class="unpaid">₹<?= number_format($unpaid, 2) ?></td>
                                <?php if ($loggedRole === 'admin' || $loggedRole === 'manager'): ?>
                                <td>
                                    <form method="post" class="payment-form">
                                        <input type="hidden" name="user_id" value="<?= $uid ?>">
                                        <input type="number" name="amount" step="0.01" placeholder="Amount" required>
                                        <input type="date" name="payment_date" required>
                                        <select name="paid_by" required>
                                            <option value="">Paid By</option>
                                            <?php
                                            $admins = $conn->query("SELECT id, name FROM users WHERE role IN ('admin','manager')");
                                            while ($a = $admins->fetch_assoc()) {
                                                echo "<option value='{$a['id']}'>{$a['name']}</option>";
                                            }
                                            ?>
                                        </select>
                                        <button type="submit" name="create" class="btn btn-primary">
                                            <i class="fas fa-rupee-sign"></i> Pay
                                        </button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="table-container">
                <h2>Payment History</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Amount</th>
                            <th>Payment Date</th>
                            <th>Paid By</th>
                            <?php if ($loggedRole === 'admin'): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $history_sql = "SELECT sp.*, u.name AS user_name, admin.name AS paid_by 
                                        FROM salary_payments sp
                                        JOIN users u ON u.id = sp.user_id
                                        JOIN users admin ON admin.id = sp.paid_by
                                        WHERE 1=1";

                        if ($loggedRole !== 'admin' && $loggedRole !== 'manager') {
                            $history_sql .= " AND sp.user_id = $loggedUserId";
                        } elseif ($filter_user) {
                            $history_sql .= " AND sp.user_id = $filter_user";
                        }

                        if ($from_date && $to_date) {
                            $history_sql .= " AND sp.payment_date BETWEEN '$from_date' AND '$to_date'";
                        }

                        $history_sql .= " ORDER BY sp.payment_date DESC";

                        $history = $conn->query($history_sql);
                        while ($row = $history->fetch_assoc()):
                        ?>
                           <tr>
                                <form method="post">
                                    <td>
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <select name="user_id" required>
                                            <?php
                                            $users_q = $conn->query("SELECT id, name FROM users WHERE role IN ('manager', 'staff')");
                                            while ($u = $users_q->fetch_assoc()) {
                                                $selected = ($u['id'] == $row['user_id']) ? 'selected' : '';
                                                echo "<option value='{$u['id']}' $selected>{$u['name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="amount" step="0.01" value="<?= $row['amount'] ?>" required>
                                    </td>
                                    <td>
                                        <input type="date" name="payment_date" value="<?= $row['payment_date'] ?>" required>
                                    </td>
                                    <td>
                                        <select name="paid_by" required>
                                            <?php
                                            $admins = $conn->query("SELECT id, name FROM users WHERE role IN ('admin', 'manager')");
                                            while ($a = $admins->fetch_assoc()) {
                                                $selected = ($a['id'] == $row['paid_by']) ? 'selected' : '';
                                                echo "<option value='{$a['id']}' $selected>{$a['name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </td>

                                    <?php if ($loggedRole === 'admin'): ?>
                                    <td>
                                        <button type="submit" name="update" class="btn btn-secondary">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <a href="salary.php?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this payment?')" class="btn btn-outline">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                    <?php endif; ?>
                                </form>
                            </tr>
 
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>