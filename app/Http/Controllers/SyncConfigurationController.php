<?php

namespace App\Http\Controllers;

use App\Models\SyncConfiguration;
use App\Services\SyncService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SyncConfigurationController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $configurations = $request->user()->syncConfigurations()
            ->with(['sourceAccount', 'targetAccount', 'syncLogs' => function($query) {
                $query->latest()->limit(1);
            }])
            ->get();

        return response()->json($configurations);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'source_account_id' => 'required|exists:clickup_accounts,id',
            'source_workspace_id' => 'required|string',
            'source_space_id' => 'nullable|string',
            'source_folder_id' => 'nullable|string',
            'source_list_id' => 'required|string',
            'target_account_id' => 'required|exists:clickup_accounts,id',
            'target_workspace_id' => 'required|string',
            'target_space_id' => 'nullable|string',
            'target_folder_id' => 'nullable|string',
            'target_list_id' => 'required|string',
            'sync_options' => 'nullable|array',
            'sync_direction' => 'required|in:one_way,two_way',
            'conflict_resolution' => 'required|in:source_wins,target_wins,manual',
            'sync_attachments' => 'boolean',
            'sync_comments' => 'boolean',
            'sync_custom_fields' => 'boolean',
            'schedule_type' => 'required|in:manual,interval,cron',
            'schedule_interval' => 'required_if:schedule_type,interval|nullable|integer|min:5',
            'schedule_cron' => 'required_if:schedule_type,cron|nullable|string',
        ]);

        $configuration = $request->user()->syncConfigurations()->create($request->all());

        if ($configuration->isScheduled()) {
            $configuration->calculateNextSyncTime();
            $configuration->save();
        }

        return response()->json($configuration, 201);
    }

    public function show(SyncConfiguration $configuration)
    {
        $this->authorize('view', $configuration);
        
        $configuration->load([
            'sourceAccount',
            'targetAccount',
            'syncLogs' => function($query) {
                $query->latest()->limit(10);
            }
        ]);

        return response()->json($configuration);
    }

    public function update(Request $request, SyncConfiguration $configuration)
    {
        $this->authorize('update', $configuration);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'sync_options' => 'nullable|array',
            'sync_direction' => 'sometimes|in:one_way,two_way',
            'conflict_resolution' => 'sometimes|in:source_wins,target_wins,manual',
            'sync_attachments' => 'boolean',
            'sync_comments' => 'boolean',
            'sync_custom_fields' => 'boolean',
            'schedule_type' => 'sometimes|in:manual,interval,cron',
            'schedule_interval' => 'required_if:schedule_type,interval|nullable|integer|min:5',
            'schedule_cron' => 'required_if:schedule_type,cron|nullable|string',
            'is_active' => 'boolean',
        ]);

        $configuration->update($request->all());

        if ($configuration->isScheduled()) {
            $configuration->calculateNextSyncTime();
            $configuration->save();
        }

        return response()->json($configuration);
    }

    public function destroy(SyncConfiguration $configuration)
    {
        $this->authorize('delete', $configuration);
        $configuration->delete();

        return response()->json(['message' => 'Configuration deleted successfully']);
    }

    public function syncNow(SyncConfiguration $configuration)
    {
        $this->authorize('update', $configuration);

        if (!$configuration->is_active) {
            return response()->json(['error' => 'Configuration is not active'], 400);
        }

        try {
            $syncService = new SyncService();
            $syncLog = $syncService->syncTasks($configuration);

            return response()->json([
                'message' => 'Sync completed successfully',
                'sync_log' => $syncLog,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Sync failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getSyncLogs(SyncConfiguration $configuration)
    {
        $this->authorize('view', $configuration);

        $logs = $configuration->syncLogs()
            ->latest()
            ->paginate(20);

        return response()->json($logs);
    }

    public function testConnection(SyncConfiguration $configuration)
    {
        $this->authorize('view', $configuration);

        try {
            $sourceConnected = false;
            $targetConnected = false;
            $sourceError = null;
            $targetError = null;

            // Test source connection
            try {
                $sourceService = new \App\Services\ClickUpService($configuration->sourceAccount);
                $sourceList = $sourceService->getLists(
                    $configuration->source_folder_id,
                    $configuration->source_space_id
                );
                $sourceConnected = true;
            } catch (\Exception $e) {
                $sourceError = $e->getMessage();
            }

            // Test target connection
            try {
                $targetService = new \App\Services\ClickUpService($configuration->targetAccount);
                $targetList = $targetService->getLists(
                    $configuration->target_folder_id,
                    $configuration->target_space_id
                );
                $targetConnected = true;
            } catch (\Exception $e) {
                $targetError = $e->getMessage();
            }

            $success = $sourceConnected && $targetConnected;

            return response()->json([
                'success' => $success,
                'source_connected' => $sourceConnected,
                'target_connected' => $targetConnected,
                'source_error' => $sourceError,
                'target_error' => $targetError,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}