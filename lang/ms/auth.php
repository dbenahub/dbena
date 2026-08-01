<?php

return [
    // Laravel default
    'failed' => 'Kelayakan ini tidak sepadan dengan rekod kami.',
    'password' => 'Kata laluan yang diberikan tidak tepat.',
    'throttle' => 'Terlalu banyak percubaan log masuk. Sila cuba lagi dalam :seconds saat.',

    // Skrin log masuk
    'sign_in' => 'Log Masuk',
    'admin_sign_in' => 'Log Masuk Admin',
    'subtitle' => 'Dashboard Prestasi Syarikat',
    'admin_subtitle' => 'Masukkan kelayakan admin anda.',
    'username' => 'Username',
    'admin_username' => 'Username Admin',
    'password_label' => 'Kata Laluan',
    'username_placeholder' => 'Masukkan username',
    'password_placeholder' => 'Masukkan kata laluan',
    'show_password' => 'Papar kata laluan',
    'hide_password' => 'Sorok kata laluan',
    'forgot_password' => 'Lupa Kata Laluan?',

    // OTP
    'otp_title' => 'Pengesahan OTP',
    'otp_sent_to' => 'Kod 6-digit telah dihantar ke',
    'otp_verify' => 'Sahkan OTP',
    'otp_resend' => 'Hantar Semula OTP',
    'otp_resend_in' => 'Hantar semula dalam :seconds saat',
    'otp_length' => 'Masukkan kod 6-digit.',
    'otp_invalid' => 'Kod OTP salah. Cuba lagi.',
    'otp_expired' => 'Kod OTP telah luput. Sila minta kod baharu.',
    'otp_not_found' => 'Tiada kod aktif. Sila minta kod baharu.',
    'otp_too_many_attempts' => 'Terlalu banyak percubaan salah. Sila minta kod baharu.',
    'otp_sent_toast' => 'OTP dihantar ke emel berdaftar anda',
    'otp_resent_toast' => 'OTP baharu dihantar ke emel berdaftar anda',

    // Lupa kata laluan
    'forgot_title' => 'Lupa Kata Laluan',
    'forgot_hint' => 'Kod set semula hanya boleh dihantar ke emel berdaftar.',
    'registered_email' => 'Emel Berdaftar',
    'send_reset_code' => 'Hantar Kod Set Semula',
    'back_to_login' => 'Kembali ke Log Masuk',
    'reset_code_title' => 'Kod Set Semula',
    'verify_code' => 'Sahkan Kod',
    'reset_code_sent_toast' => 'Kod set semula dihantar ke emel anda',

    // Set semula kata laluan
    'reset_password_title' => 'Set Semula Kata Laluan',
    'new_password' => 'Kata Laluan Baharu',
    'confirm_password' => 'Sahkan Kata Laluan',
    'new_password_placeholder' => 'Sekurang-kurangnya 8 aksara',
    'confirm_password_placeholder' => 'Taip semula kata laluan',
    'reset_password_button' => 'Set Semula Kata Laluan',

    // Kejayaan
    'login_success' => 'Log Masuk Berjaya',
    'redirecting' => 'Mengalihkan ke dashboard…',
    'redirecting_admin' => 'Mengalihkan ke admin panel…',
    'continue' => 'Teruskan',
    'password_updated' => 'Kata Laluan Dikemaskini',
    'password_updated_hint' => 'Sila log masuk semula dengan kata laluan baharu anda.',

    // Ralat
    'fill_both_fields' => 'Sila isi username dan kata laluan.',
    'invalid_credentials' => 'Username atau kata laluan tidak sah.',
    'account_inactive' => 'Akaun ini telah dinyahaktifkan. Sila hubungi pentadbir.',
    'not_admin' => 'Akaun ini tiada kebenaran admin.',
    'email_required' => 'Sila masukkan alamat emel.',
    'email_unknown' => 'Emel tidak dikenali dalam sistem.',
    'password_too_short' => 'Kata laluan mesti sekurang-kurangnya 8 aksara.',
    'password_mismatch' => 'Kata laluan tidak sepadan.',
    'password_weak' => 'Kata laluan perlu mengandungi huruf dan nombor.',

    // Panel Admin Login
    'restricted_access' => 'RESTRICTED ACCESS',
    'admin_panel_title' => 'Admin Panel',
    'admin_panel_desc' => 'Kawasan ini dikhaskan untuk kakitangan diberi kuasa sahaja. Semua konfigurasi dashboard prestasi syarikat diurus dari sini.',
    'two_factor' => 'Two-Factor OTP',

    // Emel OTP
    'otp_send_failed' => 'Kod tidak dapat dihantar ke emel. Sistem emel bermasalah — sila hubungi pentadbir.',

    'password_via_admin' => 'Lupa kata laluan? Hubungi pentadbir sistem.',

    'mail' => [
        'subject_login' => 'Kod Log Masuk DBENA Dashboard',
        'subject_reset' => 'Kod Set Semula Kata Laluan DBENA',
        'for_account' => 'Kod ini untuk akaun **:username** (:role).',
        'greeting' => 'Salam :name,',
        'line_login' => 'Berikut adalah kod pengesahan log masuk anda:',
        'line_reset' => 'Berikut adalah kod set semula kata laluan anda:',
        'expiry' => 'Kod ini sah selama :minutes minit sahaja.',
        'ignore' => 'Jika anda tidak membuat permintaan ini, sila abaikan emel ini dan maklumkan kepada pentadbir sistem.',
        'salutation' => 'Terima kasih,',
    ],
];
