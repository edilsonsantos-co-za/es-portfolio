<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use App\Models\Message;
use App\Services\Contracts\MessageInterface;
use CoreConstants;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * @var MessageInterface
     */
    private $message;

    /**
     * Create a new instance
     *
     * @param MessageInterface $message
     * @return void
     */
    public function __construct(MessageInterface $message)
    {
        $this->message = $message;
    }

    /**
     * Get all items
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $result = $this->message->getAllWithPaginate($request->all());

        return response()->json($result, !empty($result['status']) ? $result['status'] : CoreConstants::STATUS_CODE_SUCCESS);
    }

    /**
     * Store a new item
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $result = $this->message->store($request->all());

        return response()->json($result, !empty($result['status']) ? $result['status'] : CoreConstants::STATUS_CODE_SUCCESS);
    }

    /**
     * Show the given new item
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id)
    {
        $result = $this->message->getById($id);
        if (($result['status'] ?? null) === CoreConstants::STATUS_CODE_SUCCESS) {
            $result['payload']->load('replies');
        }

        return response()->json($result, !empty($result['status']) ? $result['status'] : CoreConstants::STATUS_CODE_SUCCESS);
    }

    /**
     * Update the given item
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id)
    {
        $result = $this->message->store($request->all());

        return response()->json($result, !empty($result['status']) ? $result['status'] : CoreConstants::STATUS_CODE_SUCCESS);
    }

    /**
     * Destroy the given items
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request)
    {
        $result = $this->message->deleteByIds($request->ids);

        return response()->json($result, !empty($result['status']) ? $result['status'] : CoreConstants::STATUS_CODE_SUCCESS);
    }

    /**
     * Email a reply to the person who wrote the message, record it and mark the message replied.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function reply(Request $request, int $id)
    {
        $data = $request->validate(['body' => 'required|string|max:10000']);
        $message = Message::find($id);
        if (!$message) {
            return response()->json(['message' => 'No result is found', 'payload' => null, 'status' => CoreConstants::STATUS_CODE_NOT_FOUND], CoreConstants::STATUS_CODE_NOT_FOUND);
        }

        $reply = $message->replies()->create(['body' => trim($data['body'])]);
        if (!ContactMail::reply($message, $reply)) {
            $reply->delete();
            return response()->json(['message' => 'The reply could not be sent. Nothing was recorded; try again.', 'payload' => null, 'status' => CoreConstants::STATUS_CODE_ERROR], CoreConstants::STATUS_CODE_ERROR);
        }

        $reply->update(['sent_at' => now()]);
        $message->update(['replied' => CoreConstants::TRUE]);

        return response()->json([
            'message' => "Reply sent to {$message->email}",
            'payload' => $message->load('replies'),
            'status' => CoreConstants::STATUS_CODE_SUCCESS,
        ]);
    }
}
