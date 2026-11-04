<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\Transfer;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves receipt images and delivery proofs from the private disk.
 * A file is only served if it belongs to a record the user may view.
 */
class PrivateFileController extends Controller
{
    /** Types safe to render in the browser; anything else is forced to download. */
    private const INLINE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    public function __invoke(Request $request, string $path): Response
    {
        abort_unless($request->user()?->can(Permissions::FILES_VIEW), 403);
        abort_if(str_contains($path, '..') || str_contains($path, '\\') || ! Str::startsWith($path, ['receipts/', 'proofs/']), 404);

        $transfer = Str::startsWith($path, 'receipts/')
            ? Receipt::withTrashed()->where('receipt_file', $path)->first()?->transfer()->withTrashed()->first()
            : Transfer::withTrashed()->where('delivery_proof_file', $path)->first();

        abort_unless($transfer && $request->user()->can('view', $transfer), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $inline = in_array($mime, self::INLINE_TYPES, true);

        return $disk->response($path, basename($path), [
            'Content-Type' => $inline ? $mime : 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
            'Cache-Control' => 'private, no-store',
        ], $inline ? 'inline' : 'attachment');
    }
}
