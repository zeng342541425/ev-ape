<?php

namespace App\Rules\Directory;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

/**
 * 檢測是否允許出現名為 .. 的子目錄
 *
 * Class ParentDirectory
 * @package App\Rules\Directory
 */
class ParentDirectory implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return !Str::contains($value, '..');
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return trans('validation.parent_directory');
    }
}
