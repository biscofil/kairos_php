<?php

return [

    'elgamal' => [
        'base' => 16,
        'p' => env('ELGAMAL_P'),
        'g' => env('ELGAMAL_G'),
        'q' => env('ELGAMAL_Q'),
    ],

    'mixnets' => [
        'shadow_mixes' => intval(env('SHADOW_MIXES', 5))
    ]

];
