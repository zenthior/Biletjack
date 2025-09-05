<?php
// Parola hash/rehash yardımcıları

function bj_get_password_algo_and_options() {
    // PHP 7.3+ ise Argon2id, değilse 7.2+ Argon2i, daha düşükse bcrypt (PASSWORD_DEFAULT)
    if (defined('PASSWORD_ARGON2ID')) {
        return ['algo' => PASSWORD_ARGON2ID, 'options' => [
            'memory_cost' => 1 << 17, // 128 MB
            'time_cost'   => 4,
            'threads'     => 2,
        ]];
    } elseif (defined('PASSWORD_ARGON2I')) {
        return ['algo' => PASSWORD_ARGON2I, 'options' => [
            'memory_cost' => 1 << 17, // 128 MB
            'time_cost'   => 4,
            'threads'     => 2,
        ]];
    } else {
        // Bcrypt
        return ['algo' => PASSWORD_DEFAULT, 'options' => [
            'cost' => 12,
        ]];
    }
}

function bj_hash_password($password) {
    $cfg = bj_get_password_algo_and_options();
    return password_hash($password, $cfg['algo'], $cfg['options']);
}

function bj_password_needs_rehash($hash) {
    $cfg = bj_get_password_algo_and_options();
    return password_needs_rehash($hash, $cfg['algo'], $cfg['options']);
}