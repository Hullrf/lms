<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonResource extends Model
{
    protected $fillable = ['lesson_id', 'name', 'type', 'url', 'file_path', 'file_type', 'file_size'];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Returns an embeddable URL for known services, or null if not embeddable.
     */
    public function embedUrl(): ?string
    {
        if ($this->type !== 'link' || !$this->url) return null;

        $url = $this->url;

        // YouTube
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        // Google Drive file
        if (preg_match('/drive\.google\.com\/file\/d\/([^\/]+)/', $url, $m)) {
            return 'https://drive.google.com/file/d/' . $m[1] . '/preview';
        }

        // Google Docs
        if (preg_match('/docs\.google\.com\/document\/d\/([^\/]+)/', $url, $m)) {
            return 'https://docs.google.com/document/d/' . $m[1] . '/preview';
        }

        // Google Slides
        if (preg_match('/docs\.google\.com\/presentation\/d\/([^\/]+)/', $url, $m)) {
            return 'https://docs.google.com/presentation/d/' . $m[1] . '/embed';
        }

        // Google Sheets
        if (preg_match('/docs\.google\.com\/spreadsheets\/d\/([^\/]+)/', $url, $m)) {
            return 'https://docs.google.com/spreadsheets/d/' . $m[1] . '/preview';
        }

        // Direct PDF
        if (str_ends_with(strtolower(parse_url($url, PHP_URL_PATH) ?? ''), '.pdf')) {
            return $url;
        }

        return null;
    }

    /**
     * Returns a short label describing the resource source.
     */
    public function sourceLabel(): string
    {
        if ($this->type === 'file') return 'Archivo';
        $url = $this->url ?? '';
        if (str_contains($url, 'youtube') || str_contains($url, 'youtu.be')) return 'YouTube';
        if (str_contains($url, 'drive.google')) return 'Google Drive';
        if (str_contains($url, 'docs.google.com/document')) return 'Google Docs';
        if (str_contains($url, 'docs.google.com/presentation')) return 'Google Slides';
        if (str_contains($url, 'docs.google.com/spreadsheets')) return 'Google Sheets';
        if (str_ends_with(strtolower(parse_url($url, PHP_URL_PATH) ?? ''), '.pdf')) return 'PDF';
        return 'Enlace';
    }
}
