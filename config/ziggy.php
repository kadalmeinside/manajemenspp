<?php

return [
    'groups' => [
        'public' => [
            'login',
            'logout',
            'password.*',
            'verification.*',
            'welcome',
            'public.*',
            'payment.*',
            'pendaftaran.*',
            'display.*',
            'legal.*',
            're-register.*',
            're-register-academy.*',
            'register-academy.*',
            'register-ss.*',
            'promo.validate',
            'tagihan.spp.*',
            'webhooks.*',
        ],
        'siswa' => [
            'login',
            'logout',
            'password.*',
            'verification.*',
            'welcome',
            'public.*',
            'payment.*',
            'pendaftaran.*',
            'display.*',
            'legal.*',
            're-register.*',
            're-register-academy.*',
            'register-academy.*',
            'register-ss.*',
            'promo.validate',
            'tagihan.spp.*',
            'webhooks.*',
            'siswa.*',
            'student-leaves.*',
        ],
        'admin' => [
            '*', // Admin has access to all routes
        ],
    ],
];
