<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loopie. — Akses Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { 
            --terra-cotta: #CA7842; 
            --moss-green: #B2CD9C; 
            --dark-oak: #4B352A; 
            --parchment: #F8F9F2; 
            --white: #ffffff;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--parchment);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px; /* Jaga jarak di layar HP kecil */
            background-image: 
                radial-gradient(at 0% 0%, rgba(178, 205, 156, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(202, 120, 66, 0.1) 0px, transparent 50%);
        }

        .login-card {
            background: var(--white);
            border-radius: 32px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 40px rgba(75, 53, 42, 0.08);
            border: 1px solid rgba(75, 53, 42, 0.05);
            position: relative;
        }

        .logo-text { 
            font-weight: 800; 
            font-size: 2.2rem; 
            color: var(--dark-oak);
            letter-spacing: -1.5px;
        }

        .form-label { 
            font-weight: 700; 
            font-size: 0.85rem; 
            color: var(--dark-oak);
            margin-bottom: 8px;
            display: block;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }

        .input-clean {
            width: 100%;
            padding: 14px 18px;
            background: #F3F4F0;
            border: 2px solid transparent;
            border-radius: 16px;
            font-weight: 600;
            color: var(--dark-oak);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-clean:focus {
            outline: none;
            background: var(--white);
            border-color: var(--moss-green);
            box-shadow: 0 0 0 4px rgba(178, 205, 156, 0.2);
        }

        .btn-login {
            background: var(--dark-oak);
            color: var(--white);
            border: none;
            width: 100%;
            padding: 16px;
            border-radius: 16px;
            font-weight: 700;
            font-size: 1rem;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: var(--terra-cotta);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(202, 120, 66, 0.2);
        }

        .toggle-pass {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: var(--terra-cotta);
            font-weight: 800;
            font-size: 0.7rem;
            padding: 8px;
            cursor: pointer;
            letter-spacing: 0.5px;
        }

        .admin-tag {
            background: var(--moss-green);
            color: var(--dark-oak);
            padding: 5px 14px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 15px;
        }

        /* Responsive Mobile */
        @media (max-width: 480px) {
            .login-card {
                padding: 30px 20px;
                border-radius: 28px;
            }
            .logo-text { font-size: 1.8rem; }
        }

        .loader {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid var(--white);
            border-bottom-color: transparent;
            border-radius: 50%;
            animation: rotation 1s linear infinite;
            margin-left: 10px;
        }

        @keyframes rotation { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center">
        <div class="admin-tag">Sistem Internal</div>
        <h1 class="logo-text m-0">Loopie<span style="color: var(--terra-cotta)">.</span></h1>
        <p class="text-muted fw-medium mb-5" style="font-size: 0.9rem;">Silakan masuk untuk akses dashboard admin</p>
    </div>

    <?php if(isset($_GET['pesan']) && $_GET['pesan'] == "gagal"): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4 fw-bold p-3 d-flex align-items-center" style="font-size: 0.8rem; background: #ffebeb; color: #d63031;">
            <i data-lucide="alert-circle" size="18" class="me-2"></i> Username atau Password salah!
        </div>
    <?php endif; ?>

    <form action="proses_login_admin.php" method="POST" id="loginForm">
        <div class="input-group-custom">
            <label class="form-label">Username Admin</label>
            <input type="text" name="username" class="input-clean" placeholder="Masukkan username" required autocomplete="off">
        </div>

        <div class="input-group-custom">
            <label class="form-label">Kata Sandi</label>
            <div class="position-relative">
                <input type="password" name="password" id="password" class="input-clean" placeholder="••••••••" required>
                <button type="button" onclick="togglePass(event)" class="toggle-pass">LIHAT</button>
            </div>
        </div>

        <button type="submit" class="btn-login d-flex justify-content-center align-items-center">
            Masuk ke Dashboard <div class="loader" id="loader"></div>
        </button>
    </form>
</div>

<script>
// Load icons
lucide.createIcons();

function togglePass(event) {
    const p = document.getElementById('password');
    const btn = event.currentTarget;
    if(p.type === 'password') {
        p.type = 'text';
        btn.innerText = 'SEMBUNYIKAN';
    } else {
        p.type = 'password';
        btn.innerText = 'LIHAT';
    }
}

document.getElementById('loginForm').addEventListener('submit', function () {
    const btn = this.querySelector('.btn-login');
    btn.style.opacity = '0.8';
    btn.style.pointerEvents = 'none';
    document.getElementById('loader').style.display = 'inline-block';
});
</script>

</body>
</html>