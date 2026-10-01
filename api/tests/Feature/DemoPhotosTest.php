<?php

use App\Models\Tour;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

it('attaches demo photos with uuid, order, alt text and webp versions', function () {
    Storage::fake('public');
    $dir = storage_path('framework/testing/demo-photos');
    File::ensureDirectoryExists($dir);
    $image = imagecreatetruecolor(1800, 1000);
    imagewebp($image, "{$dir}/song-kul-panorama.webp");
    config(['brand.demo_photos_path' => $dir]);

    $this->seed(DatabaseSeeder::class);

    $media = Tour::where('slug', 'song-kul-horse-trek-yurt-stay')->firstOrFail()->getMedia('gallery')->first();

    expect($media)->not->toBeNull()
        ->and($media->uuid)->not->toBeNull()
        ->and($media->order_column)->not->toBeNull()
        ->and($media->getCustomProperty('alt'))->toContain('Song-Kul')
        ->and($media->hasGeneratedConversion('w480'))->toBeTrue()
        ->and($media->getUrl('w960'))->toEndWith('.webp');

    File::deleteDirectory($dir);
});
