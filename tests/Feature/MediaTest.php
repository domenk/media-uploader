<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Http\Testing\File;
use Tests\TestCase;
use Tests\Feature\UserTest;
use App\Models\Media;
use App\Jobs\ProcessMedia;

class MediaTest extends TestCase {
	use RefreshDatabase;

	const MEDIA_TITLE_MINE = 'My image';
	const MEDIA_TITLE_NOT_MINE = 'SHOULD BE INVISIBLE';

	protected function get_auth_token($email = UserTest::USER_VALID_EMAIL) {
		$response = $this->postJson('/api/user/create', array(
			'name' => UserTest::USER_VALID_NAME,
			'email' => $email,
			'password' => UserTest::USER_VALID_PASSWORD,
		));

		$response = $this->postJson('/api/user/tokens/create', array(
			'email' => $email,
			'password' => UserTest::USER_VALID_PASSWORD,
		));

		return $response->json('token');
	}

	protected function get_auth_http_headers($email = UserTest::USER_VALID_EMAIL) {
		return array('Authorization' => 'Bearer '.$this->get_auth_token($email));
	}

	protected function create_example_media($processed = true) {
		$media1 = new Media();
		$media1->title = self::MEDIA_TITLE_MINE;
		$media1->description = 'My description';
		$media1->filename = 'file1.jpg';
		$media1->filename_thumbnail = ($processed?'file1_thumb.jpg':null);
		$media1->processed = $processed;
		$media1->user_id = 1;
		$media1->save();

		$media1 = new Media();
		$media1->title = self::MEDIA_TITLE_NOT_MINE;
		$media1->description = 'My description';
		$media1->filename = 'file2.jpg';
		$media1->filename_thumbnail = ($processed?'file2_thumb.jpg':null);
		$media1->processed = $processed;
		$media1->user_id = 2;
		$media1->save();
	}

	public function test_media_endpoints_without_auth_token(): void {
		$this->create_example_media();

		$response = $this->getJson('/api/media');
		$response->assertStatus(401);
		$response->assertJson(array('message' => 'Unauthenticated.'));

		$response = $this->postJson('/api/media');
		$response->assertStatus(401);
		$response->assertJson(array('message' => 'Unauthenticated.'));

		$response = $this->getJson('/api/media/1');
		$response->assertStatus(401);
		$response->assertJson(array('message' => 'Unauthenticated.'));

		$response = $this->getJson('/api/media/1/status');
		$response->assertStatus(401);
		$response->assertJson(array('message' => 'Unauthenticated.'));
	}

	public function test_media_endpoints_with_invalid_auth_token(): void {
		$this->create_example_media();

		$response = $this->getJson('/api/media', array('Authorization' => 'Bearer 1|invalidtoken'));
		$response->assertStatus(401);
		$response->assertJson(array('message' => 'Unauthenticated.'));

		$response = $this->postJson('/api/media', array(), array('Authorization' => 'Bearer 1|invalidtoken'));
		$response->assertStatus(401);
		$response->assertJson(array('message' => 'Unauthenticated.'));

		$response = $this->getJson('/api/media/1', array('Authorization' => 'Bearer 1|invalidtoken'));
		$response->assertStatus(401);
		$response->assertJson(array('message' => 'Unauthenticated.'));

		$response = $this->getJson('/api/media/1/status', array('Authorization' => 'Bearer 1|invalidtoken'));
		$response->assertStatus(401);
		$response->assertJson(array('message' => 'Unauthenticated.'));
	}

	public function test_get_media_with_no_results(): void {
		$response = $this->getJson('/api/media', $this->get_auth_http_headers());
		$response->assertStatus(200);
		$response->assertJsonCount(0);
	}

	public function test_get_media_with_results(): void {
		$this->create_example_media();

		$response = $this->getJson('/api/media', $this->get_auth_http_headers());
		$response->assertStatus(200);
		$response->assertJsonCount(1);
		$response->assertSee(self::MEDIA_TITLE_MINE);
		$response->assertDontSee(self::MEDIA_TITLE_NOT_MINE);
	}

	public function test_get_media_id_existing_mine(): void {
		$this->create_example_media();

		$response = $this->getJson('/api/media/1', $this->get_auth_http_headers());
		$response->assertStatus(200);
		$response->assertSee(self::MEDIA_TITLE_MINE);
		$response->assertDontSee(self::MEDIA_TITLE_NOT_MINE);

    $response->assertJson(function(AssertableJson $json) {
			return $json->hasAll(array('id', 'title', 'description', 'url', 'url_thumbnail', 'processed', 'created_at'));
		});
	}

	public function test_get_media_id_existing_not_mine(): void {
		$this->create_example_media();

		$response = $this->getJson('/api/media/2', $this->get_auth_http_headers());
		$response->assertStatus(403);
		$response->assertDontSee(self::MEDIA_TITLE_NOT_MINE);
		$response->assertJson(array('message' => 'This action is unauthorized.'));
	}

	public function test_get_media_id_nonexisting(): void {
		$this->create_example_media();

		$response = $this->getJson('/api/media/3', $this->get_auth_http_headers());
		$response->assertStatus(403);
		$response->assertJson(array('message' => 'This action is unauthorized.'));
	}

	public function test_get_media_id_status_existing_mine_processed(): void {
		$this->create_example_media(true);

		$response = $this->getJson('/api/media/1/status', $this->get_auth_http_headers());
		$response->assertStatus(200);
		$response->assertJson(array('media_id' => 1, 'status' => 'processed'));
	}

	public function test_get_media_id_status_existing_mine_not_processed(): void {
		$this->create_example_media(false);

		$response = $this->getJson('/api/media/1/status', $this->get_auth_http_headers());
		$response->assertStatus(200);
		$response->assertJson(array('media_id' => 1, 'status' => 'uploaded'));
	}

	public function test_get_media_id_status_existing_not_mine(): void {
		$this->create_example_media();

		$response = $this->getJson('/api/media/2/status', $this->get_auth_http_headers());
		$response->assertStatus(403);
		$response->assertJson(array('message' => 'This action is unauthorized.'));
	}

	public function test_get_media_id_status_nonexisting(): void {
		$this->create_example_media();

		$response = $this->getJson('/api/media/3/status', $this->get_auth_http_headers());
		$response->assertStatus(403);
		$response->assertJson(array('message' => 'This action is unauthorized.'));
	}

	public function test_post_media_empty(): void {
		$response = $this->postJson('/api/media', array(), $this->get_auth_http_headers());
		$response->assertStatus(422);
		$response->assertSee('The title field is required');
		$response->assertSee('The description field is required');
		$response->assertSee('The file field is required');
	}

	public function test_post_media_long_title(): void {
		$response = $this->postJson('/api/media', array(
			'title' => str_repeat('a', 300), // limit 255
			'description' => 'File description',
			'file' => 'my file',
		), $this->get_auth_http_headers());

		$response->assertStatus(422);
		$response->assertSee('The title field must not be greater than 255 characters');
	}

	public function test_post_media_long_description(): void {
		$response = $this->postJson('/api/media', array(
			'title' => 'File title',
			'description' => str_repeat('a', 300), // limit 255
			'file' => 'my file',
		), $this->get_auth_http_headers());

		$response->assertStatus(422);
		$response->assertSee('The description field must not be greater than 255 characters');
	}

	protected function get_fixture_file($filename) {
		return File::createWithContent($filename, file_get_contents(__DIR__.'/../fixtures/'.$filename));
	}

	protected function post_media_fixture_file($filename, $assertSuccess = true, $shouldCreateThumbnail = true) {
		Storage::fake('public');

		$response = $this->postJson('/api/media', array(
			'title' => 'File title',
			'description' => 'File description',
			'file' => $this->get_fixture_file($filename),
		), $this->get_auth_http_headers());

		if($assertSuccess) {
			$response->assertStatus(202);
			$response->assertJson(array('media_id' => 1));

			$media = Media::find(1);
			$this->assertEquals(true, $media->processed);
			$this->assertEquals($shouldCreateThumbnail, !is_null($media->filename_thumbnail));
		} else {
			$response->assertStatus(422);
			$response->assertSee('The file field must be a file of type');
		}
	}

	public function test_post_media_valid_png(): void {
		$this->post_media_fixture_file('sample.png');
	}

	public function test_post_media_valid_jpeg(): void {
		$this->post_media_fixture_file('sample.jpg');
	}

	public function test_post_media_valid_bmp(): void {
		$this->post_media_fixture_file('sample.bmp');
	}

	public function test_post_media_valid_gif(): void {
		$this->post_media_fixture_file('sample.gif');
	}

	public function test_post_media_valid_webp(): void {
		$this->post_media_fixture_file('sample.webp');
	}

	public function test_post_media_valid_mp4(): void {
		$this->post_media_fixture_file('sample.mp4');
	}

	public function test_post_media_valid_mpeg(): void {
		$this->post_media_fixture_file('sample.mpeg');
	}

	public function test_post_media_valid_avi(): void {
		$this->post_media_fixture_file('sample.avi');
	}

	public function test_post_media_valid_mov(): void {
		$this->post_media_fixture_file('sample.mov');
	}

	public function test_post_media_valid_webm(): void {
		$this->post_media_fixture_file('sample.webm');
	}

	public function test_post_media_broken_png(): void {
		$this->post_media_fixture_file('sample_broken.png', true, false);
	}

	public function test_post_media_broken_jpeg(): void {
		$this->post_media_fixture_file('sample_broken.jpg', true, true); // GD manages to create thumbnail
	}

	public function test_post_media_broken_mp4(): void {
		$this->post_media_fixture_file('sample_broken.mp4', true, false);
	}

	public function test_post_media_invalid_png(): void {
		$this->post_media_fixture_file('sample_txt.png', true, false);
	}

	public function test_post_media_invalid_mp4(): void {
		$this->post_media_fixture_file('sample_txt.mp4', true, false);
	}

	public function test_post_media_valid_png_with_supported_extension(): void {
		$this->post_media_fixture_file('sample_png.jpg');
	}

	public function test_post_media_valid_png_with_unsupported_extension(): void {
		$this->post_media_fixture_file('sample_png.dat', false);
	}

	public function test_post_media_unsupported_format(): void {
		$this->post_media_fixture_file('sample.txt', false);
	}
}
