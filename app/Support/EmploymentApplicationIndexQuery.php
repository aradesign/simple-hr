<?php

namespace App\Support;

use App\Models\EmploymentApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class EmploymentApplicationIndexQuery
{
    private const SORTABLE = [
        'application_number',
        'submitted_at',
        'gender',
        'age',
        'preferred_department',
    ];

    public function __construct(
        private readonly Builder $query,
        private readonly Request $request,
    ) {}

    public static function make(Request $request): self
    {
        return new self(
            EmploymentApplication::query()->with(['person', 'assignee', 'reviewer']),
            $request,
        );
    }

    public function apply(): Builder
    {
        $this->applySearch();
        $this->applyStatusFilter();
        $this->applyFormDataFilters();
        $this->applySorting();

        return $this->query;
    }

    private function applySearch(): void
    {
        if (! $this->request->filled('search')) {
            return;
        }

        $search = $this->request->string('search');

        $this->query->where(function (Builder $query) use ($search) {
            $query->where('application_number', 'like', "%{$search}%")
                ->orWhereHas('person', function (Builder $query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
        });
    }

    private function applyStatusFilter(): void
    {
        if ($this->request->filled('status')) {
            $this->query->where('status', $this->request->string('status'));
        }
    }

    private function applyFormDataFilters(): void
    {
        if ($this->request->filled('gender')) {
            $this->query->where('form_data->gender', $this->request->string('gender'));
        }

        if ($this->request->filled('age')) {
            $this->query->where('form_data->age', $this->request->string('age'));
        }

        if ($this->request->filled('preferred_department')) {
            $department = $this->request->string('preferred_department');
            $this->query->where('form_data->preferred_department', 'like', "%{$department}%");
        }
    }

    private function applySorting(): void
    {
        $sort = $this->request->string('sort')->toString();
        $direction = $this->request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        if (! in_array($sort, self::SORTABLE, true)) {
            $this->query->latest();

            return;
        }

        if ($sort === 'age') {
            $driver = $this->query->getConnection()->getDriverName();

            if ($driver === 'sqlite') {
                $this->query->orderByRaw(
                    "CAST(json_extract(form_data, '$.age') AS INTEGER) {$direction}"
                );
            } else {
                $this->query->orderByRaw(
                    "CAST(JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.age')) AS UNSIGNED) {$direction}"
                );
            }

            return;
        }

        if (in_array($sort, ['gender', 'preferred_department'], true)) {
            $this->query->orderBy("form_data->{$sort}", $direction);

            return;
        }

        $this->query->orderBy($sort, $direction);
    }

    /** @return list<string> */
    public static function sortableColumns(): array
    {
        return self::SORTABLE;
    }
}
