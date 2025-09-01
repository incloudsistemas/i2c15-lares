<?php

namespace App\Services\Polymorphics;

use Spatie\Activitylog\Models\Activity as ActivityLog;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ActivityLogService extends BaseService
{
    public function __construct(protected ActivityLog $activityLog)
    {
        parent::__construct();
    }

    public function transform(ActivityLog $activityLog): ?array
    {
        $map = [
            'media'           => \App\Transformers\Polymorphics\ActivityLog\MediaTransformer::class,
            'tenant_plans'    => \App\Transformers\Polymorphics\ActivityLog\TenantPlanTransformer::class,
            'tenant_accounts' => \App\Transformers\Polymorphics\ActivityLog\TenantAccountTransformer::class,
            'users'           => \App\Transformers\Polymorphics\ActivityLog\UserTransformer::class,
        ];

        $transformerClass = $map[$activityLog->log_name] ?? null;

        if (!$transformerClass) {
            return null;
        }

        $transformer = app($transformerClass);

        return $transformer->transform($activityLog);
    }

    /**
     * Logs a 'created' activity with a cleaned snapshot of the current record.
     */
    public function logCreatedActivity(Model $currentRecord, string $description): void
    {
        $attributes = $this->cleanArrayEmptyValuesRecursive($currentRecord->toArray());

        activity(MorphMapByClass(model: $currentRecord::class))
            ->performedOn($currentRecord)
            ->causedBy(auth()->user())
            ->event('created')
            ->withProperties([
                'attributes' => $attributes,
            ])
            ->log($description);
    }

    /**
     * Logs an 'updated' activity only when changes are detected (including NxN relations).
     * Produces a robust diff (associatives recursively; lists as set-like with added/removed).
     */
    public function logUpdatedActivity(
        Model $currentRecord,
        array $oldRecord,
        string $description,
        array $except = []
    ): void {
        // 1) Normalize both sides for deterministic comparison
        $current = $this->normalizeForLog($currentRecord->toArray());
        $old = $this->normalizeForLog($oldRecord);

        $current = Arr::except($current, $except);
        $old  = Arr::except($old, $except);

        // Avoid noisy top-level data
        unset($current['id'], $current['created_at'], $current['updated_at'], $current['deleted_at']);
        unset($old['id'], $old['created_at'], $old['updated_at'], $old['deleted_at']);

        // 2) Build a robust diff
        $changed = $this->arrayDiffAssocRecursive($current, $old);

        // 3) Build a compatible "old" snapshot mirroring the structure of $changed
        $oldChanged = $this->buildOldFromChanged($changed, $old);

        // 4) Clean empty nodes while preserving list diffs (added/removed)
        $changed = $this->cleanArrayEmptyValuesRecursive($changed, preserveListDiff: true);
        $oldChanged = $this->cleanArrayEmptyValuesRecursive($oldChanged, preserveListDiff: true);

        if (empty($changed) || empty($oldChanged)) {
            return; // nothing to log
        }

        activity(MorphMapByClass(model: $currentRecord::class))
            ->performedOn($currentRecord)
            ->causedBy(auth()->user())
            ->event('updated')
            ->withProperties([
                'attributes' => $changed,
                'old'        => $oldChanged,
            ])
            ->log($description);
    }

    /**
     * Logs a 'deleted' activity with a cleaned snapshot of the old record.
     */
    public function logDeletedActivity(Model $oldRecord, string $description): void
    {
        $old = $this->cleanArrayEmptyValuesRecursive($oldRecord->toArray());

        activity(MorphMapByClass(model: $oldRecord::class))
            ->performedOn($oldRecord)
            ->causedBy(auth()->user())
            ->event('deleted')
            ->withProperties([
                'old' => $old,
            ])
            ->log($description);
    }

    /**
     * Logs 'created' activity for a relation created on an owner record.
     */
    public function logOwnerRecordRelationCreatedActivity(
        Model $ownerRecord,
        Model $currentRecord,
        string $description,
        ?string $logName = null
    ): void {
        $attributes = $this->cleanArrayEmptyValuesRecursive($currentRecord->toArray());

        $logName = $logName ?? MorphMapByClass(model: $currentRecord::class);
        activity($logName)
            ->performedOn($ownerRecord)
            ->causedBy(auth()->user())
            ->event('created')
            ->withProperties([
                'attributes' => $attributes,
            ])
            ->log($description);
    }

    /**
     * Logs 'updated' activity for a relation change on an owner record.
     * Detects diffs in associative fields and NxN lists (added/removed).
     */
    public function logOwnerRecordRelationUpdatedActivity(
        Model $ownerRecord,
        Model $currentRecord,
        array $oldRecord,
        string $description,
        ?string $logName = null
    ): void {
        // 1) Normalize both sides for deterministic comparison
        $current = $this->normalizeForLog($currentRecord->toArray());
        $old = $this->normalizeForLog($oldRecord);

        // Avoid noisy top-level data
        unset($current['id'], $current['created_at'], $current['updated_at'], $current['deleted_at']);
        unset($old['id'], $old['created_at'], $old['updated_at'], $old['deleted_at']);

        // 2) Robust diff (handles lists and associative arrays)
        $changed = $this->arrayDiffAssocRecursive($current, $old);

        // 3) Build "old" mirror for the changed structure
        $oldChanged = $this->buildOldFromChanged($changed, $old);

        // 4) Clean, preserving list diff nodes
        $changed = $this->cleanArrayEmptyValuesRecursive($changed, preserveListDiff: true);
        $oldChanged = $this->cleanArrayEmptyValuesRecursive($oldChanged, preserveListDiff: true);

        if (empty($changed) || empty($oldChanged)) {
            return; // nothing to log
        }

        $logName = $logName ?? MorphMapByClass(model: $currentRecord::class);
        activity($logName)
            ->performedOn($ownerRecord)
            ->causedBy(auth()->user())
            ->event('updated')
            ->withProperties([
                'attributes' => $changed,
                'old'        => $oldChanged,
            ])
            ->log($description);
    }

    /**
     * Logs 'deleted' activity for a relation deleted on an owner record.
     */
    public function logOwnerRecordRelationDeletedActivity(
        Model $ownerRecord,
        Model $oldRecord,
        string $description,
        ?string $logName = null
    ): void {
        $old = $this->cleanArrayEmptyValuesRecursive($oldRecord->toArray());

        $logName = $logName ?? MorphMapByClass(model: $oldRecord::class);
        activity($logName)
            ->performedOn($ownerRecord)
            ->causedBy(auth()->user())
            ->event('deleted')
            ->withProperties([
                'old' => $old,
            ])
            ->log($description);
    }

    /* =========================
     * Normalization helpers
     * ========================= */

    /**
     * Produces a stable, comparable structure:
     * - Converts Arrayables/Collections into arrays
     * - Normalizes nested structures
     * - Sorts keys for associative arrays (deterministic comparisons)
     */
    protected function normalizeForLog(array $data): array
    {
        $normalize = function ($value) use (&$normalize) {
            if ($value instanceof \Illuminate\Contracts\Support\Arrayable) {
                $value = $value->toArray();
            }

            if (is_array($value)) {
                $isList = $this->isList($value);
                $out = [];

                foreach ($value as $k => $v) {
                    $out[$k] = $normalize($v);
                }

                if (!$isList) {
                    ksort($out);
                }

                return $out;
            }

            return $value; // scalars unchanged
        };

        return $normalize($data);
    }

    /**
     * Determines whether an array is a list (0..n-1 keys).
     */
    protected function isList(array $arr): bool
    {
        return array_keys($arr) === range(0, count($arr) - 1);
    }

    /* =========================
     * Robust diff
     * ========================= */

    /**
     * Recursive diff:
     * - Associative arrays: diff per-key recursively
     * - Numeric lists: set-like diff returning ['added'=>[], 'removed'=>[]]
     */
    protected function arrayDiffAssocRecursive(array $base, array $reference): array
    {
        $diff = [];

        $baseIsList = $this->isList($base);
        $refIsList  = $this->isList($reference);

        // Case 1: both sides are lists -> set-like diff
        if ($baseIsList && $refIsList) {
            // If lists differ, log the whole new list; otherwise, no diff.
            return $this->deepEqual($base, $reference) ? [] : $base;
        }

        // Case 2: at least one side is associative -> walk keys
        foreach ($base as $key => $value) {
            if (!array_key_exists($key, $reference)) {
                $diff[$key] = $value; // new key
                continue;
            }

            $refVal = $reference[$key];

            if (is_array($value) && is_array($refVal)) {
                $sub = $this->arrayDiffAssocRecursive($value, $refVal);
                if (!empty($sub)) {
                    $diff[$key] = $sub;
                }
            } elseif (!$this->deepEqual($value, $refVal)) {
                $diff[$key] = $value;
            }
        }

        return $diff;
    }

    /**
     * Deep comparator for array_udiff (0 if equal; non-zero if different).
     */
    protected function deepCompare(mixed $a, mixed $b): int
    {
        return $this->deepEqual($a, $b) ? 0 : 1;
    }

    /**
     * Deep equality for scalars and arrays (handles associative and list semantics).
     */
    protected function deepEqual(mixed $a, mixed $b): bool
    {
        if (is_array($a) && is_array($b)) {
            if ($this->isList($a) !== $this->isList($b)) {
                return false;
            }

            if ($this->isList($a)) {
                // Lists: compare as multisets (order-insensitive, multiplicity-aware)
                if (count($a) !== count($b)) {
                    return false;
                }
                $sa = array_map(fn($v) => $this->stableSerialize($v), $a);
                $sb = array_map(fn($v) => $this->stableSerialize($v), $b);
                sort($sa);
                sort($sb);
                return $sa === $sb;
            }

            // Associatives: compare key-by-key
            if (count($a) !== count($b)) {
                return false;
            }
            ksort($a);
            ksort($b);
            foreach ($a as $k => $v) {
                if (!array_key_exists($k, $b)) {
                    return false;
                }
                if (!$this->deepEqual($v, $b[$k])) {
                    return false;
                }
            }
            return true;
        }

        return $a === $b;
    }

    /**
     * Stable serialization for deep comparison inside lists (order-insensitive).
     */
    protected function stableSerialize(mixed $value): string
    {
        if (is_array($value)) {
            if ($this->isList($value)) {
                $items = array_map(fn($v) => $this->stableSerialize($v), $value);
                sort($items);
                return 'L:[' . implode(',', $items) . ']';
            } else {
                ksort($value);
                $parts = [];
                foreach ($value as $k => $v) {
                    $parts[] = $k . ':' . $this->stableSerialize($v);
                }
                return 'A:{' . implode(',', $parts) . '}';
            }
        }

        if (is_bool($value)) {
            return $value ? 'b:1' : 'b:0';
        }

        if ($value === null) {
            return 'null';
        }

        return 's:' . (string) $value;
    }

    /* =========================
     * Build "old" mirror
     * ========================= */

    /**
     * Rebuilds an "old" structure mirroring $changed.
     * For list-diff nodes (added/removed), returns the full old list.
     */
    protected function buildOldFromChanged(mixed $changedNode, mixed $oldNode)
    {
        if (!is_array($changedNode)) {
            return $oldNode; // leaf: return old scalar
        }

        if (is_array($changedNode) && $this->isList($changedNode)) {
            // When a list changed, mirror by returning the full old list
            return $oldNode;
        }

        $result = [];
        foreach ($changedNode as $k => $v) {
            $result[$k] = isset($oldNode[$k]) ? $this->buildOldFromChanged($v, $oldNode[$k]) : null;
        }
        return $result;
    }

    /* =========================
     * Safe cleanup
     * ========================= */

    /**
     * Cleans "empty" nodes but preserves list-diff structures (added/removed), even if empty.
     * This avoids accidentally dropping signals that a list was compared/changed.
     */
    protected function cleanArrayEmptyValuesRecursive(mixed $node, bool $preserveListDiff = true)
    {
        if (!is_array($node)) {
            return $node;
        }

        $isListDiff = $preserveListDiff
            && (array_key_exists('added', $node) || array_key_exists('removed', $node));

        if ($isListDiff) {
            // Always keep the added/removed structure; clean inside
            $node['added']   = $this->cleanArrayEmptyValuesRecursive($node['added']   ?? [], true);
            $node['removed'] = $this->cleanArrayEmptyValuesRecursive($node['removed'] ?? [], true);
            return $node;
        }

        $out = [];
        foreach ($node as $k => $v) {
            $v = $this->cleanArrayEmptyValuesRecursive($v, $preserveListDiff);
            $keep = true;

            if (is_array($v)) {
                $keep = !empty($v); // drop empty arrays
            } else {
                $keep = !($v === null || (is_string($v) && trim($v) === ''));
            }

            if ($keep) {
                $out[$k] = $v;
            }
        }

        return $out;
    }
}
