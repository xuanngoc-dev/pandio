<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesApiExceptions;
use App\Http\Controllers\Controller;

/**
 * Base cho toàn bộ API controller — có handleApi() try/catch chuẩn.
 */
abstract class BaseApiController extends Controller
{
    use HandlesApiExceptions;
}
