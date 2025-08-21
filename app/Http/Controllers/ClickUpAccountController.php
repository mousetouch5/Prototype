<?php

namespace App\Http\Controllers;

use App\Models\ClickUpAccount;
use App\Services\ClickUpService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ClickUpAccountController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $accounts = $request->user()->clickUpAccounts()
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
            // Test the ClickUp token directly without using the model
            $client = new \GuzzleHttp\Client();

            // Test the token by fetching user data
            $response = $client->get('https://api.clickup.com/api/v2/user', [
                'headers' => [
                    'Authorization' => $request->access_token,
                    'Content-Type' => 'application/json',
                ]
            ]);
            $userData = json_decode($response->getBody()->getContents(), true);
            
            // Get workspaces
            $response = $client->get('https://api.clickup.com/api/v2/team', [
                'headers' => [
                    'Authorization' => $request->access_token,
                    'Content-Type' => 'application/json',
                ]
            ]);
            $workspaces = json_decode($response->getBody()->getContents(), true);

            $account = $request->user()->clickUpAccounts()->create([
                'name' => $request->name,
                'account_type' => $request->account_type,
                'access_token' => $request->access_token,
                'refresh_token' => $request->refresh_token,
                'clickup_user_id' => $userData['user']['id'],
                'clickup_username' => $userData['user']['username'],
                'clickup_email' => $userData['user']['email'],
                'workspaces' => $workspaces['teams'],
                'token_expires_at' => $request->token_expires_at,
                'is_active' => true,
            ]);

            return response()->json($account, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid ClickUp credentials'], 422);
        }
    }

    public function show(ClickUpAccount $account)
    {
        $this->authorize('view', $account);
        return response()->json($account);
    }

    public function update(Request $request, ClickUpAccount $account)
    {
        $this->authorize('update', $account);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $account->update($request->only(['name', 'is_active']));

        return response()->json($account);
    }

    public function destroy(ClickUpAccount $account)
    {
        try {
            $user = auth()->user();
            
            // Detailed debugging
            \Log::info('Delete attempt - Debug Info', [
                'authenticated_user' => $user ? $user->toArray() : null,
                'user_id' => $user ? $user->id : null,
                'user_id_type' => $user ? gettype($user->id) : null,
                'account_id' => $account->id,
                'account_user_id' => $account->user_id,
                'account_user_id_type' => gettype($account->user_id),
                'strict_match' => $user && $user->id === $account->user_id,
                'loose_match' => $user && $user->id == $account->user_id,
                'account_data' => $account->toArray()
            ]);
            
            if (!$user) {
                \Log::error('No authenticated user found for delete operation');
                return response()->json(['error' => 'User not authenticated'], 401);
            }
            
            \Log::info('About to authorize delete', [
                'policy_exists' => class_exists('App\\Policies\\ClickUpAccountPolicy'),
                'user_id' => $user->id,
                'account_user_id' => $account->user_id
            ]);
            
            $this->authorize('delete', $account);
            
            // Check if account is being used in sync configurations
            try {
                $syncCount = \DB::table('sync_configurations')
                    ->where('source_account_id', $account->id)
                    ->orWhere('target_account_id', $account->id)
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
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            \Log::error('Authorization failed for delete ClickUp account', [
                'user_id' => auth()->user()->id,
                'account_id' => $account->id,
                'account_user_id' => $account->user_id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => 'You can only delete your own accounts.'
            ], 403);
        } catch (\Exception $e) {
            \Log::error('Failed to delete ClickUp account: ' . $e->getMessage());
            \Log::error('Error details: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to delete account: ' . $e->getMessage()
            ], 500);
        }
    }

    public function testConnection(ClickUpAccount $account)
    {
        $this->authorize('view', $account);

        try {
            $clickUpService = new ClickUpService($account);
            $userData = $clickUpService->getUser();

            return response()->json([
                'success' => true,
                'user' => $userData['user'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function getWorkspaces(ClickUpAccount $account)
    {
        $this->authorize('view', $account);

        try {
            $clickUpService = new ClickUpService($account);
            $workspaces = $clickUpService->getWorkspaces();

            // Log the response for debugging
            \Log::info('ClickUp workspaces response', ['workspaces' => $workspaces]);

            if (!isset($workspaces['teams'])) {
                \Log::error('Invalid workspace response structure', ['response' => $workspaces]);
                return response()->json(['error' => 'Invalid response from ClickUp API'], 500);
            }

            return response()->json($workspaces['teams']);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch workspaces', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getSpaces(ClickUpAccount $account, $workspaceId)
    {
        $this->authorize('view', $account);

        try {
            $clickUpService = new ClickUpService($account);
            $spaces = $clickUpService->getSpaces($workspaceId);

            return response()->json($spaces['spaces']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getLists(Request $request, ClickUpAccount $account)
    {
        $this->authorize('view', $account);

        $request->validate([
            'space_id' => 'required_without:folder_id|string',
            'folder_id' => 'required_without:space_id|string',
        ]);

        try {
            $clickUpService = new ClickUpService($account);
            $lists = $clickUpService->getLists(
                $request->folder_id,
                $request->space_id
            );

            return response()->json($lists['lists']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getTasks(Request $request, ClickUpAccount $account, $listId)
    {
        $this->authorize('view', $account);

        try {
            $clickUpService = new ClickUpService($account);
            $tasks = $clickUpService->getTasks($listId, $request->get('page', 0));

            return response()->json($tasks);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}