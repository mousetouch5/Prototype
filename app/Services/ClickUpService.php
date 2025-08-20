<?php

namespace App\Services;

use App\Models\ClickUpAccount;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ClickUpService extends AbstractPlatformService
{
    protected $client;
    protected $baseUrl = 'https://api.clickup.com/api/v2/';
    protected $account;

    public function __construct(ClickUpAccount $account = null)
    {
        $this->account = $account;
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => $account ? $account->access_token : '',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function setAccount(ClickUpAccount $account): void
    {
        $this->account = $account;
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => $account->access_token,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function getUser(): array
    {
        try {
            $response = $this->client->get('user');
            return json_decode($response->getBody()->getContents(), true) ?: [];
        } catch (RequestException $e) {
            throw new \Exception('Failed to fetch ClickUp user: ' . $e->getMessage());
        }
    }

    public function getWorkspaces(): array
    {
        try {
            $response = $this->client->get('team');
            $data = json_decode($response->getBody()->getContents(), true);
            
            // Log the response for debugging
            \Log::info('ClickUp API workspaces response', [
                'status_code' => $response->getStatusCode(),
                'data' => $data
            ]);
            
            if (!$data) {
                throw new \Exception('Empty response from ClickUp API');
            }
            
            return $data ?: [];
        } catch (RequestException $e) {
            \Log::error('ClickUp API request failed', [
                'endpoint' => 'team',
                'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 'no_response',
                'error' => $e->getMessage(),
                'response_body' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'no_body'
            ]);
            
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                if ($statusCode === 401) {
                    throw new \Exception('Unauthorized: Invalid or expired ClickUp token');
                } elseif ($statusCode === 403) {
                    throw new \Exception('Forbidden: Insufficient permissions for ClickUp API');
                } elseif ($statusCode === 429) {
                    throw new \Exception('Rate limit exceeded: Too many requests to ClickUp API');
                }
            }
            
            throw new \Exception('Failed to fetch workspaces: ' . $e->getMessage());
        }
    }

    public function getSpaces(string $workspaceId): array
    {
        try {
            $response = $this->client->get("team/{$workspaceId}/space", [
                'query' => ['archived' => 'false']
            ]);
            return json_decode($response->getBody()->getContents(), true) ?: [];
        } catch (RequestException $e) {
            throw new \Exception('Failed to fetch spaces: ' . $e->getMessage());
        }
    }

    public function getFolders($spaceId)
    {
        try {
            $response = $this->client->get("space/{$spaceId}/folder", [
                'query' => ['archived' => 'false']
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new \Exception('Failed to fetch folders: ' . $e->getMessage());
        }
    }

    public function getLists(?string $folderId = null, ?string $spaceId = null): array
    {
        try {
            if ($folderId) {
                $response = $this->client->get("folder/{$folderId}/list", [
                    'query' => ['archived' => 'false']
                ]);
            } elseif ($spaceId) {
                $response = $this->client->get("space/{$spaceId}/list", [
                    'query' => ['archived' => 'false']
                ]);
            } else {
                throw new \Exception('Either folderId or spaceId is required');
            }
            
            return json_decode($response->getBody()->getContents(), true) ?: [];
        } catch (RequestException $e) {
            throw new \Exception('Failed to fetch lists: ' . $e->getMessage());
        }
    }

    public function getTasks(string $listId, int $page = 0): array
    {
        try {
            $response = $this->client->get("list/{$listId}/task", [
                'query' => [
                    'archived' => 'false',
                    'page' => $page,
                    'order_by' => 'created',
                    'reverse' => 'false',
                    'subtasks' => 'true',
                    'include_closed' => 'true',
                ]
            ]);
            return json_decode($response->getBody()->getContents(), true) ?: [];
        } catch (RequestException $e) {
            throw new \Exception('Failed to fetch tasks: ' . $e->getMessage());
        }
    }

    public function getTask(string $taskId): array
    {
        try {
            $response = $this->client->get("task/{$taskId}");
            return json_decode($response->getBody()->getContents(), true) ?: [];
        } catch (RequestException $e) {
            throw new \Exception('Failed to fetch task: ' . $e->getMessage());
        }
    }

    public function createTask(string $listId, array $taskData): array
    {
        try {
            $response = $this->client->post("list/{$listId}/task", [
                'json' => $taskData
            ]);
            return json_decode($response->getBody()->getContents(), true) ?: [];
        } catch (RequestException $e) {
            throw new \Exception('Failed to create task: ' . $e->getMessage());
        }
    }

    public function updateTask(string $taskId, array $taskData): array
    {
        try {
            $response = $this->client->put("task/{$taskId}", [
                'json' => $taskData
            ]);
            return json_decode($response->getBody()->getContents(), true) ?: [];
        } catch (RequestException $e) {
            throw new \Exception('Failed to update task: ' . $e->getMessage());
        }
    }

    public function deleteTask(string $taskId): array
    {
        try {
            $response = $this->client->delete("task/{$taskId}");
            return json_decode($response->getBody()->getContents(), true) ?: [];
        } catch (RequestException $e) {
            throw new \Exception('Failed to delete task: ' . $e->getMessage());
        }
    }

    public function getCustomFields(string $listId): array
    {
        try {
            $response = $this->client->get("list/{$listId}/field");
            return json_decode($response->getBody()->getContents(), true) ?: [];
        } catch (RequestException $e) {
            throw new \Exception('Failed to fetch custom fields: ' . $e->getMessage());
        }
    }

    public function getTaskComments(string $taskId): array
    {
        try {
            $response = $this->client->get("task/{$taskId}/comment");
            return json_decode($response->getBody()->getContents(), true) ?: [];
        } catch (RequestException $e) {
            throw new \Exception('Failed to fetch comments: ' . $e->getMessage());
        }
    }

    public function createTaskComment(string $taskId, string $commentText): array
    {
        try {
            $response = $this->client->post("task/{$taskId}/comment", [
                'json' => ['comment_text' => $commentText]
            ]);
            return json_decode($response->getBody()->getContents(), true) ?: [];
        } catch (RequestException $e) {
            throw new \Exception('Failed to create comment: ' . $e->getMessage());
        }
    }

    public function getTaskAttachments($taskId)
    {
        try {
            $response = $this->client->get("task/{$taskId}/attachment");
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new \Exception('Failed to fetch attachments: ' . $e->getMessage());
        }
    }

    /**
     * Get Gantt chart data for ClickUp
     */
    public function getGanttData(array $listIds): array
    {
        $ganttData = [];
        
        foreach ($listIds as $listId) {
            try {
                $tasks = $this->getTasks($listId);
                foreach ($tasks['tasks'] ?? [] as $task) {
                    $ganttData[] = $this->transformTaskForGantt($task);
                }
            } catch (\Exception $e) {
                // Continue with other lists if one fails
                continue;
            }
        }
        
        return $ganttData;
    }

    /**
     * Transform ClickUp task to Gantt format
     */
    private function transformTaskForGantt(array $task): array
    {
        $startDate = $task['start_date'] ?? $task['date_created'] ?? null;
        $dueDate = $task['due_date'] ?? null;
        
        return [
            'id' => $task['id'],
            'name' => $task['name'],
            'start' => $startDate ? $this->formatDate($startDate) : null,
            'end' => $dueDate ? $this->formatDate($dueDate) : null,
            'progress' => $this->calculateProgress($task),
            'status' => $task['status']['status'] ?? 'unknown',
            'status_color' => $task['status']['color'] ?? '#d3d3d3',
            'assignees' => array_map(fn($a) => $a['username'], $task['assignees'] ?? []),
            'priority' => $task['priority']['priority'] ?? null,
            'priority_color' => $task['priority']['color'] ?? null,
            'dependencies' => $this->extractDependencies($task),
            'url' => $task['url'] ?? null,
            'platform' => 'clickup'
        ];
    }

    /**
     * Transform data to common format
     */
    public function transformToCommonFormat(array $data, string $type): array
    {
        switch ($type) {
            case 'task':
                return [
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'description' => $data['description'] ?? '',
                    'status' => [
                        'name' => $data['status']['status'] ?? 'unknown',
                        'color' => $data['status']['color'] ?? '#d3d3d3'
                    ],
                    'priority' => [
                        'name' => $data['priority']['priority'] ?? null,
                        'color' => $data['priority']['color'] ?? null
                    ],
                    'due_date' => $data['due_date'] ?? null,
                    'start_date' => $data['start_date'] ?? $data['date_created'] ?? null,
                    'assignees' => array_map(fn($a) => [
                        'id' => $a['id'],
                        'name' => $a['username'],
                        'email' => $a['email'] ?? null
                    ], $data['assignees'] ?? []),
                    'tags' => array_map(fn($t) => $t['name'], $data['tags'] ?? []),
                    'custom_fields' => $data['custom_fields'] ?? [],
                    'time_estimate' => $data['time_estimate'] ?? null,
                    'time_spent' => $data['time_spent'] ?? null,
                    'url' => $data['url'] ?? null,
                    'platform' => 'clickup'
                ];
            default:
                return $data;
        }
    }

    /**
     * Transform common format to ClickUp format
     */
    public function transformFromCommonFormat(array $data, string $type): array
    {
        switch ($type) {
            case 'task':
                $clickupData = [
                    'name' => $data['name'],
                    'description' => $data['description'] ?? '',
                    'status' => $data['status']['name'] ?? null,
                    'priority' => $data['priority']['name'] ?? null,
                    'due_date' => $data['due_date'] ?? null,
                    'start_date' => $data['start_date'] ?? null,
                    'assignees' => array_map(fn($a) => $a['id'], $data['assignees'] ?? []),
                    'tags' => $data['tags'] ?? [],
                ];
                
                if (isset($data['time_estimate'])) {
                    $clickupData['time_estimate'] = $data['time_estimate'];
                }
                
                return array_filter($clickupData, fn($value) => $value !== null);
            default:
                return $data;
        }
    }

    /**
     * Get platform name
     */
    public function getPlatformName(): string
    {
        return 'ClickUp';
    }

    /**
     * Get platform colors
     */
    public function getPlatformColors(): array
    {
        return [
            'primary' => '#7b68ee',
            'secondary' => '#8077f1',
            'success' => '#40BC86',
            'warning' => '#ffc107',
            'danger' => '#dc3545'
        ];
    }
}