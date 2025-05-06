<?php

namespace App\Http\Controllers;

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
            $title = $request->input('title');
            $filename = time() . '_' . $videoFile->getClientOriginalName();
            $path = $videoFile->storeAs('public/uploads', $filename);

            $this->compressVideo(Storage::path('public/uploads/' . $filename));

            return response()->json(['message' => 'Video uploaded and processed successfully', 'path' => Storage::url('uploads/' . $filename)]);
        }

        return response()->json(['error' => 'No video file uploaded'], 400);
    }

    private function compressVideo($filePath)
    {
        try {
            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries'  => '/usr/bin/ffmpeg', // Adjust path if needed
                'ffprobe.binaries' => '/usr/bin/ffprobe', // Adjust path if needed
                'timeout'          => 3600, // Set a higher timeout if needed
                'ffmpeg.threads'   => 12,   // Adjust based on your server's CPU cores
            ]);

            $video = $ffmpeg->open($filePath);

            $format = new X264('libmp3lame', 'libx264');
            $format->setKiloBitrate(1000);
            $format->setAudioKiloBitrate(128);

            // Construct the output path in the same directory
            $directory = pathinfo($filePath, PATHINFO_DIRNAME);
            $filename = pathinfo($filePath, PATHINFO_FILENAME);
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $outputPath = $directory . '/' . $filename . '_compressed.' . $extension;

            $video
                ->save($format, $outputPath);

            // Optionally, delete the original file
            if (Storage::exists(str_replace(Storage::path(''), '', $filePath))) {
                Storage::delete(str_replace(Storage::path(''), '', $filePath));
            }

            // Move the compressed file to the original filename
            if (Storage::exists(str_replace(Storage::path(''), '', $outputPath))) {
                Storage::move(str_replace(Storage::path(''), '', $outputPath), str_replace(Storage::path(''), '', $filePath));
            }

        } catch (\FFMpeg\Exception\RuntimeException $e) {
            // Handle compression errors
            error_log("Error compressing video: " . $e->getMessage());
        }
    }
}
