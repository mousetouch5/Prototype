<?php

namespace App\Services;

use App\Models\SyncConfiguration;
use App\Models\SyncLog;
use App\Contracts\PlatformServiceInterface;

class CrossPlatformSyncService extends SyncService
{
    protected $sourceService;
    protected $targetService;

    public function syncTasks(SyncConfiguration $config)
    {
        $this->syncLog = SyncLog::create([
            'sync_configuration_id' => $config->id,
            'status' => SyncLog::STATUS_PENDING,
        ]);

        try {
            $this->syncLog->markAsRunning();

            // Get platform services using the new accessor methods
            $this->sourceService = $config->sourceService;
            $this->targetService = $config->targetService;

            $sourceTasks = $this->fetchAllTasks($config);
            $results = $this->processCrossPlatformTasks($sourceTasks, $config);

            $this->syncLog->markAsCompleted($results);
            
            if ($config->isScheduled()) {
                $config->calculateNextSyncTime();
                $config->save();
            }

            return $this->syncLog;
        } catch (\Exception $e) {
            $this->syncLog->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    protected function processCrossPlatformTasks(array $sourceTasks, SyncConfiguration $config)
    {
        $created = 0;
        $updated = 0;
        $failed = 0;

        foreach ($sourceTasks as $sourceTask) {
            try {
                // Transform source task to common format
                $commonTask = $this->sourceService->transformToCommonFormat($sourceTask, 'task');
                
                // Apply field mappings
                $commonTask = $this->applyFieldMappings($commonTask, $config);
                
                // Check if task exists in target
                $targetTask = $this->findExistingCrossPlatformTask($commonTask, $config);
                
                if ($targetTask) {
                    $this->updateCrossPlatformTask($targetTask, $commonTask, $config);
                    $this->syncLog->incrementTasksUpdated();
                    $updated++;
                } else {
                    $this->createCrossPlatformTask($commonTask, $config);
                    $this->syncLog->incrementTasksCreated();
                    $created++;
                }

                // Sync additional data if enabled
                if ($config->sync_comments && isset($targetTask)) {
                    $this->syncComments($sourceTask['id'], $targetTask['id'], $config);
                }

                if ($config->sync_attachments && isset($targetTask)) {
                    $this->syncAttachments($sourceTask['id'], $targetTask['id'], $config);
                }
            } catch (\Exception $e) {
                $this->syncLog->incrementTasksFailed();
                $failed++;
                \Log::error('Cross-platform task sync failed', [
                    'source_platform' => $config->source_platform,
                    'target_platform' => $config->target_platform,
                    'task_id' => $sourceTask['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'total' => count($sourceTasks),
            'source_platform' => $config->source_platform,
            'target_platform' => $config->target_platform,
        ];
    }

    protected function applyFieldMappings(array $commonTask, SyncConfiguration $config): array
    {
        // Apply status mapping
        if (isset($config->status_mapping[$commonTask['status']['name']])) {
            $commonTask['status']['name'] = $config->status_mapping[$commonTask['status']['name']];
        }

        // Apply user mapping
        if ($config->user_mapping) {
            $mappedAssignees = [];
            foreach ($commonTask['assignees'] as $assignee) {
                if (isset($config->user_mapping[$assignee['id']])) {
                    $mappedAssignees[] = [
                        'id' => $config->user_mapping[$assignee['id']],
                        'name' => $assignee['name'],
                        'email' => $assignee['email']
                    ];
                }
            }
            $commonTask['assignees'] = $mappedAssignees;
        }

        // Apply custom field mapping
        if ($config->field_mapping) {
            $mappedFields = [];
            foreach ($commonTask['custom_fields'] as $field) {
                if (isset($config->field_mapping[$field['id']])) {
                    $field['id'] = $config->field_mapping[$field['id']];
                    $mappedFields[] = $field;
                }
            }
            $commonTask['custom_fields'] = $mappedFields;
        }

        return $commonTask;
    }

    protected function findExistingCrossPlatformTask($commonTask, SyncConfiguration $config)
    {
        // Get target list ID based on platform
        $listId = $this->getTargetListId($config);
        
        $targetTasks = $this->targetService->getTasks($listId);
        
        foreach ($targetTasks['tasks'] ?? [] as $task) {
            $targetCommonTask = $this->targetService->transformToCommonFormat($task, 'task');
            
            if ($this->isMatchingCrossPlatformTask($commonTask, $targetCommonTask, $config)) {
                return $task;
            }
        }

        return null;
    }

    protected function isMatchingCrossPlatformTask($sourceTask, $targetTask, SyncConfiguration $config)
    {
        $syncOptions = $config->sync_options ?? [];
        
        if (isset($syncOptions['match_by'])) {
            switch ($syncOptions['match_by']) {
                case 'name':
                    return $sourceTask['name'] === $targetTask['name'];
                case 'external_id':
                    // Use a custom field to store original platform ID
                    foreach ($targetTask['custom_fields'] as $field) {
                        if ($field['name'] === 'sync_external_id' && $field['value'] === $sourceTask['id']) {
                            return true;
                        }
                    }
                    return false;
                default:
                    return false;
            }
        }

        // Default to name matching
        return $sourceTask['name'] === $targetTask['name'];
    }

    protected function createCrossPlatformTask($commonTask, SyncConfiguration $config)
    {
        // Transform common format to target platform format
        $targetTaskData = $this->targetService->transformFromCommonFormat($commonTask, 'task');
        
        // Add external ID for future matching
        if ($config->sync_options['match_by'] ?? null === 'external_id') {
            $targetTaskData['custom_fields'] = $targetTaskData['custom_fields'] ?? [];
            $targetTaskData['custom_fields']['sync_external_id'] = $commonTask['id'];
        }
        
        $listId = $this->getTargetListId($config);
        return $this->targetService->createTask($listId, $targetTaskData);
    }

    protected function updateCrossPlatformTask($targetTask, $commonTask, SyncConfiguration $config)
    {
        if ($config->conflict_resolution === 'target_wins') {
            return;
        }

        // Transform common format to target platform format
        $targetTaskData = $this->targetService->transformFromCommonFormat($commonTask, 'task');
        
        return $this->targetService->updateTask($targetTask['id'], $targetTaskData);
    }

    protected function getTargetListId(SyncConfiguration $config): string
    {
        // For ClickUp, use list_id directly
        if ($config->target_platform === 'clickup') {
            return $config->target_list_id;
        }
        
        // For Monday.com, list_id is actually board_id
        if ($config->target_platform === 'monday') {
            return $config->target_list_id; // This would be the board ID
        }
        
        throw new \Exception('Unsupported target platform: ' . $config->target_platform);
    }

    protected function syncComments($sourceTaskId, $targetTaskId, SyncConfiguration $config)
    {
        if (!$targetTaskId) {
            return;
        }

        try {
            $sourceComments = $this->sourceService->getTaskComments($sourceTaskId);
            
            foreach ($sourceComments['comments'] ?? [] as $comment) {
                $commentText = $comment['comment_text'] ?? $comment['body'] ?? '';
                if ($commentText) {
                    $this->targetService->createTaskComment($targetTaskId, $commentText);
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to sync comments', [
                'source_task_id' => $sourceTaskId,
                'target_task_id' => $targetTaskId,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function syncAttachments($sourceTaskId, $targetTaskId, SyncConfiguration $config)
    {
        // Cross-platform attachment syncing is complex and would require
        // downloading files from source platform and uploading to target platform
        // This is a placeholder for the implementation
        \Log::info('Attachment syncing not yet implemented for cross-platform sync', [
            'source_task_id' => $sourceTaskId,
            'target_task_id' => $targetTaskId
        ]);
    }
}