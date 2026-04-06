# Media uploader

Simple API for uploading media files.

## Prerequisites

Package [php-ffmpeg/php-ffmpeg](https://github.com/PHP-FFMpeg/PHP-FFMpeg/) requires [ffmpeg](https://ffmpeg.org/download.html) to be installed.

File processing uses Laravel queues.

## Usage

Use `POST user/create` API endpoint to create user. Retrieve authentication token for managing media using `GET user/tokens/create` endpoint.

Endpoints `media*` require authentication token sent using `Authorization: Bearer TOKEN` HTTP header.

Handling media:
* List all media using `GET media`.
* Upload media using `POST media`.
* Get media data using `GET media/{id}`.
* Get media status after upload using `GET media/{id}/status`.

## API documentation

### `POST user/create` – create user

Creates new user.

Required fields: `name`, `email`, `password`

Example response on success:
```json
{
	"status":"success"
}
```

### `POST user/show` – show user data

Retrieves user data. May be used for testing user credentials.

Required fields: `email`, `password`

Example response on success:
```json
{
	"name": "John Doe",
	"email": "john.doe@example.com"
}
```

### `POST user/tokens/create` – create token

Creates new authentication token for the user. Retrieved token is used in `Authorization: Bearer TOKEN` HTTP header when accessing `media*` endpoints.

Required fields: `email`, `password`

Example response on success:
```json
{
	"token": "99|iPX2cfDOz7Hdqq3qiAZci..."
}
```

### `GET media` – retrieve all uploaded media

Retrieves data of all uploaded media. See `GET media/{id}` for field descriptions.

Required headers: `Authorization`

Example response on success:
```json
[
	{
		"id": 115,
		"title": "Laravel logotype",
		"description": "Transparent Laravel icon",
		"url": "https:\/\/PATH\/storage\/media\/EEkpq2qwhThdmGDCteDD60lonIoUm06lYRIcl3CF.png",
		"url_thumbnail": "https:\/\/PATH\/storage\/media\/EEkpq2qwhThdmGDCteDD60lonIoUm06lYRIcl3CF_thumb.png",
		"processed": true,
		"created_at": "2026-04-05T10:12:53.000000Z"
	},
	{
		"id": 132,
		"title": "Earth from Space",
		"description": "Image of Earth From the Perspective of Artemis II - art002e000192",
		"url": "https:\/\/PATH\/storage\/media\/evIA8HkYcishyNydtCIoyGyLu4W3KqNjr9j6eu02.jpg",
		"url_thumbnail": "https:\/\/PATH\/storage\/media\/evIA8HkYcishyNydtCIoyGyLu4W3KqNjr9j6eu02_thumb.jpg",
		"processed": true,
		"created_at": "2026-04-05T12:43:08.000000Z"
	}
]
```

### `POST media` – upload media

Uploads media (image or video) to the server. Server will create thumbnail of the media. Processing status can be checked using `GET media/{id}/status`.

Required headers: `Authorization`

Required fields:
* `title` – maximum 255 characters
* `description` – maximum 255 characters
* `file` – supported formats: JPEG, PNG, BMP, GIF, WEBP, MPEG, MP4, AVI, MOV, WEBM

Example response on success:
```json
{
	"media_id": 159,
	"status": "uploaded"
}
```

### `GET media/{id}` – get media data

Retrieves media data.

Required headers: `Authorization`

Returned fields:
* `id` – media ID
* `title` – title, as sent when file was uploaded
* `description` – description, as sent when file was uploaded
* `url` – public URL to the original file
* `url_thumbnail` – public URL to the file thumbnail (or `null` if the file has not been processed yet or the processing has failed (broken file etc.))
* `processed` – boolean, `true` if file is processed
* `created_at` – upload timestamp

Example response on success:
```json
{
	"id": 132,
	"title": "Earth from Space",
	"description": "Image of Earth From the Perspective of Artemis II - art002e000192",
	"url": "https:\/\/PATH\/storage\/media\/evIA8HkYcishyNydtCIoyGyLu4W3KqNjr9j6eu02.jpg",
	"url_thumbnail": "https:\/\/PATH\/storage\/media\/evIA8HkYcishyNydtCIoyGyLu4W3KqNjr9j6eu02_thumb.jpg",
	"processed": true,
	"created_at": "2026-04-05T12:43:08.000000Z"
}
```

### `GET media/{id}/status` – get media processing status

Retrieves media processing status. Field `status` contains value `uploaded` (not yet processed) or `processed` (thumbnail created).

Required headers: `Authorization`

Example response on success:
```json
{
	"media_id": 132,
	"status": "processed"
}
```
