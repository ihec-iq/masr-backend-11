<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class CacheService
{
    /**
     * Cache duration in seconds.
     */
    const CACHE_DURATION = 3600; // 1 hour
    const CACHE_SHORT_DURATION = 300; // 5 minutes
    const CACHE_LONG_DURATION = 86400; // 24 hours

    // Aliases for easier usage
    const DURATION_SHORT = 300; // 5 minutes
    const DURATION_NORMAL = 3600; // 1 hour
    const DURATION_LONG = 86400; // 24 hours

    /**
     * Cache categories for better cache management.
     */
    const CATEGORY_DASHBOARD = 'dashboard';
    const CATEGORY_EMPLOYEES = 'employees';
    const CATEGORY_VACATIONS = 'vacations';
    const CATEGORY_ARCHIVES = 'archives';
    const CATEGORY_LOOKUPS = 'lookups';
    const CATEGORY_USERS = 'users';
    const CATEGORY_STOCK = 'stock';

    /**
     * Registry key prefix.
     */
    const REGISTRY_PREFIX = 'cache_registry';

    /**
     * Check if the current cache driver supports tags.
     *
     * @return bool
     */
    public function supportsTags(): bool
    {
        // Drivers that support tags
        $taggedDrivers = ['redis', 'memcached', 'dynamodb', 'array', 'octane'];
        $currentDriver = config('cache.default');

        return in_array($currentDriver, $taggedDrivers);
    }

    /**
     * Get cached data or execute callback and cache result.
     * 
     * This method uses the correct Laravel parameter order and registers keys for easy invalidation.
     *
     * @param string $key Cache key
     * @param int $duration Duration in seconds
     * @param callable $callback Function to execute if cache miss
     * @param string|null $category Category for grouping (for invalidation)
     * @return mixed
     */
    public function remember(string $key, int $duration, callable $callback, ?string $category = null)
    {
        try {
            // Register the key in the category if provided
            if ($category) {
                $this->registerKey($category, $key);
            }

            return Cache::remember($key, $duration, $callback);
        } catch (\Exception $e) {
            Log::error("CacheService::remember failed for key '{$key}': " . $e->getMessage());

            // Fall back to executing callback without caching
            try {
                return $callback();
            } catch (\Exception $callbackException) {
                Log::error("Callback execution failed for key '{$key}': " . $callbackException->getMessage());
                throw $callbackException;
            }
        }
    }

    /**
     * Register a cache key under a category.
     *
     * @param string $category
     * @param string $key
     * @return void
     */
    protected function registerKey(string $category, string $key): void
    {
        try {
            $registryKey = $this->getRegistryKey($category);
            $keys = Cache::get($registryKey, []);

            if (!in_array($key, $keys)) {
                $keys[] = $key;
                // Store registry for 7 days
                Cache::put($registryKey, $keys, 60 * 60 * 24 * 7);
            }
        } catch (\Exception $e) {
            // Silent fail - registry is not critical
            Log::warning("Failed to register cache key '{$key}' in category '{$category}': " . $e->getMessage());
        }
    }

    /**
     * Get registry key for a category.
     *
     * @param string $category
     * @return string
     */
    protected function getRegistryKey(string $category): string
    {
        return self::REGISTRY_PREFIX . ".{$category}";
    }

    /**
     * Get all registered keys for a category.
     *
     * @param string $category
     * @return array
     */
    public function getCategoryKeys(string $category): array
    {
        try {
            $registryKey = $this->getRegistryKey($category);
            return Cache::get($registryKey, []);
        } catch (\Exception $e) {
            Log::error("Failed to get category keys for '{$category}': " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clear cache by key.
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        try {
            return Cache::forget($key);
        } catch (\Exception $e) {
            Log::error("Failed to forget cache key '{$key}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Invalidate all cache keys in a category.
     *
     * @param string $category
     * @return bool
     */
    public function invalidateCategory(string $category): bool
    {
        try {
            $keys = $this->getCategoryKeys($category);
            $success = true;

            foreach ($keys as $key) {
                if (!Cache::forget($key)) {
                    $success = false;
                    Log::warning("Failed to forget cache key '{$key}' in category '{$category}'");
                }
            }

            // Clear the registry itself
            $registryKey = $this->getRegistryKey($category);
            Cache::forget($registryKey);

            if ($success) {
                Log::info("Successfully invalidated category '{$category}' with " . count($keys) . " keys");
            }

            return $success;
        } catch (\Exception $e) {
            Log::error("Failed to invalidate category '{$category}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear cache by tags (if supported).
     *
     * @param array $tags
     * @return bool
     */
    public function flushTags(array $tags): bool
    {
        try {
            if (!$this->supportsTags()) {
                Log::warning("Cache driver does not support tags, falling back to category invalidation");

                // Try to map tags to categories
                foreach ($tags as $tag) {
                    $this->invalidateCategory($tag);
                }
                return true;
            }

            Cache::tags($tags)->flush();
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to flush tags: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cache lookup tables (sections, types, etc.)
     *
     * @param string $model
     * @param string $orderBy
     * @return Collection
     */
    public function cacheLookupTable(string $model, string $orderBy = 'name'): Collection
    {
        $key = "lookup_{$model}";

        return $this->remember(
            $key,
            self::CACHE_LONG_DURATION,
            function () use ($model, $orderBy) {
                $modelClass = "App\\Models\\{$model}";
                return $modelClass::orderBy($orderBy)->get();
            },
            self::CATEGORY_LOOKUPS
        );
    }

    /**
     * Cache employee list.
     *
     * @return Collection
     */
    public function cacheEmployeeList(): Collection
    {
        return $this->remember(
            'employees_list',
            self::CACHE_DURATION,
            function () {
                return \App\Models\Employee::select('id', 'name', 'employee_number', 'section_id')
                    ->where('is_person', true)
                    ->orderBy('name')
                    ->get();
            },
            self::CATEGORY_EMPLOYEES
        );
    }

    /**
     * Invalidate employee cache.
     *
     * @return bool
     */
    public function invalidateEmployeeCache(): bool
    {
        return $this->invalidateCategory(self::CATEGORY_EMPLOYEES);
    }

    /**
     * Invalidate vacation cache.
     *
     * @return bool
     */
    public function invalidateVacationCache(): bool
    {
        return $this->invalidateCategory(self::CATEGORY_VACATIONS);
    }

    /**
     * Invalidate archive cache.
     *
     * @return bool
     */
    public function invalidateArchiveCache(): bool
    {
        return $this->invalidateCategory(self::CATEGORY_ARCHIVES);
    }

    /**
     * Invalidate lookup tables cache.
     *
     * @return bool
     */
    public function invalidateLookupCache(): bool
    {
        return $this->invalidateCategory(self::CATEGORY_LOOKUPS);
    }

    /**
     * Invalidate user cache.
     *
     * @return bool
     */
    public function invalidateUserCache(): bool
    {
        return $this->invalidateCategory(self::CATEGORY_USERS);
    }

    /**
     * Invalidate dashboard cache.
     *
     * @return bool
     */
    public function invalidateDashboardCache(): bool
    {
        return $this->invalidateCategory(self::CATEGORY_DASHBOARD);
    }

    /**
     * Invalidate stock cache.
     *
     * @return bool
     */
    public function invalidateStockCache(): bool
    {
        return $this->invalidateCategory(self::CATEGORY_STOCK);
    }

    /**
     * Clear all application cache.
     *
     * @return bool
     */
    public function clearAll(): bool
    {
        try {
            Cache::flush();
            Log::info("All cache cleared successfully");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to clear all cache: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get cache statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        $stats = [
            'driver' => config('cache.default'),
            'tags_support' => $this->supportsTags(),
            'categories' => [],
        ];

        // Count keys in each category
        $categories = [
            self::CATEGORY_DASHBOARD,
            self::CATEGORY_EMPLOYEES,
            self::CATEGORY_VACATIONS,
            self::CATEGORY_ARCHIVES,
            self::CATEGORY_LOOKUPS,
            self::CATEGORY_USERS,
            self::CATEGORY_STOCK,
        ];

        foreach ($categories as $category) {
            $keys = $this->getCategoryKeys($category);
            $stats['categories'][$category] = count($keys);
        }

        return $stats;
    }
}
