<?php

namespace App\Support\Announcements;

use App\Models\Graduate;
use Illuminate\Database\Eloquent\Collection;

/**
 * Who an announcement reaches, worked out by AudienceResolver.
 * Used for the "This will reach N alumni" preview and when sending.
 */
final readonly class Audience
{
    /**
     * @param  Collection<int, Graduate>  $graduates  Everyone it reaches (demo graduates never included).
     * @param  list<string>  $unknownIds  Pasted Student ID Numbers with no matching graduate.
     * @param  int  $withoutYear  Year audience only: graduates left out because no year is on record.
     */
    public function __construct(
        public Collection $graduates,
        public array $unknownIds = [],
        public int $withoutYear = 0,
    ) {}

    public function count(): int
    {
        return $this->graduates->count();
    }

    /**
     * No email address: they only see it on the site.
     *
     * @return Collection<int, Graduate>
     */
    public function withoutEmail(): Collection
    {
        return $this->graduates->filter(fn (Graduate $graduate) => blank($graduate->email))->values();
    }

    /**
     * Never activated their alumni account, so they cannot log in to read
     * "selected" announcements until they do.
     *
     * @return Collection<int, Graduate>
     */
    public function notActivated(): Collection
    {
        return $this->graduates->reject(fn (Graduate $graduate) => $graduate->isActivated())->values();
    }
}
