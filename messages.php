<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userEmail = $conn->real_escape_string($_SESSION['user']);

$userRes = $conn->query("
    SELECT id, name
    FROM users
    WHERE email = '$userEmail'
    LIMIT 1
");

$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    die("User not found.");
}

$userId = (int)$user['id'];

$rooms = $conn->query("
    SELECT 
        cr.*,
        p.name AS product_name,
        p.image AS product_image,
        p.price AS product_price,
        buyer.name AS buyer_name,
        seller.name AS seller_name,

        (
            SELECT message_text
            FROM chat_messages
            WHERE room_id = cr.id
            ORDER BY created_at DESC
            LIMIT 1
        ) AS last_message,

        (
            SELECT created_at
            FROM chat_messages
            WHERE room_id = cr.id
            ORDER BY created_at DESC
            LIMIT 1
        ) AS last_message_time,

        (
            SELECT COUNT(*)
            FROM chat_messages
            WHERE room_id = cr.id
            AND receiver_id = $userId
            AND is_read = 0
        ) AS unread_count

    FROM chat_rooms cr
    INNER JOIN products p ON cr.product_id = p.id
    INNER JOIN users buyer ON cr.buyer_id = buyer.id
    INNER JOIN users seller ON cr.seller_id = seller.id
    WHERE cr.buyer_id = $userId
    OR cr.seller_id = $userId
    ORDER BY last_message_time DESC, cr.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .messages-wrap{
            max-width: 950px;
            margin: 30px auto;
        }

        .messages-card{
            background: white;
            border-radius: 20px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .messages-head{
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        .messages-head h2{
            margin: 0;
            color: #0f172a;
        }

    .message-room{
    display: flex;
    gap: 16px;
    padding: 16px 20px;
    text-decoration: none;
    color: rgba(0,0,0,0.65);
    border-bottom: 1px solid #e2e8f0;
    transition: 0.2s ease;
    position: relative;
    background: rgba(219,234,254,0.18);
    border-left: 4px solid rgba(37,99,235,0.15);
}

        .message-room:hover{
    background: #bfdbfe;
    color: #1d4ed8;
}

        .message-room.unread{
    background: #dbeafe;
    color: rgba(0,0,0,0.95);
    border-left: 4px solid #2563eb;
    font-weight: 700;
}

        .message-room img{
            width: 74px;
            height: 74px;
            border-radius: 14px;
            object-fit: cover;
            border: 1px solid #e5e7eb;
            background: white;
        }

        .message-room-body{
            flex: 1;
            min-width: 0;
        }

        .message-room-top{
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }

        .message-room h3{
            margin: 0 0 5px;
            font-size: 17px;
            color: #0f172a;
        }

        .message-room p{
            margin: 4px 0;
            color: #475569;
            font-size: 14px;
        }

        .last-message{
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 620px;
        }

        .message-time{
            font-size: 12px;
            color: #64748b;
            white-space: nowrap;
        }

      .unread-pill{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 8px;
    border-radius: 999px;
    background: #dc2626;
    color: white;
    font-size: 12px;
    font-weight: 800;
    margin-top: 8px;
}

        .empty-messages{
            padding: 28px;
            text-align: center;
            color: #64748b;
        }

        @media(max-width: 700px){
            .message-room{
                align-items: flex-start;
            }

            .message-room img{
                width: 58px;
                height: 58px;
            }

            .message-room-top{
                flex-direction: column;
                gap: 4px;
            }

            .last-message{
                max-width: 260px;
            }
        }
        .message-room.unread h3,
.message-room.unread p{
    color: rgba(0,0,0,0.95);
    font-weight: 700;
}

.message-room:not(.unread) h3,
.message-room:not(.unread) p{
    color: rgba(0,0,0,0.62);
    font-weight: 500;
}
    </style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
    <div class="container messages-wrap">
        <div class="messages-card">
            <div class="messages-head">
                <h2>Messages</h2>
            </div>

            <?php if ($rooms && $rooms->num_rows > 0): ?>
                <?php while ($room = $rooms->fetch_assoc()): ?>
                    <?php
                        $isBuyer = ((int)$room['buyer_id'] === $userId);
                        $otherName = $isBuyer ? $room['seller_name'] : $room['buyer_name'];
                        $unreadCount = (int)$room['unread_count'];
                    ?>

                    <a 
                        href="chat.php?room_id=<?php echo (int)$room['id']; ?>" 
                        class="message-room <?php echo ($unreadCount > 0) ? 'unread' : ''; ?>"
                    >
                        <img src="uploads/<?php echo htmlspecialchars($room['product_image']); ?>" alt="Product">

                        <div class="message-room-body">
                            <div class="message-room-top">
                                <div>
                                    <h3><?php echo htmlspecialchars($otherName); ?></h3>
                                    <p>
                                        <strong><?php echo htmlspecialchars($room['product_name']); ?></strong>
                                        · Rs <?php echo number_format((float)$room['product_price'], 2); ?>
                                    </p>
                                </div>

                                <?php if (!empty($room['last_message_time'])): ?>
                                    <span class="message-time">
                                        <?php echo htmlspecialchars($room['last_message_time']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <p class="last-message">
                                <?php echo !empty($room['last_message']) 
                                    ? htmlspecialchars($room['last_message']) 
                                    : "No messages yet."; ?>
                            </p>

                            <?php if ($unreadCount > 0): ?>
                                <span class="unread-pill">
                                    <?php echo $unreadCount; ?> new
                                </span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-messages">
                    <p>No conversations yet.</p>
                    <a href="products.php" class="small-btn primary">Browse Products</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>
</body>
</html>