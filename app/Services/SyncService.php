<?php

namespace App\Services;

use App\Models\SyncConfiguration;
use App\Models\SyncLog;
use App\Services\ClickUpService;

class SyncService
{
    protected $sourceService;
    protected $targetService;
    protected $syncLog;

    public function syncTasks(SyncConfiguration $config)
    {
        $this->syncLog = SyncLog::create([
            'sync_configuration_id' => $config->id,
            'status' => SyncLog::STATUS_PENDING,
        ]);

        try {
            $this->syncLog->markAsRunning();

            $this->sourceService = new ClickUpService($config->sourceAccount);
            $this->targetService = new ClickUpService($config->targetAccount);

            $sourceTasks = $this->fetchAllTasks($config);
            $results = $this->processTasks($sourceTasks, $config);

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

    protected function fetchAllTasks(SyncConfiguration $config)
    {
        $tasks = [];
        $page = 0;
        
        do {
            $response = $this->sourceService->getTasks($config->source_list_id, $page);
            $tasks = array_merge($tasks, $response['tasks'] ?? []);
            $page++;
        } while (!empty($response['tasks']));

        return $tasks;
    }

    protected function processTasks(array $sourceTasks, SyncConfiguration $config)
    {
        $created = 0;
        $updated = 0;
        $failed = 0;

        foreach ($sourceTasks as $sourceTask) {
            try {
                $targetTask = $this->findExistingTask($sourceTask, $config);
                
                if ($targetTask) {
                    $this->updateTask($targetTask['id'], $sourceTask, $config);
                    $this->syncLog->incrementTasksUpdated();
                    $updated++;
                } else {
                    $this->createTask($sourceTask, $config);
                    $this->syncLog->incrementTasksCreated();
                    $created++;
                }

                if ($config->sync_comments) {
                    $this->syncComments($sourceTask['id'], $targetTask['id'] ?? null, $config);
                }

                if ($config->sync_attachments) {
                    $this->syncAttachments($sourceTask['id'], $targetTask['id'] ?? null, $config);
                }
            } catch (\Exception $e) {
                $this->syncLog->incrementTasksFailed();
                $failed++;
                \Log::error('Task sync failed', [
                    'task_id' => $sourceTask['id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'total' => count($sourceTasks),
        ];
    }

    protected function findExistingTask($sourceTask, SyncConfiguration $config)
    {
        $targetTasks = $this->targetService->getTasks($config->target_list_id);
        
        foreach ($targetTasks['tasks'] ?? [] as $task) {
            if ($this->isMatchingTask($sourceTask, $task, $config)) {
                return $task;
            }
        }

        return null;
    }

    protected function isMatchingTask($sourceTask, $targetTask, $config)
    {
        $syncOptions = $config->sync_options ?? [];
        
        if (isset($syncOptions['match_by'])) {
            switch ($syncOptions['match_by']) {
                case 'name':
                    return $sourceTask['name'] === $targetTask['name'];
                case 'custom_id':
                    return isset($sourceTask['custom_id']) && 
                           isset($targetTask['custom_id']) && 
                           $sourceTask['custom_id'] === $targetTask['custom_id'];
                default:
                    return false;
            }
        }

        return $sourceTask['name'] === $targetTask['name'];
    }

    protected function createTask($sourceTask, SyncConfiguration $config)
    {
        $taskData = $this->transformTaskData($sourceTask, $config);
        return $this->targetService->createTask($config->target_list_id, $taskData);
    }

    protected function updateTask($targetTaskId, $sourceTask, SyncConfiguration $config)
    {
        if ($config->conflict_resolution === 'target_wins') {
            return;
        }

        $taskData = $this->transformTaskData($sourceTask, $config);
        return $this->targetService->updateTask($targetTaskId, $taskData);
    }

    protected function transformTaskData($sourceTask, SyncConfiguration $config)
    {
        $syncOptions = $config->sync_options ?? [];
        $fieldMapping = $syncOptions['field_mapping'] ?? [];

        $taskData = [
            'name' => $sourceTask['name'],
            'description' => $sourceTask['description'] ?? '',
            'status' => $this->mapStatus($sourceTask['status'], $config),
            'priority' => $sourceTask['priority'] ?? null,
            'due_date' => $sourceTask['due_date'] ?? null,
            'due_date_time' => $sourceTask['due_date_time'] ?? false,
            'time_estimate' => $sourceTask['time_estimate'] ?? null,
            'assignees' => $this->mapAssignees($sourceTask['assignees'] ?? [], $config),
            'tags' => $sourceTask['tags'] ?? [],
        ];

        if ($config->sync_custom_fields && isset($sourceTask['custom_fields'])) {
            $taskData['custom_fields'] = $this->mapCustomFields(
                $sourceTask['custom_fields'],
                $fieldMapping
            );
        }

        return array_filter($taskData, function($value) {
            return $value !== null;
        });
    }

    protected function mapStatus($sourceStatus, SyncConfiguration $config)
    {
        $syncOptions = $config->sync_options ?? [];
        $statusMapping = $syncOptions['status_mapping'] ?? [];

        if (isset($statusMapping[$sourceStatus['status']])) {
            return $statusMapping[$sourceStatus['status']];
        }

        return $sourceStatus['status'];
    }

    protected function mapAssignees($sourceAssignees, SyncConfiguration $config)
    {
        $syncOptions = $config->sync_options ?? [];
        $userMapping = $syncOptions['user_mapping'] ?? [];

        $assignees = [];
        foreach ($sourceAssignees as $assignee) {
            if (isset($userMapping[$assignee])) {
                $assignees[] = $userMapping[$assignee];
            }
        }

        return $assignees;
    }

    protected function mapCustomFields($sourceFields, $fieldMapping)
    {
        $mappedFields = [];
        
        foreach ($sourceFields as $field) {
            if (isset($fieldMapping[$field['id']])) {
                $mappedFields[] = [
                    'id' => $fieldMapping[$field['id']],
                    'value' => $field['value']
                ];
            }
        }

        return $mappedFields;
    }

    protected function syncComments($sourceTaskId, $targetTaskId, SyncConfiguration $config)
    {
        if (!$targetTaskId) {
            return;
        }

        $sourceComments = $this->sourceService->getTaskComments($sourceTaskId);
        
        foreach ($sourceComments['comments'] ?? [] as $comment) {
            $this->targetService->createTaskComment($targetTaskId, $comment['comment_text']);
        }
    }

    protected function syncAttachments($sourceTaskId, $targetTaskId, SyncConfiguration $config)
    {
        // Attachment syncing would require downloading and re-uploading files
        // This is a placeholder for the implementation
        return;
    }
}