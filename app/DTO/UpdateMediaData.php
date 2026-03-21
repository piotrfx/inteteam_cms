<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Http\Request;

final readonly class UpdateMediaData
{
    public function __construct(
        public ?string $altText,
        public ?string $caption,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            altText: $request->string('alt_text')->toString() ?: null,
            caption: $request->string('caption')->toString() ?: null,
        );
    }
}
