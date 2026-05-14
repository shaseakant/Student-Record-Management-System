<?php
session_start();
include '../db.php';
date_default_timezone_set('Asia/Kolkata');

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'User';

$loggedUserId = $_SESSION['user_id'];
$loggedRole = $_SESSION['role'];

// Only Admin or Manager can mark attendance
$role = $_SESSION['role'];
if (!in_array($role, ['admin', 'manager'])) {
    die("Access denied. Only Admin or Manager can access this page.");
}

$today = date('Y-m-d');
$currentMonth = date('m');
$currentYear = date('Y');

// Handle attendance marking
if (isset($_POST['submit_attendance'])) {
    foreach ($_POST['attendance'] as $userId => $status) {
        // Check if already marked today
        $check = $conn->prepare("SELECT id FROM attendance WHERE user_id=? AND date=?");
        $check->bind_param("is", $userId, $today);
        $check->execute();
        $check->store_result();

        if ($check->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO attendance (user_id, date, status, marked_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $userId, $today, $status, $_SESSION['user_id']);
            $stmt->execute();
        }
    }
    header("Location: attendance.php?success=1");
    exit();
}

// Admin editing attendance
if (isset($_POST['att_id']) && isset($_POST['edit_status']) && $role === 'admin') {
    $att_id = $_POST['att_id'];
    $edit_status = $_POST['edit_status'];

    $stmt = $conn->prepare("UPDATE attendance SET status=? WHERE id=?");
    $stmt->bind_param("si", $edit_status, $att_id);
    $stmt->execute();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance - Royal Orbit</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            padding: 20px;
        }

        .nav {
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }


        h2, h3 {
            color: var(--primary);
            margin-bottom: 15px;
        }

        h2 {
            font-size: 28px;
        }

        h3 {
            font-size: 22px;
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 8px;
            margin-top: 30px;
        }

        .card {
            background-color: var(--light);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: var(--shadow);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--primary);
            color: var(--light);
            font-weight: 600;
        }

        tr:nth-child(even) {
            background-color: var(--secondary);
        }

        tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        select, button {
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
        }

        select {
            width: 100%;
            max-width: 150px;
        }

        button {
            background-color: var(--primary);
            color: var(--light);
            border: none;
            cursor: pointer;
            font-weight: 500;
            margin-top: 10px;
        }

        button:hover {
            background-color: var(--primary-light);
            transform: translateY(-2px);
        }

        .status-present {
            color: var(--accent);
            font-weight: 500;
        }

        .status-absent {
            color: var(--primary-light);
            font-weight: 500;
        }

        .status-leave {
            color: var(--gold);
            font-weight: 500;
        }

        .success-message {
            background-color: var(--accent);
            color: var(--light);
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: inline-block;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            h2 {
                font-size: 24px;
                margin-bottom: 15px;
            }

            .card {
                padding: 15px;
            }

            table {
                display: block;
                overflow-x: auto;
            }

            th, td {
                padding: 8px 10px;
                font-size: 14px;
            }

            select {
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            h2 {
                font-size: 20px;
            }

            h3 {
                font-size: 18px;
            }

            th, td {
                padding: 6px 8px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="nav">
                <div class="user-name">
                    <div>Welcome (<?= ucfirst($role) ?>)</span></div>
                    <div id="title"><h1 style="color:white;">Royal Orbit - Attendance Management</h1></div>
                    <div><a href="dashboard.php" class="btn btn-outline" style="color:white; background-color: var(--primary-light); border-color: white;">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a></div>
                </div>
    </div>
    <div class="container">
        <div class="header">
            <h2>Attendance for <?= date("F Y") ?></h2>
            <?php if(isset($_GET['success'])): ?>
                <div class="success-message">Attendance marked successfully!</div>
            <?php endif; ?>
        </div>

        <!-- Mark today's attendance -->
        <div class="card">
            <form method="post">
                <h3>Mark Attendance (<?= $today ?>)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $users = $conn->query("SELECT id, name FROM users");
                        while ($u = $users->fetch_assoc()) {
                            $userId = $u['id'];

                            // Check if attendance already marked
                            $already = $conn->query("SELECT status FROM attendance WHERE user_id=$userId AND date='$today'");
                            $row = $already->fetch_assoc();
                            $status = $row['status'] ?? '';
                            echo "<tr>
                                    <td>{$u['name']}</td>
                                    <td>" . ($status ? "<span class='status-".strtolower($status)."'>$status</span>" : "
                                        <select name='attendance[{$userId}]'>
                                            <option value='Present'>Present</option>
                                            <option value='Absent'>Absent</option>
                                            <option value='Leave'>Leave</option>
                                        </select>") . "</td>
                                </tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <br>
                <button type="submit" name="submit_attendance">Submit Attendance</button>
            </form>
        </div>

        <!-- Monthly Chart -->
        <div class="card">
            <h3>Monthly Attendance Chart</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <?php
                            for ($d = 1; $d <= 31; $d++) {
                                $day = str_pad($d, 2, '0', STR_PAD_LEFT);
                                echo "<th>$day</th>";
                            }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $users = $conn->query("SELECT id, name FROM users");
                        while ($user = $users->fetch_assoc()) {
                            $uid = $user['id'];
                            echo "<tr><td>{$user['name']}</td>";
                            for ($d = 1; $d <= 31; $d++) {
                                $day = str_pad($d, 2, '0', STR_PAD_LEFT);
                                $fullDate = "$currentYear-$currentMonth-$day";
                                $res = $conn->query("SELECT id, status FROM attendance WHERE user_id=$uid AND date='$fullDate'");
                                $data = $res->fetch_assoc();

                                if ($data) {
                                    $attId = $data['id'];
                                    $status = $data['status'];
                                    $statusClass = strtolower($status);

                                    if ($role === 'admin') {
                                        // Admin can edit
                                        echo "<td>
                                            <form method='post' style='display:inline-block;'>
                                                <input type='hidden' name='att_id' value='$attId'>
                                                <select name='edit_status' onchange='this.form.submit()'>
                                                    <option value='Present' " . ($status == 'Present' ? 'selected' : '') . ">P</option>
                                                    <option value='Absent' " . ($status == 'Absent' ? 'selected' : '') . ">A</option>
                                                    <option value='Leave' " . ($status == 'Leave' ? 'selected' : '') . ">L</option>
                                                </select>
                                            </form>
                                        </td>";
                                    } else {
                                        // Manager just views
                                        echo "<td><span class='status-$statusClass'>".substr($status, 0, 1)."</span></td>";
                                    }

                                } else {
                                    echo "<td>-</td>";
                                }
                            }
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>