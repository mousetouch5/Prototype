<?php

namespace App\Http\Controllers;

use App\Models\MondayAccount;
use App\Services\MondayService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MondayAccountController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $accounts = $request->user()->mondayAccounts()
            ->where('is_active', true)
            ->get();

        return response()->json($accounts);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'access_token' => 'required|string',
            'account_type' => 'required|in:personal,oauth',
        ]);

        try {
            // Test the Monday.com token directly without using the model
            $client = new \GuzzleHttp\Client();

            // Test the token by fetching user data
            $response = $client->post('https://api.monday.com/v2', [
                'headers' => [
                    'Authorization' => $request->access_token,
                    'Content-Type' => 'application/json',
                    'API-Version' => '2023-10',
                ],
                'json' => [
                    'query' => 'query { me { id name email } }'
                ]
            ]);
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (isset($result['errors'])) {
                throw new \Exception('Invalid Monday.com credentials');
            }
            
            $userData = $result['data']['me'];
            
            // Get boards
            $response = $client->post('https://api.monday.com/v2', [
                'headers' => [
                    'Authorization' => $request->access_token,
                    'Content-Type' => 'application/json',
                    'API-Version' => '2023-10',
                ],
                'json' => [
                    'query' => 'query { boards { id name description } }'
                ]
            ]);
            $result = json_decode($response->getBody()->getContents(), true);
            $boards = $result['data']['boards'] ?? [];

            $account = $request->user()->mondayAccounts()->create([
                'name' => $request->name,
                'account_type' => $request->account_type,
                'access_token' => $request->access_token,
                'monday_user_id' => $userData['id'],
                'monday_username' => $userData['name'],
                'monday_email' => $userData['email'],
                'boards' => $boards,
                'token_expires_at' => null,
                'is_active' => true,
            ]);

            return response()->json($account, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid Monday.com credentials'], 422);
        }
    }

    public function show(MondayAccount $account)
    {
        $this->authorize('view', $account);
        return response()->json($account);
    }

    public function update(Request $request, MondayAccount $account)
    {
        $this->authorize('update', $account);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $account->update($request->only(['name', 'is_active']));

        return response()->json($account);
    }

    public function destroy(MondayAccount $account)
    {
        try {
            $this->authorize('delete', $account);
            
            // Check if account is being used in sync configurations
            try {
                $syncCount = \DB::table('sync_configurations')
                    ->where(function($query) use ($account) {
                        $query->where('source_account_id', $account->id)
                              ->where('source_platform', 'monday');
                    })
                    ->orWhere(function($query) use ($account) {
                        $query->where('target_account_id', $account->id)
                              ->where('target_platform', 'monday');
                    })
                    ->count();
                    
                if ($syncCount > 0) {
                    return response()->json([
                        'error' => 'Cannot delete account that is being used in sync configurations. Please delete the sync configurations first.'
                    ], 422);
                }
            } catch (\Exception $e) {
                // If sync_configurations table doesn't exist, continue with deletion
                \Log::info('sync_configurations table check failed, continuing with deletion: ' . $e->getMessage());
            }
            
            $account->delete();

            return response()->json(['message' => 'Account deleted successfully']);
        } catch (\Exception $e) {
            \Log::error('Failed to delete Monday account: ' . $e->getMessage());
            \Log::error('Error details: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to delete account: ' . $e->getMessage()
            ], 500);
        }
    }

    public function testConnection(MondayAccount $account)
    {
        $this->authorize('view', $account);

        try {
            $mondayService = new MondayService($account);
            $user = $mondayService->getUser();

            return response()->json([
                'success' => true,
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function getBoards(MondayAccount $account)
    {
        $this->authorize('view', $account);

        try {
            $mondayService = new MondayService($account);
            $boards = $mondayService->getWorkspaces();

            return response()->json($boards);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getGroups(MondayAccount $account, $boardId)
    {
        $this->authorize('view', $account);

        try {
            $mondayService = new MondayService($account);
            $groups = $mondayService->getSpaces($boardId);

            return response()->json($groups);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getItems(Request $request, MondayAccount $account, $boardId)
    {
        $this->authorize('view', $account);

        try {
            $mondayService = new MondayService($account);
            $items = $mondayService->getTasks($boardId);

            return response()->json($items['tasks']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}