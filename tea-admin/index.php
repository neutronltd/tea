<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true){
    header("location: login.php");
    exit;
}

// Include the database connection file
require_once "db.php";

// --- EXPORT TO CSV LOGIC ---
if(isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=tea_members_' . date('Y-m-d') . '.csv');
    
    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');
    
    // Output the column headings
    fputcsv($output, array('ID', 'Name', 'Company', 'Title', 'Phone', 'Address', 'Email', 'Newsletter Subscribed', 'Submission Date'));
    
    // Fetch the data
    $sql_export = "SELECT id, name, company, title, phone, address, email, newsletter_subscribed, submission_date FROM members ORDER BY id DESC";
    $result_export = $conn->query($sql_export);
    
    // Loop over the rows, outputting them
    if ($result_export->num_rows > 0) {
        while($row = $result_export->fetch_assoc()) {
            // Format boolean value for readability
            $row['newsletter_subscribed'] = $row['newsletter_subscribed'] ? 'Yes' : 'No';
            fputcsv($output, $row);
        }
    }
    exit();
}


// --- PAGINATION LOGIC ---
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total number of records
$total_records_sql = "SELECT COUNT(*) FROM members";
$total_result = $conn->query($total_records_sql);
$total_records = $total_result->fetch_row()[0];
$total_pages = ceil($total_records / $records_per_page);


// --- FETCH MEMBERS DATA FOR CURRENT PAGE ---
$members = [];
$sql = "SELECT id, name, company, title, phone, address, email, newsletter_subscribed, submission_date FROM members ORDER BY id DESC LIMIT ?, ?";

if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("ii", $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $members[] = $row;
        }
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEA Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Lato', sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('https://demo.neutron.com.bd/tea/background-full.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #2c3e50;
        }
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: rgba(44, 62, 80, 0.95);
            color: white;
            padding: 2rem 0;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0,0,0,0.2);
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
        }
        .sidebar-header {
            text-align: center;
            padding: 0 1rem;
            margin-bottom: 3rem;
        }
        .sidebar-logo {
            max-width: 120px;
        }
        .sidebar-nav a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 1rem 2rem;
            font-family: 'Oswald', sans-serif;
            font-size: 1.1rem;
            text-transform: uppercase;
            transition: background-color 0.3s, padding-left 0.3s;
        }
        .sidebar-nav a.active, .sidebar-nav a:hover {
            background-color: #c0392b;
            padding-left: 2.5rem;
        }
        .sidebar-footer {
            margin-top: auto;
        }
        .main-content {
            flex-grow: 1;
            padding: 2rem 3rem;
            overflow-y: auto;
        }
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255,255,255,0.2);
        }
        .content-header h1 {
            font-family: 'Oswald', sans-serif;
            font-size: 2.5rem;
            color: #2c3e50;
            margin: 0;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        .export-btn {
            background-color: #c0392b;
            color: white;
            text-decoration: none;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            font-weight: 700;
            transition: background-color 0.3s;
        }
        .export-btn:hover {
            background-color: #a53125;
        }
        .table-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        .members-table {
            width: 100%;
            border-collapse: collapse;
        }
        .members-table th, .members-table td {
            padding: 0.6rem 1rem; /* Reduced top/bottom padding */
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .members-table th {
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            background-color: #f2f2f2;
        }
        .members-table tr:hover {
            background-color: #f9f9f9;
        }
        .view-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .view-btn:hover {
            background-color: #2980b9;
        }
        .pagination {
            margin-top: 2rem;
            text-align: center;
        }
        .pagination a {
            color: #2c3e50;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border: 1px solid #ddd;
            margin: 0 2px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .pagination a.active, .pagination a:hover {
            background-color: #c0392b;
            color: white;
            border-color: #c0392b;
        }
        .menu-toggle {
            display: none;
            background: #c0392b;
            border: none;
            color: white;
            font-size: 1.5rem;
            padding: 0.5rem 0.8rem;
            border-radius: 5px;
            cursor: pointer;
            position: fixed;
            top: 1.5rem;
            left: 1.5rem;
            z-index: 1001;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.visible {
            display: flex;
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 2rem 2.5rem;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            position: relative;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            animation: slide-down 0.3s ease-out;
        }
        @keyframes slide-down {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-close {
            color: #aaa;
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
        }
        .modal-close:hover, .modal-close:focus {
            color: #333;
        }
        .modal-header h2 {
            font-family: 'Oswald', sans-serif;
            margin-top: 0;
            color: #c0392b;
        }
        .modal-body .detail-item {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        .modal-body .detail-item:last-child {
            border-bottom: none;
        }
        .modal-body .detail-item strong {
            display: block;
            font-family: 'Oswald', sans-serif;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                position: fixed;
                height: 100%;
                transform: translateX(-100%);
            }
            .sidebar.visible {
                transform: translateX(0);
            }
            .main-content {
                padding: 2rem;
            }
            .menu-toggle {
                display: block;
            }
            .content-header {
                padding-top: 4rem;
            }
        }

        @media (max-width: 768px) {
            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .content-header h1 {
                font-size: 2rem;
            }
            .members-table .th-hidden-mobile, .members-table .td-hidden-mobile {
                display: none; /* Hide specific columns on mobile */
            }
            .members-table tr {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 0.5rem;
            }
            .td-name {
                font-weight: bold;
                flex-grow: 1;
            }
        }
    </style>
</head>
<body>

    <button class="menu-toggle" id="menu-toggle">&#9776;</button>

    <div class="dashboard-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="https://demo.neutron.com.bd/tea/logo.png" alt="Logo" class="sidebar-logo">
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="active">Members</a>
            </nav>
            <div class="sidebar-footer">
                <nav class="sidebar-nav">
                    <a href="logout.php">Logout</a>
                </nav>
            </div>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1>Member Submissions</h1>
                <a href="index.php?export=csv" class="export-btn">Export to CSV</a>
            </header>

            <div class="table-container">
                <table class="members-table">
                    <thead>
                        <tr>
                            <th class="th-hidden-mobile">ID</th>
                            <th>Name</th>
                            <th class="th-hidden-mobile">Company</th>
                            <th class="th-hidden-mobile">Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($members)): ?>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td class="td-hidden-mobile"><?php echo htmlspecialchars($member['id']); ?></td>
                                    <td class="td-name"><?php echo htmlspecialchars($member['name']); ?></td>
                                    <td class="td-hidden-mobile"><?php echo htmlspecialchars($member['company']); ?></td>
                                    <td class="td-hidden-mobile"><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td class="td-actions">
                                        <button class="view-btn" 
                                            data-name="<?php echo htmlspecialchars($member['name']); ?>"
                                            data-company="<?php echo htmlspecialchars($member['company']); ?>"
                                            data-title="<?php echo htmlspecialchars($member['title']); ?>"
                                            data-phone="<?php echo htmlspecialchars($member['phone']); ?>"
                                            data-address="<?php echo htmlspecialchars($member['address']); ?>"
                                            data-email="<?php echo htmlspecialchars($member['email']); ?>"
                                            data-subscribed="<?php echo $member['newsletter_subscribed'] ? 'Yes' : 'No'; ?>"
                                            data-date="<?php echo date('F j, Y, g:i a', strtotime($member['submission_date'])); ?>">
                                            View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center;">No member submissions found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="index.php?page=<?php echo $i; ?>" class="<?php if($page == $i) echo 'active'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <div id="member-modal" class="modal-overlay">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <div class="modal-header">
                <h2 id="modal-name"></h2>
            </div>
            <div class="modal-body">
                <div class="detail-item">
                    <strong>Company</strong>
                    <p id="modal-company"></p>
                </div>
                <div class="detail-item">
                    <strong>Title</strong>
                    <p id="modal-title"></p>
                </div>
                <div class="detail-item">
                    <strong>Email Address</strong>
                    <p id="modal-email"></p>
                </div>
                <div class="detail-item">
                    <strong>Phone Number</strong>
                    <p id="modal-phone"></p>
                </div>
                <div class="detail-item">
                    <strong>Address</strong>
                    <p id="modal-address"></p>
                </div>
                <div class="detail-item">
                    <strong>Newsletter</strong>
                    <p id="modal-subscribed"></p>
                </div>
                <div class="detail-item">
                    <strong>Submission Date</strong>
                    <p id="modal-date"></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.getElementById('sidebar');
            const modal = document.getElementById('member-modal');
            const closeBtn = document.querySelector('.modal-close');
            const viewBtns = document.querySelectorAll('.view-btn');

            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('visible');
                });
            }

            viewBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const data = this.dataset;
                    document.getElementById('modal-name').textContent = data.name;
                    document.getElementById('modal-company').textContent = data.company || 'N/A';
                    document.getElementById('modal-title').textContent = data.title || 'N/A';
                    document.getElementById('modal-email').textContent = data.email || 'N/A';
                    document.getElementById('modal-phone').textContent = data.phone || 'N/A';
                    document.getElementById('modal-address').textContent = data.address || 'N/A';
                    document.getElementById('modal-subscribed').textContent = data.subscribed;
                    document.getElementById('modal-date').textContent = data.date;
                    modal.classList.add('visible');
                });
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    modal.classList.remove('visible');
                });
            }

            if (modal) {
                modal.addEventListener('click', function(event) {
                    if (event.target === modal) {
                        modal.classList.remove('visible');
                    }
                });
            }
        });
    </script>

</body>
</html>
