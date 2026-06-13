```php
<?php
session_start();
header('Content-Type: application/json');
error_reporting(0);

// 🔴 CHANGE THESE TO YOUR INFINITYFREE DATABASE DETAILS
$host = "sql104.infinityfree.com";
$db_user = "if0_42173580";
$db_pass = "1hDb11Ft4S2S";
$db_name = "if0_42173580_db_cityfm";

// CONNECT DATABASE
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

// CHECK CONNECTION
if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
    exit();
}

// GET ACTION FROM HTML
$action = $_POST['action_type'] ?? "";

/* =========================
   REGISTER
========================= */
if ($action == "register") {

    $name = trim($_POST['reg_name']);
    $phone = trim($_POST['reg_phone']);
    $password = trim($_POST['reg_pass']);

    if (!$name || !$phone || !$password) {
        echo json_encode([
            "status" => "error",
            "message" => "All fields are required"
        ]);
        exit();
    }

    // CHECK IF USER EXISTS
    $check = $conn->query("SELECT id FROM users WHERE phone='$phone'");

    if ($check && $check->num_rows > 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Phone number already registered"
        ]);
        exit();
    }

    // INSERT USER
    $sql = "INSERT INTO users (fullname, phone, password)
            VALUES ('$name', '$phone', '$password')";

    if ($conn->query($sql)) {
        echo json_encode([
            "status" => "registered",
            "message" => "Account created successfully"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Registration failed"
        ]);
    }

    exit();
}

/* =========================
   LOGIN
========================= */
if ($action == "login") {

    $phone = trim($_POST['user_phone']);
    $password = trim($_POST['user_pass']);

    $result = $conn->query("SELECT * FROM users WHERE phone='$phone' LIMIT 1");

    if ($result && $result->num_rows > 0) {

        $row = $result->fetch_assoc();

        if ($password == $row['password']) {
            echo json_encode([
                "status" => "success",
                "message" => "Login successful"
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Wrong password"
            ]);
        }

    } else {
        echo json_encode([
            "status" => "error",
            "message" => "User not found"
        ]);
    }

    exit();
}

/* =========================
   RECOVER PASSWORD
========================= */
if ($action == "recover") {

    $phone = trim($_POST['recover_phone']);

    $result = $conn->query("SELECT * FROM users WHERE phone='$phone' LIMIT 1");

    if ($result && $result->num_rows > 0) {

        $row = $result->fetch_assoc();

        echo json_encode([
            "status" => "recovered",
            "message" => "Your password is: " . $row['password']
        ]);

    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Phone number not found"
        ]);
    }

    exit();
}

// DEFAULT RESPONSE
echo json_encode([
    "status" => "error",
    "message" => "Invalid request"
]);

$conn->close();
?>
```
