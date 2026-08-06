<?php

namespace App\Models\Concerns;

use App\Support\Media;

/**
 * Khi serialize JSON, các field trong mediaUrlAttributes()
 * được chuyển thành URL tuyệt đối. Giá trị trong DB vẫn là path tương đối.
 */
trait HasPublicMediaUrls
{
    /**
     * @return list<string>
     */
    abstract protected function mediaUrlAttributes(): array;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        foreach ($this->mediaUrlAttributes() as $attribute) {
            $raw = $this->attributes[$attribute] ?? null;
            if (is_string($raw) && $raw !== '') {
                $array[$attribute] = Media::url($raw);
            }
        }

        return $array;
    }
}
