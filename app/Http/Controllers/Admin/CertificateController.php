<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CertificateActionRequest;
use App\Http\Requests\Admin\CertificateStoreRequest;
use App\Http\Requests\Admin\CertificateVerificationRequest;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\CertificateVerification;
use App\Models\District;
use App\Models\Opportunity;
use App\Services\CertificateIntegrityService;
use App\Services\CertificateVerificationService;
use App\Services\CertificateWorkflowService;
use App\Support\CertificatePermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can(CertificatePermissions::VIEW), 403);
        $scope = Certificate::query()->accessibleTo($request->user());

        return view('admin.certificates.index', [
            'certificates' => $scope
                ->select('id', 'uuid', 'certificate_number', 'certificate_type_id', 'district_id', 'status', 'holder_name_snapshot', 'issued_at', 'expires_at', 'updated_at')
                ->with(['type:id,name', 'district:id,name'])
                ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
                ->latest('id')
                ->cursorPaginate(20)
                ->withQueryString(),
        ]);
    }

    public function overview(Request $request): View
    {
        abort_unless($request->user()->can(CertificatePermissions::VIEW), 403);
        $now = now();
        $scope = Certificate::query()->accessibleTo($request->user());
        $metrics = (clone $scope)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? AND (expires_at IS NULL OR expires_at >= ?) THEN 1 ELSE 0 END) as active', [Certificate::STATUS_ACTIVE, $now])
            ->selectRaw('SUM(CASE WHEN status = ? AND expires_at < ? THEN 1 ELSE 0 END) as expired', [Certificate::STATUS_ACTIVE, $now])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as suspended', [Certificate::STATUS_SUSPENDED])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as revoked', [Certificate::STATUS_REVOKED])
            ->selectRaw('SUM(CASE WHEN status = ? AND expires_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as expiring', [Certificate::STATUS_ACTIVE, $now, $now->copy()->addDays(30)])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as draft', [Certificate::STATUS_DRAFT])
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as other', [Certificate::STATUS_CANCELLED, Certificate::STATUS_SUPERSEDED])
            ->first();
        $regional = (clone $scope)
            ->join('districts', 'districts.id', '=', 'certificates.district_id')
            ->join('regions', 'regions.id', '=', 'districts.region_id')
            ->selectRaw('regions.name, COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN certificates.status = ? AND (certificates.expires_at IS NULL OR certificates.expires_at >= ?) THEN 1 ELSE 0 END) as active', [Certificate::STATUS_ACTIVE, $now])
            ->groupBy('regions.id', 'regions.name')
            ->orderBy('regions.name')
            ->get();
        $quarterStarts = collect(range(1, 4))->map(fn (int $quarter) => $now->copy()->startOfYear()->addMonths(($quarter - 1) * 3));
        $quarterSelect = (clone $scope)->selectRaw('COUNT(*) as scoped_total');

        foreach ($quarterStarts as $index => $start) {
            $quarterSelect->selectRaw(
                "SUM(CASE WHEN status = ? AND expires_at >= ? AND expires_at < ? AND expires_at < ? THEN 1 ELSE 0 END) as q{$index}",
                [Certificate::STATUS_ACTIVE, $start, $start->copy()->addMonths(3), $now]
            );
        }

        $quarterlyExpiries = $quarterSelect->first();

        return view('admin.certificates.overview', [
            'metrics' => $metrics,
            'charts' => [
                'regional' => [
                    'labels' => $regional->pluck('name')->all(),
                    'total' => $regional->pluck('total')->map(fn ($value) => (int) $value)->all(),
                    'active' => $regional->pluck('active')->map(fn ($value) => (int) $value)->all(),
                ],
                'status' => [
                    'labels' => ['Draft', 'Active', 'Expired', 'Suspended', 'Revoked', 'Other'],
                    'values' => [(int) $metrics->draft, (int) $metrics->active, (int) $metrics->expired, (int) $metrics->suspended, (int) $metrics->revoked, (int) $metrics->other],
                ],
                'expiries' => [
                    'labels' => ['Q1', 'Q2', 'Q3', 'Q4'],
                    'values' => collect(range(0, 3))->map(fn (int $quarter) => (int) $quarterlyExpiries->{"q{$quarter}"})->all(),
                    'year' => $now->year,
                ],
            ],
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can(CertificatePermissions::ISSUE), 403);

        return view('admin.certificates.form', [
            'types' => CertificateType::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'uuid', 'name']),
            'districts' => District::query()->orderBy('name')->get(['id', 'uuid', 'name']),
            'opportunities' => Opportunity::query()->orderBy('title')->get(['id', 'uuid', 'title']),
        ]);
    }

    public function store(CertificateStoreRequest $request): RedirectResponse
    {
        $certificate = Certificate::create($request->certificateData() + [
            'certificate_number' => 'GIPA-CERT-'.now()->format('Y').'-'.Str::upper(Str::random(10)),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return to_route('staff.certificates.show', $certificate)->with('status', 'Certificate draft created. Review the snapshot before issuance.');
    }

    public function show(Request $request, Certificate $certificate, CertificateIntegrityService $integrity): View
    {
        Gate::authorize('view', $certificate);
        $certificate->load([
            'type:id,name,code',
            'district:id,name,region_id',
            'district.region:id,name',
            'issuer:id,name',
            'lifecycleEvents' => fn ($query) => $query->with('actor:id,name')->latest('occurred_at'),
            'verifications' => fn ($query) => $query->with(['officer:id,name', 'media'])->latest('created_at')->limit(30),
        ]);

        return view('admin.certificates.show', [
            'certificate' => $certificate,
            'integrityResult' => $certificate->status === Certificate::STATUS_DRAFT ? 'draft' : $integrity->result($certificate),
        ]);
    }

    public function action(CertificateActionRequest $request, Certificate $certificate, string $action, CertificateWorkflowService $workflow): RedirectResponse
    {
        $reason = (string) $request->validated('reason');
        $certificate = match ($action) {
            'issue' => $workflow->issue($certificate, $request->user())->certificate,
            'suspend' => $workflow->suspend($certificate, $request->user(), $reason),
            'reinstate' => $workflow->reinstate($certificate, $request->user(), $reason),
            'revoke' => $workflow->revoke($certificate, $request->user(), $reason),
            default => throw ValidationException::withMessages(['workflow' => 'Unknown certificate action.']),
        };

        return to_route('staff.certificates.show', $certificate)->with('status', 'Certificate lifecycle updated.');
    }

    public function verify(CertificateVerificationRequest $request, Certificate $certificate, CertificateVerificationService $service): RedirectResponse
    {
        $verification = $service->record($certificate, $request->user(), $request->safe()->except('evidence'));
        if ($request->hasFile('evidence') && ! $verification->hasMedia($verification::EVIDENCE_COLLECTION)) {
            $upload = $request->file('evidence');
            $verification->addMedia($upload)
                ->usingFileName(Str::uuid().'.'.$upload->guessExtension())
                ->withCustomProperties([
                    'checksum_sha256' => hash_file('sha256', $upload->getRealPath()),
                    'malware_scan_status' => 'pending',
                ])
                ->toMediaCollection($verification::EVIDENCE_COLLECTION);
        }

        return to_route('staff.certificates.show', $certificate)->with('status', "Field verification {$verification->reference} recorded.");
    }

    public function evidence(Request $request, CertificateVerification $verification): StreamedResponse
    {
        Gate::authorize('viewEvidence', $verification);
        $media = $verification->getFirstMedia($verification::EVIDENCE_COLLECTION);
        abort_unless($media, 404);

        return Storage::disk($media->disk)->download($media->getPathRelativeToRoot(), 'verification-evidence.'.$media->extension);
    }

    public function artifact(Request $request, Certificate $certificate, string $artifact): StreamedResponse
    {
        Gate::authorize('view', $certificate);
        [$path, $filename] = match ($artifact) {
            'pdf' => [$certificate->pdf_path, $certificate->certificate_number.'.pdf'],
            'qr' => [$certificate->qr_code_path, $certificate->certificate_number.'-verification.png'],
            default => abort(404),
        };
        abort_unless($path && Storage::exists($path), 404);

        return Storage::download($path, $filename);
    }
}
