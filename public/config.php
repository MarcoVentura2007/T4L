<?php
define('DATA_DIR', __DIR__ . '/../data');

if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0777, true);
}

define('USER_FILE', DATA_DIR . '/utenti.txt');

if (!file_exists(USER_FILE)) {
    file_put_contents(USER_FILE, '');
}

define('PEPPER_FILE', DATA_DIR . '/pepper.txt');

if (!file_exists(PEPPER_FILE)) {
    file_put_contents(PEPPER_FILE, bin2hex(random_bytes(32)));
}

define('PEPPER', trim(file_get_contents(PEPPER_FILE)));
