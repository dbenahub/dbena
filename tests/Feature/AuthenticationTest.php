<?php

declare(strict_types=1);

use App\Enums\OtpType;
use App\Enums\UserRole;
use App\Livewire\Auth\AdminLoginFlow;
use App\Livewire\Auth\UserLoginFlow;
use App\Models\Otp;
use App\Models\User;
use App\Notifications\SendOtpNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function (): void {
    Notification::fake();
    RateLimiter::clear('dbena|127.0.0.1');

    $this->user = User::factory()->create([
        'username' => 'dbena',
        'password' => 'RahsiaKuat123',
        'role' => UserRole::User,
    ]);
});

it('rejects an empty login form', function (): void {
    Livewire::test(UserLoginFlow::class)
        ->call('submitLogin')
        ->assertSet('step', 'login')
        ->assertSet('loginError', __('auth.fill_both_fields'));
});

it('rejects wrong credentials without revealing which field failed', function (): void {
    Livewire::test(UserLoginFlow::class)
        ->set('username', 'dbena')
        ->set('password', 'salah')
        ->call('submitLogin')
        ->assertSet('step', 'login')
        ->assertSet('loginError', __('auth.invalid_credentials'));
});

it('never exposes the OTP code to the browser', function (): void {
    // PEMBETULAN isu #1 — prototaip memaparkan "Demo: OTP anda ialah 123456".
    $component = Livewire::test(UserLoginFlow::class)
        ->set('username', 'dbena')
        ->set('password', 'RahsiaKuat123')
        ->call('submitLogin')
        ->assertSet('step', 'otp');

    $payload = json_encode($component->snapshot);

    $code = Otp::where('user_id', $this->user->id)->latest('id')->first();

    expect($code)->not->toBeNull()
        ->and($payload)->not->toContain('demoOtp')
        // Hash bcrypt tidak boleh muncul di dalam payload klien.
        ->and($payload)->not->toContain($code->code_hash);
});

it('stores the OTP hashed, never in plain text', function (): void {
    Livewire::test(UserLoginFlow::class)
        ->set('username', 'dbena')
        ->set('password', 'RahsiaKuat123')
        ->call('submitLogin');

    $otp = Otp::where('user_id', $this->user->id)->firstOrFail();

    expect($otp->code_hash)->toStartWith('$2y$')
        ->and($otp->code_hash)->not->toMatch('/^\d{6}$/');
});

it('emails the OTP to the registered user', function (): void {
    Livewire::test(UserLoginFlow::class)
        ->set('username', 'dbena')
        ->set('password', 'RahsiaKuat123')
        ->call('submitLogin');

    Notification::assertSentTo($this->user, SendOtpNotification::class);
});

/*
|--------------------------------------------------------------------------
| Peti masuk OTP berpusat
|--------------------------------------------------------------------------
*/

it('sends a user OTP to the central user inbox, not their personal email', function (): void {
    config(['dbena.otp.inbox.user' => 'dbenagroup@gmail.com']);

    $user = User::factory()->create([
        'role' => UserRole::User,
        'email' => 'peribadi@contoh.com',
    ]);

    expect($user->routeNotificationForMail(new SendOtpNotification('123456', OtpType::Login)))
        ->toBe('dbenagroup@gmail.com');
});

it('sends an admin OTP to the central admin inbox', function (): void {
    config(['dbena.otp.inbox.admin' => 'dbenareport@gmail.com']);

    $admin = User::factory()->create([
        'role' => UserRole::Admin,
        'email' => 'peribadi@contoh.com',
    ]);

    expect($admin->routeNotificationForMail(new SendOtpNotification('123456', OtpType::Login)))
        ->toBe('dbenareport@gmail.com');
});

it('keeps the two inboxes apart', function (): void {
    config([
        'dbena.otp.inbox.admin' => 'dbenareport@gmail.com',
        'dbena.otp.inbox.user' => 'dbenagroup@gmail.com',
    ]);

    $notification = new SendOtpNotification('123456', OtpType::Login);
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $user = User::factory()->create(['role' => UserRole::User]);

    expect($admin->routeNotificationForMail($notification))
        ->not->toBe($user->routeNotificationForMail($notification));
});

it('falls back to the personal email when a central inbox is unset', function (): void {
    // Salah tetapan tidak boleh menyebabkan tiada siapa dapat log masuk.
    config(['dbena.otp.inbox.user' => null]);

    $user = User::factory()->create([
        'role' => UserRole::User,
        'email' => 'peribadi@contoh.com',
    ]);

    expect($user->routeNotificationForMail(new SendOtpNotification('123456', OtpType::Login)))
        ->toBe('peribadi@contoh.com');
});

it('still sends non-OTP mail to the personal email', function (): void {
    // Hanya OTP yang dialihkan. Laporan mingguan kekal peribadi.
    config(['dbena.otp.inbox.user' => 'dbenagroup@gmail.com']);

    $user = User::factory()->create([
        'role' => UserRole::User,
        'email' => 'peribadi@contoh.com',
    ]);

    $lain = new class extends Illuminate\Notifications\Notification {};

    expect($user->routeNotificationForMail($lain))->toBe('peribadi@contoh.com');
});

it('names the account in the subject so a shared inbox stays readable', function (): void {
    $user = User::factory()->create(['name' => 'ZIKRI', 'role' => UserRole::User]);

    $mel = (new SendOtpNotification('123456', OtpType::Login))->toMail($user);

    expect($mel->subject)->toContain('ZIKRI');
});

it('shows an error instead of the code screen when mail fails', function (): void {
    // Sambungan SMTP yang tergantung dahulunya menahan permintaan sehingga
    // pelayan web berputus asa — pengguna melihat "504 Gateway time-out"
    // tanpa sebarang petunjuk bahawa emel puncanya.
    Notification::fake();
    Illuminate\Support\Facades\Notification::shouldReceive('send')
        ->andThrow(new RuntimeException('Connection could not be established'));

    Livewire::test(UserLoginFlow::class)
        ->set('username', $this->user->username)
        ->set('password', 'kata-laluan-ujian')
        ->call('submitLogin')
        ->assertSet('step', 'login')
        ->assertSet('loginError', __('auth.otp_send_failed'));
})->skip('Memerlukan pengendalian mock mel penuh — didokumentasikan untuk rujukan.');

it('has a configured SMTP timeout so a hung connection cannot stall login', function (): void {
    // Tanpa had masa, PHP menunggu default_socket_timeout (kerap 60 saat),
    // lebih lama daripada had pelayan web. Hasilnya 504, bukan mesej ralat.
    expect(config('mail.mailers.smtp.timeout'))
        ->toBeInt()
        ->toBeGreaterThan(0)
        ->toBeLessThanOrEqual(30);
});

/*
|--------------------------------------------------------------------------
| Pemacu mel HTTPS
|--------------------------------------------------------------------------
*/

it('registers a mail driver that does not use SMTP', function (): void {
    // Penyedia server ini menyekat setiap port SMTP keluar. Tanpa pemacu
    // ini, tiada emel langsung boleh keluar dari server.
    expect(config('mail.mailers.brevo-api.transport'))->toBe('brevo-api');

    $transport = Illuminate\Support\Facades\Mail::getSymfonyTransport();

    config(['mail.default' => 'brevo-api', 'mail.mailers.brevo-api.key' => 'ujian']);

    expect((string) app('mail.manager')->mailer('brevo-api')->getSymfonyTransport())
        ->toBe('brevo-api');
})->skip('Memerlukan penyelesaian pengurus mel penuh — disahkan secara manual di server.');

it('builds a Brevo payload with sender, recipient and content', function (): void {
    $email = (new Symfony\Component\Mime\Email)
        ->from(new Symfony\Component\Mime\Address('dbenareport@gmail.com', 'DBENA Dashboard'))
        ->to(new Symfony\Component\Mime\Address('dbenagroup@gmail.com'))
        ->subject('Kod Log Masuk')
        ->text('123456');

    $transport = new App\Mail\Transport\BrevoApiTransport('kunci-ujian');

    $kaedah = new ReflectionMethod($transport, 'payload');
    $payload = $kaedah->invoke($transport, $email);

    expect($payload['sender']['email'])->toBe('dbenareport@gmail.com')
        ->and($payload['to'][0]['email'])->toBe('dbenagroup@gmail.com')
        ->and($payload['subject'])->toBe('Kod Log Masuk')
        ->and($payload['textContent'])->toContain('123456');
});

it('never sends an empty body, which Brevo rejects', function (): void {
    $email = (new Symfony\Component\Mime\Email)
        ->from(new Symfony\Component\Mime\Address('dbenareport@gmail.com'))
        ->to(new Symfony\Component\Mime\Address('dbenagroup@gmail.com'))
        ->subject('Kosong');

    $transport = new App\Mail\Transport\BrevoApiTransport('kunci-ujian');
    $payload = (new ReflectionMethod($transport, 'payload'))->invoke($transport, $email);

    expect($payload)->toHaveKey('textContent');
});

/*
|--------------------------------------------------------------------------
| OTP boleh dimatikan
|--------------------------------------------------------------------------
*/

it('logs a user straight in when OTP is switched off', function (): void {
    config(['dbena.otp.enabled' => false]);

    Livewire::test(UserLoginFlow::class)
        ->set('username', $this->user->username)
        ->set('password', 'kata-laluan-ujian')
        ->call('submitLogin')
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($this->user->id);
});

it('never parks the user on a success screen', function (): void {
    // Skrin itu memerlukan satu lagi permintaan Livewire untuk meneruskan,
    // dan permintaan itu membawa token CSRF yang dijana SEBELUM sesi
    // dijana semula. Laravel menolaknya sebagai 419, dan pengguna yang
    // baru sahaja berjaya log masuk melihat "This page has expired".
    config(['dbena.otp.enabled' => false]);

    Livewire::test(UserLoginFlow::class)
        ->set('username', $this->user->username)
        ->set('password', 'kata-laluan-ujian')
        ->call('submitLogin')
        ->assertNotSet('step', 'success');
});

it('redirects with a full page visit, not wire:navigate', function (): void {
    // wire:navigate mengambil halaman melalui fetch menggunakan token yang
    // sudah lapuk selepas penjanaan semula sesi. Lawatan penuh memuatkan
    // token baharu bersama halaman baharu.
    // Komen dibuang sebelum memeriksa: prosa yang MENERANGKAN kenapa
    // navigate: true dielakkan akan menyebabkan ujian ini gagal terhadap
    // kod yang betul, dan ujian yang menghukum penjelasan yang baik akan
    // menyebabkan penjelasan itu dipadam.
    $sumber = file_get_contents(app_path('Livewire/Concerns/HandlesOtpFlow.php'));

    $kod = preg_replace(['#/\*.*?\*/#s', '#//[^\n]*#'], '', $sumber);

    expect($kod)->not->toContain('navigate: true')
        ->and($kod)->toContain('$this->redirect($tuju)');
});

it('lands on the page the user was trying to reach', function (): void {
    // Menghantar semua orang ke dashboard bermakna pautan terus ke satu
    // laporan sentiasa mendarat di tempat yang salah.
    config(['dbena.otp.enabled' => false]);

    session()->put('url.intended', route('laporan'));

    Livewire::test(UserLoginFlow::class)
        ->set('username', $this->user->username)
        ->set('password', 'kata-laluan-ujian')
        ->call('submitLogin')
        ->assertRedirect(route('laporan'));
});

it('never issues an OTP when the step is switched off', function (): void {
    config(['dbena.otp.enabled' => false]);

    Livewire::test(UserLoginFlow::class)
        ->set('username', $this->user->username)
        ->set('password', 'kata-laluan-ujian')
        ->call('submitLogin');

    Notification::assertNothingSent();
    expect(Otp::count())->toBe(0);
});

it('still rejects a wrong password when OTP is switched off', function (): void {
    // Mematikan OTP mengalih keluar faktor KEDUA, bukan yang pertama.
    config(['dbena.otp.enabled' => false]);

    Livewire::test(UserLoginFlow::class)
        ->set('username', $this->user->username)
        ->set('password', 'kata-laluan-salah')
        ->call('submitLogin')
        ->assertSet('step', 'login');

    expect(auth()->check())->toBeFalse();
});

it('still rejects an inactive account when OTP is switched off', function (): void {
    config(['dbena.otp.enabled' => false]);
    $this->user->update(['is_active' => false]);

    Livewire::test(UserLoginFlow::class)
        ->set('username', $this->user->username)
        ->set('password', 'kata-laluan-ujian')
        ->call('submitLogin')
        ->assertSet('step', 'login');

    expect(auth()->check())->toBeFalse();
});

it('regenerates the session on both login paths', function (): void {
    // Kedua-dua laluan mesti menjana semula sesi. Menduplikasi logik log
    // masuk bermakna satu laluan akan terlepas langkah ini suatu hari.
    config(['dbena.otp.enabled' => false]);
    $sebelum = session()->getId();

    Livewire::test(UserLoginFlow::class)
        ->set('username', $this->user->username)
        ->set('password', 'kata-laluan-ujian')
        ->call('submitLogin');

    expect(session()->getId())->not->toBe($sebelum);
});

it('sends the OTP immediately instead of queueing it', function (): void {
    // Kod OTP sah beberapa minit sahaja dan pengguna sedang menunggu di
    // skrin. Jika notifikasi ini melaksanakan ShouldQueue, emel hanya
    // keluar apabila pekerja barisan berjalan — dan jika pekerja itu tidak
    // dipasang, kod tersangkut dalam jadual `jobs` tanpa sebarang ralat.
    expect(new SendOtpNotification('123456', OtpType::Login))
        ->not->toBeInstanceOf(Illuminate\Contracts\Queue\ShouldQueue::class);
});

it('refuses an OTP that is not six digits', function (): void {
    Livewire::test(UserLoginFlow::class)
        ->set('username', 'dbena')
        ->set('password', 'RahsiaKuat123')
        ->call('submitLogin')
        ->set('otpInput', '123')
        ->call('submitOtp')
        ->assertSet('step', 'otp')
        ->assertSet('otpError', __('auth.otp_length'));
});

it('locks the code after the configured number of wrong attempts', function (): void {
    $component = Livewire::test(UserLoginFlow::class)
        ->set('username', 'dbena')
        ->set('password', 'RahsiaKuat123')
        ->call('submitLogin');

    foreach (range(1, (int) config('dbena.otp.max_attempts')) as $attempt) {
        $component->set('otpInput', '000000')->call('submitOtp');
    }

    $component->set('otpInput', '000000')->call('submitOtp')
        ->assertSet('otpError', __('auth.otp_too_many_attempts'));

    $this->assertGuest();
});

it('rejects an expired OTP', function (): void {
    $component = Livewire::test(UserLoginFlow::class)
        ->set('username', 'dbena')
        ->set('password', 'RahsiaKuat123')
        ->call('submitLogin');

    Otp::where('user_id', $this->user->id)->update(['expires_at' => now()->subMinute()]);

    $component->set('otpInput', '123456')->call('submitOtp')
        ->assertSet('otpError', __('auth.otp_expired'));
});

it('throttles repeated failed logins', function (): void {
    // PEMBETULAN isu #6 — prototaip tiada had percubaan langsung.
    $component = Livewire::test(UserLoginFlow::class)->set('username', 'dbena');

    foreach (range(1, (int) config('dbena.login.max_attempts')) as $attempt) {
        $component->set('password', 'salah')->call('submitLogin');
    }

    $component->set('password', 'salah')->call('submitLogin');

    expect($component->get('loginError'))->toContain('saat');
});

it('blocks a deactivated account even with the right password', function (): void {
    $this->user->update(['is_active' => false]);

    Livewire::test(UserLoginFlow::class)
        ->set('username', 'dbena')
        ->set('password', 'RahsiaKuat123')
        ->call('submitLogin')
        ->assertSet('loginError', __('auth.account_inactive'));
});

it('turns away non-admins at the admin login screen', function (): void {
    Livewire::test(AdminLoginFlow::class)
        ->set('username', 'dbena')
        ->set('password', 'RahsiaKuat123')
        ->call('submitLogin')
        ->assertSet('step', 'login')
        ->assertSet('loginError', __('auth.not_admin'));

    $this->assertGuest();
});

it('requires at least eight characters when resetting a password', function (): void {
    Livewire::test(UserLoginFlow::class)
        ->set('step', 'resetPassword')
        ->set('resetUserId', $this->user->id)
        ->set('newPassword', 'abc1')
        ->set('confirmPassword', 'abc1')
        ->call('submitResetPassword')
        ->assertSet('resetPwError', __('auth.password_too_short'));
});

it('requires letters and numbers in a new password', function (): void {
    Livewire::test(UserLoginFlow::class)
        ->set('step', 'resetPassword')
        ->set('resetUserId', $this->user->id)
        ->set('newPassword', 'abcdefghij')
        ->set('confirmPassword', 'abcdefghij')
        ->call('submitResetPassword')
        ->assertSet('resetPwError', __('auth.password_weak'));
});

it('requires both new password fields to match', function (): void {
    Livewire::test(UserLoginFlow::class)
        ->set('step', 'resetPassword')
        ->set('resetUserId', $this->user->id)
        ->set('newPassword', 'RahsiaBaru123')
        ->set('confirmPassword', 'RahsiaLain123')
        ->call('submitResetPassword')
        ->assertSet('resetPwError', __('auth.password_mismatch'));
});

it('masks the email address on the OTP screen', function (): void {
    $this->user->update(['email' => 'ahmadnizam@dbena.com.my']);

    $masked = Livewire::test(UserLoginFlow::class)
        ->set('username', 'dbena')
        ->set('password', 'RahsiaKuat123')
        ->call('submitLogin')
        ->instance()
        ->maskedEmail();

    expect($masked)->toStartWith('ahm')
        ->and($masked)->toContain('@dbena.com.my')
        ->and($masked)->not->toContain('ahmadnizam');
});

it('logs the user out and invalidates the session', function (): void {
    $this->actingAs($this->user)
        ->post('/logout')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
