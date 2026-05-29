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

$signatures = $conn->query("
    SELECT
        s.*,
        u.name AS user_name,
        u.email AS user_email
    FROM signatures s
    LEFT JOIN users u ON s.user_id = u.id
    ORDER BY s.id DESC
");

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