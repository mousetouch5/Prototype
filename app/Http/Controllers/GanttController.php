<?php

namespace App\Http\Controllers;

use App\Models\ClickUpAccount;
use App\Models\MondayAccount;
use App\Services\ClickUpService;
use App\Services\MondayService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class GanttController extends Controller
{
    use AuthorizesRequests;

    public function getGanttData(Request $request)
    {
        $request->validate([
            'accounts' => 'required|array',
            'accounts.*.platform' => 'required|in:clickup,monday',
            'accounts.*.account_id' => 'required|integer',
            'accounts.*.list_ids' => 'required|array',
        ]);

        $ganttData = [];

        foreach ($request->accounts as $accountData) {
            try {
                $platform = $accountData['platform'];
                $accountId = $accountData['account_id'];
                $listIds = $accountData['list_ids'];

                if ($platform === 'clickup') {
                    $account = ClickUpAccount::findOrFail($accountId);
                    $this->authorize('view', $account);
                    
                    $service = new ClickUpService($account);
                    $data = $service->getGanttData($listIds);
                } else if ($platform === 'monday') {
                    $account = MondayAccount::findOrFail($accountId);
                    $this->authorize('view', $account);
                    
                    $service = new MondayService($account);
                    $data = $service->getGanttData($listIds);
                }

                $ganttData = array_merge($ganttData, $data);
            } catch (\Exception $e) {
                // Log error but continue with other accounts
                \Log::error('Gantt data fetch failed', [
                    'platform' => $platform ?? 'unknown',
                    'account_id' => $accountId ?? null,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        // Sort by start date
        usort($ganttData, function ($a, $b) {
            $aStart = $a['start'] ?? '9999-12-31';
            $bStart = $b['start'] ?? '9999-12-31';
            return strcmp($aStart, $bStart);
        });

        return response()->json([
            'tasks' => $ganttData,
            'total_count' => count($ganttData),
            'platforms' => array_unique(array_column($ganttData, 'platform'))
        ]);
    }

    public function getAccountLists(Request $request)
    {
        $request->validate([
            'platform' => 'required|in:clickup,monday',
            'account_id' => 'required|integer',
        ]);

        try {
            $platform = $request->platform;
            $accountId = $request->account_id;

            if ($platform === 'clickup') {
                $account = ClickUpAccount::findOrFail($accountId);
                $this->authorize('view', $account);
                
                $service = new ClickUpService($account);
                $workspaces = $service->getWorkspaces();
                
                $lists = [];
                foreach ($workspaces['teams'] ?? [] as $workspace) {
                    try {
                        $spaces = $service->getSpaces($workspace['id']);
                        foreach ($spaces['spaces'] ?? [] as $space) {
                            $spaceLists = $service->getLists(null, $space['id']);
                            foreach ($spaceLists['lists'] ?? [] as $list) {
                                $lists[] = [
                                    'id' => $list['id'],
                                    'name' => $list['name'],
                                    'workspace' => $workspace['name'],
                                    'space' => $space['name'],
                                    'task_count' => $list['task_count'] ?? 0
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            } else if ($platform === 'monday') {
                $account = MondayAccount::findOrFail($accountId);
                $this->authorize('view', $account);
                
                $service = new MondayService($account);
                $boards = $service->getLists();
                
                $lists = [];
                foreach ($boards as $board) {
                    $lists[] = [
                        'id' => $board['id'],
                        'name' => $board['name'],
                        'workspace' => 'Monday.com',
                        'space' => 'Board',
                        'task_count' => 0 // Monday API doesn't return item count easily
                    ];
                }
            }

            return response()->json($lists);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch lists: ' . $e->getMessage()
            ], 400);
        }
    }
}