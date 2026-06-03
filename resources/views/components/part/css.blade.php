<link rel="stylesheet" href="{{ asset('assets/css/main/app.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/main/app-dark.css') }}">
<link rel="shortcut icon" href="{{ asset('assets/images/logo/favicon.svg') }}" type="image/x-icon">
<link rel="shortcut icon" href="{{ asset('assets/images/logo/favicon.png') }}" type="image/png">

<link rel="stylesheet" href="{{ asset('assets/css/shared/iconly.css') }}">

<!-- Pastikan ini di head -->
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> -->
 <link rel="stylesheet" href="{{ asset('assets/css/fontawesome.min.css') }}">

<style>
    /* Dot notifikasi */
    .badge-dot {
        position: absolute;
        top: 0;
        left: 20px;
        right: 0;
        width: 8px;
        height: 8px;
        background-color: rgb(1, 232, 1);
        border-radius: 50%;
    }

    /* Kunci ukuran font secara absolut */
    header .nama-esp h5 {
        font-size: 1.1rem !important;
        line-height: 1.3rem !important;
    }

    /* Responsive tetap berlaku */
    @media (max-width: 576px) {
        header .nama-esp h5 {
            font-size: 0.9rem !important;
            line-height: 1.1rem !important;
        }
    }

    /* Perubahan warna dark-mode saja */
    [data-bs-theme="dark"] header .nama-esp h5,
    .dark header .nama-esp h5 {
        color: #e9e9e9 !important;
    }

    /* dropdown box */
    .notif-menu {
        width: 300px;
        border-radius: 10px;
        overflow: hidden;
    }

    /* area list */
    .notif-list {
        max-height: 250px;
        overflow-y: auto;
    }

    /* tiap item notifikasi */
    .notif-item {
        display: flex;
        align-items: flex-start;
        padding: 10px 12px;
        gap: 10px;
        text-decoration: none;
        color: inherit;
    }

    .notif-item:hover {
        background: #f5f6f8;
    }

    /* ikon bulat */
    .notif-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* image notif */
    .notif-img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    /* teks notif */
    .notif-text {
        font-size: 14px;
        line-height: 1.2;
    }
</style>
