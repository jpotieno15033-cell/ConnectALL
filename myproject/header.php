<?php
session_start();
$db_host = 'localhost'; $db_user = 'root'; $db_pass = ''; $db_name = 'project_db';
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Database connection failed: " . $e->getMessage()); }

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = trim($_POST['username']); $password = trim($_POST['password']);
    if (!empty($username) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        try {
            $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)")->execute([$username, $hashed_password]);
            $success = "Account created successfully! You can now log in below.";
        } catch (PDOException $e) { $error = ($e->getCode() == 23000) ? "Username taken." : "Failed."; }
    } else { $error = "Fill all fields."; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username']); $password = trim($_POST['password']);
    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]); $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id']; $_SESSION['username'] = $user['username'];
            header("Location: " . $_SERVER['PHP_SELF']); exit;
        } else { $error = "Invalid username or password."; }
    } else { $error = "Fill all fields."; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_post') {
    if (!empty($_SESSION['user_id'])) {
        $content = trim($_POST['content']);
        if (!empty($content)) {
            $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)")->execute([$_SESSION['user_id'], $content]);
            $success = "Post successfully uploaded!";
        } else { $error = "Post content cannot be empty."; }
    } else { $error = "Session expired."; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_comment') {
    if (!empty($_SESSION['user_id'])) {
        $post_id = intval($_POST['post_id']); $comment_text = trim($_POST['comment_text']);
        if (!empty($comment_text)) {
            $pdo->prepare("INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)")->execute([$post_id, $_SESSION['user_id'], $comment_text]);
            $success = "Reply posted!";
        } else { $error = "Comment cannot be empty."; }
    } else { $error = "Session missing."; }
}

if (isset($_GET['like_post_id'])) {
    if (!empty($_SESSION['user_id'])) {
        $post_id = intval($_GET['like_post_id']); $user_id = $_SESSION['user_id'];
        $check = $pdo->prepare("SELECT * FROM post_likes WHERE user_id = ? AND post_id = ?");
        $check->execute([$user_id, $post_id]);
        if ($check->rowCount() == 0) {
            $pdo->prepare("INSERT INTO post_likes (user_id, post_id) VALUES (?, ?)")->execute([$user_id, $post_id]);
            $pdo->prepare("UPDATE posts SET likes = likes + 1 WHERE id = ?")->execute([$post_id]);
        } else {
            $pdo->prepare("DELETE FROM post_likes WHERE user_id = ? AND post_id = ?")->execute([$user_id, $post_id]);
            $pdo->prepare("UPDATE posts SET likes = GREATEST(0, likes - 1) WHERE id = ?")->execute([$post_id]);
        }
        header("Location: " . $_SERVER['PHP_SELF']); exit;
    } else { $error = "Log in first."; }
}

if (isset($_GET['logout'])) { session_destroy(); header("Location: " . $_SERVER['PHP_SELF']); exit; }

$posts = [];
if (!empty($_SESSION['user_id'])) {
    $posts = $pdo->query("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}
?>
