<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilemparkan apabila kod OTP dijana tetapi emel gagal dihantar.
 *
 * Ini dibezakan daripada kegagalan lain kerana tindakannya berbeza: kod
 * tersebut wujud dan sah, cuma tiada cara untuk pengguna melihatnya. Skrin
 * log masuk mesti mengatakannya dengan jelas, bukan memaparkan skrin
 * "masukkan kod" untuk kod yang tidak akan sampai.
 */
class OtpDeliveryException extends RuntimeException {}
