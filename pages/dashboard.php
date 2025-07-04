<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../accounts/login.php");
  exit;
}

require_once '../config/db.php';

// Quick Stats
$total_products = $mysqli->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc()['total'];

$deliveries_this_week = $mysqli->query("
    SELECT COUNT(*) AS total FROM delivery_logs
    WHERE YEARWEEK(delivery_date, 1) = YEARWEEK(CURDATE(), 1)
")->fetch_assoc()['total'];

$out_of_stock = $mysqli->query("
    SELECT COUNT(*) AS total FROM products p
    LEFT JOIN (
        SELECT d.product_id,
              SUM(d.delivered_reams) AS total_reams,
              (SUM(d.delivered_reams) * 500) - IFNULL(SUM(u.used_sheets), 0) AS balance
        FROM delivery_logs d
        LEFT JOIN usage_logs u ON u.product_id = d.product_id
        GROUP BY d.product_id
    ) AS stock ON p.id = stock.product_id
    WHERE IFNULL(balance, 0) <= 0
")->fetch_assoc()['total'];

// Recent Deliveries
$recent_deliveries = $mysqli->query("
    SELECT d.delivery_date, p.product_type, p.product_group, p.product_name, d.delivered_reams
    FROM delivery_logs d
    JOIN products p ON d.product_id = p.id
    ORDER BY d.delivery_date DESC
    LIMIT 5
");

// Recent Usage
$recent_usage = $mysqli->query("
    SELECT u.log_date, p.product_type, p.product_group, p.product_name, u.used_sheets
    FROM usage_logs u
    JOIN products p ON u.product_id = p.id
    ORDER BY u.log_date DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="icon" type="image/png" href="../assets/images/plainlogo.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    :root {
      --primary: #1877f2;
      --secondary: #166fe5;
      --light: #f0f2f5;
      --dark: #1c1e21;
      --gray: #65676b;
      --light-gray: #e4e6eb;
      --card-bg: #ffffff;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background-color: var(--light);
      color: var(--dark);
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      width: 250px;
      background-color: var(--card-bg);
      height: 100vh;
      position: fixed;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      padding: 20px 0;
    }

    .brand {
      padding: 0 20px 40px;
      border-bottom: 1px solid var(--light-gray);
      margin-bottom: 20px;
    }

    .brand img {
      height: 100px;
      width: auto;
      padding-left: 40px;
      transform: rotate(45deg);
    }

    .brand h2 {
      font-size: 18px;
      font-weight: 600;
      color: var(--dark);
    }

    .nav-menu {
      list-style: none;
    }

    .nav-menu li a {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      color: var(--dark);
      text-decoration: none;
      transition: background-color 0.3s;
    }

    .nav-menu li a:hover,
    .nav-menu li a.active {
      background-color: var(--light-gray);
    }

    .nav-menu li a i {
      margin-right: 10px;
      color: var(--gray);
    }

    /* Main Content */
    .main-content {
      flex: 1;
      margin-left: 250px;
      padding: 20px;
    }

    /* Header */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid var(--light-gray);
    }

    .header h1 {
      font-size: 24px;
      font-weight: 600;
      color: var(--dark);
    }

    .user-info {
      display: flex;
      align-items: center;
    }

    .user-info img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      margin-right: 10px;
      object-fit: cover;
    }

    .user-details h4 {
      font-weight: 500;
      font-size: 16px;
    }

    .user-details small {
      color: var(--gray);
      font-size: 14px;
    }

    /* Stats Cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: var(--card-bg);
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .stat-card .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }

    .stat-card .card-icon {
      width: 50px;
      height: 50px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: rgba(24, 119, 242, 0.1);
      color: var(--primary);
    }

    .stat-card h3 {
      font-size: 28px;
      font-weight: 600;
      margin-bottom: 5px;
    }

    .stat-card p {
      color: var(--gray);
      font-size: 14px;
    }

    /* Tables Section */
    .tables-section {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
      gap: 20px;
    }

    .table-card {
      background: var(--card-bg);
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .table-card h3 {
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      color: var(--dark);
    }

    .table-card h3 i {
      margin-right: 10px;
      color: var(--primary);
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid var(--light-gray);
    }

    th {
      font-weight: 500;
      color: var(--gray);
      font-size: 14px;
    }

    tr:hover td {
      background-color: rgba(24, 119, 242, 0.05);
    }

    .view-all {
      display: inline-block;
      margin-top: 15px;
      color: var(--primary);
      text-decoration: none;
      font-weight: 500;
    }

    .view-all:hover {
      text-decoration: underline;
    }

    @media (max-width: 992px) {
      .tables-section {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        position: fixed;
        width: 50px;
        overflow: hidden;
        height: 200px;
        bottom: 10px;
        padding: 0;
        left: 10px;
        background-color: rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(2px);
        box-shadow: 1px 1px 10px rgb(190, 190, 190);
        border-radius: 100px;
      }

      .sidebar img,
      .sidebar .brand,
      .sidebar .nav-menu li a span {
        display: none;
      }

      .sidebar .nav-menu li a {
        justify-content: center;
      }

      .sidebar .nav-menu li a i {
        margin-right: 0;
      }

      .main-content {
        margin-left: 0;
        overflow: auto;
        margin-bottom: 200px;
      }
    }

    @media (max-width: 576px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }

      .header {
        flex-direction: column;
        align-items: flex-start;
      }

      .user-info {
        margin-top: 10px;
      }
    }
  </style>
</head>

<body>
  <div class="sidebar">
    <div class="brand">
      <img src="../assets/images/plainlogo.png" alt="">
    </div>
    <ul class="nav-menu">
      <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
      <li><a href="products.php"><i class="fas fa-boxes"></i> <span>Products</span></a></li>
      <li><a href="delivery.php"><i class="fas fa-truck"></i> <span>Deliveries</span></a></li>
      <li><a href="job_orders.php"><i class="fas fa-clipboard-list"></i> <span>Job Orders</span></a></li>
      <li><a href="../accounts/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
  </div>

  <div class="main-content">
    <header class="header">
      <h1>Dashboard Overview</h1>
      <div class="user-info">
        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=random" alt="User">
        <div class="user-details">
          <h4><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
          <small><?php echo $_SESSION['role']; ?></small>
        </div>
      </div>
    </header>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="card-header">
          <div>
            <p>Total Products</p>
            <h3><?php echo $total_products; ?></h3>
          </div>
          <div class="card-icon">
            <i class="fas fa-boxes"></i>
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="card-header">
          <div>
            <p>Deliveries This Week</p>
            <h3><?php echo $deliveries_this_week; ?></h3>
          </div>
          <div class="card-icon">
            <i class="fas fa-truck"></i>
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="card-header">
          <div>
            <p>Out of Stock</p>
            <h3><?php echo $out_of_stock; ?></h3>
          </div>
          <div class="card-icon">
            <i class="fas fa-exclamation-triangle"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Tables Section -->
    <div class="tables-section">
      <div class="table-card">
        <h3><i class="fas fa-truck"></i> Recent Deliveries</h3>
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Product</th>
              <th>Reams</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $recent_deliveries->fetch_assoc()): ?>
              <tr>
                <td><?php echo date("M j, Y", strtotime($row['delivery_date'])); ?></td>
                <td><?php echo "{$row['product_type']} - {$row['product_group']} - {$row['product_name']}"; ?></td>
                <td><?php echo number_format($row['delivered_reams'], 2); ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
        <a href="delivery.php" class="view-all">View All Deliveries →</a>
      </div>

      <div class="table-card">
        <h3><i class="fas fa-file-alt"></i> Recent Usage</h3>
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Product</th>
              <th>Sheets Used</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $recent_usage->fetch_assoc()): ?>
              <tr>
                <td><?php echo date("M j, Y", strtotime($row['log_date'])); ?></td>
                <td><?php echo "{$row['product_type']} - {$row['product_group']} - {$row['product_name']}"; ?></td>
                <td><?php echo number_format($row['used_sheets'], 2); ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
        <a href="job_orders.php" class="view-all">View All Usage →</a>
      </div>
    </div>
  </div>
</body>

</html>