<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'User';

// Sample counts (replace with real DB queries)
$users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$orders = $conn->query("SELECT COUNT(*) AS total FROM orders")->fetch_assoc()['total'];
$menu = $conn->query("SELECT COUNT(*) AS total FROM menu_items")->fetch_assoc()['total'];
$stocks = $conn->query("SELECT COUNT(*) AS total FROM stocks")->fetch_assoc()['total'];
$attendance = $conn->query("SELECT COUNT(*) AS total FROM attendance")->fetch_assoc()['total'];
$salary = $conn->query("SELECT COUNT(*) AS total FROM salary_payments")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard</title>
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

  body {
    font-family: 'Poppins', sans-serif;
    background-color: var(--gray);
    color: var(--dark);
    margin: 0;
    padding: 0;
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
    position: relative;
    z-index: 10;
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
  }

  header a:hover {
    background-color: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
  }

  /* Navigation */
  ul {
    list-style: none;
    margin: 0;
    padding: 0;
    background: var(--dark);
    display: flex;
    overflow-x: auto;
    scrollbar-width: none; /* Firefox */
  }

  ul::-webkit-scrollbar {
    display: none; /* Chrome/Safari */
  }

  ul li {
    flex: 1;
    min-width: 120px;
  }

  ul li a {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem 0.5rem;
    text-decoration: none;
    color: var(--light);
    transition: var(--transition);
    font-size: 0.9rem;
    gap: 6px;
  }

  ul li a i {
    font-size: 1.2rem;
  }

  ul li.active a,
  ul li a:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: var(--gold);
  }

  ul li.active {
    border-bottom: 3px solid var(--gold);
  }

  /* Dashboard Cards */
  .dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    padding: 2rem;
  }

  .card {
    background: var(--light);
    padding: 1.8rem;
    border-radius: 12px;
    box-shadow: var(--shadow);
    text-align: center;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    animation: fadeInUp 0.6s ease-out forwards;
    opacity: 0;
  }

  .card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--primary);
    transition: var(--transition);
  }

  .card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
  }

  .card:hover::before {
    width: 8px;
    background: var(--gold);
  }

  .card i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: var(--primary);
    transition: var(--transition);
  }

  .card:hover i {
    color: var(--gold);
    transform: scale(1.1);
  }

  .card h3 {
    margin: 0.5rem 0;
    font-size: 2rem;
    color: var(--dark);
    font-weight: 700;
  }

  .card p {
    color: #666;
    font-size: 0.95rem;
    margin: 0;
  }

  /* Animations */
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* Delayed animations for cards */
  .card:nth-child(1) { animation-delay: 0.1s; }
  .card:nth-child(2) { animation-delay: 0.2s; }
  .card:nth-child(3) { animation-delay: 0.3s; }
  .card:nth-child(4) { animation-delay: 0.4s; }
  .card:nth-child(5) { animation-delay: 0.5s; }
  .card:nth-child(6) { animation-delay: 0.6s; }

  /* Responsive Adjustments */
  @media (max-width: 768px) {
    .dashboard {
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 1rem;
      padding: 1rem;
    }
    
    ul li {
      min-width: 100px;
    }
    
    ul li a {
      padding: 0.8rem 0.3rem;
      font-size: 0.8rem;
    }
    
    ul li a i {
      font-size: 1rem;
    }
    
    .card {
      padding: 1.5rem 1rem;
    }
    
    .card i {
      font-size: 2rem;
    }
    
    .card h3 {
      font-size: 1.6rem;
    }
  }

  @media (max-width: 480px) {
    header {
      flex-direction: column;
      gap: 0.8rem;
      padding: 1rem;
      text-align: center;
    }
    
    .dashboard {
      grid-template-columns: 1fr 1fr;
    }
  }
</style>

<!-- Add this to your head section -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header>
  <div>Welcome  (<?= ucfirst($role) ?>)</div>
  <div><h1>Royal Orbit - Dashboard</h1></div>
  <div><a href="../logout.php" style="color:#fff;">Logout</a></div>
</header>

<ul>
  <li class="active"><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li> 
  <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
  <li><a href="users_report.php"><i class="fas fa-book"></i> Info</a></li>
</ul>

<div class="dashboard">
  <div class="card">
    <i class="fas fa-users"></i>
    <h3><?= $users ?></h3>
    <p>Total Users</p>
  </div>
  <div class="card">
    <i class="fas fa-shopping-cart"></i>
    <h3><?= $orders ?></h3>
    <p>Total Orders</p>
  </div>
  <div class="card">
    <i class="fas fa-pizza-slice"></i>
    <h3><?= $menu ?></h3>
    <p>Menu Items</p>
  </div>
  <div class="card">
    <i class="fas fa-boxes"></i>
    <h3><?= $stocks ?></h3>
    <p>Stock Items</p>
  </div>
  <div class="card">
    <i class="fas fa-user-check"></i>
    <h3><?= $attendance ?></h3>
    <p>Attendance Entries</p>
  </div>
  <div class="card">
    <i class="fas fa-money-bill-wave"></i>
    <h3><?= $salary ?></h3>
    <p>Salary Payments</p>
  </div>
</div>

</body>
</html>
