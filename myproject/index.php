<?php
session_start();
$db_host = 'localhost'; $db_user = 'root'; $db_pass = ''; $db_name = 'project_db';
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Database failed: " . $e->getMessage()); }

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'register') {
        $u = trim($_POST['username']); $p = trim($_POST['password']);
        if (!empty($u) && !empty($p)) {
            try {
                $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)")->execute([$u, password_hash($p, PASSWORD_DEFAULT)]);
                $msg = "Account created! You can now log in.";
            } catch (PDOException $e) { $msg = "Username taken."; }
        }
    }
    if ($action === 'login') {
        $u = trim($_POST['username']); $p = trim($_POST['password']);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?"); $stmt->execute([$u]); $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($p, $user['password'])) {
            $_SESSION['user_id'] = $user['id']; $_SESSION['username'] = $user['username'];
            header("Location: " . $_SERVER['PHP_SELF']); exit;
        } else { $msg = "Invalid login credentials."; }
    }
    if ($action === 'create_post' && !empty($_SESSION['user_id'])) {
        $c = trim($_POST['content']);
        if (!empty($c)) { $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)")->execute([$_SESSION['user_id'], $c]); }
    }
    if ($action === 'create_comment' && !empty($_SESSION['user_id'])) {
        $p_id = intval($_POST['post_id']); $txt = trim($_POST['comment_text']);
        if (!empty($txt)) { $pdo->prepare("INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)")->execute([$p_id, $_SESSION['user_id'], $txt]); }
    }
}

if (isset($_GET['like_post_id']) && !empty($_SESSION['user_id'])) {
    $p_id = intval($_GET['like_post_id']); $u_id = $_SESSION['user_id'];
    $check = $pdo->prepare("SELECT * FROM post_likes WHERE user_id = ? AND post_id = ?"); $check->execute([$u_id, $p_id]);
    if ($check->rowCount() == 0) {
        $pdo->prepare("INSERT INTO post_likes (user_id, post_id) VALUES (?, ?)")->execute([$u_id, $p_id]);
        $pdo->prepare("UPDATE posts SET likes = likes + 1 WHERE id = ?")->execute([$p_id]);
    } else {
        $pdo->prepare("DELETE FROM post_likes WHERE user_id = ? AND post_id = ?")->execute([$u_id, $p_id]);
        $pdo->prepare("UPDATE posts SET likes = GREATEST(0, likes - 1) WHERE id = ?")->execute([$p_id]);
    }
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}
if (isset($_GET['logout'])) { session_destroy(); header("Location: " . $_SERVER['PHP_SELF']); exit; }

$posts = (!empty($_SESSION['user_id'])) ? $pdo->query("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC")->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"><title>ConnectALL</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php if (!isset($_SESSION['user_id'])): ?>
    <div class="login-screen">
        <div class="login-card">
            <div style="text-align: center; margin-bottom: 24px;"><h1 class="brand-title" style="margin:0; font-size:32px;">ConnectALL</h1><p style="color:#536471; font-size:14px; margin-top:4px;">Join the conversation today</p></div>
            <?php if (!empty($msg)): ?><div style="background:#e8f5fe; color:#1d4ed8; padding:10px; border-radius:8px; font-size:13px; text-align:center; margin-bottom:16px;"><?php echo $msg; ?></div><?php endif; ?>
            <form method="POST"><input type="hidden" name="action" value="login"><input type="text" name="username" class="input-field" placeholder="Username" required><input type="password" name="password" class="input-field" placeholder="Password" required><button type="submit" class="btn-primary">Log In</button></form>
            <div style="border-top:1px solid #eff3f4; margin-top:20px; padding-top:20px;">
                <form method="POST"><input type="hidden" name="action" value="register"><input type="text" name="username" class="input-field" placeholder="Choose Username" required><input type="password" name="password" class="input-field" placeholder="Create Password" required><button type="submit" class="btn-primary btn-success">Create Account</button></form>
            </div>
        </div>
    </div>
<?php else: ?>
    <nav class="app-header">
        <span class="brand-title">ConnectALL</span>
        <div style="display: flex; align-items: center; gap: 16px;">
            <span style="font-size: 14px; color: #536471;">✨ <b><?php echo htmlspecialchars($_SESSION['username']); ?></b></span>
            <a href="?logout=1" style="color: #f43f5e; font-size: 13px; font-weight:700; text-decoration: none; background: #fff1f2; padding: 6px 12px; border-radius: 20px;">Log Out</a>
        </div>
    </nav>
    <div class="feed-layout">
        <div class="content-card" style="border-bottom: 3px solid #1d4ed8;">
            <form method="POST"><input type="hidden" name="action" value="create_post"><textarea name="content" class="input-field" rows="3" placeholder="What's happening?" style="resize: none;" required></textarea><button type="submit" class="btn-primary" style="width: auto; float: right; padding: 8px 20px;">Publish</button></form><div style="clear: both;"></div>
        </div>
        <h4 style="font-size:18px; font-weight:800; color:#536471; margin-bottom:16px; padding-left:4px;">Timeline</h4>
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $post): ?>
                <div class="content-card">
                    <div style="display: flex; align-items: center; margin-bottom: 12px;">
                        <div class="user-badge"><?php echo strtoupper(substr($post['username'], 0, 1)); ?></div>
                        <div><strong style="font-size: 15px; display: block;"><?php echo htmlspecialchars($post['username']); ?></strong><span style="font-size: 12px; color: #536471;"><?php echo date('M j, g:i a', strtotime($post['created_at'])); ?></span></div>
                    </div>
                    <p style="font-size: 16px; line-height: 1.5; margin: 0 0 12px 0; white-space: pre-wrap;"><?php echo htmlspecialchars($post['content']); ?></p>
                    <div style="border-top: 1px solid #eff3f4; pt: 8px; padding-top:8px; display:flex;">
                        <a href="?like_post_id=<?php echo $post['id']; ?>" class="metrics-action">❤️ <b><?php echo $post['likes']; ?></b> Likes</a>
                        <span class="metrics-action" onclick="var s=document.getElementById('c-'+<?php echo $post['id']; ?>).style; s.display=(s.display==='block')?'none':'block';">💬 Comments</span>
                    </div>
                    <div id="c-<?php echo $post['id']; ?>" class="comment-box">
                        <div style="max-height: 150px; overflow-y: auto; margin-bottom: 12px;">
                            <?php 
                            $c_stmt = $pdo->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE comments.post_id = ? ORDER BY comments.created_at ASC"); 
                            $c_stmt->execute([$post['id']]); $comments = $c_stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <?php if(count($comments) > 0): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment-bubble"><b><?php echo htmlspecialchars($comment['username']); ?>:</b> <?php echo htmlspecialchars($comment['comment_text']); ?></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color:#536471; font-size:13px; font-style: italic; margin:4px 0;">No responses yet.</p>
                            <?php endif; ?>
                        </div>
                        <form method="POST" style="display: flex; gap: 8px;"><input type="hidden" name="action" value="create_comment"><input type="hidden" name="post_id" value="<?php echo $post['id']; ?>"><input type="text" name="comment_text" class="input-field" placeholder="Write a reply..." style="margin: 0; flex: 1; padding: 8px;" required><button type="submit" class="btn-primary" style="width: auto; padding: 4px 16px; font-size: 13px;">Reply</button></form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="content-card" style="text-align: center; color: #536471; padding: 40px;"><h5 style="margin: 0;">Timeline is quiet...</h5></div>
        <?php endif; ?>
    </div>
<?php endif; ?>
</body>
</html>
