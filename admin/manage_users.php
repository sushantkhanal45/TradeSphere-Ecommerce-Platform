<?php
session_start();
include "../config/db.php";
include "admin_layout.php";
include "../includes/mail_helper.php";

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$adminEmail = $conn->real_escape_string($_SESSION['admin']);

$adminCheck = $conn->query("
    SELECT * 
    FROM users 
    WHERE email='$adminEmail' 
    AND role='admin' 
    LIMIT 1
");

$adminUser = $adminCheck ? $adminCheck->fetch_assoc() : null;

if (!$adminUser) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

$_SESSION['admin_name'] = $adminUser['name'] ?? 'Admin';

$success = "";
$error = "";

function notifyUser($conn, $userId, $message) {
    $userId = (int)$userId;
    $safeMessage = $conn->real_escape_string($message);

    $conn->query("
        INSERT INTO notifications (user_id, order_id, message)
        VALUES ($userId, NULL, '$safeMessage')
    ");
}

function getUserById($conn, $userId) {
    $userId = (int)$userId;

    $res = $conn->query("
        SELECT *
        FROM users
        WHERE id=$userId
        LIMIT 1
    ");

    return $res ? $res->fetch_assoc() : null;
}

/* REMOVE USER */
if (isset($_POST['remove_user'])) {
    $userId = (int)$_POST['user_id'];
    $reason = trim($_POST['remove_reason'] ?? '');

    if ($reason === '') {
        $error = "Please provide a reason for removing the user.";
    } else {
        $targetUser = getUserById($conn, $userId);

        if (!$targetUser) {
            $error = "User not found.";
        } elseif ($targetUser['email'] === $_SESSION['admin']) {
            $error = "You cannot remove your own admin account.";
        } else {
            if (function_exists('sendUserRemovedEmail')) {
                @sendUserRemovedEmail(
                    $targetUser['email'],
                    $targetUser['name'],
                    $reason
                );
            }

            if ($conn->query("DELETE FROM users WHERE id=$userId")) {
                $success = "User removed successfully.";
            } else {
                $error = "Could not remove user. This user may have related records such as products, orders, chats, or notifications.";
            }
        }
    }
}

/* SELLER APPROVAL */
if (isset($_POST['approve_seller'])) {
    $userId = (int)$_POST['user_id'];
    $targetUser = getUserById($conn, $userId);

    if (!$targetUser) {
        $error = "User not found.";
    } else {
        if ($conn->query("
            UPDATE users
            SET seller_status='approved',
                seller_verified_at=NOW()
            WHERE id=$userId
        ")) {
            notifyUser(
                $conn,
                $userId,
                "Your seller verification request has been approved. You can now list products on TradeSphere."
            );

            if (function_exists('sendSellerApprovedEmail')) {
                @sendSellerApprovedEmail($targetUser['email'], $targetUser['name']);
            }

            $success = "Seller request approved successfully.";
        } else {
            $error = "Could not approve seller request.";
        }
    }
}

/* SELLER REJECTION */
if (isset($_POST['reject_seller'])) {
    $userId = (int)$_POST['user_id'];
    $reason = trim($_POST['reject_reason'] ?? '');

    if ($reason === '') {
        $reason = "Seller verification requirements were not met.";
    }

    $targetUser = getUserById($conn, $userId);

    if (!$targetUser) {
        $error = "User not found.";
    } else {
        if ($conn->query("
            UPDATE users
            SET seller_status='rejected'
            WHERE id=$userId
        ")) {
            notifyUser(
                $conn,
                $userId,
                "Your seller verification request was rejected. Reason: " . $reason
            );

            if (function_exists('sendSellerRejectedEmail')) {
                @sendSellerRejectedEmail($targetUser['email'], $targetUser['name'], $reason);
            }

            $success = "Seller request rejected successfully.";
        } else {
            $error = "Could not reject seller request.";
        }
    }
}

/* RESET SELLER STATUS */
if (isset($_POST['reset_seller'])) {
    $userId = (int)$_POST['user_id'];
    $targetUser = getUserById($conn, $userId);

    if (!$targetUser) {
        $error = "User not found.";
    } else {
        if ($conn->query("
            UPDATE users
            SET seller_status='none',
                seller_requested_at=NULL,
                seller_verified_at=NULL
            WHERE id=$userId
        ")) {
            notifyUser(
                $conn,
                $userId,
                "Your seller verification status has been reset by the administrator."
            );

            $success = "Seller status reset successfully.";
        } else {
            $error = "Could not reset seller status.";
        }
    }
}

/* ROLE MANAGEMENT */
if (isset($_POST['make_admin'])) {
    $userId = (int)$_POST['user_id'];

    if ($conn->query("UPDATE users SET role='admin' WHERE id=$userId")) {
        $success = "User role changed to admin.";
    } else {
        $error = "Could not update user role.";
    }
}

if (isset($_POST['make_user'])) {
    $userId = (int)$_POST['user_id'];

    if ($conn->query("UPDATE users SET role='user' WHERE id=$userId")) {
        $success = "Admin role removed successfully.";
    } else {
        $error = "Could not update user role.";
    }
}

$users = $conn->query("
    SELECT 
        u.id,
        u.name,
        u.email,
        u.role,
        u.is_verified,
        u.seller_status,
        u.seller_requested_at,
        u.seller_verified_at,
        u.created_at,
        COUNT(DISTINCT p.id) AS product_count,
        COUNT(DISTINCT o.id) AS order_count
    FROM users u
    LEFT JOIN products p ON u.id = p.user_id
    LEFT JOIN orders o ON u.email = o.buyer_email
    GROUP BY 
        u.id, u.name, u.email, u.role, u.is_verified,
        u.seller_status, u.seller_requested_at, u.seller_verified_at, u.created_at
    ORDER BY 
        CASE 
            WHEN u.seller_status='pending' THEN 0
            ELSE 1
        END,
        u.id DESC
");

function sellerBadgeClass($status) {
    if ($status === 'approved') return 'badge-green';
    if ($status === 'pending') return 'badge-yellow';
    if ($status === 'rejected') return 'badge-red';
    return 'badge-gray';
}

adminHeader("Manage Users");
?>

<?php if ($success): ?>
    <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="admin-card">
    <h2 style="margin-top:0;">User and Seller Verification Management</h2>
    <p class="muted" style="margin-bottom:18px;">
        View users, manage admin roles, approve or reject seller verification requests, and remove user accounts.
    </p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User Details</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Seller Status</th>
                    <th>Activity</th>
                    <th>Seller Dates</th>
                    <th>Seller Action</th>
                    <th>Role Action</th>
                    <th>Remove User</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($users && $users->num_rows > 0): ?>
                    <?php while ($row = $users->fetch_assoc()): ?>
                        <?php
                            $sellerStatus = $row['seller_status'] ?? 'none';
                            if ($sellerStatus === '') $sellerStatus = 'none';

                            $isCurrentAdmin = ($row['email'] === $_SESSION['admin']);
                        ?>

                        <tr>
                            <td><strong>#<?php echo (int)$row['id']; ?></strong></td>

                            <td>
                                <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                <span class="muted"><?php echo htmlspecialchars($row['email']); ?></span><br>
                                <span class="muted">Joined: <?php echo htmlspecialchars($row['created_at'] ?? ''); ?></span>
                            </td>

                            <td>
                                <?php if ($row['role'] === 'admin'): ?>
                                    <span class="badge badge-purple">Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-blue">User</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ((int)$row['is_verified'] === 1): ?>
                                    <span class="badge badge-green">Verified</span>
                                <?php else: ?>
                                    <span class="badge badge-red">Not Verified</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge <?php echo sellerBadgeClass($sellerStatus); ?>">
                                    <?php echo htmlspecialchars(ucfirst($sellerStatus)); ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge badge-gray"><?php echo (int)$row['product_count']; ?> Products</span>
                                <br><br>
                                <span class="badge badge-gray"><?php echo (int)$row['order_count']; ?> Orders</span>
                            </td>

                            <td>
                                <?php if (!empty($row['seller_requested_at'])): ?>
                                    <span class="muted">Requested: <?php echo htmlspecialchars($row['seller_requested_at']); ?></span><br>
                                <?php endif; ?>

                                <?php if (!empty($row['seller_verified_at'])): ?>
                                    <span class="muted">Verified: <?php echo htmlspecialchars($row['seller_verified_at']); ?></span>
                                <?php endif; ?>

                                <?php if (empty($row['seller_requested_at']) && empty($row['seller_verified_at'])): ?>
                                    <span class="muted">No seller request</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="action-row">
                                    <?php if ($sellerStatus === 'pending'): ?>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$row['id']; ?>">
                                            <button type="submit" name="approve_seller" class="mini-btn btn-green">Approve</button>
                                        </form>

                                        <form 
                                            method="POST" 
                                            style="margin:0;display:flex;gap:6px;align-items:center;"
                                            onsubmit="return confirm('Reject this seller verification request?');"
                                        >
                                            <input type="hidden" name="user_id" value="<?php echo (int)$row['id']; ?>">
                                            <input 
                                                type="text" 
                                                name="reject_reason" 
                                                placeholder="Reason"
                                                style="width:140px;padding:7px 9px;border:1px solid #d1d5db;border-radius:8px;"
                                            >
                                            <button type="submit" name="reject_seller" class="mini-btn btn-red">Reject</button>
                                        </form>

                                    <?php elseif ($sellerStatus === 'approved'): ?>
                                        <span class="badge badge-green">Seller Approved</span>

                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$row['id']; ?>">
                                            <button type="submit" name="reset_seller" class="mini-btn btn-gray">Reset</button>
                                        </form>

                                    <?php elseif ($sellerStatus === 'rejected'): ?>
                                        <span class="badge badge-red">Rejected</span>

                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$row['id']; ?>">
                                            <button type="submit" name="reset_seller" class="mini-btn btn-gray">Reset</button>
                                        </form>

                                    <?php else: ?>
                                        <span class="muted">No action</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td>
                                <?php if ($isCurrentAdmin): ?>
                                    <span class="badge badge-gray">Current Admin</span>
                                <?php else: ?>
                                    <?php if ($row['role'] === 'admin'): ?>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$row['id']; ?>">
                                            <button type="submit" name="make_user" class="mini-btn btn-dark">Make User</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$row['id']; ?>">
                                            <button type="submit" name="make_admin" class="mini-btn btn-blue">Make Admin</button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($isCurrentAdmin): ?>
                                    <span class="badge badge-gray">Protected</span>
                                <?php else: ?>
                                    <form 
                                        method="POST"
                                        style="margin:0;display:flex;gap:6px;align-items:center;"
                                        onsubmit="return confirm('Remove this user account? This action cannot be undone.');"
                                    >
                                        <input type="hidden" name="user_id" value="<?php echo (int)$row['id']; ?>">
                                        <input
                                            type="text"
                                            name="remove_reason"
                                            placeholder="Reason"
                                            required
                                            style="width:140px;padding:7px 9px;border:1px solid #d1d5db;border-radius:8px;"
                                        >
                                        <button type="submit" name="remove_user" class="mini-btn btn-red">
                                            Remove
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php adminFooter($success, $error); ?>