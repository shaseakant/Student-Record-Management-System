<?php
include '../db.php';
session_start();

// Get session user data

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'User';

// Handle Create
if (isset($_POST['create'])) {
    $name_new = $_POST['name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role_new = $_POST['role'];
    $salary = $_POST['salary'];

    $stmt = $conn->prepare("INSERT INTO users (name, username, password, role, salary) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssd", $name_new, $username, $password, $role_new, $salary);
    if (!$stmt->execute()) {
        die("Create failed: " . $stmt->error);
    }
    header("Location: users.php");
    exit;
}

// Handle Update
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name_up = $_POST['name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role_up = $_POST['role'];
    $salary = $_POST['salary'];

    $stmt = $conn->prepare("UPDATE users SET name=?, username=?, password=?, role=?, salary=? WHERE id=?");
    $stmt->bind_param("ssssdi", $name_up, $username, $password, $role_up, $salary, $id);
    if (!$stmt->execute()) {
        die("Update failed: " . $stmt->error);
    }
    header("Location: users.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        die("Delete failed: " . $stmt->error);
    }
    header("Location: users.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Royal Orbit</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #C41E3A;    /* Royal Orbit red */
            --primary-light: #E84545;
            --secondary: #F5E8C7;  /* Cream */
            --accent: #2E8B57;     /* Leaf green */
            --gold: #D4AF37;       /* Premium gold */
            --dark: #222222;
            --light: #FFFFFF;
            --gray: #F5F5F5;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
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

        /* Header Styles */
        header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--light);
            padding: 1.2rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        header div:first-child {
            font-size: 1.2rem;
            font-weight: 600;
        }

        header a {
            color: var(--light);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: rgba(255, 255, 255, 0.1);
        }

        header a:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        h2 {
            color: var(--primary);
            font-size: 1.8rem;
            position: relative;
            display: inline-block;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--gold);
        }

        h3 {
            color: var(--dark);
            margin: 1.5rem 0 1rem;
            font-size: 1.3rem;
        }

        /* Form Styles */
        form {
            background: var(--light);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            transition: var(--transition);
        }

        form:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .form-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        input, select {
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: var(--transition);
            flex: 1;
            min-width: 200px;
        }

        input:focus, select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(196, 30, 58, 0.1);
        }

        button {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            padding: 0.8rem 1.8rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(196, 30, 58, 0.3);
        }

        button i {
            font-size: 0.9rem;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            background: var(--light);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 1rem;
            margin-top: 1.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: var(--primary);
            color: white;
            font-weight: 500;
            position: sticky;
            top: 0;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .update-btn {
            background-color: var(--accent);
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .delete-btn {
            background-color: #e74c3c;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .form-group {
                flex-direction: column;
                gap: 0.8rem;
            }
            
            input, select {
                width: 100%;
            }
            
            th, td {
                padding: 0.8rem;
                font-size: 0.9rem;
            }
            
            .actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            header {
                flex-direction: column;
                gap: 0.8rem;
                padding: 1rem;
                text-align: center;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div>Welcome (<?= ucfirst($role) ?>)</div>
        <div><h1>Royal Orbit - Users</h1></div>
        <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </header>

    <div class="container">
        <div class="header">
            <h2>Users Management</h2>
        </div>

        <!-- Create User Form -->
        <form method="post">
            <h3>Create New User</h3>
            <div class="form-group">
                <input type="text" name="name" placeholder="Name" required>
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <select name="role" required>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="staff">Staff</option>
                </select>
                <input type="number" name="salary" placeholder="Salary" step="0.01" required>
            </div>
            <button type="submit" name="create"><i class="fas fa-user-plus"></i> Create User</button>
        </form>

        <!-- Users Table -->
        <h3>All Users</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Role</th>
                        <th>Salary</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT id, name, username, password, role, salary FROM users");
                    while ($row = $result->fetch_assoc()) {
                        echo "<form method='post'><tr>
                            <td>
                                <input type='hidden' name='id' value='{$row['id']}' />
                                {$row['id']}
                            </td>
                            <td><input type='text' name='name' value='{$row['name']}' required /></td>
                            <td><input type='text' name='username' value='{$row['username']}' required /></td>
                            <td><input type='text' name='password' value='{$row['password']}' required /></td>
                            <td>
                                <select name='role'>
                                    <option value='admin' " . ($row['role'] == 'admin' ? 'selected' : '') . ">Admin</option>
                                    <option value='manager' " . ($row['role'] == 'manager' ? 'selected' : '') . ">Manager</option>
                                    <option value='staff' " . ($row['role'] == 'staff' ? 'selected' : '') . ">Staff</option>
                                </select>
                            </td>
                            <td><input type='number' name='salary' value='{$row['salary']}' step='0.01' required /></td>
                            <td class='actions'>
                                <button type='submit' name='update' class='update-btn'><i class='fas fa-save'></i> Update</button>
                                <a href='users.php?delete={$row['id']}' class='delete-btn' onclick='return confirm(\"Are you sure you want to delete this user?\")'><i class='fas fa-trash-alt'></i> Delete</a>
                            </td>
                        </tr></form>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Confirmation for delete action
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this user?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>