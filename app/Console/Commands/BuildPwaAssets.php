<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BuildPwaAssets extends Command
{
    protected $signature = 'pwa:assets';

    protected $description = 'Build public, non-personalized PWA icons, manifest and offline fallback';

    public function handle(): int
    {
        File::ensureDirectoryExists(public_path('icons'));
        foreach ([192, 512] as $size) {
            $image = imagecreatetruecolor($size, $size);
            $green = imagecolorallocate($image, 4, 120, 87);
            $white = imagecolorallocate($image, 255, 255, 255);
            imagefill($image, 0, 0, $green);
            imagesetthickness($image, (int) round($size * .04));
            imageline($image, (int) ($size * .23), (int) ($size * .47), (int) ($size * .5), (int) ($size * .24), $white);
            imageline($image, (int) ($size * .5), (int) ($size * .24), (int) ($size * .77), (int) ($size * .47), $white);
            imagerectangle($image, (int) ($size * .3), (int) ($size * .47), (int) ($size * .7), (int) ($size * .76), $white);
            imagerectangle($image, (int) ($size * .45), (int) ($size * .57), (int) ($size * .55), (int) ($size * .76), $white);
            imagepng($image, public_path('icons/icon-'.$size.'.png'));
            imagedestroy($image);
        }
        File::put(public_path('offline.html'), view('pwa.offline')->render());
        File::put(public_path('manifest.json'), json_encode([
            'id' => '/', 'name' => 'RentFlow', 'short_name' => 'RentFlow',
            'description' => __('app.tagline', [], 'en'), 'lang' => 'en', 'dir' => 'auto',
            'start_url' => '/dashboard', 'scope' => '/', 'display' => 'standalone',
            'background_color' => '#f8fafc', 'theme_color' => '#047857',
            'icons' => array_map(fn (int $size) => ['src' => '/icons/icon-'.$size.'.png', 'sizes' => $size.'x'.$size, 'type' => 'image/png', 'purpose' => 'any maskable'], [192, 512]),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->info('PWA public assets built.');

        return self::SUCCESS;
    }
}
