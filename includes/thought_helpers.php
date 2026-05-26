<?php

function thoughtProfileSelectSql(): string
{
    return "
        (SELECT profile_image FROM client_information WHERE client_id = t.sender_id AND t.sender_type = 'client' LIMIT 1) AS client_profile,
        (SELECT profile_image FROM admin_users WHERE admin_id = t.sender_id AND t.sender_type = 'admin' LIMIT 1) AS admin_profile
    ";
}

function fetchThoughtsFeed(mysqli $conn, string $user_id, string $user_type)
{
    $uid = $conn->real_escape_string($user_id);
    $utype = $conn->real_escape_string($user_type);
    $profiles = thoughtProfileSelectSql();

    $sql = "
        SELECT t.*,
            (SELECT COUNT(*) FROM thought_likes WHERE thought_id = t.id) AS likes,
            $profiles,
            tr.is_read
        FROM thoughts t
        JOIN thoughts_receivers tr ON tr.thought_id = t.id
        WHERE tr.receiver_id = '$uid' AND tr.receiver_type = '$utype'

        UNION

        SELECT t.*,
            (SELECT COUNT(*) FROM thought_likes WHERE thought_id = t.id) AS likes,
            $profiles,
            NULL AS is_read
        FROM thoughts t
        WHERE t.sender_id = '$uid' AND t.sender_type = '$utype'

        ORDER BY created_at DESC
    ";

    return mysqli_query($conn, $sql);
}

function getThoughtProfileImage(array $row): string
{
    if (($row['sender_type'] ?? '') === 'admin') {
        return !empty($row['admin_profile']) ? $row['admin_profile'] : 'default.png';
    }

    return !empty($row['client_profile']) ? $row['client_profile'] : 'default.png';
}

function distributeThoughtToReceivers(mysqli $conn, int $thought_id, string $sender_id, string $sender_type): void
{
    $insert = $conn->prepare(
        "INSERT INTO thoughts_receivers (thought_id, receiver_id, receiver_type, is_read, read_at)
         VALUES (?, ?, ?, 0, NULL)"
    );

    if ($sender_type === 'client') {
        $admins = mysqli_query($conn, "SELECT admin_id FROM admin_users");
        while ($admin = mysqli_fetch_assoc($admins)) {
            $receiver_id = (string) $admin['admin_id'];
            $receiver_type = 'admin';
            $insert->bind_param('iss', $thought_id, $receiver_id, $receiver_type);
            $insert->execute();
        }

        $clientStmt = $conn->prepare("SELECT client_id FROM client_information WHERE client_id != ?");
        $clientStmt->bind_param('s', $sender_id);
        $clientStmt->execute();
        $clients = $clientStmt->get_result();
        while ($client = $clients->fetch_assoc()) {
            $receiver_id = (string) $client['client_id'];
            $receiver_type = 'client';
            $insert->bind_param('iss', $thought_id, $receiver_id, $receiver_type);
            $insert->execute();
        }
        $clientStmt->close();
    } else {
        $adminStmt = $conn->prepare("SELECT admin_id FROM admin_users WHERE admin_id != ?");
        $adminStmt->bind_param('s', $sender_id);
        $adminStmt->execute();
        $admins = $adminStmt->get_result();
        while ($admin = $admins->fetch_assoc()) {
            $receiver_id = (string) $admin['admin_id'];
            $receiver_type = 'admin';
            $insert->bind_param('iss', $thought_id, $receiver_id, $receiver_type);
            $insert->execute();
        }
        $adminStmt->close();

        $clients = mysqli_query($conn, "SELECT client_id FROM client_information");
        while ($client = mysqli_fetch_assoc($clients)) {
            $receiver_id = (string) $client['client_id'];
            $receiver_type = 'client';
            $insert->bind_param('iss', $thought_id, $receiver_id, $receiver_type);
            $insert->execute();
        }
    }

    $insert->close();
}

function notifyThoughtActivity(
    mysqli $conn,
    int $thought_id,
    string $except_receiver_id,
    string $except_receiver_type
): void {
    $markUnread = $conn->prepare(
        "UPDATE thoughts_receivers
         SET is_read = 0, read_at = NULL
         WHERE thought_id = ?
           AND NOT (receiver_id = ? AND receiver_type = ?)"
    );
    $markUnread->bind_param('iss', $thought_id, $except_receiver_id, $except_receiver_type);
    $markUnread->execute();
    $markUnread->close();

    $senderStmt = $conn->prepare("SELECT sender_id, sender_type FROM thoughts WHERE id = ? LIMIT 1");
    $senderStmt->bind_param('i', $thought_id);
    $senderStmt->execute();
    $thought = $senderStmt->get_result()->fetch_assoc();
    $senderStmt->close();

    if (!$thought) {
        return;
    }

    $sender_id = (string) $thought['sender_id'];
    $sender_type = (string) $thought['sender_type'];

    if ($sender_id === $except_receiver_id && $sender_type === $except_receiver_type) {
        return;
    }

    $existsStmt = $conn->prepare(
        "SELECT 1 FROM thoughts_receivers
         WHERE thought_id = ? AND receiver_id = ? AND receiver_type = ?
         LIMIT 1"
    );
    $existsStmt->bind_param('iss', $thought_id, $sender_id, $sender_type);
    $existsStmt->execute();
    $exists = $existsStmt->get_result()->num_rows > 0;
    $existsStmt->close();

    if ($exists) {
        $updateSender = $conn->prepare(
            "UPDATE thoughts_receivers SET is_read = 0, read_at = NULL
             WHERE thought_id = ? AND receiver_id = ? AND receiver_type = ?"
        );
        $updateSender->bind_param('iss', $thought_id, $sender_id, $sender_type);
        $updateSender->execute();
        $updateSender->close();
        return;
    }

    $insert = $conn->prepare(
        "INSERT INTO thoughts_receivers (thought_id, receiver_id, receiver_type, is_read, read_at)
         VALUES (?, ?, ?, 0, NULL)"
    );
    $insert->bind_param('iss', $thought_id, $sender_id, $sender_type);
    $insert->execute();
    $insert->close();
}

function renderThoughtFeed(mysqli $conn, string $user_id, string $user_type): void
{
    $thoughts = fetchThoughtsFeed($conn, $user_id, $user_type);
    if (!$thoughts) {
        echo '<li><p class="text-muted mb-0">No thoughts yet. Be the first to post.</p></li>';
        return;
    }

    while ($row = mysqli_fetch_assoc($thoughts)) {
        $profileImage = getThoughtProfileImage($row);
        $thoughtId = (int) $row['id'];
        $isUnread = isset($row['is_read']) && (int) $row['is_read'] === 0;
        $unreadStyle = $isUnread ? 'background:#f0f9ff;border-left:3px solid #00b4d8;padding-left:10px;' : '';

        echo '<li class="thought-card" style="border-bottom:1px solid #eee; margin-bottom:14px; padding-bottom:10px;' . $unreadStyle . '">';
        echo '<div style="display:flex;align-items:flex-start;position:relative;">';
        echo '<img src="' . htmlspecialchars($profileImage) . '" alt="Profile" style="width:38px;height:38px;border-radius:50%;margin-right:10px;">';
        echo '<div style="flex:1;">';
        echo '<span style="font-weight:600;vertical-align:middle;">' . htmlspecialchars($row['username']) . '</span>';
        if ($isUnread) {
            echo ' <span class="badge badge-primary" style="font-size:10px;">New</span>';
        }
        echo '<div style="margin:2px 0 0 0;">' . nl2br(htmlspecialchars($row['content'])) . '</div>';
        echo '</div>';
        echo '<div style="position:absolute;top:0;right:0;font-size:13px;color:#888;">' . date('M d, Y H:i', strtotime($row['created_at'])) . '</div>';
        echo '</div>';

        echo '<div style="margin-left:48px;margin-top:4px;text-align:left;">';
        echo '<form method="POST" action="includes/like_thought.php" style="display:inline;">';
        echo '<input type="hidden" name="thought_id" value="' . $thoughtId . '">';
        echo '<button type="submit" style="border:none;background:none;color:#007bff;cursor:pointer;padding-right:6px;">';
        echo '<i class="fas fa-thumbs-up"></i> <span style="font-size:12px;color:#000;">' . (int) $row['likes'] . '</span>';
        echo '</button></form>';
        echo '<button type="button" class="comment-toggle-btn" style="border:none;background:none;color:#007bff;cursor:pointer;padding-left:2px;" onclick="toggleCommentBox(' . $thoughtId . ')">';
        echo '<i class="fas fa-comment"></i></button>';
        echo '</div>';

        $commentStmt = $conn->prepare("SELECT * FROM thought_comments WHERE thought_id = ? ORDER BY created_at ASC");
        $commentStmt->bind_param('i', $thoughtId);
        $commentStmt->execute();
        $comments = $commentStmt->get_result();

        echo '<div style="margin-left:48px;margin-top:6px;">';
        while ($c = $comments->fetch_assoc()) {
            echo '<div style="display:flex;align-items:center;margin-bottom:4px;">';
            echo '<div style="border-radius:8px;padding:4px 10px;">';
            echo '<strong>' . htmlspecialchars($c['username']) . ':</strong> ' . htmlspecialchars($c['comment']);
            echo '<span style="font-size:11px;color:#aaa;margin-left:8px;">' . date('M d, H:i', strtotime($c['created_at'])) . '</span>';
            echo '</div></div>';
        }
        $commentStmt->close();

        echo '<div id="comment-box-' . $thoughtId . '" style="display:none;margin-top:6px;">';
        echo '<form method="POST" action="includes/comment_thought.php" style="position:relative;">';
        echo '<input type="hidden" name="thought_id" value="' . $thoughtId . '">';
        echo '<input type="text" name="comment" placeholder="Add a comment..." required style="width:100%;padding:6px 36px 6px 10px;border-radius:4px;border:1px solid #ccc;">';
        echo '<button type="submit" style="position:absolute;top:50%;right:6px;transform:translateY(-50%);background:none;border:none;color:#007bff;font-size:16px;cursor:pointer;">';
        echo '<i class="fas fa-arrow-up"></i></button>';
        echo '</form></div></div></li>';
    }
}
