<?php
/**
 * Admin Reports
 * Watch Collection - College Project
 * Read-only: Sales, Order, Customer, Revenue reports
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();

$page_title = 'Reports';

// ---- Optional date range filter (defaults to all-time) ----
$start_date = filter_input(INPUT_GET, 'start_date', FILTER_DEFAULT);
$end_date   = filter_input(INPUT_GET, 'end_date', FILTER_DEFAULT);

$valid_date = fn($d) => $d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
$start_date = $valid_date($start_date) ? $start_date : null;
$end_date   = $valid_date($end_date) ? $end_date : null;

$where  = 'WHERE order_status != \'Cancelled\'';
$params = [];
if ($start_date) { $where .= ' AND created_at >= ?'; $params[] = $start_date . ' 00:00:00'; }
if ($end_date)   { $where .= ' AND created_at <= ?'; $params[] = $end_date . ' 23:59:59'; }

// ---- Sales Report ----
$stmt = $pdo->prepare("SELECT COUNT(*) AS total_orders, COALESCE(SUM(total_amount),0) AS total_sales
                        FROM orders $where");
$stmt->execute($params);
$sales = $stmt->fetch();

// ---- Order Report (by status, ignores date filter to show full pipeline) ----
$stmt = $pdo->query("SELECT order_status, COUNT(*) AS total
                      FROM orders
                      GROUP BY order_status");
$order_status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$statuses = ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

// ---- Customer Report ----
$total_customers  = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$active_customers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$inactive_customers = $total_customers - $active_customers;

$stmt = $pdo->query("SELECT u.full_name, u.email, COUNT(o.id) AS order_count, COALESCE(SUM(o.total_amount),0) AS total_spent
                      FROM users u
                      JOIN orders o ON o.user_id = u.id AND o.order_status != 'Cancelled'
                      GROUP BY u.id
                      ORDER BY order_count DESC, total_spent DESC
                      LIMIT 5");
$top_customers = $stmt->fetchAll();

// ---- Revenue Report (by payment method) ----
$stmt = $pdo->prepare("SELECT payment_method, COUNT(*) AS total_orders, COALESCE(SUM(total_amount),0) AS total_revenue
                        FROM orders $where
                        GROUP BY payment_method");
$stmt->execute($params);
$revenue_by_method = $stmt->fetchAll();

require __DIR__ . '/../includes/admin-header.php';
require __DIR__ . '/../includes/admin-sidebar.php';
?>

<style>
.reports-filter { display: flex; gap: 12px; align-items: end; margin-bottom: 24px; flex-wrap: wrap; }
.reports-filter label { display: block; font-size: 0.85rem; color: var(--color-text-secondary); margin-bottom: 4px; }
.reports-filter input[type="date"] { padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 12px; }
.reports-filter .btn-primary { background: var(--color-primary); color: #fff; border: none; padding: 9px 20px; border-radius: 12px; font-weight: 600; cursor: pointer; text-decoration: none; }
.reports-filter .btn-secondary { background: #fff; color: var(--color-primary); border: 1px solid var(--color-primary); padding: 9px 20px; border-radius: 12px; font-weight: 600; text-decoration: none; }

.report-section { background: var(--color-card); border: 1px solid var(--color-border); border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.report-section h2 { font-family: 'DM Serif Display', serif; font-size: 1.3rem; margin-bottom: 16px; color: var(--color-text-primary); }

.report-stats { display: flex; gap: 32px; flex-wrap: wrap; margin-bottom: 8px; }
.report-stat-value { font-size: 1.6rem; font-weight: 700; color: var(--color-primary); }
.report-stat-label { font-size: 0.85rem; color: var(--color-text-secondary); }

.report-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
.report-table th, .report-table td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--color-border); font-size: 0.92rem; }
.report-table th { color: var(--color-text-secondary); font-weight: 600; }

.status-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
.status-Pending { background: #FEF3C7; color: #92400E; }
.status-Confirmed { background: #DBEAFE; color: #1E40AF; }
.status-Processing { background: #E0E7FF; color: #3730A3; }
.status-Shipped { background: #CFFAFE; color: #155E75; }
.status-Delivered { background: #DCFCE7; color: #166534; }
.status-Cancelled { background: #FEE2E2; color: #991B1B; }
</style>

<h1 style="font-family: var(--font-heading); margin-bottom: 24px;">Reports</h1>

<form class="reports-filter" method="GET" action="reports.php">
    <div>
        <label for="start_date">From</label>
        <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date ?? '') ?>">
    </div>
    <div>
        <label for="end_date">To</label>
        <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date ?? '') ?>">
    </div>
    <button type="submit" class="btn-primary">Apply Filter</button>
    <?php if ($start_date || $end_date): ?>
        <a href="reports.php" class="btn-secondary">Clear</a>
    <?php endif; ?>
</form>

<div class="report-section">
    <h2>Sales Report</h2>
    <div class="report-stats">
        <div>
            <div class="report-stat-value"><?= (int) $sales['total_orders'] ?></div>
            <div class="report-stat-label">Total Orders (excl. Cancelled)</div>
        </div>
        <div>
            <div class="report-stat-value">₹<?= number_format((float) $sales['total_sales'], 2) ?></div>
            <div class="report-stat-label">Total Sales</div>
        </div>
    </div>
</div>

<div class="report-section">
    <h2>Order Report</h2>
    <table class="report-table">
        <thead><tr><th>Status</th><th>Total Orders</th></tr></thead>
        <tbody>
        <?php foreach ($statuses as $status): ?>
            <tr>
                <td><span class="status-badge status-<?= $status ?>"><?= $status ?></span></td>
                <td><?= (int) ($order_status_counts[$status] ?? 0) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="report-section">
    <h2>Customer Report</h2>
    <div class="report-stats">
        <div>
            <div class="report-stat-value"><?= $total_customers ?></div>
            <div class="report-stat-label">Total Customers</div>
        </div>
        <div>
            <div class="report-stat-value"><?= $active_customers ?></div>
            <div class="report-stat-label">Active</div>
        </div>
        <div>
            <div class="report-stat-value"><?= $inactive_customers ?></div>
            <div class="report-stat-label">Inactive</div>
        </div>
    </div>

    <h3 style="margin-top:16px; font-size:1rem; color:var(--color-text-secondary);">Top Customers</h3>
    <?php if (empty($top_customers)): ?>
        <p style="color:var(--color-text-secondary);">No orders yet.</p>
    <?php else: ?>
        <table class="report-table">
            <thead><tr><th>Name</th><th>Email</th><th>Orders</th><th>Total Spent</th></tr></thead>
            <tbody>
            <?php foreach ($top_customers as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['full_name']) ?></td>
                    <td><?= htmlspecialchars($c['email']) ?></td>
                    <td><?= (int) $c['order_count'] ?></td>
                    <td>₹<?= number_format((float) $c['total_spent'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="report-section">
    <h2>Revenue Report</h2>
    <?php if (empty($revenue_by_method)): ?>
        <p style="color:var(--color-text-secondary);">No revenue data for this period.</p>
    <?php else: ?>
        <table class="report-table">
            <thead><tr><th>Payment Method</th><th>Orders</th><th>Revenue</th></tr></thead>
            <tbody>
            <?php foreach ($revenue_by_method as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['payment_method']) ?></td>
                    <td><?= (int) $r['total_orders'] ?></td>
                    <td>₹<?= number_format((float) $r['total_revenue'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</main>
</div>
</body>
</html>