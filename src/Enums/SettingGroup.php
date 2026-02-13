<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Enums;

enum SettingGroup: string
{
    case General = 'general';
    case Authentication = 'authentication';
    case Notifications = 'notifications';
    case Security = 'security';
    case Appearance = 'appearance';
    case System = 'system';

    /**
     * Get the human-readable label for this group.
     */
    public function label(): string
    {
        return match ($this) {
            self::General => 'General',
            self::Authentication => 'Authentication',
            self::Notifications => 'Notifications',
            self::Security => 'Security',
            self::Appearance => 'Appearance',
            self::System => 'System',
        };
    }
}
