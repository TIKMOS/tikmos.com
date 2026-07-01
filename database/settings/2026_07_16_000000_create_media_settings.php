<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('media.accepted_formats', ['png', 'jpg', 'jpeg', 'webp']);
        $this->migrator->add('media.max_size_kb', 4096);
        $this->migrator->add('media.quality', 82);
        $this->migrator->add('media.blur_up', true);
        $this->migrator->add('media.gallery_max', 8);
    }
};
