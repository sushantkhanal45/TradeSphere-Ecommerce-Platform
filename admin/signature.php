<?php
session_start();
include "../config/db.php";
include "../includes/rsa_helper.php";
include "admin_layout.php";

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

$selectedAction = $_GET['action_type'] ?? '';

$query = "
    SELECT
        s.*,
        u.name AS user_name,
        u.email AS user_email
    FROM signatures s
    LEFT JOIN users u ON s.user_id = u.id
";

if ($selectedAction !== '') {
    $safeAction = $conn->real_escape_string($selectedAction);
    $query .= " WHERE s.action_type = '$safeAction'";
}

$query .= " ORDER BY s.id DESC";

$signatures = $conn->query($query);

$totalSignatures = 0;
$totalValid = 0;
$totalInvalid = 0;

if ($signatures) {
    while ($tmp = $signatures->fetch_assoc()) {
        $totalSignatures++;

        if (verifySignature($tmp['signed_data'], $tmp['signature'])) {
            $totalValid++;
        } else {
            $totalInvalid++;
        }
    }

    $signatures->data_seek(0);
}

adminHeader("RSA Signature Audit");
?>

<div class="admin-grid">
    <div class="stat-card">
        <h3>Total Signatures</h3>
        <div class="number"><?php echo $totalSignatures; ?></div>
    </div>

    <div class="stat-card">
        <h3>Valid Signatures</h3>
        <div class="number"><?php echo $totalValid; ?></div>
    </div>

    <div class="stat-card">
        <h3>Invalid Signatures</h3>
        <div class="number"><?php echo $totalInvalid; ?></div>
    </div>
</div>

<div class="admin-card">
    <h2 style="margin-top:0;">RSA Security Audit Log</h2>

    <p class="muted" style="margin-bottom:20px;">
        This section displays digitally signed marketplace actions.
        Every record is verified using RSA digital signature validation
        to ensure data integrity and authenticity.
    </p>

    <form method="GET" style="margin-bottom:22px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <label for="action_type"><strong>Filter by Action:</strong></label>

        <select 
            name="action_type" 
            id="action_type" 
            onchange="this.form.submit()"
            style="
                padding:10px 14px;
                border:1px solid #d1d5db;
                border-radius:10px;
                min-width:260px;
                background:white;
            "
        >
            <option value="">All Actions</option>

            <optgroup label="Products">
                <option value="product_created" <?php echo ($selectedAction === 'product_created') ? 'selected' : ''; ?>>
                    Product Created
                </option>
                <option value="product_updated" <?php echo ($selectedAction === 'product_updated') ? 'selected' : ''; ?>>
                    Product Updated
                </option>
                <option value="product_deleted" <?php echo ($selectedAction === 'product_deleted') ? 'selected' : ''; ?>>
                    Product Deleted
                </option>
                <option value="product_status_update" <?php echo ($selectedAction === 'product_status_update') ? 'selected' : ''; ?>>
                    Product Status Update
                </option>
            </optgroup>

            <optgroup label="Offers">
                <option value="offer_created" <?php echo ($selectedAction === 'offer_created') ? 'selected' : ''; ?>>
                    Offer Created
                </option>
                <option value="offer_accepted" <?php echo ($selectedAction === 'offer_accepted') ? 'selected' : ''; ?>>
                    Offer Accepted
                </option>
                <option value="offer_rejected" <?php echo ($selectedAction === 'offer_rejected') ? 'selected' : ''; ?>>
                    Offer Rejected
                </option>
            </optgroup>

            <optgroup label="Orders">
                <option value="order_created" <?php echo ($selectedAction === 'order_created') ? 'selected' : ''; ?>>
                    Order Created
                </option>
                <option value="seller_delivery_status_update" <?php echo ($selectedAction === 'seller_delivery_status_update') ? 'selected' : ''; ?>>
                    Delivery Update
                </option>
                <option value="buyer_confirmed_received" <?php echo ($selectedAction === 'buyer_confirmed_received') ? 'selected' : ''; ?>>
                    Buyer Received
                </option>
            </optgroup>

            <optgroup label="Payments">
                <option value="payment_success" <?php echo ($selectedAction === 'payment_success') ? 'selected' : ''; ?>>
                    Payment Success
                </option>
                <option value="payment_failure" <?php echo ($selectedAction === 'payment_failure') ? 'selected' : ''; ?>>
                    Payment Failure
                </option>
            </optgroup>

            <optgroup label="Chat / Transaction Messages">
                <option value="chat_offer" <?php echo ($selectedAction === 'chat_offer') ? 'selected' : ''; ?>>
                    Chat Offer
                </option>
                <option value="chat_acceptance" <?php echo ($selectedAction === 'chat_acceptance') ? 'selected' : ''; ?>>
                    Chat Acceptance
                </option>
                <option value="chat_delivery_agreement" <?php echo ($selectedAction === 'chat_delivery_agreement') ? 'selected' : ''; ?>>
                    Delivery Agreement
                </option>
                <option value="chat_cancellation_request" <?php echo ($selectedAction === 'chat_cancellation_request') ? 'selected' : ''; ?>>
                    Cancellation Request
                </option>
            </optgroup>
        </select>

        <?php if ($selectedAction !== ''): ?>
            <a href="signature.php" class="badge badge-gray" style="text-decoration:none;">
                Clear Filter
            </a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Action Type</th>
                    <th>Related ID</th>
                    <th>Signed Data</th>
                    <th>Verification</th>
                    <th>Created At</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($signatures && $signatures->num_rows > 0): ?>
                <?php while ($row = $signatures->fetch_assoc()): ?>
                    <?php
                    $isValid = verifySignature(
                        $row['signed_data'],
                        $row['signature']
                    );
                    ?>

                    <tr>
                        <td>
                            <strong>#<?php echo (int)$row['id']; ?></strong>
                        </td>

                        <td>
                            <?php if (!empty($row['user_name'])): ?>
                                <strong>
                                    <?php echo htmlspecialchars($row['user_name']); ?>
                                </strong>

                                <br>

                                <span class="muted">
                                    <?php echo htmlspecialchars($row['user_email']); ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-gray">
                                    Unknown User
                                </span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="badge badge-blue">
                                <?php echo htmlspecialchars($row['action_type']); ?>
                            </span>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row['related_id']); ?>
                        </td>

                        <td>
                            <div style="
                                max-width:350px;
                                max-height:160px;
                                overflow:auto;
                                padding:10px;
                                border-radius:10px;
                                background:#f8fafc;
                                border:1px solid #e5e7eb;
                                font-size:12px;
                                line-height:1.5;
                                word-break:break-word;
                            ">
                                <?php echo htmlspecialchars($row['signed_data']); ?>
                            </div>
                        </td>

                        <td>
                            <?php if ($isValid): ?>
                                <span class="badge badge-green">
                                    Valid Signature
                                </span>
                            <?php else: ?>
                                <span class="badge badge-red">
                                    Invalid Signature
                                </span>
                            <?php endif; ?>

                            <div style="
                                margin-top:10px;
                                font-size:11px;
                                color:#64748b;
                                word-break:break-all;
                                max-width:280px;
                            ">
                                <?php echo htmlspecialchars(substr($row['signature'], 0, 100)); ?>...
                            </div>
                        </td>

                        <td>
                            <span class="muted">
                                <?php echo htmlspecialchars($row['created_at']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">
                        No RSA signature records found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php adminFooter(); ?>