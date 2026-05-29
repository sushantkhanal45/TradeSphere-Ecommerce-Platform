<?php
function adminHeader($pageTitle) {
    $adminName = $_SESSION['admin_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?> - TradeSphere Admin</title>
    <link rel="stylesheet" href="../css/style.css">

    <style>
        *{
            box-sizing:border-box;
        }

        body{
            margin:0;
            background:#f4f6fb;
            font-family:"Segoe UI", Arial, sans-serif;
            color:#111827;
        }

        .admin-layout{
            display:flex;
            min-height:100vh;
        }

        .admin-sidebar{
            width:260px;
            background:#0f172a;
            color:white;
            padding:26px 20px;
            position:fixed;
            top:0;
            left:0;
            height:100vh;
            overflow-y:auto;
        }

        .admin-brand{
            font-size:26px;
            font-weight:800;
            margin-bottom:6px;
        }

        .admin-subtitle{
            font-size:13px;
            color:#cbd5e1;
            margin-bottom:28px;
        }

        .admin-menu{
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .admin-menu a{
            color:#e5e7eb;
            text-decoration:none;
            padding:12px 14px;
            border-radius:12px;
            font-weight:600;
            transition:0.2s ease;
            display:flex;
            align-items:center;
            gap:10px;
        }

        .admin-menu a:hover,
        .admin-menu a.active{
            background:#2563eb;
            color:white;
        }

        .admin-main{
            margin-left:260px;
            width:calc(100% - 260px);
            padding:28px;
        }

        .admin-topbar{
            background:white;
            border-radius:20px;
            padding:20px 24px;
            box-shadow:0 10px 30px rgba(15,23,42,0.08);
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
            margin-bottom:24px;
        }

        .admin-topbar h1{
            margin:0;
            font-size:28px;
        }

        .admin-topbar p{
            margin:6px 0 0;
            color:#64748b;
        }

        .admin-profile{
            display:flex;
            align-items:center;
            gap:12px;
        }

        .admin-avatar{
            width:44px;
            height:44px;
            border-radius:50%;
            background:#2563eb;
            color:white;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:800;
        }

        .admin-card{
            background:white;
            border-radius:20px;
            box-shadow:0 10px 30px rgba(15,23,42,0.08);
            padding:22px;
            margin-bottom:22px;
        }

        .admin-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(220px,1fr));
            gap:18px;
        }

        .stat-card{
            background:white;
            border-radius:20px;
            padding:24px;
            box-shadow:0 10px 30px rgba(15,23,42,0.08);
            border-left:5px solid #2563eb;
        }

        .stat-card h3{
            margin:0 0 12px;
            font-size:15px;
            color:#64748b;
        }

        .stat-card .number{
            font-size:34px;
            font-weight:900;
            color:#0f172a;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        th, td{
            padding:13px 12px;
            border-bottom:1px solid #e5e7eb;
            text-align:left;
            vertical-align:middle;
            font-size:14px;
        }

        th{
            background:#f8fafc;
            font-weight:800;
            color:#334155;
        }

        tr:hover{
            background:#f9fafb;
        }

        .table-wrap{
            overflow-x:auto;
        }

        .badge{
            display:inline-block;
            padding:6px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:800;
        }

        .badge-green{background:#dcfce7;color:#166534;}
        .badge-red{background:#fee2e2;color:#991b1b;}
        .badge-blue{background:#dbeafe;color:#1d4ed8;}
        .badge-yellow{background:#fef3c7;color:#92400e;}
        .badge-gray{background:#f3f4f6;color:#374151;}
        .badge-purple{background:#ede9fe;color:#6d28d9;}

        .mini-btn{
            border:none;
            padding:8px 11px;
            border-radius:9px;
            cursor:pointer;
            font-size:12px;
            font-weight:800;
            text-decoration:none;
            display:inline-block;
        }

        .btn-blue{background:#2563eb;color:white;}
        .btn-green{background:#16a34a;color:white;}
        .btn-red{background:#dc2626;color:white;}
        .btn-dark{background:#111827;color:white;}
        .btn-gray{background:#d1d5db;color:#374151;}

        .action-row{
            display:flex;
            gap:8px;
            flex-wrap:wrap;
            align-items:center;
        }

        .form-row{
            display:flex;
            flex-direction:column;
            gap:7px;
            margin-bottom:16px;
        }

        .form-row label{
            font-weight:800;
        }

        .form-row input,
        .form-row select,
        .form-row textarea{
            width:100%;
            padding:12px 14px;
            border:1px solid #d1d5db;
            border-radius:12px;
            outline:none;
            font-size:14px;
        }

        .form-row input:focus,
        .form-row select:focus,
        .form-row textarea:focus{
            border-color:#2563eb;
            box-shadow:0 0 0 3px rgba(37,99,235,0.12);
        }

        .success-msg,
        .error-msg{
            padding:12px 14px;
            border-radius:12px;
            margin-bottom:18px;
            font-weight:700;
        }

        .success-msg{
            background:#dcfce7;
            color:#166534;
        }

        .error-msg{
            background:#fee2e2;
            color:#991b1b;
        }

        .muted{
            color:#64748b;
            font-size:13px;
        }

        .trade-toast{
            position:fixed;
            top:24px;
            right:24px;
            min-width:280px;
            max-width:420px;
            padding:14px 18px;
            border-radius:12px;
            color:white;
            font-weight:800;
            z-index:99999;
            opacity:0;
            transform:translateY(-16px);
            transition:0.25s ease;
            box-shadow:0 10px 25px rgba(0,0,0,0.18);
        }

        .trade-toast.show{
            opacity:1;
            transform:translateY(0);
        }

        .trade-toast.success{background:#16a34a;}
        .trade-toast.error{background:#dc2626;}
        .trade-toast.warning{background:#f59e0b;}

        @media(max-width:900px){
            .admin-sidebar{
                position:relative;
                width:100%;
                height:auto;
            }

            .admin-layout{
                flex-direction:column;
            }

            .admin-main{
                margin-left:0;
                width:100%;
                padding:18px;
            }

            .admin-topbar{
                flex-direction:column;
                align-items:flex-start;
            }
        }
    </style>
</head>
<body>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-brand">TradeSphere</div>
        <div class="admin-subtitle">Admin Control Panel</div>

        <nav class="admin-menu">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="manage_users.php">👥 Manage Users</a>
            <a href="manage_products.php">📦 Manage Products</a>
            <a href="manage_categories.php">🏷 Manage Categories</a>
            <a href="manage_orders.php">🧾 Manage Orders</a>
            <a href="signature.php">🔐 RSA Signatures</a>
            <a href="admin_logout.php">🚪 Logout</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
                <p>Manage and monitor TradeSphere marketplace activities.</p>
            </div>

            <div class="admin-profile">
                <div>
                    <strong><?php echo htmlspecialchars($adminName); ?></strong><br>
                    <span class="muted">Administrator</span>
                </div>
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                </div>
            </div>
        </div>
<?php
}

function adminFooter($success = "", $error = "") {
?>
    </main>
</div>

<div id="tradeToast" class="trade-toast"></div>

<script>
function showTradeToast(message, type = "success") {
    const toast = document.getElementById("tradeToast");
    if (!toast || !message) return;

    toast.textContent = message;
    toast.className = "trade-toast show " + type;

    setTimeout(() => {
        toast.classList.remove("show");
    }, 2500);
}

<?php if (!empty($success)): ?>
showTradeToast("<?php echo addslashes($success); ?>", "success");
<?php endif; ?>

<?php if (!empty($error)): ?>
showTradeToast("<?php echo addslashes($error); ?>", "error");
<?php endif; ?>
</script>

</body>
</html>
<?php
}
?>