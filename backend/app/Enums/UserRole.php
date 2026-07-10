<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Viewer = 'viewer';
    case Admin = 'admin';
}
