<?php

use Illuminate\Support\Str;
use App\Models\OTP;
use Illuminate\Support\Carbon;

function test()
{
    return 'hello';
}

function OtpValidate($request)
{
    $status = null;

    $otpEntry = OTP::where('email', $request['email'])
        ->where('expired_at', '>', Carbon::now())
        ->orderBy('created_at', 'desc')
        ->first();

    if ($otpEntry && ($request['otp'] == $otpEntry->otp)) {
        OTP::where('email', $request['email'])
            ->update([
                'is_used' => 1
            ]);
        $status = true;
    } else {
        $status = false;
    }

    return $status;
}

function paginateData($query, $page, $perPage, $totalItems)
{
    $offset = ($page - 1) * $perPage;
    $totalPages = ceil($totalItems / $perPage);

    $meta = [
        'current_page' => $page,
        'per_page' => $perPage,
        // 'total_items' => $totalItems,
        'total_pages' => $totalPages,
    ];

    $models = $query->skip($offset)
        ->take($perPage)
        ->get();
    return  [
        "data" => $models,
        "meta" => $meta
    ];
}

function generateSlug($model, $name)
{
    $slug = sprintf(Str::slug($name));
    $slugBase = $slug;
    $count = 1;

    while ($model::where('slug', $slug)->exists()) {
        $slug = $slugBase . '-' . $count++;
    }
    return $slug;
}
