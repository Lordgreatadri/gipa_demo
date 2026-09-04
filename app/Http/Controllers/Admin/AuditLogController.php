<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditPermissions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    /**
     * Maximum rows written to an export to keep memory and PDF size bounded.
     */
    private const EXPORT_LIMIT = 5000;

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can(AuditPermissions::LOGS_VIEW), 403);

        $filters = $this->validateFilters($request);

        $activities = $this->filteredQuery($filters)
            ->with('causer:id,name')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $causerIds = Activity::query()
            ->where('causer_type', User::class)
            ->whereNotNull('causer_id')
            ->distinct()
            ->pluck('causer_id');

        return view('admin.audit.index', [
            'activities' => $activities,
            'logNames' => Activity::query()->whereNotNull('log_name')->distinct()->orderBy('log_name')->pluck('log_name'),
            'events' => Activity::query()->whereNotNull('event')->distinct()->orderBy('event')->pluck('event'),
            'subjectTypes' => Activity::query()->whereNotNull('subject_type')->distinct()->orderBy('subject_type')->pluck('subject_type'),
            'causers' => User::query()->whereIn('id', $causerIds)->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'canExport' => (bool) $request->user()?->can(AuditPermissions::LOGS_EXPORT),
        ]);
    }

    /**
     * Stream the filtered audit trail as CSV or render it to PDF for evidence
     * packs. Both formats respect the active filters and the export row cap.
     */
    public function export(Request $request): StreamedResponse|Response
    {
        abort_unless($request->user()?->can(AuditPermissions::LOGS_EXPORT), 403);

        $format = $request->string('format')->lower()->value();
        abort_unless(in_array($format, ['csv', 'pdf'], true), 404);

        $filters = $this->validateFilters($request);
        $filename = 'audit-log-'.now()->format('Ymd-His');

        if ($format === 'pdf') {
            $activities = $this->filteredQuery($filters)
                ->with('causer:id,name')
                ->latest()
                ->limit(self::EXPORT_LIMIT)
                ->get();

            return Pdf::loadView('admin.audit.export-pdf', [
                'activities' => $activities,
                'filters' => $filters,
                'generatedAt' => now(),
            ])->download($filename.'.pdf');
        }

        return $this->streamCsv($filters, $filename.'.csv');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'log' => ['nullable', 'string', 'max:100'],
            'event' => ['nullable', 'string', 'max:100'],
            'causer' => ['nullable', 'integer'],
            'subject' => ['nullable', 'string', 'max:191'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'q' => ['nullable', 'string', 'max:191'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        return Activity::query()
            ->when($filters['log'] ?? null, fn (Builder $query, $log) => $query->where('log_name', $log))
            ->when($filters['event'] ?? null, fn (Builder $query, $event) => $query->where('event', $event))
            ->when($filters['subject'] ?? null, fn (Builder $query, $subject) => $query->where('subject_type', $subject))
            ->when($filters['causer'] ?? null, fn (Builder $query, $causer) => $query
                ->where('causer_type', User::class)
                ->where('causer_id', $causer))
            ->when($filters['from'] ?? null, fn (Builder $query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, $to) => $query->whereDate('created_at', '<=', $to))
            ->when($filters['q'] ?? null, fn (Builder $query, $term) => $query->where('description', 'like', '%'.$term.'%'));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function streamCsv(array $filters, string $filename): StreamedResponse
    {
        $query = $this->filteredQuery($filters)->with('causer:id,name')->latest();

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Timestamp', 'Actor', 'Log', 'Event', 'Description', 'Subject type', 'Subject id', 'Properties']);

            $written = 0;
            foreach ($query->lazy() as $activity) {
                fputcsv($handle, [
                    $activity->created_at instanceof Carbon ? $activity->created_at->toIso8601String() : (string) $activity->created_at,
                    $activity->causer?->name ?? 'System',
                    $activity->log_name,
                    $activity->event,
                    $activity->description,
                    $activity->subject_type ? class_basename($activity->subject_type) : '',
                    $activity->subject_id,
                    $activity->properties->isNotEmpty() ? $activity->properties->toJson() : '',
                ]);

                if (++$written >= self::EXPORT_LIMIT) {
                    break;
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
