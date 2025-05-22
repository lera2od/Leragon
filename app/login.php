<?php
require_once 'include/mysql.php';

$error = null;
$mode = "login";

$ip = $_SERVER['REMOTE_ADDR'];
$max_attempts = 5;
$lockout_time = 300;
$attempts_file = 'logs/login_attempts.json';

if (!file_exists(dirname($attempts_file))) {
    mkdir(dirname($attempts_file), 0777, true);
}

$data = [];
if (file_exists($attempts_file)) {
    $data = json_decode(file_get_contents($attempts_file), true) ?: [];
}

$attempts = $data[$ip]['attempts'] ?? 0;
$last_time = $data[$ip]['last_time'] ?? 0;

if ($attempts >= $max_attempts && (time() - $last_time) < $lockout_time) {
    $error = "Too many failed login attempts. Please try again later.";
}

$usercount = $conn->query("SELECT COUNT(*) as count FROM users");
$usercount = $usercount->fetch_assoc();

if ($usercount["count"] == 0) {
    $mode = "register";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST["username"] ?? '');
    $password = $_POST["password"] ?? '';
    $password_hash = hash("sha256", $password);

    if ($mode === "login") {
        if ($attempts >= $max_attempts && (time() - $last_time) < $lockout_time) {
            $error = "Too many failed login attempts. Please try again later.";
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
            $stmt->bind_param("ss", $username, $password_hash);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                unset($data[$ip]);
                file_put_contents($attempts_file, json_encode($data));
                $_SESSION["user"] = $username;
                $_SESSION["password"] = $password_hash;
                header("Location: /");
                exit;
            } else {
                $data[$ip]['attempts'] = $attempts + 1;
                $data[$ip]['last_time'] = time();
                file_put_contents($attempts_file, json_encode($data));
                $error = "Invalid username or password.";
            }
        }
    } elseif ($mode === "register") {
        $email = trim($_POST["email"] ?? '');
        $confirm_password = $_POST["confirm_password"] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $error = "Username already exists.";
            } else {
                $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $email, $password_hash);
                if ($stmt->execute()) {
                    unset($data[$ip]);
                    file_put_contents($attempts_file, json_encode($data));
                    $_SESSION["user"] = $username;
                    $_SESSION["password"] = $password_hash;
                    header("Location: /");
                    exit;
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo ucfirst($mode); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        form {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        input {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <h2><?php echo $mode === "register" ? "Register" : "Login"; ?></h2>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if ($mode === "register"): ?>
        <p>Register the first user.</p>
    <?php endif; ?>
    <form method="post" action="">
        <label>Username:</label>
        <input type="text" name="username" required>

        <?php if ($mode === "register"): ?>
            <label>Email:</label>
            <input type="email" name="email" required>

            <label>Password:</label>
            <input type="password" name="password" required>

            <label>Confirm Password:</label>
            <input type="password" name="confirm_password" required>
        <?php else: ?>
            <label>Password:</label>
            <input type="password" name="password" required>
        <?php endif; ?>


        <button type="submit"><?php echo $mode === "register" ? "Register" : "Login"; ?></button>
    </form>
</body>

</html>