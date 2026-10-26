<?php
// Copy OUTSIDE public web root as config.php and set environment variables.
// Never commit secrets or the real config file.
return [
  'dsn' => getenv('IMOTI_DB_DSN') ?: '',
  'user' => getenv('IMOTI_DB_USER') ?: '',
  'password' => getenv('IMOTI_DB_PASSWORD') ?: '',
  'allowed_origin' => getenv('IMOTI_ALLOWED_ORIGIN') ?: 'https://imotipanagyurishte.bg',
  'rate_secret' => getenv('IMOTI_RATE_SECRET') ?: '',
];