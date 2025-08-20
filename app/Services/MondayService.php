<?php

namespace App\Services;

use App\Models\MondayAccount;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class MondayService extends AbstractPlatformService
{
    protected $baseUrl = 'https://api.monday.com/v2/';
    protected $account;

    public function __construct(MondayAccount $account = null)
    {
        $this->account = $account;
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => $account ? $account->access_token : '',
                'Content-Type' => 'application/json',
                'API-Version' => '2023-10',
            ],
        ]);
    }

    public function setAccount(MondayAccount $account): void
    {
        $this->account = $account;
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => $account->access_token,
                'Content-Type' => 'application/json',
                'API-Version' => '2023-10',
            ],
        ]);
    }

    public function getUser(): array
    {
        try {
            $query = 'query { me { id name email } }';
            $response = $this->makeGraphQLRequest($query);
            return $response['data']['me'];
        } catch (RequestException $e) {
            $this->handleApiException($e, 'fetch Monday user');
        }
    }

    public function getWorkspaces(): array
    {
        try {
            $query = 'query { boards { id name description } }';
            $response = $this->makeGraphQLRequest($query);
            return $response['data']['boards'];
        } catch (RequestException $e) {
            $this->handleApiException($e, 'fetch Monday boards');
        }
    }

    public function getSpaces(string $workspaceId): array
    {
        // Monday.com doesn't have spaces like ClickUp, so we'll return groups within a board
        try {
            $query = 'query { boards(ids: [' . $workspaceId . ']) { groups { id title } } }';
            $response = $this->makeGraphQLRequest($query);
            return $response['data']['boards'][0]['groups'] ?? [];
        } catch (RequestException $e) {
            $this->handleApiException($e, 'fetch Monday groups');
        }
    }

    public function getLists(?string $folderId = null, ?string $spaceId = null): array
    {
        // In Monday.com context, lists are boards
        try {
            $query = 'query { boards { id name description state } }';
            $response = $this->makeGraphQLRequest($query);
            return $response['data']['boards'];
        } catch (RequestException $e) {
            $this->handleApiException($e, 'fetch Monday boards');
        }
    }

    public function getTasks(string $listId, int $page = 0): array
    {
        try {
            $query = 'query { 
                boards(ids: [' . $listId . ']) { 
                    items { 
                        id 
                        name 
                        state
                        column_values { 
                            id 
                            text 
                            value 
                            column { 
                                title 
                                type 
                            } 
                        } 
                        group { 
                            id 
                            title 
                        }
                    } 
                } 
            }';
            $response = $this->makeGraphQLRequest($query);
            return [
                'tasks' => $response['data']['boards'][0]['items'] ?? []
            ];
        } catch (RequestException $e) {
            $this->handleApiException($e, 'fetch Monday items');
        }
    }

    public function getTask(string $taskId): array
    {
        try {
            $query = 'query { 
                items(ids: [' . $taskId . ']) { 
                    id 
                    name 
                    state
                    column_values { 
                        id 
                        text 
                        value 
                        column { 
                            title 
                            type 
                        } 
                    } 
                    group { 
                        id 
                        title 
                    }
                    board {
                        id
                        name
                    }
                } 
            }';
            $response = $this->makeGraphQLRequest($query);
            return $response['data']['items'][0] ?? [];
        } catch (RequestException $e) {
            $this->handleApiException($e, 'fetch Monday item');
        }
    }

    public function createTask(string $listId, array $taskData): array
    {
        try {
            $itemName = addslashes($taskData['name']);
            $groupId = $taskData['group_id'] ?? null;
            
            $mutation = 'mutation { 
                create_item(
                    board_id: ' . $listId . ', 
                    item_name: "' . $itemName . '"' .
                    ($groupId ? ', group_id: "' . $groupId . '"' : '') . '
                ) { 
                    id 
                    name 
                } 
            }';
            
            $response = $this->makeGraphQLRequest($mutation);
            return $response['data']['create_item'];
        } catch (RequestException $e) {
            $this->handleApiException($e, 'create Monday item');
        }
    }

    public function updateTask(string $taskId, array $taskData): array
    {
        try {
            $updates = [];
            
            if (isset($taskData['name'])) {
                $itemName = addslashes($taskData['name']);
                $mutation = 'mutation { 
                    change_item_name(
                        item_id: ' . $taskId . ', 
                        new_name: "' . $itemName . '"
                    ) { 
                        id 
                        name 
                    } 
                }';
                $this->makeGraphQLRequest($mutation);
            }
            
            // Handle column value updates
            if (isset($taskData['column_values'])) {
                foreach ($taskData['column_values'] as $columnId => $value) {
                    $valueJson = json_encode($value);
                    $mutation = 'mutation { 
                        change_column_value(
                            item_id: ' . $taskId . ', 
                            column_id: "' . $columnId . '", 
                            value: "' . addslashes($valueJson) . '"
                        ) { 
                            id 
                        } 
                    }';
                    $this->makeGraphQLRequest($mutation);
                }
            }
            
            return $this->getTask($taskId);
        } catch (RequestException $e) {
            $this->handleApiException($e, 'update Monday item');
        }
    }

    public function deleteTask(string $taskId): array
    {
        try {
            $mutation = 'mutation { 
                delete_item(item_id: ' . $taskId . ') { 
                    id 
                } 
            }';
            $response = $this->makeGraphQLRequest($mutation);
            return $response['data']['delete_item'];
        } catch (RequestException $e) {
            $this->handleApiException($e, 'delete Monday item');
        }
    }

    public function getTaskComments(string $taskId): array
    {
        try {
            $query = 'query { 
                items(ids: [' . $taskId . ']) { 
                    updates { 
                        id 
                        body 
                        created_at 
                        creator { 
                            id 
                            name 
                        } 
                    } 
                } 
            }';
            $response = $this->makeGraphQLRequest($query);
            return [
                'comments' => $response['data']['items'][0]['updates'] ?? []
            ];
        } catch (RequestException $e) {
            $this->handleApiException($e, 'fetch Monday updates');
        }
    }

    public function createTaskComment(string $taskId, string $comment): array
    {
        try {
            $commentText = addslashes($comment);
            $mutation = 'mutation { 
                create_update(
                    item_id: ' . $taskId . ', 
                    body: "' . $commentText . '"
                ) { 
                    id 
                    body 
                } 
            }';
            $response = $this->makeGraphQLRequest($mutation);
            return $response['data']['create_update'];
        } catch (RequestException $e) {
            $this->handleApiException($e, 'create Monday update');
        }
    }

    public function getCustomFields(string $listId): array
    {
        try {
            $query = 'query { 
                boards(ids: [' . $listId . ']) { 
                    columns { 
                        id 
                        title 
                        type 
                        settings_str 
                    } 
                } 
            }';
            $response = $this->makeGraphQLRequest($query);
            return $response['data']['boards'][0]['columns'] ?? [];
        } catch (RequestException $e) {
            $this->handleApiException($e, 'fetch Monday columns');
        }
    }

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
                continue;
            }
        }
        
        return $ganttData;
    }

    private function transformTaskForGantt(array $task): array
    {
        $startDate = null;
        $endDate = null;
        $status = 'unknown';
        $statusColor = '#d3d3d3';
        $assignees = [];
        
        // Extract data from column values
        foreach ($task['column_values'] ?? [] as $column) {
            switch ($column['column']['type']) {
                case 'date':
                    if (strpos(strtolower($column['column']['title']), 'start') !== false) {
                        $startDate = $column['text'];
                    } elseif (strpos(strtolower($column['column']['title']), 'due') !== false || 
                             strpos(strtolower($column['column']['title']), 'end') !== false) {
                        $endDate = $column['text'];
                    }
                    break;
                case 'status':
                    $status = $column['text'];
                    $statusData = json_decode($column['value'], true);
                    if ($statusData && isset($statusData['color'])) {
                        $statusColor = $statusData['color'];
                    }
                    break;
                case 'people':
                    $peopleData = json_decode($column['value'], true);
                    if ($peopleData && isset($peopleData['personsAndTeams'])) {
                        $assignees = array_map(fn($p) => $p['name'] ?? 'Unknown', $peopleData['personsAndTeams']);
                    }
                    break;
            }
        }
        
        return [
            'id' => $task['id'],
            'name' => $task['name'],
            'start' => $startDate ? $this->formatDate($startDate) : null,
            'end' => $endDate ? $this->formatDate($endDate) : null,
            'progress' => $this->calculateMondayProgress($task),
            'status' => $status,
            'status_color' => $statusColor,
            'assignees' => $assignees,
            'group' => $task['group']['title'] ?? null,
            'platform' => 'monday'
        ];
    }

    private function calculateMondayProgress(array $task): int
    {
        foreach ($task['column_values'] ?? [] as $column) {
            if ($column['column']['type'] === 'status') {
                $status = strtolower($column['text']);
                if (in_array($status, ['done', 'complete', 'finished'])) {
                    return 100;
                } else if (in_array($status, ['working on it', 'in progress', 'started'])) {
                    return 50;
                }
            }
        }
        return 0;
    }

    public function transformToCommonFormat(array $data, string $type): array
    {
        switch ($type) {
            case 'task':
                $commonTask = [
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'description' => '',
                    'status' => ['name' => 'unknown', 'color' => '#d3d3d3'],
                    'priority' => ['name' => null, 'color' => null],
                    'due_date' => null,
                    'start_date' => null,
                    'assignees' => [],
                    'tags' => [],
                    'custom_fields' => [],
                    'platform' => 'monday'
                ];
                
                // Extract data from column values
                foreach ($data['column_values'] ?? [] as $column) {
                    switch ($column['column']['type']) {
                        case 'status':
                            $commonTask['status'] = [
                                'name' => $column['text'],
                                'color' => $this->extractColorFromValue($column['value'])
                            ];
                            break;
                        case 'date':
                            if (strpos(strtolower($column['column']['title']), 'start') !== false) {
                                $commonTask['start_date'] = $column['text'];
                            } elseif (strpos(strtolower($column['column']['title']), 'due') !== false) {
                                $commonTask['due_date'] = $column['text'];
                            }
                            break;
                        case 'people':
                            $peopleData = json_decode($column['value'], true);
                            if ($peopleData && isset($peopleData['personsAndTeams'])) {
                                $commonTask['assignees'] = array_map(fn($p) => [
                                    'id' => $p['id'],
                                    'name' => $p['name'],
                                    'email' => $p['email'] ?? null
                                ], $peopleData['personsAndTeams']);
                            }
                            break;
                        case 'tags':
                            $tagsData = json_decode($column['value'], true);
                            if ($tagsData && isset($tagsData['tag_ids'])) {
                                $commonTask['tags'] = $tagsData['tag_ids'];
                            }
                            break;
                    }
                }
                
                return $commonTask;
            default:
                return $data;
        }
    }

    public function transformFromCommonFormat(array $data, string $type): array
    {
        switch ($type) {
            case 'task':
                return [
                    'name' => $data['name'],
                    'column_values' => [
                        // This would need to be mapped to specific board columns
                        // Implementation depends on board structure
                    ]
                ];
            default:
                return $data;
        }
    }

    private function extractColorFromValue(string $value): string
    {
        $data = json_decode($value, true);
        return $data['color'] ?? '#d3d3d3';
    }

    private function makeGraphQLRequest(string $query): array
    {
        $response = $this->client->post('', [
            'json' => ['query' => $query]
        ]);
        
        $result = json_decode($response->getBody()->getContents(), true);
        
        if (isset($result['errors'])) {
            throw new \Exception('GraphQL Error: ' . json_encode($result['errors']));
        }
        
        return $result;
    }

    public function getPlatformName(): string
    {
        return 'Monday.com';
    }

    public function getPlatformColors(): array
    {
        return [
            'primary' => '#00D2FF',
            'secondary' => '#0085FF',
            'success' => '#00C875',
            'warning' => '#FFCB00',
            'danger' => '#E2445C'
        ];
    }
}