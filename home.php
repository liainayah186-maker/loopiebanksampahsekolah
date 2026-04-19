<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loopie — Pilah Sampah Jadi Hadiah</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { 
            --dark-oak: #4B352A; 
            --terra-cotta: #CA7842; 
            --moss-green: #B2CD9C; 
            --cream-sand: #F0F2BD; 
            --pure-white: #FFFFFF; 
            --transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--cream-sand);
            color: var(--dark-oak);
            margin: 0;
            overflow-x: hidden;
        }

        h1, h2, h3, .logo { font-family: 'Poppins', sans-serif; }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        /* --- NAVBAR --- */
        .navbar {
            padding: 1.5rem 10%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(240, 242, 189, 0.8);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(75, 53, 42, 0.05);
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            text-decoration: none;
            color: var(--dark-oak);
            letter-spacing: -1.5px;
        }
        .logo span { color: var(--terra-cotta); }

        .btn-fill {
            padding: 12px 28px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
            background: var(--terra-cotta);
            color: white;
            transition: var(--transition);
            box-shadow: 0 10px 20px rgba(202, 120, 66, 0.2);
        }
        .btn-fill:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(202, 120, 66, 0.3); }

        /* --- HERO SECTION --- */
        .hero-guest {
            padding: 60px 10% 100px;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            align-items: center;
            gap: 60px;
            min-height: 70vh;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 25px;
            letter-spacing: -2px;
        }

        .hero-content p {
            font-size: 1.1rem;
            line-height: 1.7;
            opacity: 0.8;
            margin-bottom: 40px;
            max-width: 550px;
        }

        .hero-visual {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .circle-bg {
            position: absolute;
            width: 400px;
            height: 400px;
            background: var(--moss-green);
            border-radius: 60px;
            transform: rotate(15deg);
            z-index: 1;
            opacity: 0.4;
        }

        .hero-img {
            max-height: 440px;
            width: auto;
            position: relative;
            z-index: 2;
            filter: drop-shadow(0 25px 50px rgba(75, 53, 42, 0.15));
            animation: float 5s ease-in-out infinite;
        }

        /* --- STEPS / PANDUAN --- */
        .steps-section {
            padding: 80px 10%;
            background: rgba(255, 255, 255, 0.5);
            text-align: center;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 50px;
        }

        .step-card {
            background: var(--pure-white);
            padding: 30px;
            border-radius: 30px;
            position: relative;
            transition: var(--transition);
        }

        .step-number {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--dark-oak);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        .step-card h4 { margin: 15px 0 10px; font-weight: 800; }
        .step-card p { font-size: 0.85rem; opacity: 0.7; line-height: 1.5; }

        /* --- CALL TO ACTION BOX --- */
        .cta-box {
            background: var(--dark-oak);
            color: white;
            padding: 40px;
            border-radius: 40px;
            margin: 60px 10%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cta-text h2 { margin: 0 0 10px; }
        .cta-text p { margin: 0; opacity: 0.8; }

        /* --- FOOTER --- */
        .minimal-footer {
            padding: 50px 10%;
            text-align: center;
            border-top: 1px solid rgba(75, 53, 42, 0.05);
        }

        @media (max-width: 992px) {
            .hero-guest { grid-template-columns: 1fr; text-align: center; }
            .steps-grid { grid-template-columns: 1fr 1fr; }
            .cta-box { flex-direction: column; text-align: center; gap: 30px; }
        }
        .contact-pill {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: rgba(202, 120, 66, 0.1);
    padding: 8px 16px;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--terra-cotta);
    margin-top: 15px;
}
    </style>
</head>
<body>
    <?php include 'loading.php'; ?>

    <nav class="navbar">
        <a href="index.php" class="logo">Loopie<span>.</span></a>
        <div class="nav-btns">
            <a href="login.php" class="btn-fill">Masuk Kelas</a>
        </div>
    </nav>

    <section class="hero-guest">
        <div class="hero-content">
            <h1>Langkah Kecil, <br>Dampak <span style="color: var(--terra-cotta)">Besar.</span></h1>
            <p>Loopie membantu kelasmu mengelola sampah harian menjadi poin yang bermanfaat. Bersama teman sekelas, jadikan lingkungan sekolah lebih bersih dan hijau!</p>
            <a href="#panduan" class="btn-fill" style="background: var(--dark-oak);">Lihat Cara Kerja</a>
        </div>

        <div class="hero-visual">
            <div class="circle-bg"></div>
            <img src="hero.png" alt="Loopie Eco" class="hero-img">
        </div>
    </section>

    <section class="steps-section" id="panduan">
        <h2 style="font-size: 2.2rem;">Bagaimana Cara Mengikutinya?</h2>
        <p>Ikuti 4 langkah mudah untuk mulai mengumpulkan poin kelas.</p>

        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <div style="color: var(--terra-cotta); margin-bottom: 10px;"><i data-lucide="user-check" size="32"></i></div>
                <h4>Ambil Akun</h4>
                <p>Hubungi petugas Bank Sampah sekolah untuk mendapatkan akses akun kelasmu.</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <div style="color: var(--terra-cotta); margin-bottom: 10px;"><i data-lucide="trash-2" size="32"></i></div>
                <h4>Kumpulkan</h4>
                <p>Kumpulkan sampah plastik atau kertas bekas jajan di sudut kelasmu.</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <div style="color: var(--terra-cotta); margin-bottom: 10px;"><i data-lucide="scale" size="32"></i></div>
                <h4>Setor & Timbang</h4>
                <p>Bawa sampah ke petugas untuk ditimbang dan dicatat sebagai poin kelas.</p>
            </div>
            <div class="step-card">
                <div class="step-number">4</div>
                <div style="color: var(--terra-cotta); margin-bottom: 10px;"><i data-lucide="trending-up" size="32"></i></div>
                <h4>Naik Peringkat</h4>
                <p>Pantau posisi kelasmu di Leaderboard dan kumpulkan poin sebanyak mungkin!</p>
            </div>
        </div>
    </section>

   <div class="cta-box">
    <div class="cta-text">
        <h2>Siap Menjadi Kelas Terhijau?</h2>
        <p>Gunakan akun yang diberikan petugas untuk masuk.</p>
        
        <div class="contact-pill">
            <i data-lucide="map-pin" size="16"></i> Pos: Samping Kantin Sekolah
        </div>
        <div class="contact-pill" style="margin-left: 10px;">
            <i data-lucide="phone" size="16"></i> WA: 0812-3456-7890
        </div>
    </div>
    <a href="login.php" class="btn-fill" style="background: white; color: var(--dark-oak); padding: 18px 40px;">LOGIN SEKARANG</a>
</div>
    <footer class="minimal-footer">
        <div class="footer-text">
            &copy; 2026 <b>Loopie Project</b> — Langkah kecil dari kelas untuk bumi yang lebih baik.
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>