<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Enums;

enum SettingRole: string
{
    case System = 'system';
    case User = 'user';
    case Plugin = 'plugin';
}
