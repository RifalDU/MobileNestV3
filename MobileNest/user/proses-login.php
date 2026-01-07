<?php
// SIMPLE LOGIN PROCESSOR

// 1. Start session
session_start();

// 2. Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// 3. Get input
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// 4. Validate input
if (empty($username) || empty($password)) {
    $_SESSION['error'] = 'Username dan password harus diisi!';
    header('Location: login.php');
    exit;
}

// 5. Connect to database
$servername = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'mobilenest_db';

$conn = new mysqli($servername, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    $_SESSION['error'] = 'Database error: ' . $conn->connect_error;
    header('Location: login.php');
    exit;
}

// 6. Set charset
$conn->set_charset('utf8mb4');

// 7. Query user
$sql = "SELECT id_user, username, email, password, nama_lengkap FROM users WHERE username = ? OR email = ? LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['error'] = 'Database error: ' . $conn->error;
    header('Location: login.php');
    exit;
}

$stmt->bind_param('ss', $username, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Username atau email tidak ditemukan!';
    header('Location: login.php');
    exit;
}

// 8. Fetch user data
$user = $result->fetch_assoc();
$stmt->close();

// 9. Verify password
if (!password_verify($password, $user['password'])) {
    $_SESSION['error'] = 'Password salah!';
    header('Location: login.php');
    exit;
}

// 10. Login success - set session
$_SESSION['user_id'] = $user['id_user'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['nama_lengkap'] = $user['nama_lengkap'];
$_SESSION['logged_in'] = true;

$_SESSION['success'] = 'Login berhasil! Selamat datang ' . $user['nama_lengkap'];

$conn->close();

// 11. Redirect to home
header('Location: ../index.php');
exit;
?>