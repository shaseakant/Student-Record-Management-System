<?php
date_default_timezone_set('Asia/Kolkata');
include '../db.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'User';
$name = $_SESSION['name'] ?? 'User';
$role = $_SESSION['role'] ?? 'user';


$loggedUserId = $_SESSION['user_id'];
$loggedUserRole = $_SESSION['role'];
$loggedUserName = $_SESSION['username'];

$currentYear = date('Y');
$currentMonth = date('m');
$currentMonthName = date('F');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Report - <?= htmlspecialchars($loggedUserName) ?></title>
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
      padding: 0;
    }

    header {
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      color: white;
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: var(--shadow);
    }

    header h1 {
      font-size: 1.5rem;
    }

    a {
      color: var(--gold);
      text-decoration: none;
      font-weight: bold;
    }

    .container {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 1rem;
    }

    .card {
      background-color: var(--light);
      padding: 1.5rem;
      border-radius: 8px;
      box-shadow: var(--shadow);
      margin: 1.5rem;
    }

    h3 {
      color: var(--primary);
      margin-bottom: 1rem;
      font-size: 1.25rem;
      border-bottom: 2px solid var(--gold);
      padding-bottom: 5px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
      font-size: 0.95rem;
    }

    th, td {
      border: 1px solid #ddd;
      padding: 0.6rem;
      text-align: center;
    }

    th {
      background-color: var(--primary);
      color: white;
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    tr:hover {
      background-color: #f1f1f1;
    }

    .status-P {
      color: green;
      font-weight: bold;
    }

    .status-A {
      color: red;
      font-weight: bold;
    }

    .status-L {
      color: orange;
      font-weight: bold;
    }

    @media (max-width: 768px) {
      table, thead, tbody, th, td, tr {
        font-size: 0.75rem;
      }
    }
  </style>
</head>
<body>

<header>
  <div>Welcome (<?= ucfirst($role) ?>)</div>
  <h1>Royal Orbit - User Report</h1>
  <div><a href="dashboard.php">&larr; Dashboard</a></div>
</header>

<div class="container">

  <!-- Salary Table -->
  <div class="card">
    <h3>Monthly Salary - <?= htmlspecialchars($currentMonthName) ?> <?= $currentYear ?></h3>
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Amount (₹)</th>
          <th>Updated By</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $salaryQuery = $conn->query("
          SELECT sp.amount, sp.payment_date, u.name AS paid_by_name
          FROM salary_payments sp
          LEFT JOIN users u ON sp.paid_by = u.id
          WHERE sp.user_id = $loggedUserId AND MONTH(sp.payment_date) = $currentMonth AND YEAR(sp.payment_date) = $currentYear
          ORDER BY sp.payment_date DESC
        ");
        if ($salaryQuery->num_rows > 0) {
          while ($row = $salaryQuery->fetch_assoc()) {
            echo "<tr>
              <td>" . htmlspecialchars($row['payment_date']) . "</td>
              <td>" . number_format($row['amount'], 2) . "</td>
              <td>" . htmlspecialchars($row['paid_by_name']) . "</td>
            </tr>";
          }
        } else {
          echo "<tr><td colspan='3'>No salary paid this month.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>

  <!-- Attendance Table -->
  <div class="card">
    <h3><?= htmlspecialchars($loggedUserName) ?> - Attendance Overview (<?= $currentMonthName ?> <?= $currentYear ?>)</h3>
    <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th>User</th>
            <?php
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
            for ($d = 1; $d <= $daysInMonth; $d++) {
              echo "<th>" . str_pad($d, 2, '0', STR_PAD_LEFT) . "</th>";
            }
            ?>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?= htmlspecialchars($loggedUserName) ?></td>
            <?php
            for ($d = 1; $d <= $daysInMonth; $d++) {
              $day = str_pad($d, 2, '0', STR_PAD_LEFT);
              $fullDate = "$currentYear-$currentMonth-$day";
              $res = $conn->query("SELECT status FROM attendance WHERE user_id=$loggedUserId AND date='$fullDate'");
              $status = $res->fetch_assoc()['status'] ?? '-';

              switch ($status) {
                case 'Present':  echo "<td class='status-P'>✅</td>"; break;
                case 'Absent':   echo "<td class='status-A'>❌</td>"; break;
                case 'Leave':    echo "<td class='status-L'>🟡</td>"; break;
                default:         echo "<td>-</td>";
              }
            }
            ?>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>
</body>
</html>
