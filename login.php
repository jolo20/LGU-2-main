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

                    <div class="form-field">
                        <label class="small" for="email">Email</label>
                        <input id="email" type="text" placeholder="Enter your email" autocomplete="username">
                    </div>

                    <div class="form-field">
                        <label class="small" for="password">Password</label>
                        <input id="password" type="password" placeholder="Enter your password"
                            autocomplete="current-password">
                    </div>

                    <div class="controls">
                        <label class="remember"><input type="checkbox" id="remember"> Remember me</label>
                        <a class="forgot" href="#">Forgot Password?</a>
                    </div>

                    <button class="btn-login" id="btnLogin" type="button">LOGIN</button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>