<?php

use App\Models\User;

return [
    'icon' => 'heroicon-o-adjustments',
    'group' => 'Tool',

    /**
     * List Model you want to exclude from model factory
     */
    "excludes" => [
        // User::class
    ],

    /**
     * List All nicknames for model class
     */
    "nicknames" => [
        User::class => "User"
    ],
];
