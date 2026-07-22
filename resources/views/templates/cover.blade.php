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
            background: linear-gradient(160deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            color: #f1f5f9;
        }

        .cover-container {
            width: 100%;
            max-width: 560px;
            text-align: center;
        }

        .cover-title {
            font-size: clamp(1.5rem, 5vw, 2.25rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
        }

        .cover-subtitle {
            font-size: 0.95rem;
            color: #94a3b8;
            margin-bottom: 28px;
        }

        .cover-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 32px;
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
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
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
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: #ffffff;
            font-size: 1.05rem;
            font-weight: 700;
            padding: 16px 48px;
            border-radius: 9999px;
            text-decoration: none;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.45);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .watch-more-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(99, 102, 241, 0.6);
        }

        .watch-more-btn svg {
            width: 20px;
            height: 20px;
        }
    </style>
</head>
<body>
    <div class="cover-container">
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
