<?php

namespace App\Http\Controllers;

use App\Models\Video;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{

    public function upload(Request $request)
    {
        if ($request->hasFile('video')) {
            $videoFile = $request->file('video');
            $filename = time() . '_' . str_replace(' ', '_', $videoFile->getClientOriginalName());

            $relativePath = 'uploads/' . $filename;
            $request->file('video')->storeAs('uploads', $filename, 'public');

            $this->compressVideo(Storage::disk('public')->path($relativePath));

            $video = Video::create([
                'title' => $request->input('title'),
                'video' => $relativePath,
            ]);

            return response()->json([
                'message' => 'Video uploaded successfully',
                'path' => Storage::disk('public')->url($relativePath)
            ]);
        }
        return response()->json(['error' => 'No video uploaded'], 400);
    }

    //     private function compressVideo($filePath)
    // {
    //     try {
    //         $ffmpeg = \FFMpeg\FFMpeg::create([
    //             'ffmpeg.binaries'  => '/usr/bin/ffmpeg', // Adjust if necessary
    //             'ffprobe.binaries' => '/usr/bin/ffprobe',
    //             'timeout'          => 3600,
    //             'ffmpeg.threads'   => 12,
    //         ]);

    //         $video = $ffmpeg->open($filePath);

    //         $format = new \FFMpeg\Format\Video\X264('libmp3lame', 'libx264');
    //         $format->setAudioKiloBitrate(128);

    //         // Use CRF to control quality and reduce size (~40%)
    //         $format->setAdditionalParameters([
    //             '-crf', '28',         // 28 is a good balance (higher = smaller/lower quality)
    //             '-preset', 'medium'   // Use 'slow' for better compression
    //         ]);

    //         // Build output path
    //         $directory = pathinfo($filePath, PATHINFO_DIRNAME);
    //         $filename = pathinfo($filePath, PATHINFO_FILENAME);
    //         $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    //         $outputPath = $directory . '/' . $filename . '_compressed.' . trim($extension);

    //         // Save the compressed video
    //         $video->save($format, $outputPath);

    //         // Replace the original file
    //         $relativeOriginal = str_replace(Storage::path(''), '', $filePath);
    //         $relativeCompressed = str_replace(Storage::path(''), '', $outputPath);

    //         if (Storage::exists($relativeOriginal)) {
    //             Storage::delete($relativeOriginal);
    //         }

    //         if (Storage::exists($relativeCompressed)) {
    //             Storage::move($relativeCompressed, $relativeOriginal);
    //         }

    //     } catch (\FFMpeg\Exception\RuntimeException $e) {
    //         error_log("Error compressing video: " . $e->getMessage());
    //     }
    // }


    private function compressVideo($filePath)
    {
        try {
            $ffmpeg = \FFMpeg\FFMpeg::create([
                'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
                'ffprobe.binaries' => '/usr/bin/ffprobe',
                'timeout'          => 3600,
                'ffmpeg.threads'   => 12,
            ]);

            $video = $ffmpeg->open($filePath);

            $format = new \FFMpeg\Format\Video\X264('libmp3lame', 'libx264');
            $format->setAudioKiloBitrate(128);
            $format->setAdditionalParameters([
                '-crf',
                '28',
                '-preset',
                'medium'
            ]);

            $directory = pathinfo($filePath, PATHINFO_DIRNAME);
            $filename = pathinfo($filePath, PATHINFO_FILENAME);
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $outputPath = $directory . '/' . $filename . '_compressed.' . $extension;

            $video->save($format, $outputPath);

            unlink($filePath); // Delete original
            rename($outputPath, $filePath); // Rename compressed to original name

        } catch (\FFMpeg\Exception\RuntimeException $e) {
            error_log("Error compressing video: " . $e->getMessage());
        }
    }
    public function allVideos()
    {
        $videos = Video::all();

        return response()->json(['videos' => $videos]);
    }
}
