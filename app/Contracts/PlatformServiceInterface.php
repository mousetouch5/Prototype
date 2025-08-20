<?php

namespace App\Contracts;

interface PlatformServiceInterface
{
    /**
     * Get user information
     */
    public function getUser(): array;

    /**
     * Get all workspaces/boards
     */
    public function getWorkspaces(): array;

    /**
     * Get spaces/folders within a workspace
     */
    public function getSpaces(string $workspaceId): array;

    /**
     * Get lists within a space
     */
    public function getLists(?string $folderId = null, ?string $spaceId = null): array;

    /**
     * Get tasks within a list
     */
    public function getTasks(string $listId, int $page = 0): array;

    /**
     * Get a specific task
     */
    public function getTask(string $taskId): array;

    /**
     * Create a new task
     */
    public function createTask(string $listId, array $taskData): array;

    /**
     * Update an existing task
     */
    public function updateTask(string $taskId, array $taskData): array;

    /**
     * Delete a task
     */
    public function deleteTask(string $taskId): array;

    /**
     * Get task comments
     */
    public function getTaskComments(string $taskId): array;

    /**
     * Create a task comment
     */
    public function createTaskComment(string $taskId, string $comment): array;

    /**
     * Get custom fields for a list
     */
    public function getCustomFields(string $listId): array;

    /**
     * Get platform-specific data for Gantt chart
     */
    public function getGanttData(array $listIds): array;

    /**
     * Transform platform-specific data to common format
     */
    public function transformToCommonFormat(array $data, string $type): array;

    /**
     * Transform common format to platform-specific format
     */
    public function transformFromCommonFormat(array $data, string $type): array;
}