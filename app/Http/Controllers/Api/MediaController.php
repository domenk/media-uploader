<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Jobs\ProcessMedia;

class MediaController extends Controller {
	public function index(Request $request) {
		$mediaArray = array();

		$usersMedia = Media::where('user_id', $request->user()->id)->get();
		foreach($usersMedia as $media) {
			$mediaArray[] = $this->getMediaResponseArray($media);
		}

		return $mediaArray;
	}

	public function store(Request $request) {
		$request->validate([
			'title' => ['required', 'max:255'],
			'description' => ['required', 'max:255'],
			'file' => ['file', 'mimes:jpeg,png,bmp,gif,webp,mpeg,mp4,avi,mov,webm'],
		]);

		$newFilename = Storage::disk('public')->putFile('media', $request->file('file'));

		$media = Media::create([
			'title' => $request->input('title'),
			'description' => $request->input('description'),
			'filename' => $newFilename,
			'user_id' => $request->user()->id,
		]);

		ProcessMedia::dispatch($media);

		return response()->make($this->getStatusResponseArray($media), 202);
	}

	public function show(string $id) {
		$media = Media::find($id);
		$this->authorize('view', $media);
		return $this->getMediaResponseArray($media);
	}

	public function getStatus(string $id) {
		$media = Media::find($id);
		$this->authorize('view', $media);
		return $this->getStatusResponseArray($media);
	}

	protected function getMediaResponseArray(Media $media) {
		return array(
			'id' => $media->id,
			'title' => $media->title,
			'description' => $media->description,
			'url' => url(Storage::url($media->filename)),
			'url_thumbnail' => (!is_null($media->filename_thumbnail)?url(Storage::url($media->filename_thumbnail)):null),
			'processed' => !empty($media->processed),
			'created_at' => $media->created_at,
		);
	}

	protected function getStatusResponseArray(Media $media) {
		return array(
			'media_id' => $media->id,
			'status' => ($media->processed?'processed':'uploaded'),
		);
	}
}
