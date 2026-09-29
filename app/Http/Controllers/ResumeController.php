<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ResumeController extends Controller
{
    /**
     * Stream the uploaded CV with an attachment disposition, so browsers
     * download it rather than trying to render the PDF inline.
     */
    public function __invoke(): Response
    {
        $profile = Profile::current();
        $path = $profile->cv_path;

        abort_if(blank($path), 404);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        $absolute = $disk->path($path);

        $filename = str($profile->full_name ?: 'resume')
            ->slug()
            ->append('.pdf')
            ->value();

        return response()->file($absolute, [
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
