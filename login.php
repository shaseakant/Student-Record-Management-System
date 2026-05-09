<?php
session_start();
include 'db.php';

// Auto-login if cookie is set
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user']) && isset($_COOKIE['remember_pass'])) {
    $user = $_COOKIE['remember_user'];
    $pass = $_COOKIE['remember_pass'];

    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $user, $pass);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $name, $role);
        $stmt->fetch();
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $name;
        $_SESSION['role'] = $role;

        switch ($role) {
            case 'admin':
                header("Location: admin/dashboard.php");
                break;
            case 'manager':
                header("Location: manager/dashboard.php");
                break;
            case 'staff':
                header("Location: staff/dashboard.php");
                break;
        }
        exit();
    }
}

// Manual login via form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);
    $remember = isset($_POST['remember']);

    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $user, $pass);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $name, $role);
        $stmt->fetch();

        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $name;
        $_SESSION['role'] = $role;

        // Set cookies if "remember me" checked (30 days)
        if ($remember) {
            setcookie("remember_user", $user, time() + (30 * 24 * 60 * 60), "/");
            setcookie("remember_pass", $pass, time() + (30 * 24 * 60 * 60), "/");
        }

        switch ($role) {
            case 'admin':
                header("Location: admin/dashboard.php");
                break;
            case 'manager':
                header("Location: manager/dashboard.php");
                break;
            case 'staff':
                header("Location: staff/dashboard.php");
                break;
        }
        exit();
    } else {
        echo "<script>alert('Invalid username or password.'); window.location.href='index.html';</script>";
    }
    $stmt->close();
}

$conn->close();
?>
