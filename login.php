<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); 
define('DB_NAME', 'login');

if (isset($_SESSION['flash'])) {
    $error = $_SESSION['flash'];
    unset($_SESSION['flash']);
} else {
    $error = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['email'] ?? ''); 
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $error = 'Please enter email/username and password.';
    } else {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($mysqli->connect_errno) {
            $error = 'Database connection failed: ' . $mysqli->connect_error;
        } else {
            $sql = 'SELECT id, username, password, email FROM users WHERE email = ? OR username = ? LIMIT 1';
            $stmt = $mysqli->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ss', $identifier, $identifier);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $hash = $row['password'];
                    $verified = false;
                    if (password_verify($password, $hash)) {
                        $verified = true;
                    } elseif ($password === $hash) {
                        $verified = true;
                    }

                    if ($verified) {
                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['username'] = $row['username'];
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        $error = 'Invalid credentials.';
                    }
                } else {
                    $error = 'Invalid credentials.';
                }
                $stmt->close();
            } else {
                $error = 'Database query failed.';
            }
            $mysqli->close();
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>LGU2 — Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>
    <div class="stage">
        <div class="card" role="main" aria-label="LGU2 login">
            <!-- Left branding -->
            <div class="brand" aria-hidden="false">
                <div class="logo-wrap">
                    <div style="display:flex; align-items:center; gap:18px;">
                        <div>
                            <div class="logo">
                                <div class="unit">LGU</div>
                                <div class="lgutext">2</div>
                            </div>
                            <div class="logo-small">LOCAL GOVERNMENT UNIT</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right login form -->
            <div class="form-area">
                <div class="form-box" role="form" aria-labelledby="login-title">
                    <h3 id="login-title">Login</h3>
                    <p class="sub">Sign in to your account</p>

                    <?php if ($error): ?>
                        <div class="error" style="color:#c0392b; margin-bottom:12px;"><?=htmlspecialchars($error)?></div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="form-field">
                            <label class="small" for="email">Email</label>
                            <input id="email" name="email" type="text" placeholder="Enter your email or username" autocomplete="username" value="<?=isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''?>">
                        </div>

                        <div class="form-field">
                            <label class="small" for="password">Password</label>
                            <input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password">
                        </div>

                        <div class="controls">
                            <a class="forgot" href="#">Forgot Password?</a>
                        </div>

                        <button class="btn-login" id="btnLogin" type="submit">LOGIN</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>