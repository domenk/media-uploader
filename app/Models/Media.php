<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Auth\Access\Response;

#[Fillable(['title', 'description', 'filename', 'user_id'])]
class Media extends Model {
}
