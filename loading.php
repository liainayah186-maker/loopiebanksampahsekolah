<style>
    /* --- STYLE PRELOADER LOOPIE --- */
    #preloader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: #F0F2BD; /* Warna Cream Sand */
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000; /* Sangat tinggi agar menutupi semua elemen */
        transition: opacity 0.6s ease, visibility 0.6s;
    }

    .loader-content {
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }

    /* Ring Muter Estetik */
    .spinner-loopie {
        width: 70px;
        height: 70px;
        border: 6px solid rgba(75, 53, 42, 0.08); /* Dark Oak Transparan */
        border-top: 6px solid #CA7842; /* Terra Cotta */
        border-right: 6px solid #B2CD9C; /* Moss Green */
        border-radius: 50%;
        animation: spinLoopie 1s cubic-bezier(0.68, -0.55, 0.27, 1.55) infinite;
    }

    /* Teks Branding */
    .loader-brand {
        font-family: 'Poppins', sans-serif;
        font-size: 2.2rem;
        font-weight: 800;
        color: #4B352A; /* Dark Oak */
        letter-spacing: -2px;
        margin: 0;
    }
    .loader-brand span {
        color: #CA7842; /* Terra Cotta */
        display: inline-block;
        animation: bounceDot 1.5s infinite;
    }

    /* Sub-teks */
    .loader-sub {
        font-family: 'Inter', sans-serif;
        font-size: 0.85rem;
        font-weight: 600;
        color: #4B352A;
        opacity: 0.5;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    /* Animasi Keyframes */
    @keyframes spinLoopie {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes bounceDot {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    /* Class untuk menghilangkan loader via JS */
    .loader-hidden {
        opacity: 0;
        visibility: hidden;
    }
</style>

<div id="preloader">
    <div class="loader-content">
        <div class="spinner-loopie"></div>
        <h1 class="loader-brand">Loopie<span>.</span></h1>
        <div class="loader-sub">Menyiapkan Langkah Hijau...</div>
    </div>
</div>

<script>
    // Logika untuk menghilangkan preloader
    window.addEventListener("load", function() {
        const preloader = document.getElementById("preloader");
        
        // Beri sedikit jeda agar transisi terasa halus (800ms)
        setTimeout(() => {
            preloader.classList.add("loader-hidden");
        }, 800);
    });
</script>