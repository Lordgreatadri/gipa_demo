<?php

namespace App\Jobs;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateCertificateArtifacts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $certificateId) {}

    public function handle(): void
    {
        $certificate = Certificate::query()
            ->with(['type:id,name', 'district:id,name,region_id', 'district.region:id,name', 'issuer:id,name'])
            ->findOrFail($this->certificateId);
        $directory = "certificates/{$certificate->uuid}";
        $qrPath = "{$directory}/verification.png";
        $pdfPath = "{$directory}/certificate.pdf";

        try {
            $verificationUrl = route('certificates.verify', $certificate->public_token);
            $qrCode = (new Builder(
                writer: new PngWriter,
                data: $verificationUrl,
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 360,
                margin: 18,
            ))->build();
            Storage::put($qrPath, $qrCode->getString());

            $pdf = Pdf::loadView('pdf.certificate', [
                'certificate' => $certificate,
                'verificationUrl' => $verificationUrl,
                'qrDataUri' => $qrCode->getDataUri(),
            ])->setPaper('a4', 'landscape');
            Storage::put($pdfPath, $pdf->output());

            $certificate->forceFill([
                'artifact_status' => Certificate::ARTIFACT_READY,
                'qr_code_path' => $qrPath,
                'pdf_path' => $pdfPath,
                'artifacts_generated_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            Storage::delete([$qrPath, $pdfPath]);
            throw $exception;
        }
    }

    public function failed(): void
    {
        Certificate::query()->whereKey($this->certificateId)->update([
            'artifact_status' => Certificate::ARTIFACT_FAILED,
            'qr_code_path' => null,
            'pdf_path' => null,
        ]);
    }
}
