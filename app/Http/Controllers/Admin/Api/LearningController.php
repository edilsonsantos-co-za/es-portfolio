<?php

namespace App\Http\Controllers\Admin\Api;

use App\Helpers\MediaStorage;
use App\Http\Controllers\Controller;
use App\Models\LearningItem;
use CoreConstants;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Courses completed and learning plans on the Education page: list, add, change, remove.
 */
class LearningController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->ok('Data is fetched successfully', [
            'certificates' => LearningItem::certificates()->get(),
            'plans' => LearningItem::plans()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->save($request, new LearningItem());
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = LearningItem::find($id);

        return $item ? $this->save($request, $item) : $this->notFound();
    }

    public function destroy(int $id): JsonResponse
    {
        $item = LearningItem::find($id);
        if (!$item) {
            return $this->notFound();
        }
        $this->deleteUploadedImage($item);
        $item->delete();

        return $this->ok('Removed', null);
    }

    private function save(Request $request, LearningItem $item): JsonResponse
    {
        $kind = $request->input('kind', $item->kind);
        $isCertificate = $kind === LearningItem::CERTIFICATE;

        $data = $request->validate([
            'kind' => ['required', Rule::in([LearningItem::CERTIFICATE, LearningItem::PLAN])],
            'title' => 'required|string|max:191',
            'issuer' => 'required|string|max:191',
            'summary' => 'nullable|string|max:1000',
            // A plan is a goal: it never carries a date or a certificate, so it cannot look like one earned.
            'completed_on' => [$isCertificate ? 'required' : 'prohibited', 'nullable', 'date', 'before_or_equal:today'],
            'verification_url' => [$isCertificate ? 'nullable' : 'prohibited', 'url:https', 'max:191'],
            'image' => [($isCertificate && !$item->image) ? 'required' : 'nullable', $isCertificate ? 'image' : 'prohibited', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'image.required' => 'Add the certificate image.',
            'completed_on.prohibited' => 'A plan has no completion date.',
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $stored = MediaStorage::storeUploadedFile($file, 'certificates', 'webp');
            if (!$stored) {
                return response()->json(['message' => 'The image could not be stored', 'payload' => null, 'status' => CoreConstants::STATUS_CODE_ERROR], CoreConstants::STATUS_CODE_ERROR);
            }
            [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];
            $this->deleteUploadedImage($item);
            $data['image'] = $stored;
            $data['image_width'] = $width;
            $data['image_height'] = $height;
        } else {
            unset($data['image']);
        }

        if (!$item->exists) {
            $data['sort'] = (int) LearningItem::where('kind', $kind)->max('sort') + 1;
        }
        $item->fill($data)->save();

        return $this->ok($item->wasRecentlyCreated ? 'Added' : 'Saved', $item->fresh());
    }

    /** Images that shipped with the site (public/certificates) are part of the code and are never deleted. */
    private function deleteUploadedImage(LearningItem $item): void
    {
        if ($item->image && !is_file(public_path($item->image)) && MediaStorage::exists($item->image)) {
            MediaStorage::delete($item->image);
        }
    }

    private function ok(string $message, $payload): JsonResponse
    {
        return response()->json(['message' => $message, 'payload' => $payload, 'status' => CoreConstants::STATUS_CODE_SUCCESS]);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['message' => 'No result is found', 'payload' => null, 'status' => CoreConstants::STATUS_CODE_NOT_FOUND], CoreConstants::STATUS_CODE_NOT_FOUND);
    }
}
