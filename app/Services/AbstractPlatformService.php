<?php

namespace App\Services;

use App\Contracts\PlatformServiceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

abstract class AbstractPlatformService implements PlatformServiceInterface
{
    protected $client;
    protected $baseUrl;
    protected $account;

    /**
     * Common task data structure
     */
    protected $commonTaskFields = [
        'id',
        'name',
        'description',
        'status',
        'priority',
        'due_date',
        'start_date',
        'assignees',
        'tags',
        'custom_fields',
        'dependencies',
        'time_estimate',
        'time_spent',
        'progress'
    ];

    /**
     * Platform-specific error handling
     */
    protected function handleApiException(RequestException $e, string $action): void
    {
        throw new \Exception("Failed to {$action}: " . $e->getMessage());
    }

    /**
     * Transform date to ISO format
     */
    protected function formatDate(?string $date): ?string
    {
        if (!$date) return null;
        
        try {
            return date('Y-m-d\TH:i:s\Z', is_numeric($date) ? intval($date) / 1000 : strtotime($date));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse date from various formats
     */
    protected function parseDate(?string $date): ?int
    {
        if (!$date) return null;
        
        try {
            return is_numeric($date) ? intval($date) : strtotime($date) * 1000;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract dependencies from task data
     */
    protected function extractDependencies(array $taskData): array
    {
        return $taskData['dependencies'] ?? [];
    }

    /**
     * Calculate task progress percentage
     */
    protected function calculateProgress(array $taskData): int
    {
        // Default implementation - can be overridden by platforms
        if (isset($taskData['status'])) {
            $status = strtolower($taskData['status']['status'] ?? $taskData['status']);
            if (in_array($status, ['complete', 'closed', 'done'])) {
                return 100;
            } else if (in_array($status, ['in progress', 'in_progress', 'working'])) {
                return 50;
            }
        }
        return 0;
    }

    /**
     * Abstract method to get platform name
     */
    abstract public function getPlatformName(): string;

    /**
     * Abstract method to get platform color scheme
     */
    abstract public function getPlatformColors(): array;
}