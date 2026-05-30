<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;

if ($roomId <= 0) {
    die("Invalid chat room.");
}

$userEmail = $conn->real_escape_string($_SESSION['user']);

$userRes = $conn->query("
    SELECT id, name, email
    FROM users
    WHERE email = '$userEmail'
    LIMIT 1
");

$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    die("User not found.");
}

$userId = (int)$user['id'];

$roomRes = $conn->query("
    SELECT 
        cr.*,
        p.name AS product_name,
        p.image AS product_image,
        p.price AS product_price,
        p.status AS product_status,
        buyer.name AS buyer_name,
        seller.name AS seller_name
    FROM chat_rooms cr
    INNER JOIN products p ON cr.product_id = p.id
    INNER JOIN users buyer ON cr.buyer_id = buyer.id
    INNER JOIN users seller ON cr.seller_id = seller.id
    WHERE cr.id = $roomId
    AND (cr.buyer_id = $userId OR cr.seller_id = $userId)
    LIMIT 1
");

$room = $roomRes ? $roomRes->fetch_assoc() : null;

if (!$room) {
    die("Chat room not found or access denied.");
}

$isBuyer = ((int)$room['buyer_id'] === $userId);
$isSeller = ((int)$room['seller_id'] === $userId);

$otherUserName = $isBuyer ? $room['seller_name'] : $room['buyer_name'];

$acceptedOfferAmount = null;
$acceptedOfferId = 0;

$acceptedOfferRes = $conn->query("
    SELECT id, offer_amount
    FROM product_offers
    WHERE product_id = " . (int)$room['product_id'] . "
    AND buyer_id = " . (int)$room['buyer_id'] . "
    AND seller_id = " . (int)$room['seller_id'] . "
    AND status = 'accepted'
    ORDER BY id DESC
    LIMIT 1
");

if ($acceptedOfferRes && $acceptedOfferRes->num_rows > 0) {
    $acceptedOffer = $acceptedOfferRes->fetch_assoc();
    $acceptedOfferId = (int)$acceptedOffer['id'];
    $acceptedOfferAmount = (float)$acceptedOffer['offer_amount'];
}

$latestOffer = null;

$latestOfferRes = $conn->query("
    SELECT *
    FROM product_offers
    WHERE product_id = " . (int)$room['product_id'] . "
    AND buyer_id = " . (int)$room['buyer_id'] . "
    AND seller_id = " . (int)$room['seller_id'] . "
    ORDER BY id DESC
    LIMIT 1
");

if ($latestOfferRes && $latestOfferRes->num_rows > 0) {
    $latestOffer = $latestOfferRes->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .chat-wrap{
            max-width: 950px;
            margin: 30px auto;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .chat-header{
            padding: 18px 22px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: #f8fafc;
        }

        .chat-header img{
            width: 78px;
            height: 78px;
            object-fit: cover;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            background: white;
        }

        .chat-header-info{
            flex: 1;
        }

        .chat-header h2{
            margin: 0;
            font-size: 20px;
            color: #0f172a;
        }

        .chat-header p{
            margin: 5px 0 0;
            color: #64748b;
            line-height: 1.5;
        }

        .offer-summary{
            margin-top: 8px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .offer-pill{
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .offer-pill.accepted{
            background: #dcfce7;
            color: #166534;
        }

        .offer-pill.pending{
            background: #fef3c7;
            color: #92400e;
        }

        .offer-pill.rejected,
        .offer-pill.expired{
            background: #fee2e2;
            color: #991b1b;
        }

        .accepted-offer-actions{
            margin-top: 12px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .chat-box{
            height: 430px;
            overflow-y: auto;
            padding: 20px;
            background: #f1f5f9;
        }

        .message-row{
            display: flex;
            margin-bottom: 12px;
        }

        .message-row.mine{
            justify-content: flex-end;
        }

        .message-bubble{
            max-width: 72%;
            padding: 12px 14px;
            border-radius: 16px;
            background: white;
            color: #0f172a;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            line-height: 1.5;
        }

        .message-row.mine .message-bubble{
            background: #2563eb;
            color: white;
        }

        .message-meta{
            font-size: 11px;
            opacity: 0.75;
            margin-top: 6px;
        }

        .signed-badge{
            display: inline-block;
            margin-top: 7px;
            padding: 4px 8px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: 11px;
            font-weight: 700;
        }

        .message-row.mine .signed-badge{
            background: rgba(255,255,255,0.18);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .chat-actions-panel{
            display:flex;
            gap:8px;
            flex-wrap:wrap;
            padding:12px 16px;
            background:#ffffff;
            border-top:1px solid #e5e7eb;
        }

        .chat-action-btn,
        .chat-chip{
            border:none;
            padding:9px 12px;
            border-radius:999px;
            background:#eff6ff;
            color:#1d4ed8;
            font-weight:700;
            cursor:pointer;
            transition: 0.2s ease;
        }

        .chat-action-btn{
            background:#2563eb;
            color:white;
        }

        .chat-action-btn.success{
            background:#16a34a;
            color:white;
        }

        .chat-chip:hover,
        .chat-action-btn:hover{
            transform:translateY(-1px);
        }

        .offer-box{
            display:none;
            gap:10px;
            padding:12px 16px;
            background:#f8fafc;
            border-top:1px solid #e5e7eb;
        }

        .offer-box input{
            flex:1;
            padding:12px;
            border-radius:12px;
            border:1px solid #cbd5e1;
        }

        .offer-box button{
            border:none;
            padding:12px 16px;
            border-radius:12px;
            background:#16a34a;
            color:white;
            font-weight:700;
            cursor:pointer;
        }

        .offer-action-row{
            margin-top:10px;
            display:flex;
            gap:8px;
            flex-wrap: wrap;
        }

        .offer-action-row button{
            border:none;
            border-radius:10px;
            padding:8px 12px;
            font-weight:700;
            cursor:pointer;
        }

        .accept-offer-btn{
            background:#16a34a;
            color:white;
        }

        .reject-offer-btn{
            background:#dc2626;
            color:white;
        }

        .chat-form{
            display: flex;
            gap: 10px;
            padding: 16px;
            border-top: 1px solid #e5e7eb;
            background: white;
        }

        .chat-form input[type="text"]{
            flex: 1;
            padding: 13px 14px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
        }

        .chat-form button{
            padding: 12px 18px;
            border-radius: 12px;
            border: none;
            background: #2563eb;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }

        .chat-form button:hover{
            background: #1d4ed8;
        }

        @media(max-width: 768px){
            .chat-header{
                align-items: flex-start;
            }

            .message-bubble{
                max-width: 86%;
            }

            .chat-form{
                flex-direction: column;
            }

            .offer-box{
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
    <div class="container">

        <div class="chat-wrap">
            <div class="chat-header">
                <img src="uploads/<?php echo htmlspecialchars($room['product_image']); ?>" alt="Product">

                <div class="chat-header-info">
                    <h2>Chat with <?php echo htmlspecialchars($otherUserName); ?></h2>

                    <p>
                        Product:
                        <strong><?php echo htmlspecialchars($room['product_name']); ?></strong>
                        · Listed Price:
                        <strong>Rs <?php echo number_format((float)$room['product_price'], 2); ?></strong>
                    </p>

                    <div class="offer-summary">
                        <?php if ($acceptedOfferAmount !== null): ?>
                            <span class="offer-pill accepted">
                                Accepted Offer: Rs <?php echo number_format($acceptedOfferAmount, 2); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($latestOffer): ?>
                            <span class="offer-pill <?php echo htmlspecialchars($latestOffer['status']); ?>">
                                Latest Offer: Rs <?php echo number_format((float)$latestOffer['offer_amount'], 2); ?>
                                · <?php echo htmlspecialchars(ucfirst($latestOffer['status'])); ?>
                            </span>
                        <?php endif; ?>

                        <span class="offer-pill">
                            Product Status: <?php echo htmlspecialchars(ucfirst($room['product_status'])); ?>
                        </span>
                    </div>

                    <?php if ($isBuyer && $acceptedOfferAmount !== null && $room['product_status'] !== 'sold'): ?>
                        <div class="accepted-offer-actions">
                            <button
                                type="button"
                                class="chat-action-btn success"
                                onclick="addNegotiatedItemToCart()"
                            >
                                Add to Cart for Rs <?php echo number_format($acceptedOfferAmount, 2); ?>
                            </button>

                            <a href="cart.php" class="chat-chip" style="text-decoration:none;">
                                Go to Cart
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="chat-box" id="chatBox">
                Loading messages...
            </div>

            <div class="chat-actions-panel">
                <?php if ($isBuyer): ?>
                    <button type="button" class="chat-action-btn" onclick="openOfferBox()">Make Offer</button>
                    <button type="button" class="chat-chip" onclick="quickMessage('Is this still available?')">Is this available?</button>
                    <button type="button" class="chat-chip" onclick="quickMessage('Can you send more photos?')">More photos?</button>
                    <button type="button" class="chat-chip" onclick="quickMessage('Is the price negotiable?')">Negotiable?</button>
                <?php else: ?>
                    <button type="button" class="chat-chip" onclick="quickMessage('Yes, it is available.')">Available</button>
                    <button type="button" class="chat-chip" onclick="quickMessage('The price is fixed.')">Price fixed</button>
                    <button type="button" class="chat-chip" onclick="quickMessage('Yes, we can negotiate.')">Can negotiate</button>
                <?php endif; ?>
            </div>

            <?php if ($isBuyer): ?>
                <div id="offerBox" class="offer-box">
                    <input type="number" id="offerAmount" min="1" step="0.01" placeholder="Enter offer amount">
                    <button type="button" onclick="sendOffer()">Send Offer</button>
                </div>
            <?php endif; ?>

            <form class="chat-form" id="chatForm">
                <input type="hidden" name="room_id" value="<?php echo (int)$roomId; ?>">
                <input type="hidden" name="message_type" value="normal">

                <input type="text" name="message" id="messageInput" placeholder="Type your message..." autocomplete="off">

                <button type="submit">Send</button>
            </form>
        </div>

    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>

<script>
const roomId = <?php echo (int)$roomId; ?>;
const chatBox = document.getElementById("chatBox");
const chatForm = document.getElementById("chatForm");
const messageInput = document.getElementById("messageInput");

let firstLoad = true;

function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}

function fetchMessages() {
    const wasNearBottom =
        chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 120;

    fetch("chat_fetch_messages.php?room_id=" + roomId)
        .then(response => response.json())
        .then(data => {
            if (data.status !== "success") {
                chatBox.innerHTML = "<p>Could not load messages.</p>";
                return;
            }

            let html = "";

            if (data.messages.length === 0) {
                html = "<p style='text-align:center; color:#64748b;'>No messages yet. Start the conversation.</p>";
            } else {
                data.messages.forEach(msg => {
                    let offerButtons = "";

                    if (msg.offer && msg.offer.can_respond) {
                        offerButtons = `
                            <div class="offer-action-row">
                                <button class="accept-offer-btn" onclick="respondOffer(${msg.offer.id}, 'accept')">Accept Offer</button>
                                <button class="reject-offer-btn" onclick="respondOffer(${msg.offer.id}, 'reject')">Reject Offer</button>
                            </div>
                        `;
                    }

                    html += `
                        <div class="message-row ${msg.is_mine ? "mine" : ""}">
                            <div class="message-bubble">
                                <div>${escapeHtml(msg.message_text)}</div>

                                ${msg.is_signed ? "<span class='signed-badge'>RSA Signed</span>" : ""}

                                ${offerButtons}

                                <div class="message-meta">
                                    ${escapeHtml(msg.sender_name)} · ${escapeHtml(msg.created_at)}
                                </div>
                            </div>
                        </div>
                    `;
                });
            }

            chatBox.innerHTML = html;

            if (firstLoad || wasNearBottom) {
                chatBox.scrollTop = chatBox.scrollHeight;
            }

            firstLoad = false;
        })
        .catch(() => {
            chatBox.innerHTML = "<p>Could not load messages.</p>";
        });
}

chatForm.addEventListener("submit", function(e) {
    e.preventDefault();

    const message = messageInput.value.trim();

    if (message === "") {
        return;
    }

    const formData = new FormData(chatForm);

    fetch("chat_send_message.php", {
        method: "POST",
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            messageInput.value = "";
            fetchMessages();
        } else {
            showToast(data.message || "Could not send message.", "error");
        }
    })
    .catch(() => {
        showToast("Could not send message.", "error");
    });
});

function openOfferBox() {
    const box = document.getElementById("offerBox");

    if (!box) return;

    box.style.display = (box.style.display === "flex") ? "none" : "flex";
}

function sendOffer() {
    const amountInput = document.getElementById("offerAmount");

    if (!amountInput) return;

    const amount = amountInput.value;

    if (!amount || parseFloat(amount) <= 0) {
        showToast("Enter a valid offer amount.", "warning");
        return;
    }

    const formData = new URLSearchParams();
    formData.append("room_id", roomId);
    formData.append("offer_amount", amount);

    fetch("chat_make_offer.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showToast(data.message || "Offer updated.", "success");

        if (data.status === "success") {
            amountInput.value = "";
            document.getElementById("offerBox").style.display = "none";
            fetchMessages();
            setTimeout(() => window.location.reload(), 600);
        }
    })
    .catch(() => {
        showToast("Could not send offer.", "error");
    });
}

function respondOffer(offerId, actionType) {
    const confirmText = actionType === "accept"
        ? "Accept this offer?"
        : "Reject this offer?";

    if (!confirm(confirmText)) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append("offer_id", offerId);
    formData.append("action_type", actionType);

    fetch("chat_offer_action.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showToast(data.message || "Offer updated.", "success");
        fetchMessages();
        setTimeout(() => window.location.reload(), 700);
    })
    .catch(() => {
        showToast("Could not update offer.", "error");
    });
}

function addNegotiatedItemToCart() {
    const formData = new URLSearchParams();
    formData.append("room_id", roomId);

    fetch("chat_add_offer_to_cart.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showToast(data.message || "Cart updated.", "success");

        if (data.status === "success") {
            window.location.href = "cart.php";
        }
    })
    .catch(() => {
        showToast("Could not add negotiated item to cart.", "error");
    });
}

function quickMessage(text) {
    messageInput.value = text;
    messageInput.focus();
}

fetchMessages();
setInterval(fetchMessages, 2000);
function refreshMessageBadgeNow() {
    fetch("ajax_nav_counts.php")
    .then(response => response.json())
    .then(data => {
        const messageBadge = document.getElementById("messageFloatingCount");
        const notificationBadge = document.getElementById("notificationCountBadge");

        if (messageBadge) {
            if (parseInt(data.messages) > 0) {
                messageBadge.innerText = data.messages;
                messageBadge.style.display = "flex";
            } else {
                messageBadge.innerText = "";
                messageBadge.style.display = "none";
            }
        }

        if (notificationBadge) {
            if (parseInt(data.notifications) > 0) {
                notificationBadge.innerText = data.notifications;
                notificationBadge.style.display = "flex";
            } else {
                notificationBadge.innerText = "";
                notificationBadge.style.display = "none";
            }
        }
    })
    .catch(() => {});
}
setTimeout(function(){

    refreshMessageBadgeNow();

    document.querySelectorAll(".notification-item").forEach(item => {

        const text = item.innerText.toLowerCase();

        if(
            text.includes("new message about") ||
            text.includes("message") ||
            text.includes("chat")
        ){
            item.classList.remove("unread");
        }

    });

},700);
</script>

</body>
</html>