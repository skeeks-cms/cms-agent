<?php
namespace skeeks\cms\agent;

/** Domain-owned choice and validation; the scheduler stores only the payload. */
interface JobTargetProviderInterface
{
    public function label(): string;
    public function items(int $siteId): array;
    public function selected(array $payload): ?string;
    /** Resolve again on save; reject absent or foreign-site objects. */
    public function payload(string $id, int $siteId): array;
}
