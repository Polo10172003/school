<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

if (!function_exists('mailer_default_config')) {
    /**
     * Base mailer configuration with sane defaults for Hostinger SMTP.
     *
     * @return array<string,mixed>
     */
    function mailer_default_config(): array
    {
        $cryptoMethod = getenv('SMTP_CRYPTO_METHOD');
        if (is_string($cryptoMethod) && $cryptoMethod !== '') {
            $cryptoMethod = strtolower(trim($cryptoMethod));
        } else {
            $cryptoMethod = null;
        }

        $resolvedCrypto = null;
        switch ($cryptoMethod) {
            case 'tls_client':
                if (defined('STREAM_CRYPTO_METHOD_TLS_CLIENT')) {
                    $resolvedCrypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                }
                break;
            case 'tls12':
            case 'tls1_2':
            case 'tlsv1.2':
            case 'tlsv1_2':
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $resolvedCrypto = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                }
                break;
            case 'tls13':
            case 'tls1_3':
            case 'tlsv1.3':
            case 'tlsv1_3':
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                    $resolvedCrypto = STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                }
                break;
            default:
                // Hostinger (and most shared SMTP providers) expect TLS 1.2.
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $resolvedCrypto = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                } elseif (defined('STREAM_CRYPTO_METHOD_TLS_CLIENT')) {
                    $resolvedCrypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                }
                break;
        }

        $sslOptions = [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
            'ciphers'           => 'DEFAULT@SECLEVEL=1',
        ];
        if ($resolvedCrypto !== null) {
            $sslOptions['crypto_method'] = $resolvedCrypto;
        }

        $forceIpv4 = getenv('SMTP_FORCE_IPV4');
        if ($forceIpv4 !== false) {
            $forceIpv4 = filter_var($forceIpv4, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        } else {
            $forceIpv4 = null;
        }

        $base = [
            'host'        => getenv('SMTP_HOST') ?: 'smtp.hostinger.com',
            'username'    => getenv('SMTP_USERNAME') ?: 'no-reply@rosariodigital.site',
            'password'    => getenv('SMTP_PASSWORD') ?: 'Dan@65933',
            'from_email'  => getenv('SMTP_FROM') ?: 'no-reply@rosariodigital.site',
            'from_name'   => getenv('SMTP_FROM_NAME') ?: 'Escuela De Sto. Rosario',
            'charset'     => 'UTF-8',
            'encoding'    => 'base64',
            'timeout'     => 20,
            'force_ipv4'  => $forceIpv4,
            'smtp_options' => [
                'ssl' => $sslOptions,
                'tls' => $sslOptions,
            ],
            'fallback_to_mail' => filter_var(
                getenv('SMTP_ALLOW_MAIL_FALLBACK'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ),
            'log_path' => getenv('SMTP_LOG_PATH') ?: (__DIR__ . '/../temp/mailer_last_error.log'),
        ];

        if ($base['fallback_to_mail'] === null) {
            $base['fallback_to_mail'] = true;
        }

        if ($base['force_ipv4'] === null) {
            $base['force_ipv4'] = false;
        }

        $debugLevel = getenv('SMTP_DEBUG_LEVEL');
        if ($debugLevel !== false) {
            $base['debug_level'] = (int) $debugLevel;
        }

        $debugLog = getenv('SMTP_DEBUG_LOG');
        if ($debugLog !== false) {
            $base['debug_log'] = $debugLog;
        }

        return $base;
    }

    /**
     * Apply reusable defaults to a PHPMailer instance.
     *
     * @param PHPMailer             $mail
     * @param array<string,mixed>   $overrides
     *
     * @return array<string,mixed>  The effective configuration.
     */
    function mailer_apply_defaults(PHPMailer $mail, array $overrides = []): array
    {
        $config = array_merge(mailer_default_config(), $overrides);

        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->SMTPAutoTLS = true;
        $mail->CharSet = (string) $config['charset'];
        $mail->Encoding = (string) $config['encoding'];
        $smtpHost = (string) $config['host'];
        $hostList = [$smtpHost];
        if (!empty($config['force_ipv4'])) {
            $resolved = gethostbyname($smtpHost);
            if (is_string($resolved) && $resolved !== '' && $resolved !== $smtpHost) {
                $hostList[] = $resolved;
            }
        }
        $mail->Host = implode(';', array_unique(array_filter($hostList)));
        if (!empty($config['force_ipv4'])) {
            $mail->Hostname = $smtpHost;
        }
        $mail->Username = (string) $config['username'];
        $mail->Password = (string) $config['password'];
        $mail->Timeout = (int) $config['timeout'];
        $mail->SMTPOptions = $config['smtp_options'] ?? [];

        if (!empty($config['from_email'])) {
            $mail->setFrom((string) $config['from_email'], (string) ($config['from_name'] ?? ''));
        }

        if (!empty($config['debug_level'])) {
            $mail->SMTPDebug = (int) $config['debug_level'];
        }

        if (!empty($config['debug_log'])) {
            $logPath = (string) $config['debug_log'];
            $mail->Debugoutput = static function (string $str, int $level) use ($logPath): void {
                @file_put_contents(
                    $logPath,
                    sprintf("[%s][lvl:%d] %s\n", date('c'), $level, $str),
                    FILE_APPEND
                );
            };
        }

        return $config;
    }

    /**
     * Default transport attempts ordered by preference.
     *
     * @return array<int,array<string,mixed>>
     */
    function mailer_default_transports(): array
    {
        return [
            [
                'label'  => 'starttls://587',
                'secure' => PHPMailer::ENCRYPTION_STARTTLS,
                'port'   => (int) (getenv('SMTP_PORT_TLS') ?: 587),
                'smtp_auto_tls' => true,
            ],
            [
                'label'  => 'smtps://465',
                'secure' => PHPMailer::ENCRYPTION_SMTPS,
                'port'   => (int) (getenv('SMTP_PORT_SSL') ?: 465),
                'smtp_auto_tls' => false,
            ],
        ];
    }

    /**
     * Attempt to send the message trying multiple SMTP transports when needed.
     *
     * @param PHPMailer                      $mail
     * @param array<int,array<string,mixed>> $transports
     * @param callable|null                  $logger Receives log lines for diagnostics.
     *
     * @throws Exception
     */
    function mailer_send_with_fallback(
        PHPMailer $mail,
        array $transports = [],
        ?callable $logger = null,
        bool $fallbackToMail = false
    ): void {
        $attempts = $transports ?: mailer_default_transports();
        $lastException = null;
        $effectiveLogger = $logger;

        if (!$effectiveLogger) {
            $defaultLog = mailer_default_config()['log_path'] ?? null;
            if ($defaultLog) {
                $effectiveLogger = static function (string $line) use ($defaultLog): void {
                    @file_put_contents(
                        $defaultLog,
                        sprintf("[%s] %s\n", date('c'), $line),
                        FILE_APPEND
                    );
                };
            }
        }

        foreach ($attempts as $transport) {
            $label = $transport['label'] ?? sprintf(
                '%s:%s',
                (string) ($transport['secure'] ?? ''),
                (string) ($transport['port'] ?? '')
            );

            $mail->SMTPSecure = $transport['secure'] ?? PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = (int) ($transport['port'] ?? 465);
            $mail->SMTPAutoTLS = isset($transport['smtp_auto_tls'])
                ? (bool) $transport['smtp_auto_tls']
                : ($mail->SMTPSecure === PHPMailer::ENCRYPTION_STARTTLS);

            if ($effectiveLogger) {
                $effectiveLogger(sprintf('Attempting SMTP via %s', $label));
            }

            try {
                $mail->send();
                if ($effectiveLogger) {
                    $effectiveLogger(sprintf('SMTP via %s succeeded', $label));
                }
                return;
            } catch (Exception $exception) {
                $lastException = $exception;
                $mail->smtpClose();

                if ($effectiveLogger) {
                    $effectiveLogger(sprintf(
                        'SMTP via %s failed: %s',
                        $label,
                        $exception->getMessage()
                    ));
                }
            }
        }

        if ($fallbackToMail) {
            if ($effectiveLogger) {
                $effectiveLogger('All SMTP attempts failed, falling back to PHP mail() transport.');
            }
            $mail->isMail();
            $mail->SMTPDebug = 0;
            $mail->Debugoutput = 'error_log';
            try {
                $mail->send();
                if ($effectiveLogger) {
                    $effectiveLogger('mail() transport succeeded.');
                }
                return;
            } catch (Exception $mailException) {
                $lastException = $mailException;
                if ($effectiveLogger) {
                    $effectiveLogger('mail() transport failed: ' . $mailException->getMessage());
                }
            }
            return;
        }

        if ($lastException instanceof Exception) {
            throw $lastException;
        }

        throw new Exception('Mailer encountered an unknown error.');
    }
}
