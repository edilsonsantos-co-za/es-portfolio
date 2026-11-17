<?php

namespace App\Http\Controllers\Frontend\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use App\Services\Contracts\FrontendInterface;
use App\Services\Contracts\MessageInterface;
use CoreConstants;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeneralController extends Controller
{
    /**
     * @var FrontendInterface
     */
    private $frontend;

    /**
     * Create a new instance
     *
     * @param FrontendInterface $frontend
     * @return void
     */
    public function __construct(FrontendInterface $frontend)
    {
        $this->frontend = $frontend;
    }

    /**
     * Get all projects
     *
     * @return JsonResponse
     */
    public function getProjects()
    {
        $result = $this->frontend->getAllProjects();

        return response()->json($result, !empty($result['status']) ? $result['status'] : CoreConstants::STATUS_CODE_SUCCESS);
    }

    /**
     * Store a new message
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        // A visitor cannot edit an existing message or mark it replied.
        $result = resolve(MessageInterface::class)->store($request->only(['name', 'email', 'subject', 'body']));

        if (($result['status'] ?? null) === CoreConstants::STATUS_CODE_SUCCESS) {
            $message = $result['payload'];
            // Sent after the message is saved: if mail is down the message is still in the inbox.
            $sent = ContactMail::received($message);
            $result = [
                'message' => $sent['confirmation']
                    ? "Thanks, {$message->name}. Your message is in, and a confirmation is on its way to {$message->email}."
                    : "Thanks, {$message->name}. Your message is in. I will reply to {$message->email}.",
                'payload' => null,
                'status' => CoreConstants::STATUS_CODE_SUCCESS,
            ];
        } elseif (($result['status'] ?? null) === CoreConstants::STATUS_CODE_BAD_REQUEST && $result['payload']) {
            $result['message'] = collect($result['payload']->all())->first() ?: 'Please check the form.';
            $result['payload'] = null;
        } else {
            $result = ['message' => 'Your message could not be sent. Please try again in a minute.', 'payload' => null, 'status' => CoreConstants::STATUS_CODE_ERROR];
        }

        return response()->json($result, !empty($result['status']) ? $result['status'] : CoreConstants::STATUS_CODE_SUCCESS);
    }
}
