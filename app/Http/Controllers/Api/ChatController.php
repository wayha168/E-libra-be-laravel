<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageSent;
use App\Http\Responses\ApiResponses;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    // ─── User endpoints ───────────────────────────────────────────────────────

    /**
     * Return the authenticated user's conversation (create one if it doesn't exist).
     */
    public function userConversation(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversation = ChatConversation::firstOrCreate(
            ['user_id' => $user->id],
            ['status' => 'open']
        );

        return ApiResponses::ok('Conversation fetched', $this->formatConversation($conversation, $user->id));
    }

    /**
     * Return paginated messages for the authenticated user's conversation.
     */
    public function userMessages(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversation = ChatConversation::where('user_id', $user->id)->firstOrFail();

        $messages = $conversation->messages()
            ->with('sender.role', 'sender.profileImage')
            ->latest()
            ->paginate(50);

        // Mark admin messages as read
        ChatMessage::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return ApiResponses::ok('Messages fetched', [
            'messages'   => $messages->getCollection()->map->toArray()->values(),
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
                'per_page'     => $messages->perPage(),
                'total'        => $messages->total(),
            ],
        ]);
    }

    /**
     * Send a message from the user to admin.
     */
    public function userSend(Request $request): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $user = $request->user();

        $conversation = ChatConversation::firstOrCreate(
            ['user_id' => $user->id],
            ['status' => 'open']
        );

        if ($conversation->status === 'closed') {
            $conversation->update(['status' => 'open']);
        }

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'message'         => $request->input('message'),
        ]);

        $conversation->update(['last_message_at' => now()]);

        $message->load('sender.role', 'sender.profileImage');

        broadcast(new ChatMessageSent($message));

        return ApiResponses::created('Message sent', $message->toArray());
    }

    // ─── Admin endpoints ───────────────────────────────────────────────────────

    /**
     * List all conversations (admin only).
     */
    public function adminConversations(Request $request): JsonResponse
    {
        $conversations = ChatConversation::with(['user.profileImage', 'user.role'])
            ->withCount(['messages as unread_count' => function ($q) use ($request) {
                $q->where('sender_id', '!=', $request->user()->id)
                  ->whereNull('read_at');
            }])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        $data = $conversations->getCollection()->map(fn ($c) => $this->formatConversation($c, $request->user()->id));

        return ApiResponses::ok('Conversations fetched', [
            'conversations' => $data,
            'pagination'    => [
                'current_page' => $conversations->currentPage(),
                'last_page'    => $conversations->lastPage(),
                'per_page'     => $conversations->perPage(),
                'total'        => $conversations->total(),
            ],
        ]);
    }

    /**
     * Get messages for a specific conversation (admin only).
     */
    public function adminMessages(Request $request, ChatConversation $conversation): JsonResponse
    {
        $admin = $request->user();

        $messages = $conversation->messages()
            ->with('sender.role', 'sender.profileImage')
            ->latest()
            ->paginate(50);

        // Mark user messages as read by admin
        ChatMessage::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $admin->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return ApiResponses::ok('Messages fetched', [
            'conversation' => $this->formatConversation($conversation, $admin->id),
            'messages'     => $messages->getCollection()->map->toArray()->values(),
            'pagination'   => [
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
                'per_page'     => $messages->perPage(),
                'total'        => $messages->total(),
            ],
        ]);
    }

    /**
     * Send a reply from admin to user.
     */
    public function adminSend(Request $request, ChatConversation $conversation): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $admin = $request->user();

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $admin->id,
            'message'         => $request->input('message'),
        ]);

        $conversation->update(['last_message_at' => now(), 'status' => 'open']);

        $message->load('sender.role', 'sender.profileImage');

        broadcast(new ChatMessageSent($message));

        return ApiResponses::created('Message sent', $message->toArray());
    }

    /**
     * Close a conversation (admin only).
     */
    public function adminClose(ChatConversation $conversation): JsonResponse
    {
        $conversation->update(['status' => 'closed']);

        return ApiResponses::ok('Conversation closed', ['id' => $conversation->id, 'status' => 'closed']);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function formatConversation(ChatConversation $conversation, string $viewerId): array
    {
        $lastMsg = $conversation->messages()->latest()->first();

        return [
            'id'              => $conversation->id,
            'status'          => $conversation->status,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'unread_count'    => $conversation->unreadCount($viewerId),
            'user'            => $conversation->user ? [
                'id'    => $conversation->user->id,
                'name'  => $conversation->user->name,
                'email' => $conversation->user->email,
                'role'  => $conversation->user->role?->role,
                'image' => $conversation->user->profileImage?->url,
            ] : null,
            'last_message'    => $lastMsg ? [
                'id'         => $lastMsg->id,
                'sender_id'  => $lastMsg->sender_id,
                'message'    => $lastMsg->message,
                'created_at' => $lastMsg->created_at?->toIso8601String(),
            ] : null,
        ];
    }
}
