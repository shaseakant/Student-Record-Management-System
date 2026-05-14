<?php
date_default_timezone_set('Asia/Kolkata');
include '../db.php';
session_start();

// Admin login check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.html");
    exit();
}

$name = $_SESSION['name'] ?? 'Admin';
$role = $_SESSION['role'] ?? 'admin';

// Date filters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Today's date
$today = date('Y-m-d');

// Orders
$todayOrders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = '$today'")->fetch_assoc()['count'];
$monthlyOrders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE())")->fetch_assoc()['count'];
$filteredOrders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) BETWEEN '$startDate' AND '$endDate'")->fetch_assoc()['count'];

// Income
$todayIncome = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE DATE(order_date) = '$today'")->fetch_assoc()['total'] ?? 0;
$monthlyIncome = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0;
$filteredIncome = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE DATE(order_date) BETWEEN '$startDate' AND '$endDate'")->fetch_assoc()['total'] ?? 0;

// Stock spends
$todaySpends = ($res = $conn->query("SELECT SUM(price * quantity) as total FROM stocks WHERE DATE(timestamp) = '$today'")) ? ($res->fetch_assoc()['total'] ?? 0) : 0;
$monthlySpends = ($res = $conn->query("SELECT SUM(price * quantity) as total FROM stocks WHERE MONTH(timestamp) = MONTH(CURDATE()) AND YEAR(timestamp) = YEAR(CURDATE())")) ? ($res->fetch_assoc()['total'] ?? 0) : 0;
$filteredSpends = ($res = $conn->query("SELECT SUM(price * quantity) as total FROM stocks WHERE DATE(timestamp) BETWEEN '$startDate' AND '$endDate'")) ? ($res->fetch_assoc()['total'] ?? 0) : 0;

// Salary Distribution
$salaryData = $conn->query("SELECT name, salary FROM users");
$salaryNames = [];
$salaryValues = [];
while ($row = $salaryData->fetch_assoc()) {
    $salaryNames[] = $row['name'];
    $salaryValues[] = $row['salary'];
}

// Orders per user
$orderUser = $conn->query("SELECT users.name, COUNT(orders.id) as order_count FROM orders JOIN users ON users.id = orders.placed_by GROUP BY users.name");
$orderNames = [];
$orderCounts = [];
while ($row = $orderUser->fetch_assoc()) {
    $orderNames[] = $row['name'];
    $orderCounts[] = $row['order_count'];
}

// Attendance
$attUser = $conn->query("SELECT users.name,
    SUM(attendance.status = 'Present') as present,
    SUM(attendance.status = 'Absent') as absent,
    SUM(attendance.status = 'Leave') as leave_count
    FROM attendance
    JOIN users ON users.id = attendance.user_id
    GROUP BY users.name");

$attLabels = [];
$presentCounts = [];
$absentCounts = [];
$leaveCounts = [];
while ($row = $attUser->fetch_assoc()) {
    $attLabels[] = $row['name'];
    $presentCounts[] = $row['present'];
    $absentCounts[] = $row['absent'];
    $leaveCounts[] = $row['leave_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Report Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

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
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        h2, h3 {
            color: var(--primary);
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

        .section {
            background-color: var(--light);
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            border-radius: 8px;
            text-align: center
        }

        form {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            justify-content:center;
        }

        input[type="date"], button {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            border: 1px solid var(--primary);
            transition: var(--transition);
        }

        button {
            background-color: var(--primary);
            color: white;
            cursor: pointer;
        }

        button:hover {
            background-color: var(--primary-light);
        }

        .chart {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-around;
        }

        .chart-box {
            background: var(--light);
            padding: 20px;
            box-shadow: var(--shadow);
            border-radius: 10px;
            flex: 1 1 30%;
            min-width: 300px;
        }

        canvas {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <header>
        <div>Welcome (<?= ucfirst($role) ?>)</div>
        <div><h1>Royal Orbit - Reports</h1></div>
        <div><a href="dashboard.php" class="btn btn-outline" style="color:white; background-color: var(--primary-light); border-color: ;">
            <i class="fas fa-arrow-left"></i> Dashboard</a></div>
    </header>
    <form method="get" class="section">
        <label>Start Date: <input type="date" name="start_date" value="<?= $startDate ?>"></label>
        <label>End Date: <input type="date" name="end_date" value="<?= $endDate ?>"></label>
        <button type="submit">Filter</button>
    </form>

    <div class="section">
        <h3>Orders Report</h3>
        <p><strong>Today's Orders:</strong> <?= $todayOrders ?></p>
        <p><strong>Monthly Orders:</strong> <?= $monthlyOrders ?></p>
        <p><strong>Filtered Orders (<?= $startDate ?> to <?= $endDate ?>):</strong> <?= $filteredOrders ?></p>
    </div>

    <div class="section">
        <h3>Income Report</h3>
        <p><strong>Today's Income:</strong> ₹<?= $todayIncome ?></p>
        <p><strong>Monthly Income:</strong> ₹<?= $monthlyIncome ?></p>
        <p><strong>Filtered Income (<?= $startDate ?> to <?= $endDate ?>):</strong> ₹<?= $filteredIncome ?></p>
    </div>

    <div class="section">
        <h3>Stock Spend Report</h3>
        <p><strong>Today's Spend:</strong> ₹<?= $todaySpends ?></p>
        <p><strong>Monthly Spend:</strong> ₹<?= $monthlySpends ?></p>
        <p><strong>Filtered Spend (<?= $startDate ?> to <?= $endDate ?>):</strong> ₹<?= $filteredSpends ?></p>
    </div>

    <div class="chart">
        <div class="chart-box">
            <h3>Salary Distribution</h3>
            <canvas id="salaryChart"></canvas>
        </div>
        <div class="chart-box">
            <h3>Orders Per User</h3>
            <canvas id="orderChart"></canvas>
        </div>
        <div class="chart-box">
            <h3>Attendance Per User</h3>
            <canvas id="attendanceChart"></canvas>
        </div>
    </div>

    <script>
        new Chart(document.getElementById('salaryChart'), {
            type: 'pie',
            data: {
                labels: <?= json_encode($salaryNames) ?>,
                datasets: [{
                    data: <?= json_encode($salaryValues) ?>,
                    backgroundColor: ['#C41E3A', '#E84545', '#F5E8C7', '#2E8B57', '#D4AF37', '#222222'],
                }]
            }
        });

        new Chart(document.getElementById('orderChart'), {
            type: 'pie',
            data: {
                labels: <?= json_encode($orderNames) ?>,
                datasets: [{
                    data: <?= json_encode($orderCounts) ?>,
                    backgroundColor: ['#E84545', '#C41E3A', '#F5E8C7', '#2E8B57', '#D4AF37', '#222222'],
                }]
            }
        });

        new Chart(document.getElementById('attendanceChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($attLabels) ?>,
                datasets: [
                    {
                        label: 'Present',
                        data: <?= json_encode($presentCounts) ?>,
                        backgroundColor: '#2E8B57'
                    },
                    {
                        label: 'Absent',
                        data: <?= json_encode($absentCounts) ?>,
                        backgroundColor: '#C41E3A'
                    },
                    {
                        label: 'Leave',
                        data: <?= json_encode($leaveCounts) ?>,
                        backgroundColor: '#D4AF37'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Attendance Report' }
                }
            }
        });
    </script>
</body>
</html>
