<?php
session_start();
header('Content-Type: application/json');

$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "cityfm_db";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database Connection Failed"]);
    exit();
}

$action = $_POST['action_type'] ?? '';

if ($action == "register") {
    $name = $conn->real_escape_string($_POST['reg_name']);
    $phone = $conn->real_escape_string(trim($_POST['reg_phone']));
    $password = $conn->real_escape_string($_POST['reg_pass']);

    $check = $conn->query("SELECT id FROM users WHERE phone='$phone'");
    if ($check->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "This Phone Number is already registered!"]);
        exit();
    }

    $sql = "INSERT INTO users (fullname, phone, password) VALUES ('$name', '$phone', '$password')";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["status" => "registered", "message" => "Account created successfully! Please log in."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Registration failed."]);
    }
    exit();
}

if ($action == "login") {
    $phone = $conn->real_escape_string(trim($_POST['user_phone']));
    $password = $conn->real_escape_string($_POST['user_pass']);

    $result = $conn->query("SELECT * FROM users WHERE phone='$phone' AND password='$password'");

    if ($result->num_rows > 0) {
        // Tells index.html that verification passed, letting JavaScript trigger the exact tab rewrite
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid Phone Number or Password!"]);
    }
    exit();
}

if ($action == "recover") {
    $phone = $conn->real_escape_string(trim($_POST['recover_phone']));
    $result = $conn->query("SELECT * FROM users WHERE phone='$phone'");

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $pwd = $row['password'];
        $usr = $row['fullname'];
        echo json_encode(["status" => "recovered", "message" => "Hello $usr! Your password is: $pwd"]);
    } else {
        echo json_encode(["status" => "error", "message" => "This Phone Number is not registered."]);
    }
    exit();
}

$conn->close();
?>
