<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class UserTest extends TestCase {
	use RefreshDatabase;

	const USER_VALID_NAME = 'Example User';
	const USER_VALID_EMAIL = 'user@example.com';
	const USER_VALID_PASSWORD = 'MyPassword!';

	public function test_user_create_with_empty_data(): void {
		$response = $this->postJson('/api/user/create');

		$response->assertStatus(422);
		$response->assertSee('The name field is required');
		$response->assertSee('The email field is required');
		$response->assertSee('The password field is required');
	}

	public function test_user_create_with_long_name(): void {
		$response = $this->postJson('/api/user/create', array(
			'name' => str_repeat('a', 300), // limit is 255
			'email' => self::USER_VALID_EMAIL,
			'password' => self::USER_VALID_PASSWORD,
		));

		$response->assertStatus(422);
		$response->assertSee('The name field must not be greater than 255 characters');
		$response->assertDontSee('The email field');
		$response->assertDontSee('The password field');
	}

	public function test_user_create_with_long_email(): void {
		$response = $this->postJson('/api/user/create', array(
			'name' => self::USER_VALID_NAME,
			'email' => str_repeat('a', 300).'@example.com', // limit is 255
			'password' => self::USER_VALID_PASSWORD,
		));

		$response->assertStatus(422);
		$response->assertSee('The email field must not be greater than 255 characters');
		$response->assertDontSee('The name field');
		$response->assertDontSee('The password field');
	}

	public function test_user_create_with_invalid_email(): void {
		$response = $this->postJson('/api/user/create', array(
			'name' => self::USER_VALID_NAME,
			'email' => 'user|example.com',
			'password' => self::USER_VALID_PASSWORD,
		));

		$response->assertStatus(422);
		$response->assertSee('The email field must be a valid email address');
		$response->assertDontSee('The name field');
		$response->assertDontSee('The password field');
	}

	protected function user_create_with_valid_data() {
		$response = $this->postJson('/api/user/create', array(
			'name' => self::USER_VALID_NAME,
			'email' => self::USER_VALID_EMAIL,
			'password' => self::USER_VALID_PASSWORD,
		));
		return $response;
	}

	public function test_user_create_with_valid_data(): void {
		$response = $this->user_create_with_valid_data();
		$response->assertStatus(200);
		$response->assertJson(array('status' => 'success'));
	}

	public function test_user_create_with_same_valid_data_twice(): void {
		$response = $this->user_create_with_valid_data();
		$response = $this->user_create_with_valid_data();

		$response->assertStatus(422);
		$response->assertSee('The email has already been taken');
	}

	public function test_user_show_for_existing_user_with_valid_credentials(): void {
		$this->user_create_with_valid_data();

		$response = $this->postJson('/api/user/show', array(
			'email' => self::USER_VALID_EMAIL,
			'password' => self::USER_VALID_PASSWORD,
		));

		$response->assertStatus(200);
		$response->assertJson(array('name' => self::USER_VALID_NAME, 'email' => self::USER_VALID_EMAIL));
	}

	public function test_user_show_for_existing_user_with_invalid_credentials(): void {
		$this->user_create_with_valid_data();

		$response = $this->postJson('/api/user/show', array(
			'email' => self::USER_VALID_EMAIL,
			'password' => 'WrongPassword!',
		));

		$response->assertStatus(422);
		$response->assertJson(array('error' => 'wrong_credentials'));
	}

	public function test_user_show_for_nonexisting_user(): void {
		$this->user_create_with_valid_data();

		$response = $this->postJson('/api/user/show', array(
			'email' => 'unknown@example.com',
			'password' => self::USER_VALID_PASSWORD,
		));

		$response->assertStatus(422);
		$response->assertJson(array('error' => 'wrong_credentials'));
	}

	public function test_user_tokens_create_for_existing_user_with_valid_credentials(): void {
		$this->user_create_with_valid_data();

		$response = $this->postJson('/api/user/tokens/create', array(
			'email' => self::USER_VALID_EMAIL,
			'password' => self::USER_VALID_PASSWORD,
		));

		$response->assertStatus(200);
		$response->assertJson(function(AssertableJson $json) {
			return $json->has('token');
		});
	}

	public function test_user_tokens_create_for_existing_user_with_invalid_credentials(): void {
		$this->user_create_with_valid_data();

		$response = $this->postJson('/api/user/tokens/create', array(
			'email' => self::USER_VALID_EMAIL,
			'password' => 'WrongPassword!',
		));

		$response->assertStatus(422);
		$response->assertJson(array('error' => 'wrong_credentials'));
	}

	public function test_user_tokens_create_for_nonexisting_user(): void {
		$this->user_create_with_valid_data();

		$response = $this->postJson('/api/user/tokens/create', array(
			'email' => 'unknown@example.com',
			'password' => self::USER_VALID_PASSWORD,
		));

		$response->assertStatus(422);
		$response->assertJson(array('error' => 'wrong_credentials'));
	}
}
