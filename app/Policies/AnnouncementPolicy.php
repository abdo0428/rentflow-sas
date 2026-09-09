<?php

namespace App\Policies;

class AnnouncementPolicy extends CompanyResourcePolicy
{
    protected string $permission = 'announcements';
}
