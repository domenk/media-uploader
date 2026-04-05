<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use FFMpeg;
use App\Models\Media;

class ProcessMedia implements ShouldQueue {
	use Queueable;

	protected $media;

	public function __construct(Media $media) {
		$this->media = $media;
	}

	public function handle(): void {
		$storage = Storage::disk('public');

		$mediaFilenamePathParts = pathinfo($this->media->filename);
		$mediaFilenamePrefix = (isset($mediaFilenamePathParts['dirname'])?$mediaFilenamePathParts['dirname'].'/':'').$mediaFilenamePathParts['filename'];

		$mediaImageFilename = $this->media->filename;

		$mediaMimeType = mime_content_type($storage->path($this->media->filename));

		if(explode('/', $mediaMimeType)[0] == 'video') {
			$mediaImageFilename = $mediaFilenamePrefix.'_frame.jpg';

			$ffprobe = FFMpeg\FFProbe::create();
			$mediaDuration = $ffprobe->format($storage->path($this->media->filename))->get('duration');

			$ffmpeg = FFMpeg\FFMpeg::create();
			$video = $ffmpeg->open($storage->path($this->media->filename));
			$video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(min(2, $mediaDuration / 2)))->save($storage->path($mediaImageFilename));
		}

		$thumbnailFilename = $mediaFilenamePrefix.'_thumb.'.pathinfo($mediaImageFilename, PATHINFO_EXTENSION);

		$manager = ImageManager::usingDriver(Driver::class);
		$image = $manager->decode($storage->path($mediaImageFilename));
		$image->scaleDown(200, 200);
		$storage->put($thumbnailFilename, (string) $image->encode());

		$this->media->filename_thumbnail = $thumbnailFilename;
		$this->media->processed = true;
		$this->media->save();
	}
}
