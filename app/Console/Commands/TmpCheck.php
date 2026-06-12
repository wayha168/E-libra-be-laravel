<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tmpcheck')]
#[Description('Command description')]
class TmpCheck extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $c = \App\Models\Category::query()->with(['image', 'bannerImage'])->first();
        if (!$c) {
            $this->info('no categories');
            return 0;
        }

        $this->info('image_id=' . ($c->image_id ?? 'null'));
        $this->info('banner_image_id=' . ($c->banner_image_id ?? 'null'));

        $img = $c->image?->url;
        $ban = $c->bannerImage?->url;
        $this->info('image=' . ($img ?? 'null'));
        $this->info('banner=' . ($ban ?? 'null'));

        if ($c->image_id) {
            $this->info('image count for image_id=' . (\App\Models\Image::query()->where('id', $c->image_id)->count()));
        }
        if ($c->banner_image_id) {
            $this->info('banner image count for banner_image_id=' . (\App\Models\Image::query()->where('id', $c->banner_image_id)->count()));
        }

        return 0;
    }
}
