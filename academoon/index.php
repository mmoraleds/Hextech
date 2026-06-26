<?php
// ============================================================
//  Academon · index.php  (login & register page)
//  If already logged in, skip straight to the dashboard
// ============================================================
session_start();
if (!empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/config.php';
    header('Location: ' . REDIRECT_AFTER_LOGIN);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Academon · Login &amp; Register</title>

    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet" />

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            width: 100%;
            min-height: 100vh;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            background: url('sunset.png') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Press Start 2P', monospace;
            padding: 20px;
            overflow-x: hidden;
        }

        /* ----- main container ----- */
        .container {
            position: relative;
            width: 520px;
            max-width: 96%;
            padding-top: 0px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* ----- logo ----- */
        .logo-png {
            position: absolute;
            top: -135px;
            left: 50%;
            transform: translateX(-50%);
            width: min(680px, 95vw);
            height: auto;
            z-index: 2;
            margin: 0;
            filter: drop-shadow(0 8px 20px rgba(0,0,0,0.40));
            image-rendering: auto;
            pointer-events: none;
        }

        /* ----- card ----- */
        .card {
            background: rgba(255,241,203,0.92);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border-radius: 40px;
            padding: 38px 44px 38px;
            width: 100%;
            margin-top: 80px;
            box-shadow: 0 20px 32px rgba(0,0,0,0.35), 0 6px 12px rgba(0,0,0,0.15);
            border: 3px solid rgba(255,215,100,0.8);
            position: relative;
            z-index: 1;
            overflow: visible;
            transition: 0.2s;
        }

        /* ----- form elements ----- */
        .form-group { width: 100%; }

        label {
            display: block;
            margin: 14px 0 10px;
            font-size: 12px;
            color: #1e1a2b;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            opacity: 0.9;
        }

        input {
            width: 100%;
            height: 48px;
            border: none;
            outline: none;
            border-radius: 30px;
            background: #8f8df4;
            color: #ffffff;
            padding: 0 22px;
            font-size: 13px;
            font-family: 'Press Start 2P', monospace;
            transition: 0.2s;
            box-shadow: inset 0 3px 6px rgba(0,0,0,0.15);
        }

        input::placeholder {
            color: #d6d4ff;
            font-size: 10px;
        }

        input:focus {
            background: #7a78e0;
            box-shadow: 0 0 0 4px #b7b4ff, inset 0 2px 6px rgba(0,0,0,0.25);
        }

        /* ----- buttons ----- */
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 14px;
            margin: 28px 0 6px;
        }

        button {
            border: none;
            border-radius: 40px;
            background: #2c2025;
            color: white;
            font-family: 'Press Start 2P', monospace;
            font-size: 12px;
            cursor: pointer;
            transition: 0.15s;
            box-shadow: 0 6px 0 #0f0b0e;
            letter-spacing: 1px;
            padding: 0 18px;
            height: 48px;
            min-width: 130px;
            flex: 1 0 auto;
        }

        button:hover  { transform: translateY(-2px); background: #1f151a; box-shadow: 0 8px 0 #0f0b0e; }
        button:active { transform: translateY(4px);  box-shadow: 0 2px 0 #0f0b0e; }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* ----- feedback & toggle ----- */
        .feedback {
            text-align: center;
            margin-top: 18px;
            font-size: 10px;
            color: #2c2025;
            opacity: 0.85;
            min-height: 1.4em;
            letter-spacing: 0.3px;
        }
        .feedback.error   { color: #b13e3e; opacity: 1; }
        .feedback.success { color: #1f6b3b; opacity: 1; }

        .toggle-link {
            text-align: center;
            margin-top: 16px;
            font-size: 10px;
            color: #2c2025;
            opacity: 0.7;
            cursor: pointer;
            transition: 0.2s;
            letter-spacing: 0.3px;
            border-bottom: 1px dashed transparent;
            user-select: none;
        }
        .toggle-link:hover { opacity: 1; border-bottom-color: #2c2025; }

        /* ----- responsive ----- */
        @media (max-width: 750px) {
            .logo-png { top: -115px; }
            .card { padding: 30px 28px 30px; margin-top: 70px; }
        }
        @media (max-width: 600px) {
            .logo-png { top: -95px; }
            .card { padding: 24px 20px 24px; margin-top: 60px; }
            button { font-size: 10px; height: 42px; min-width: 100px; }
        }
        @media (max-width: 480px) {
            .logo-png { top: -75px; }
            .card { padding: 20px 16px 22px; margin-top: 50px; border-width: 2px; }
            label { font-size: 10px; margin: 10px 0 8px; }
            input { font-size: 10px; height: 40px; padding: 0 14px; }
            button { font-size: 9px; height: 38px; min-width: 80px; padding: 0 12px; }
            .btn-group { gap: 10px; }
        }
        @media (max-width: 380px) {
            .logo-png { top: -55px; }
            .card { padding: 14px 12px 16px; margin-top: 40px; }
            input { font-size: 9px; height: 34px; padding: 0 10px; }
        }
    </style>
</head>
<body>

<div class="container">
    <img class="logo-png" src="academon.png" alt="Academon logo" />

    <div class="card" id="appCard">
        <div id="formContainer"></div>
        <div id="feedback" class="feedback"></div>
        <div class="toggle-link" id="toggleMode">✦ switch to register ✦</div>
    </div>
</div>

<script>
(function () {
    "use strict";

    const formContainer = document.getElementById('formContainer');
    const feedbackEl    = document.getElementById('feedback');
    const toggleLink    = document.getElementById('toggleMode');

    let currentMode = 'login'; // 'login' | 'register'

    // ---- feedback helpers ----
    function setFeedback(msg, type) {
        feedbackEl.textContent = msg;
        feedbackEl.className   = 'feedback' + (type ? ' ' + type : '');
    }
    function clearFeedback() {
        feedbackEl.textContent = '';
        feedbackEl.className   = 'feedback';
    }

    // ---- render form ----
    function renderForm() {
        const isLogin    = currentMode === 'login';
        const btnLabel   = isLogin ? 'LOGIN' : 'CREATE ACCOUNT';
        const toggleText = isLogin ? '✦ switch to register ✦' : '✦ switch to login ✦';

        let html = `
            <div class="form-group">
                <label>USERNAME</label>
                <input type="text" id="username" placeholder="ENTER USERNAME" autocomplete="username" />
            </div>
            <div class="form-group">
                <label>PASSWORD</label>
                <input type="password" id="password" placeholder="••••••••" autocomplete="${isLogin ? 'current-password' : 'new-password'}" />
            </div>`;

        if (!isLogin) {
            html += `
            <div class="form-group">
                <label>CONFIRM PASSWORD</label>
                <input type="password" id="password2" placeholder="••••••••" autocomplete="new-password" />
            </div>`;
        }

        html += `
            <div class="btn-group">
                <button id="actionBtn">${btnLabel}</button>
            </div>`;

        formContainer.innerHTML = html;
        toggleLink.textContent  = toggleText;

        // clear feedback on any input
        formContainer.querySelectorAll('input').forEach(inp => {
            inp.addEventListener('input', clearFeedback);
            inp.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); handleAction(); }
            });
        });

        document.getElementById('actionBtn').addEventListener('click', function (e) {
            e.preventDefault();
            handleAction();
        });

        document.getElementById('username').focus();
    }

    // ---- client-side validation ----
    function validate(username, password, password2, isLogin) {
        if (!username)              return 'Username is required';
        if (username.length < 2)   return 'Username must be at least 2 characters';
        if (username.length > 50)  return 'Username too long';
        if (!/^[a-zA-Z0-9_]+$/.test(username)) return 'Username: letters, numbers, underscores only';
        if (!password)             return 'Password is required';
        if (!isLogin && password.length < 6) return 'Password must be at least 6 characters';
        if (!isLogin && password !== password2) return 'Passwords do not match';
        return null;
    }

    // ---- submit to PHP ----
    async function handleAction() {
        const isLogin   = currentMode === 'login';
        const username  = (document.getElementById('username')?.value  || '').trim();
        const password  = document.getElementById('password')?.value   || '';
        const password2 = document.getElementById('password2')?.value  || '';

        const validationError = validate(username, password, password2, isLogin);
        if (validationError) {
            setFeedback('✗ ' + validationError, 'error');
            return;
        }

        const btn = document.getElementById('actionBtn');
        btn.disabled     = true;
        btn.textContent  = isLogin ? 'LOGGING IN...' : 'CREATING...';
        clearFeedback();

        try {
            const endpoint = isLogin ? 'login.php' : 'register.php';
            const payload  = isLogin
                ? { username, password }
                : { username, password, password2 };

            const res  = await fetch(endpoint, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });

            const data = await res.json();

            if (data.success) {
                setFeedback('✓ ' + data.message, 'success');

                if (isLogin && data.redirect) {
                    // Brief pause so the user sees the welcome message, then redirect
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 800);
                } else if (!isLogin) {
                    // Switch to login after successful register
                    setTimeout(() => {
                        currentMode = 'login';
                        renderForm();
                        setFeedback('✓ Account created! Please login.', 'success');
                    }, 1000);
                }
            } else {
                setFeedback('✗ ' + data.message, 'error');
                btn.disabled    = false;
                btn.textContent = isLogin ? 'LOGIN' : 'CREATE ACCOUNT';
            }

        } catch (err) {
            setFeedback('✗ Could not reach server. Is XAMPP running?', 'error');
            btn.disabled    = false;
            btn.textContent = isLogin ? 'LOGIN' : 'CREATE ACCOUNT';
        }
    }

    // ---- toggle mode ----
    toggleLink.addEventListener('click', function () {
        clearFeedback();
        currentMode = (currentMode === 'login') ? 'register' : 'login';
        renderForm();
    });

    // ---- init ----
    renderForm();

})();
</script>

</body>
</html>
