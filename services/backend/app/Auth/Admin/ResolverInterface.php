<?php

namespace App\Auth\Admin;

use App\Models\Admin;
use Laravel\Socialite\Contracts\User as SocialiteUser;

interface ResolverInterface
{
    public function resolve(SocialiteUser $microsoftUser): ?Admin;
}
