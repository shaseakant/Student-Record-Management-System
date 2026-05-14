<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'User';

// File upload directory
$upload_dir = "../uploads/";

// CREATE or UPDATE
if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $id = $_POST['id'] ?? null;

    // Handle file upload
    $image_name = null;
    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $upload_dir . $image_name;
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
    }

    if ($id) {
        if ($image_name) {
            $stmt = $conn->prepare("UPDATE menu_items SET name=?, price=?, image=? WHERE id=?");
            $stmt->bind_param("sdsi", $name, $price, $image_name, $id);
        } else {
            $stmt = $conn->prepare("UPDATE menu_items SET name=?, price=? WHERE id=?");
            $stmt->bind_param("sdi", $name, $price, $id);
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO menu_items (name, price, image) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $name, $price, $image_name);
    }

    $stmt->execute();
    header("Location: menu_items.php");
}

// DELETE
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM menu_items WHERE id=$id");
    header("Location: menu_items.php");
}

// EDIT
$edit_item = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM menu_items WHERE id=$id");
    $edit_item = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Menu Management - Royal Orbit</title>
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

        .header-bar {
            display: flex;
            height:80px;
            justify-content: space-around;
            align-items: center;
            color: white;
            background-color: var(--primary);
            width: 100%;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            color: white;
            font-size: 1.5rem;
            text-align: center;
            flex: 1;
        }

        .user-info,
        .dashboard-link {
            flex-shrink: 0;
        }

        .btn.btn-outline {
            background: transparent;
            border: 1px solid white;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            transition: background 0.3s, color 0.3s;
        }

        .btn.btn-outline:hover {
            background: white;
            color: var(--primary);
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

        .btn-danger {
            background: #e74c3c;
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

        .file-input {
            position: relative;
            margin-bottom: 1rem;
        }

        .file-input input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            border: 2px dashed #ddd;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .file-input-label:hover {
            border-color: var(--primary);
        }

        .image-preview {
            margin-top: 1rem;
            text-align: center;
        }

        .image-preview img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #eee;
        }

        .button-wrapper {
            display: flex;
            justify-content: center;
            
        }


        .table-container {
            overflow-x: auto;
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 1rem;
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

        .menu-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }

        .img-fallback {
            width: 80px;
            height: 80px;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            color: #999;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .actions a {
            color: white;
            padding: 0.5rem;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
        }

        .edit-btn {
            background: var(--accent);
        }

        .delete-btn {
            background: #e74c3c;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
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
            
            .actions {
                flex-direction: column;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container"> 
        <div class="header-bar">
            <div class="user-info">
                Welcome (<?= ucfirst($role) ?>)</span>
            </div>
            <div>
            <h1 class="page-title">Royal Orbit - Menu Management</h1>
            </div>

            <div class="dashboard-link">
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>
    </div>
    </header>

    <div class="container">
        <div class="card">
            <h2><?= $edit_item ? "Edit Menu Item" : "Add New Menu Item" ?></h2>
            
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $edit_item['id'] ?? '' ?>">
                
                <div class="form-group">
                    <label for="name">Item Name</label>
                    <input type="text" id="name" name="name" 
                           placeholder="Enter item name" 
                           value="<?= htmlspecialchars($edit_item['name'] ?? '') ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="price">Price (₹)</label>
                    <input type="number" step="0.01" id="price" name="price" 
                           placeholder="Enter price" 
                           value="<?= $edit_item['price'] ?? '' ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label>Item Image</label>
                    <div class="file-input">
                        <label class="file-input-label" id="fileLabel">
                            <i class="fas fa-cloud-upload-alt" style="margin-right: 8px;"></i>
                            <?= $edit_item ? 'Change Image' : 'Choose Image' ?>
                        </label>
                        <input type="file" name="image" id="image" 
                               accept="image/*" <?= $edit_item ? '' : 'required' ?>>
                    </div>
                    
                    <?php if (!empty($edit_item['image'])): ?>
                        <div class="image-preview">
                            <p>Current Image:</p>
                            <?php 
                            $imagePath = '../uploads/' . $edit_item['image'];
                            $imageExists = file_exists($imagePath) && is_file($imagePath);
                            ?>
                            <?php if($imageExists): ?>
                                <img src="<?= $imagePath ?>" alt="Current Image" 
                                     onerror="this.onerror=null; this.src='data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\' fill=\'%23999\'%3E%3Crect width=\'100\' height=\'100\' rx=\'10\'/%3E%3Ctext x=\'50%\' y=\'50%\' font-family=\'Arial\' font-size=\'14\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3ENo Image%3C/text%3E%3C/svg%3E'">
                            <?php else: ?>
                                <div class="img-fallback">
                                    <i class="fas fa-utensils"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="button-wrapper">
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="fas fa-<?= $edit_item ? 'save' : 'plus' ?>"></i>
                        <?= $edit_item ? "Update Item" : "Add Item" ?>
                    </button>
                </div>

                
                <?php if($edit_item): ?>
                    <a href="menu_items.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <div class="table-container">
                <h2 style="display:flex; align-items:center;justify-content: center;">Menu Items</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Price (₹)</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT * FROM menu_items ORDER BY id DESC");
                        while ($row = $result->fetch_assoc()):
                            $imagePath = '../uploads/' . $row['image'];
                            $imageExists = file_exists($imagePath) && is_file($imagePath);
                        ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td>₹<?= number_format($row['price'], 2) ?></td>
                                <td>
                                    <?php if($imageExists): ?>
                                        <img src="<?= $imagePath ?>" class="menu-image" 
                                             onerror="this.onerror=null; this.src='data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\' fill=\'%23999\'%3E%3Crect width=\'100\' height=\'100\' rx=\'10\'/%3E%3Ctext x=\'50%\' y=\'50%\' font-family=\'Arial\' font-size=\'14\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3ENo Image%3C/text%3E%3C/svg%3E'">
                                    <?php else: ?>
                                        <div class="img-fallback">
                                            <i class="fas fa-utensils"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="menu_items.php?edit=<?= $row['id'] ?>" class="edit-btn" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="menu_items.php?delete=<?= $row['id'] ?>" class="delete-btn" title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this menu item?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Update file input label with selected file name
        document.getElementById('image').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Choose Image';
            document.getElementById('fileLabel').innerHTML = 
                `<i class="fas fa-cloud-upload-alt" style="margin-right: 8px;"></i>${fileName}`;
        });
    </script>
</body>
</html>
