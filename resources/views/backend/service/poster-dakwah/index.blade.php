@extends('layouts.mobile.index')

@section('title', $title)

@section('header')
    <x-section-page-mobile :label="$title" :icon="$icon" />
@endsection

@section('content')
    <div class="mt-3 min-vh-100">
        <!-- Hadith Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <p class="mb-0" style="text-align: justify; line-height: 1.65">
                    Rasulullah ﷺ bersabda: <br>
                    "Semoga Allah mencerahkan wajah seseorang yang mendengar hadisku, lalu menghafalnya, kemudian
                    menyampaikannya. Bisa jadi orang yang diberi tahu lebih memahami daripada orang yang mendengar
                    langsung."
                    <small>(HR. Abu Dawud No. 3660, Tirmidzi No. 2656, dan Ibnu Majah No. 230)</small>
                </p>
            </div>
        </div>

        @php
            $posterDakwahImage = [
                ['judul' => 'Poster Dakwah 1', 'image' => 'poster1.jpeg'],
                ['judul' => 'Poster Dakwah 2', 'image' => 'poster2.jpeg'],
                ['judul' => 'Poster Dakwah 3', 'image' => 'poster3.jpeg'],
                ['judul' => 'Poster Dakwah 4', 'image' => 'poster4.jpeg'],
                ['judul' => 'Poster Dakwah 5', 'image' => 'poster5.jpeg'],
                ['judul' => 'Poster Dakwah 6', 'image' => 'poster6.jpeg'],
                ['judul' => 'Poster Dakwah 7', 'image' => 'poster7.jpeg'],
                ['judul' => 'Poster Dakwah 8', 'image' => 'poster8.jpeg'],
            ];
        @endphp

        <!-- Poster Gallery -->
        <div class="poster-gallery">
            @foreach ($posterDakwahImage as $poster)
                <div class="poster-card">
                    <a href="{{ asset('images/poster/' . $poster['image']) }}" data-lightbox="poster"
                        data-title="{{ $poster['judul'] }}">
                        <img src="{{ asset('images/poster/' . $poster['image']) }}" alt="{{ $poster['judul'] }}"
                            class="poster-image">
                    </a>
                    <div class="poster-actions">
                        <a href="{{ asset('images/poster/' . $poster['image']) }}" download class="btn btn-sm btn-light">
                            <i class="fas fa-download"></i> Unduh
                        </a>
                        <button class="btn btn-sm btn-light share-btn"
                            data-url="{{ asset('images/poster/' . $poster['image']) }}" data-title="{{ $poster['judul'] }}">
                            <i class="fas fa-share-alt"></i> Bagikan
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('styles')
    {{-- Lightbox2 CSS --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">

    <style>
        .poster-gallery {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            padding-bottom: 5rem;
        }

        .poster-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            background-color: #fff;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .poster-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }

        .poster-actions {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem;
            background-color: #f8f9fa;
        }

        .poster-actions .btn {
            flex: 1;
            margin: 0 0.25rem;
            font-size: 0.8rem;
        }

        @media (max-width: 576px) {
            .poster-gallery {
                grid-template-columns: repeat(2, 1fr);
            }

            .poster-image {
                height: 250px;
            }
        }
    </style>
@endpush

@push('scripts')
    {{-- Lightbox2 JS --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize share buttons
            const shareButtons = document.querySelectorAll('.share-btn');

            shareButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const url = this.dataset.url;
                    const title = this.dataset.title || 'Poster Dakwah';

                    sharePoster(url, title);
                });
            });

            // Configure lightbox
            lightbox.option({
                'resizeDuration': 200,
                'wrapAround': true,
                'disableScrolling': true,
                'albumLabel': 'Poster %1 dari %2'
            });
        });

        async function sharePoster(url, title) {
            const shareData = {
                title: title,
                text: 'Lihat poster dakwah ini: ' + title,
                url: url
            };

            try {
                if (navigator.share && navigator.canShare(shareData)) {
                    await navigator.share(shareData);
                } else {
                    // Fallback for browsers that don't support Web Share API
                    fallbackShare(url, title);
                }
            } catch (err) {
                console.error('Error sharing:', err);
                fallbackShare(url, title);
            }
        }

        function fallbackShare(url, title) {
            // Copy to clipboard as fallback
            const textToCopy = `${url}`;

            navigator.clipboard.writeText(textToCopy).then(() => {
                alert('Link poster telah disalin ke clipboard:\n\n' + title + '\n' + textToCopy);
            }).catch(() => {
                // Final fallback if clipboard fails
                prompt('Salin link berikut:', url);
            });
        }
    </script>
@endpush
