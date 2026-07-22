<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background:
                radial-gradient(circle at 15% 10%, rgba(99, 102, 241, 0.25), transparent 32rem),
                radial-gradient(circle at 90% 85%, rgba(14, 165, 233, 0.16), transparent 28rem),
                #070b16;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 18px;
            color: #f1f5f9;
        }

        .cover-container {
            width: 100%;
            max-width: 720px;
            text-align: center;
        }

        .cover-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            color: #a5b4fc;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .cover-eyebrow::before {
            content: '';
            width: 24px;
            height: 1px;
            background: currentColor;
        }

        .cover-title {
            font-size: clamp(2rem, 7vw, 3.75rem);
            font-weight: 800;
            letter-spacing: -0.045em;
            line-height: 1.02;
            margin-bottom: 12px;
        }

        .cover-subtitle {
            font-size: 0.95rem;
            color: #94a3b8;
            line-height: 1.6;
            margin: 0 auto 30px;
            max-width: 480px;
        }

        .cover-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 28px;
            padding: 10px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            border-radius: 24px;
            background: rgba(15, 23, 42, 0.68);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(16px);
        }

        .cover-grid.single {
            grid-template-columns: 1fr;
        }

        .cover-grid-item {
            position: relative;
            aspect-ratio: 1 / 1;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.2);
            box-shadow: none;
            background: #1e293b;
        }

        .cover-grid-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }

        .cover-grid-item:hover img {
            transform: scale(1.05);
        }

        .watch-more-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            color: #0f172a;
            font-size: 1.05rem;
            font-weight: 700;
            padding: 16px 28px;
            border-radius: 9999px;
            text-decoration: none;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .watch-more-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.4);
        }

        .watch-more-btn svg {
            width: 20px;
            height: 20px;
        }

        @media (max-width: 480px) {
            .cover-grid { gap: 7px; padding: 7px; border-radius: 19px; }
            .cover-grid-item { border-radius: 12px; }
            .watch-more-btn { width: 100%; justify-content: center; }
        }

        @media (prefers-reduced-motion: reduce) {
            .cover-grid-item img, .watch-more-btn { transition: none; }
        }
    </style>
</head>
<body>
    <div class="cover-container">
        <p class="cover-eyebrow">Featured gallery</p>
        <h1 class="cover-title">{{ $page->title }}</h1>
        <p class="cover-subtitle">Angalia picha hizi kisha bonyeza kitufe hapa chini kuendelea</p>

        <div class="cover-grid {{ count($coverImages) === 1 ? 'single' : '' }}">
            @foreach($coverImages as $coverImage)
                <div class="cover-grid-item">
                    <img src="{{ asset('storage/'.$coverImage) }}" alt="{{ $page->title }}" loading="lazy">
                </div>
            @endforeach
        </div>

        <a href="{{ route('page.show', ['page' => $page->slug]) }}" class="watch-more-btn">
            Watch More
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </a>
    </div>
</body>
</html>
