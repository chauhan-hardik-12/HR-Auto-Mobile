<?php
require_once "includes/auth.php";
require_once "assets/config/db.php";
require_once "includes/settings_loader.php";

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$basePathUrl = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');

$userId = (int) $_SESSION['user_id'];

// 1. Fetch user info
$userStmt = $pdo->prepare("SELECT id, name, email, phone, created_at FROM users WHERE id = :id LIMIT 1");
$userStmt->execute(['id' => $userId]);
$user = $userStmt->fetch();

// 2. Fetch bookings matching schema
$bookingSql = "
    SELECT
        b.id AS booking_id,
        b.booking_date,
        b.booking_time,
        b.address,
        b.amount,
        b.status,
        b.payment_status,
        vt.name AS vehicle_type_name,
        br.name AS brand_name,
        m.name AS model_name,
        s.name AS service_name
    FROM bookings b
    LEFT JOIN models m ON b.model_id = m.id
    LEFT JOIN brands br ON m.brand_id = br.id
    LEFT JOIN vehicle_types vt ON br.vehicle_type_id = vt.id
    LEFT JOIN services s ON b.service_id = s.id
    WHERE b.user_id = :user_id
    ORDER BY b.booking_date DESC, b.booking_time DESC, b.id DESC
";

$bookingStmt = $pdo->prepare($bookingSql);
$bookingStmt->execute(['user_id' => $userId]);
$bookings = $bookingStmt->fetchAll();

$totalBookings = count($bookings);
$activeBookings = 0;
$completedBookings = 0;

foreach ($bookings as $b) {
    if (in_array($b['status'], ['pending', 'confirmed'], true)) {
        $activeBookings++;
    } elseif ($b['status'] === 'completed') {
        $completedBookings++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - HR Auto Mobile</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
        }

        /* Global Main Navbar */
        .site-navbar {
            background: #111;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .brand {
            color: white;
            font-size: 28px;
            cursor: pointer;
        }

        .brand span {
            color: var(--main-color);
        }

        .nav-links {
            display: flex;
            gap: 25px;
            flex-direction: row;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            flex-direction: row;
        }

        .nav-links a:hover {
            color: #2563eb;
        }

        .menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .user-profile-icon,
        .user .user-profile-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            min-width: 38px;
            min-height: 38px;
            aspect-ratio: 1 / 1;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff !important;
            font-size: 1.25rem;
            transition: all 0.25s ease;
            text-decoration: none;
            flex-shrink: 0;
            line-height: 1;
            padding: 0;
            box-sizing: border-box;
        }

        .user-profile-icon:hover,
        .user .user-profile-icon:hover {
            background: var(--main-color, #1c69d4);
            border-color: var(--main-color, #1c69d4);
            color: #fff !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(28, 105, 212, 0.35);
        }

        .user-profile-icon .bx,
        .user-profile-icon i,
        .user .user-profile-icon .bx,
        .user .user-profile-icon i {
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: inherit;
        }

        .btn-logout-nav {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.88rem;
            font-weight: 500;
            color: #fca5a5 !important;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            text-decoration: none;
            transition: all 0.25s ease;
            cursor: pointer;
            line-height: 1.2;
        }

        .btn-logout-nav .bx,
        .btn-logout-nav i {
            font-size: 1.1rem;
            color: inherit;
            margin: 0 !important;
        }

        .btn-logout-nav:hover {
            background: #dc2626 !important;
            color: #ffffff !important;
            border-color: #dc2626 !important;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35);
            transform: translateY(-1px);
        }

        .btn-logout-nav:active {
            transform: translateY(0);
            background: #b91c1c !important;
        }

        /* Dashboard Container */
        .container {
            max-width: 1060px;
            margin: 2rem auto;
            width: 100%;
            padding: 50px 0px;
        }

        /* Profile Welcome Banner */
        .banner {
            background: linear-gradient(50deg, #1e40af, #2563eb);
            color: #fff;
            padding: 2rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .banner h2 {
            font-size: 1.5rem;
            margin-bottom: 0.3rem;
        }

        .banner p {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .btn-book-action {
            background: #ffffff;
            color: #2563eb;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: transform 0.1s ease;
        }

        .btn-book-action:hover {
            transform: translateY(-2px);
        }

        /* Quick Explore / Full Site Shortcuts */
        .explore-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .explore-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem;
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .explore-card:hover {
            border-color: #2563eb;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
        }

        .explore-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .explore-info h4 {
            font-size: 0.95rem;
            color: #0f172a;
            margin-bottom: 0.1rem;
        }

        .explore-info p {
            font-size: 0.8rem;
            color: #64748b;
        }

        /* Stats Grid */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #fff;
            padding: 1.25rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .stat-card p {
            color: #64748b;
            font-size: 0.85rem;
            margin-bottom: 0.2rem;
        }

        .stat-card h3 {
            font-size: 1.5rem;
        }

        /* Table */
        .table-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th,
        td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
        }

        th {
            background: #f8fafc;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: 0.05em;
        }

        .badge {
            padding: 0.35rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-pending {
            background: #fef3c7;
            color: #b45309;
        }

        .badge-confirmed {
            background: #e0f2fe;
            color: #0369a1;
        }

        .badge-completed {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-cancelled {
            background: #fee2e2;
            color: #b91c1c;
        }
    </style>
</head>

<body>

    <!-- Full Site Navigation Header -->
    <header class="navbar">
        <a href="<?= $basePathUrl ?>/index.php" class="logo">
            <?= siteSetting('logo_text', 'HR Auto Mobile') ?>
            <span>.</span>
        </a>
        <div class="menu">
            <a href="<?= $basePathUrl ?>/index.php" class="nav-link">Home</a>
            <a href="<?= $basePathUrl ?>/about.php" class="nav-link">About</a>
            <a href="<?= $basePathUrl ?>/services.php" class="nav-link">Services</a>
            <a href="<?= $basePathUrl ?>/contact.php" class="nav-link">Contact</a>
            <div class="user">
                <a href="<?= $basePathUrl ?>/dashboard.php" title="Customer Dashboard" class="user-profile-icon"><i class='bx bx-user'></i></a>
                <a href="<?= $basePathUrl ?>/logout.php" class="btn-logout-nav" title="Logout"><i class='bx bx-log-out'></i> Logout</a>
            </div>
        </div>
        <div class="menu-icon">
            <div class="line1"></div>
            <div class="line2"></div>
            <div class="line3"></div>
        </div>
    </header>


    <main class="container">
        <!-- Customer Banner -->
        <section class="banner">
            <div>
                <h2>Welcome Back, <?= htmlspecialchars($user['name']) ?>!</h2>
                <p><i class='bx bx-envelope'></i> <?= htmlspecialchars($user['email']) ?> &nbsp;|&nbsp; <i
                        class='bx bx-phone'></i> <?= htmlspecialchars($user['phone']) ?></p>
            </div>
            <a href="services.php" class="btn-book-action">
                <i class='bx bx-plus-circle'></i> Book New Service
            </a>
        </section>

        <!-- Full Site Quick Access Cards -->
        <section class="explore-grid">
            <a href="services.php" class="explore-card">
                <div class="explore-icon"><i class='bx bx-wrench'></i></div>
                <div class="explore-info">
                    <h4>All Services</h4>
                    <p>Book Car & Bike packages</p>
                </div>
            </a>
            <a href="index.php" class="explore-card">
                <div class="explore-icon"><i class='bx bx-home-alt'></i></div>
                <div class="explore-info">
                    <h4>Home Page</h4>
                    <p>Featured maintenance plans</p>
                </div>
            </a>
            <a href="contact.php" class="explore-card">
                <div class="explore-icon"><i class='bx bx-support'></i></div>
                <div class="explore-info">
                    <h4>Help & Support</h4>
                    <p>Get in touch with mechanics</p>
                </div>
            </a>
        </section>

        <!-- Performance Stats -->
        <section class="stats">
            <div class="stat-card">
                <p>Total Bookings</p>
                <h3><?= $totalBookings ?></h3>
            </div>
            <div class="stat-card">
                <p>Active Appointments</p>
                <h3 style="color:#d97706;"><?= $activeBookings ?></h3>
            </div>
            <div class="stat-card">
                <p>Completed Services</p>
                <h3 style="color:#16a34a;"><?= $completedBookings ?></h3>
            </div>
        </section>

        <!-- Bookings History Table -->
        <h3 style="margin-bottom: 1rem; color: #0f172a;">My Service Appointments</h3>
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Vehicle</th>
                        <th>Service</th>
                        <th>Schedule</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($bookings)): ?>
                        <?php foreach ($bookings as $b):
                            $isBike = strtolower($b['vehicle_type_name'] ?? '') === 'bike';
                            ?>
                            <tr>
                                <td><strong>#<?= str_pad($b['booking_id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                                <td>
                                    <i class='bx <?= $isBike ? 'bx-cycling' : 'bx-car' ?>'></i>
                                    <?= htmlspecialchars(($b['brand_name'] ?? '') . ' ' . ($b['model_name'] ?? 'Vehicle')) ?>
                                </td>
                                <td><?= htmlspecialchars($b['service_name'] ?? 'Custom Package') ?></td>
                                <td>
                                    <?= date('d M Y', strtotime($b['booking_date'])) ?><br>
                                    <small style="color:#64748b;"><?= date('h:i A', strtotime($b['booking_time'])) ?></small>
                                </td>
                                <td><strong>₹<?= number_format((float) $b['amount'], 2) ?></strong></td>
                                <td>
                                    <span class="badge badge-<?= htmlspecialchars($b['status']) ?>">
                                        <?= htmlspecialchars($b['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:2.5rem; color:#64748b;">
                                You have no service bookings yet.<br>
                                <a href="services.php"
                                    style="color:#2563eb; font-weight:600; text-decoration:none; display:inline-block; margin-top:0.5rem;">Browse
                                    Car &amp; Bike Services</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        // Force reload from server if restored from browser back-forward cache after logout
        window.addEventListener('pageshow', function (event) {
            if (event.persisted || (window.performance && (window.performance.navigation && window.performance.navigation.type === 2))) {
                window.location.reload();
            }
        });
    </script>
    <script src="assets/js/script.js"></script>
</body>

</html>