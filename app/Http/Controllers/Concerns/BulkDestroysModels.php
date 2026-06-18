<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait BulkDestroysModels
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function bulkDestroyModels(
        Request $request,
        string $modelClass,
        callable $deleter,
        string $successMessage,
    ): RedirectResponse {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $deleted = 0;

        foreach ($validated['ids'] as $id) {
            /** @var Model|null $model */
            $model = $modelClass::query()->find($id);

            if (! $model) {
                continue;
            }

            $this->authorize('delete', $model);
            $deleter($model);
            $deleted++;
        }

        if ($deleted === 0) {
            return back()->with('error', 'موردی برای حذف یافت نشد.');
        }

        return back()->with('success', str_replace(':count', (string) $deleted, $successMessage));
    }
}
