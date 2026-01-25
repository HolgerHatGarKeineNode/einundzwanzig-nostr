<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class MoveMediaToPrivateDisk extends Command
{
    protected $signature = 'media:move-to-private
                            {--dry-run : Show what would be moved without actually moving}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Move all Spatie Media Library files from public disk to private disk';

    private int $movedCount = 0;

    private int $skippedCount = 0;

    private int $errorCount = 0;

    public function handle(): int
    {
        $mediaOnPublicDisk = Media::query()
            ->where('disk', 'public')
            ->get();

        if ($mediaOnPublicDisk->isEmpty()) {
            $this->info('No media files found on public disk. Nothing to migrate.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d media file(s) on public disk.', $mediaOnPublicDisk->count()));

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No files will be moved.');
            $this->newLine();
            $this->showDryRunTable($mediaOnPublicDisk);

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Do you want to proceed with moving these files to the private disk?')) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $this->newLine();
        $progressBar = $this->output->createProgressBar($mediaOnPublicDisk->count());
        $progressBar->start();

        foreach ($mediaOnPublicDisk as $media) {
            $this->processMedia($media);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->showSummary();

        return $this->errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function processMedia(Media $media): void
    {
        $sourceDisk = Storage::disk('public');
        $targetDisk = Storage::disk('private');

        $relativePath = $this->getRelativePath($media);

        if (! $sourceDisk->exists($relativePath)) {
            $this->newLine();
            $this->warn(sprintf('  Source file not found: %s (Media ID: %d)', $relativePath, $media->id));
            $this->skippedCount++;

            return;
        }

        if ($targetDisk->exists($relativePath)) {
            $this->newLine();
            $this->warn(sprintf('  Target already exists: %s (Media ID: %d)', $relativePath, $media->id));
            $this->skippedCount++;

            return;
        }

        try {
            DB::beginTransaction();

            $mediaDirectory = (string) $media->id;

            if (! $targetDisk->exists($mediaDirectory)) {
                $targetDisk->makeDirectory($mediaDirectory);
            }

            $fileContent = $sourceDisk->get($relativePath);
            if ($fileContent === null) {
                throw new \RuntimeException('Failed to read source file');
            }

            $targetDisk->put($relativePath, $fileContent);

            if (! $targetDisk->exists($relativePath)) {
                throw new \RuntimeException('Failed to write target file');
            }

            $this->moveConversions($media, $sourceDisk, $targetDisk);

            $media->disk = 'private';
            $media->save();

            $sourceDisk->delete($relativePath);
            $this->cleanupEmptyDirectories($sourceDisk, $mediaDirectory);

            DB::commit();
            $this->movedCount++;

        } catch (Throwable $e) {
            DB::rollBack();

            $this->cleanupFailedMigration($targetDisk, (string) $media->id);

            $this->newLine();
            $this->error(sprintf('  Failed to move Media ID %d: %s', $media->id, $e->getMessage()));
            $this->errorCount++;
        }
    }

    private function moveConversions(Media $media, $sourceDisk, $targetDisk): void
    {
        $conversionsPath = $this->getConversionsPath($media);

        if (! $sourceDisk->exists($conversionsPath)) {
            return;
        }

        $conversionFiles = $sourceDisk->files($conversionsPath);

        if (! $targetDisk->exists($conversionsPath)) {
            $targetDisk->makeDirectory($conversionsPath);
        }

        foreach ($conversionFiles as $conversionFile) {
            $content = $sourceDisk->get($conversionFile);
            if ($content !== null) {
                $targetDisk->put($conversionFile, $content);
                $sourceDisk->delete($conversionFile);
            }
        }

        $this->cleanupEmptyDirectories($sourceDisk, $conversionsPath);
    }

    private function getRelativePath(Media $media): string
    {
        return sprintf('%s/%s', $media->id, $media->file_name);
    }

    private function getConversionsPath(Media $media): string
    {
        return sprintf('%s/conversions', $media->id);
    }

    private function cleanupEmptyDirectories($disk, string $path): void
    {
        while ($path !== '.' && $path !== '') {
            $files = $disk->files($path);
            $directories = $disk->directories($path);

            if (empty($files) && empty($directories)) {
                $disk->deleteDirectory($path);
                $path = dirname($path);
            } else {
                break;
            }
        }
    }

    private function cleanupFailedMigration($disk, string $mediaDirectory): void
    {
        if ($disk->exists($mediaDirectory)) {
            $disk->deleteDirectory($mediaDirectory);
        }
    }

    private function showDryRunTable($mediaCollection): void
    {
        $rows = $mediaCollection->map(fn (Media $media) => [
            $media->id,
            $media->model_type,
            $media->model_id,
            $media->collection_name,
            $media->file_name,
            $this->formatBytes($media->size),
        ])->toArray();

        $this->table(
            ['ID', 'Model Type', 'Model ID', 'Collection', 'Filename', 'Size'],
            $rows
        );
    }

    private function showSummary(): void
    {
        $this->info('Migration complete.');
        $this->newLine();
        $this->table(
            ['Status', 'Count'],
            [
                ['Moved', $this->movedCount],
                ['Skipped', $this->skippedCount],
                ['Errors', $this->errorCount],
            ]
        );

        if ($this->errorCount > 0) {
            $this->newLine();
            $this->error('Some files failed to migrate. Please check the errors above and retry.');
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return sprintf('%.2f %s', $bytes, $units[$unitIndex]);
    }
}
